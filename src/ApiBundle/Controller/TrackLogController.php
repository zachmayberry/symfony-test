<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\TrackLog;
use AppBundle\Form\TrackLogType;

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
 * TrackLog controller.
 * @RouteResource("TrackLog")
 */
class TrackLogController extends VoryxController
{
    /**
     * Get a TrackLog entity
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @return Response
     *
     */
    public function getAction(TrackLog $entity)
    {
        return $entity;
    }

    /**
     * Get all TrackLog entities.
     *
     * @View(serializerEnableMaxDepthChecks=true)
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
            ->getRepository('AppBundle:TrackLog')
            ->findAllQueryBuilder($paramFetcher);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'get_tracklogs');

        return $paginatedCollection;
    }

    /**
     * Create a TrackLog entity.
     *
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function postAction(Request $request)
    {
        $entity = new TrackLog();
        $form = $this->createForm(get_class(new TrackLogType()), $entity, array("method" => $request->getMethod()));
        $this->removeExtraFields($request, $form);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // get related entities
            $track = $entity->getTrack();

            // don't add log if track is not available anymore
            if (!$track) {
                return FOSView::create(null, Codes::HTTP_NOT_FOUND);
            }
            $therapy = $entity->getTherapy();

            // complete track data
            $entity->setTrack($track);
            $entity->setTrackSavedId($track->getId());
            $entity->setTrackTitle($track->getTitle());
            $entity->setType($track->getType());
            $entity->setTitle($track->getTitle());
            $entity->setArtistTitle($track->getArtistsString());

            // cmplete user data
            $entity->setUserName($entity->getUser()->getFullName());

            // cmplete therapy data
            if ($therapy) {
                $entity->setTherapy($therapy);
            }
            $entity->setTherapyTitle($therapy ? $therapy->getTitle() : 'Source Therapy is not available anymore');

            // also increment track's playCount
            $track->incrementPlayCount();
            $track->incrementPlayTime($entity->getDuration());
            $em->persist($track);

            $em->persist($entity);
            $em->flush();

            return $entity;
        }

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Update a TrackLog entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, TrackLog $entity)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new TrackLogType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->flush();

                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Partial Update to a TrackLog entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, TrackLog $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a TrackLog entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, TrackLog $entity)
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
