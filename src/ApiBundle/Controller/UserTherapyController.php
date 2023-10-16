<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Playlist;
use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Entity\UserTherapy;
use AppBundle\Form\TherapySessionType;
use AppBundle\Form\UserTherapyType;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View as FOSView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Voryx\RESTGeneratorBundle\Controller\VoryxController;

/**
 * UserTherapy controller.
 * @RouteResource("UserTherapy")
 */
class UserTherapyController extends VoryxController
{
    /**
     * Get a UserTherapy entity
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @return Response
     */
    public function getAction(UserTherapy $entity)
    {
        return $entity;
    }

    /**
     * Get all UserTherapy entities.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "List"})
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @QueryParam(name="page", requirements="\d+", nullable=true, description="Which page to return when more than limit.")
     * @QueryParam(name="limit", requirements="\d+", default="9999", description="How many notes to return.")
     * @QueryParam(name="order_by", nullable=true, array=true, description="Order by fields. Must be an array ie. &order_by[name]=ASC&order_by[description]=DESC")
     * @QueryParam(name="filters", nullable=true, array=true, description="Filter by fields. Must be an array ie. &filters[id]=3")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:UserTherapy')
            ->findAllQueryBuilder($paramFetcher);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'get_therapies');

        return $paginatedCollection;
    }

    /**
     * Create a UserTherapy entity.
     *
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function postAction(Request $request)
    {
        $originalRequest = clone $request;

        $entity = new UserTherapy();
        $form = $this->createForm(UserTherapyType::class, $entity, array("method" => $request->getMethod()));
        $this->removeExtraFields($request, $form);
        $form->handleRequest($request);

        if ($form->isValid()) {

            /** @var Therapy $sourceTherapy */
            $sourceTherapy = $entity->getTherapy();

            // check if we only want to validate the form without submitting it
            if ($originalRequest->get('validateOnly')) {
                return FOSView::create(null, Codes::HTTP_OK);
            }

            // inherit type from parent therapy
            $entity->setType($sourceTherapy->getType());

            // clone binaural playlist if the therapy has one
            if ($sourceTherapy->getBinauralPlaylist()) {
                $entity->setBinauralPlaylist(clone $sourceTherapy->getBinauralPlaylist());
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);

            // get start date and start time
            $startDate = new \DateTime($originalRequest->get('startDate'));
            $iterateDate = clone $startDate;
            $startTimes = $originalRequest->get('startTimes');
            $sessionCount = 0; // we want to flag the sessions with nth of total sessions
            $daysPerWeek = $entity->getDays();
            $daysCount = 0;

            // Create therapy sessions for each day of the cycle
            $days = $daysPerWeek * $entity->getCycle() * ($entity->getCycleType() == 'month' ? 4 : 1);
            while ($days > 0) {
                $daysCount++;

                // Create as much sessions per day as set in UserTherapy->rate
                for ($i = 0; $i < $entity->getRate(); $i++) {
                    $sessionCount++;
                    $startTime = new \DateTime($startTimes[$i] ? $startTimes[$i] : $startTimes[count($startTimes - 1)]);
                    $session = new TherapySession();
                    $session->setUser($entity->getUser());
                    $session->setUserTherapy($entity);
                    $session->setStartDate($iterateDate);
                    $session->setStartTime($startTime);
                    $session->setNOfTotal($sessionCount);
                    $session->setIncludesHq($entity->getIncludesHq());
                    $em->persist($session);
                    //var_dump($startTimes[$i] ? $startTimes[$i] : $startTimes[count($startTimes - 1)]);

                    // clone music playlists to all sessions if the therapy has one
                    if ($sourceTherapy->getMusicPlaylist()) {
                        $session->setMusicPlaylist(clone $sourceTherapy->getMusicPlaylist());
                    }

                    // if therapy is compiled, copy file to all sessions
                    $session->setFileName($sourceTherapy->getFileName());
                    $session->setFileSize($sourceTherapy->getFileSize());
                    $session->setFileNameHq($sourceTherapy->getFileNameHq());
                    $session->setFileSizeHq($sourceTherapy->getFileSizeHq());
                    if ($sourceTherapy->virtualIsCompiled()) {
                        $session->setCompileStatus($sourceTherapy->getCompileStatus());
                        $session->setCompileStatusHq($sourceTherapy->getCompileStatusHq());
                    }
                    else {
                        $session->setCompileStatus(Therapy::STATUS_UNCOMPILED);
                        $session->setCompileStatusHq(Therapy::STATUS_UNCOMPILED);
                    }
                }

                //die;

                //$fullWeekCount += $daysBetweenSessions;

                $fillWeek = ($sessionCount % $daysPerWeek == 0) ? 0 : 0; // TODO...

                $iterateDate = clone $iterateDate;
                if ($daysCount >= $daysPerWeek) {
                    $daysBetweenSessions = 7 - $daysCount + 1; // add next session one week after the first session
                    $daysCount = 0; // reset counter
                } else {
                    $daysBetweenSessions = 1; // add next session on the next day
                    // $daysBetweenSessions = ceil((7 - $daysPerWeek) / $daysPerWeek); // TODO...
                }
                $iterateDate->add(new \DateInterval("P${daysBetweenSessions}D")); // plus 1 day


                $days--;
            }

            // Persist everything in database
            $em->flush();

            return $entity;
        }

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Update a UserTherapy entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, UserTherapy $entity)
    {
        $originalRequest = clone $request;

        try {
            $em = $this->getDoctrine()->getManager();
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new UserTherapyType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {

                // check if we only want to validate the form without submitting it
                if ($originalRequest->get('validateOnly')) {
                    return FOSView::create(null, Codes::HTTP_OK);
                }

                $em->flush();

                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Partial Update to a UserTherapy entity.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, UserTherapy $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a UserTherapy entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, UserTherapy $entity)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($entity);
            $em->flush();

            return null;
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
