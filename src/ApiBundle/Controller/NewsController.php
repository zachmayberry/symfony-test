<?php

namespace ApiBundle\Controller;

use AppBundle\Entity\Activity;
use AppBundle\Entity\News;
use AppBundle\Form\NewsType;

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
 * News controller.
 * @RouteResource("News")
 */
class NewsController extends VoryxController
{
    /**
     * Get a News entity
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @return Response
     *
     */
    public function getAction(News $entity)
    {
        return $entity;
    }

    /**
     * Get all News entities.
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
        $user = $this->getUser();

        $qb = $this->getDoctrine()
            ->getRepository('AppBundle:News')
            ->findAllQueryBuilder($paramFetcher, $user);

        $paginatedCollection = $this->get('pagination_factory')
            ->createCollection($qb, $paramFetcher, 'cget_news');

        return $paginatedCollection;
    }

    /**
     * Create a News entity.
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

        $entity = new News();
        $form = $this->createForm(get_class(new NewsType()), $entity, array("method" => $request->getMethod()));
        $this->removeExtraFields($request, $form);
        $form->handleRequest($request);

        if ($form->isValid()) {

            // check if we only want to validate the form without submitting it
            if ($originalRequest->get('validateOnly')) {
                return FOSView::create(null, Codes::HTTP_OK);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);

            // Create activity log
            $this->createActivityLogForNews($entity);

            // save to database
            $em->flush();

            return $entity;
        }

        return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Update a News entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function putAction(Request $request, News $entity)
    {
        $originalRequest = clone $request;

        try {
            $em = $this->getDoctrine()->getManager();
            $request->setMethod('PATCH'); //Treat all PUTs as PATCH
            $form = $this->createForm(get_class(new NewsType()), $entity, array("method" => $request->getMethod()));
            $this->removeExtraFields($request, $form);
            $form->handleRequest($request);
            if ($form->isValid()) {

                // check if we only want to validate the form without submitting it
                if ($originalRequest->get('validateOnly')) {
                    return FOSView::create(null, Codes::HTTP_OK);
                }

                // update publish date of activity log if changed
                /** @var Activity $activity */
                $activity = $em->getRepository(Activity::class)->findOneByNews($entity);

                if ($activity === null) {
                    $this->createActivityLogForNews($entity);
                }
                else if ($entity->getDate() != $activity->getPublishedAt()) {
                    $activity->setPublishedAt($entity->getDate());
                }

                $em->flush();

                return $entity;
            }

            return FOSView::create(array('errors' => $form->getErrors()), Codes::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Partial Update to a News entity.
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function patchAction(Request $request, News $entity)
    {
        return $this->putAction($request, $entity);
    }

    /**
     * Delete a News entity.
     *
     * @View(statusCode=204)
     *
     * @param Request $request
     * @param $entity
     *
     * @return Response
     */
    public function deleteAction(Request $request, News $entity)
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

    /**
     * Create Activity entry for a given news object
     *
     * @param News $entity
     * @param boolean $doFlush
     * @return Activity
     */
    public function createActivityLogForNews(News $entity, $doFlush = false)
    {
        $em = $this->getDoctrine()->getManager();

        $activity = new Activity();

        $activity->setType(Activity::TYPE_NEWS);
        $activity->setNews($entity);
        $activity->setUser($this->getUser());
        $activity->setTitle($entity->getTitle());

        // set publish date to release date
        $activity->setPublishedAt($entity->getDate());

        $em->persist($activity);

        if ($doFlush === true) {
            $em->flush();
        }

        return $activity;
    }
}
