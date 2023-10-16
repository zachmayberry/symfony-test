<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Playlist;
use AppBundle\Entity\PlaylistTrack;
use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Entity\TherapySessionFeedback;
use AppBundle\Entity\Track;
use AppBundle\Form\TherapySessionType;

use Assetic\Exception\Exception;
use Doctrine\Common\Collections\ArrayCollection;
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
 * TherapySession controller.
 * @RouteResource("TherapySession")
 */
class TherapySessionController extends VoryxController
{
    /**
     * Get a TherapySession entity
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @return Response
     *
     */
    public function getAction(TherapySession $entity)
    {
        return $entity;
    }

    /**
     * Get all TherapySession entities.
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
            ->getRepository('AppBundle:TherapySession')
            ->findAllQueryBuilder($paramFetcher);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'get_therapysessions');

        return $paginatedCollection;
    }

    /**
     * Create a TherapySession entity.
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

        $entity = new TherapySession();
        $form = $this->createForm(get_class(new TherapySessionType()), $entity, array("method" => $request->getMethod()));
        $this->removeExtraFields($request, $form);
        $form->handleRequest($request);

        if ($form->isValid()) {

            // check if we only want to validate the form without submitting it
            if ($originalRequest->get('validateOnly')) {
                return FOSView::create(null, Codes::HTTP_OK);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $entity;
        }

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Update a TherapySession entity.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail"})
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, TherapySession $entity)
    {
        $originalRequest = clone $request;

        try {
            $em = $this->getDoctrine()->getManager();
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new TherapySessionType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {

                // check if we only want to validate the form without submitting it
                if ($originalRequest->get('validateOnly')) {
                    return FOSView::create(null, Codes::HTTP_OK);
                }

                // get music playlist data from request
                if ($musicPlaylistData = $originalRequest->get("musicPlaylist")) {

                    // get existing playlist or create new one
                    /** @var Playlist $musicPlaylist */
                    if (!$musicPlaylist = $entity->getMusicPlaylist()) {
                        $musicPlaylist = new Playlist();
                        $musicPlaylist->setType(Playlist::TYPE_MUSIC);
                    }

                    // only update if playlist items have changed
                    $hasChanges = Playlist::comparePlaylistWithFormData($musicPlaylist, $musicPlaylistData);

                    if ($hasChanges && is_array($musicPlaylistData)) {

                        // clear existing playlist
                        $musicPlaylist->clearPlaylistTracks();

                        foreach ($musicPlaylistData as $key => $trackId) {

                            if ($track = $em->getRepository(Track::class)->find($trackId)) {
                                $playlistTrack = new PlaylistTrack();
                                $playlistTrack->setSorting($key); // $binauralSorting[$key]
                                $playlistTrack->setTrack($track);
                                $playlistTrack->setPlaylist($musicPlaylist);
                                $musicPlaylist->getPlaylistTracks()->add($playlistTrack);
                            }
                        }

                        $entity->setMusicPlaylist($musicPlaylist);
                        $entity->setCompileStatus(Therapy::STATUS_UNCOMPILED);
                        $entity->setCompileStatusHq(Therapy::STATUS_UNCOMPILED);

                        // update all sibling sessions
                        $siblingSessions = $em->getRepository(TherapySession::class)->findPendingSiblingSession($entity);
                        /** @var TherapySession $siblingSession */
                        foreach ($siblingSessions as $siblingSession) {
                            $siblingSession->setMusicPlaylist($musicPlaylist);
                            $siblingSession->setCompileStatus(Therapy::STATUS_UNCOMPILED);
                            $siblingSession->setCompileStatusHq(Therapy::STATUS_UNCOMPILED);
                        }

                    }

                    // update all upcoming sessions?
                    /*if ($originalRequest->get("updateAll")) {
                        $followingSessions = $em->getRepository(TherapySession::class)->findAllAfterSession($entity);
                        foreach ($followingSessions as $followingSession) {
                            $followingSession->setMusicPlaylist($musicPlaylist);
                            $followingSession->setCompileStatus(Therapy::STATUS_UNCOMPILED);
                        }
                    }*/
                }

                // change music playlists of all following sessions of this therapy
                /*if ($originalRequest->get("changeUpcoming")) {
                    //$entity->getUserTherapy()->get
                }*/

/*
                 // check which tracks have been listened in this session
                 if ($entity->isCompleted()) {

                     // check if it was completed before, otherwise this has been done before
                     $uow = $em->getUnitOfWork();
                     $uow->computeChangeSets(); // do not compute changes if inside a listener
                     $changeSet = $uow->getEntityChangeSet($entity);

                     if (isset($changeSet['status'])) {

                     }

                     var_dump($changeSet);
                     die;
                 }
                 throw new \Exception($entity->getStatus());
 */

                // add therapy session feedback if provided
                $feedbackType = $originalRequest->get("feedbackType");
                if ($feedbackType) {
                    $feedbackComment = $originalRequest->get("feedbackComment");

                    $feedback = new TherapySessionFeedback();
                    $feedback->setFeedback($feedbackComment);
                    $feedback->setType($feedbackType);
                    $feedback->setSession($entity);
                    if ($entity->getBaseTherapy()) {
                        $feedback->setTherapy($entity->getBaseTherapy());
                    }
                    $feedback->setUser($entity->getUser());

                    $em->persist($feedback);

                    // add feedback to session
                    $entity->setFeedback($feedback);
                }

                // increment play count on base therapy if session is completed
                // we assume that it has changed from uncompleted to completed
                // since we don't update completed sessions afterwards anymore!
                // TODO: check if status has changed to completed
                //$uow = $em->getUnitOfWork();
                //$uow->computeChangeSets();
                //$changeset = $uow->getEntityChangeSet($entity);
                //var_dump($changeset);
                //throw new \Exception("_____");
                if ($entity->isCompleted()) {
                    $baseTherapy = $entity->getBaseTherapy();
                    if ($baseTherapy) {
                        $baseTherapy->incrementCompletedSessionsCounter();
                        $baseTherapy->incrementTotalPlayedTime();
                    }
                }

                // update therapy counters and user therapy status
                $this->get('app.user_therapy_service')->updateAllUserTherapyStatus($entity->getUser());

                $em->flush();

                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Partial Update to a TherapySession entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, TherapySession $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a TherapySession entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, TherapySession $entity)
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
