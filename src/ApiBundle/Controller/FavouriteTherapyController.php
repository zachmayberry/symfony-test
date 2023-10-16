<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\FavouriteTherapy;
use AppBundle\Form\FavouriteTherapyType;

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
 * FavouriteTherapy controller.
 * @RouteResource("FavouriteTherapy")
 */
class FavouriteTherapyController extends VoryxController
{
    /**
     * Get a FavouriteTherapy entity
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @return Response
     *
     */
    public function getAction(FavouriteTherapy $entity)
    {
        return $entity;
    }

    /**
     * Get all FavouriteTherapy entities.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return Response
     *
     * @QueryParam(name="page", requirements="\d+", nullable=true, description="Which page to return when more than limit.")
     * @QueryParam(name="limit", requirements="\d+", default="9999", description="How many notes to return.")
     * @QueryParam(name="order_by", nullable=true, array=true, description="Sorting by fields. Must be an array ie. &order_by[name]=ASC&order_by[description]=DESC")
     * @QueryParam(name="filters", nullable=true, array=true, description="Filter by fields. Must be an array ie. &filters[id]=3")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:FavouriteTherapy')
            ->findAllQueryBuilder($paramFetcher);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'get_favouritetherapies');

        return $paginatedCollection;
    }

    /**
     * Create a FavouriteTherapy entity.
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
        $originalRequest = clone $request;

        $entity = new FavouriteTherapy();
        $form = $this->createForm(get_class(new FavouriteTherapyType()), $entity, array("method" => $request->getMethod()));
        $this->removeExtraFields($request, $form);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $entity;
        }

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Update a FavouriteTherapy entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, FavouriteTherapy $entity)
    {
        $originalRequest = clone $request;

        try {
            $em = $this->getDoctrine()->getManager();
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new FavouriteTherapyType()), $entity, array("method" => $request->getMethod()));
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
     * Partial Update to a FavouriteTherapy entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, FavouriteTherapy $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a FavouriteTherapy entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, FavouriteTherapy $entity)
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
