<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Track;
use AppBundle\Form\TrackType;

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
 * Track controller.
 * @RouteResource("Track")
 */
class TrackController extends VoryxController
{
    /**
     * Get a Track entity
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail", "List"})
     *
     * @return Response
     *
     */
    public function getAction(Track $entity)
    {
        return $entity;
    }

    /**
     * Get all Track entities.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail", "List"})
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
            ->getRepository('AppBundle:Track')
            ->findAllQueryBuilder($paramFetcher);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'get_tracks');

        return $paginatedCollection;
    }

    /**
     * Create a Track entity.
     *
     * @View(statusCode=201, serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail", "List"})
     *
     * @param Request $request
     *
     * @return Response
     *
     */
    public function postAction(Request $request)
    {
        $originalRequest = clone $request;

        if ($request->get('symptoms') && !is_array($request->get('symptoms'))) {
            $request->request->set('symptoms', explode(',', $request->get('symptoms')));
        }

        $entity = new Track();
        $form = $this->createForm(get_class(new TrackType()), $entity, array("method" => $request->getMethod()));
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

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Update a Track entity.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail", "List"})
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, Track $entity)
    {
        try {
            $originalRequest = clone $request;

            $em = $this->getDoctrine()->getManager();
            $request->setMethod('POST'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new TrackType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {

                // check if we only want to validate the form without submitting it
                if ($originalRequest->get('validateOnly')) {
                    return FOSView::create(null, Codes::HTTP_OK);
                }

                // update here instead of preUpdate hook
                $entity->setUpdatedAt(new \DateTime());

                $em->flush();

                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors(true, false)), Codes::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Partial Update to a Track entity.
     *
     * @View(serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "Detail", "List"})
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, Track $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a Track entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, Track $entity)
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
