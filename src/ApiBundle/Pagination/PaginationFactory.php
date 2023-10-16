<?php

namespace ApiBundle\Pagination;

use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use FOS\RestBundle\View\View as FOSView;
use FOS\RestBundle\Util\Codes;


class PaginationFactory
{
    /**
     * @var RouterInterface
     */
    private $router;


    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param QueryBuilder $qb
     * @param Request $request
     * @param $route
     * @param array $routeParams
     * @return PaginatedCollection
     */
    public function createCollection(QueryBuilder $qb, ParamFetcherInterface $paramFetcher, $route, array $routeParams = array())
    {
        try {
            $page = $paramFetcher->get('page') ?: 1;
            $limit = $paramFetcher->get('limit');
            // show all items when limit is set to 0 explicitly
            if ((int)$limit === 0) {
                $limit = 9999;
            }

            $adapter = new DoctrineORMAdapter($qb);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($limit);
            $pagerfanta->setCurrentPage($page);

            $items = [];
            foreach ($pagerfanta->getCurrentPageResults() as $result) {
                $items[] = $result;
            }

            if (!count($items)) {
                return FOSView::create('Not Found', Codes::HTTP_NO_CONTENT);
            }

            $paginatedCollection = new PaginatedCollection($items, $page, $pagerfanta->getNbResults());

            // make sure query parameters are included in pagination links
            $order_by = $paramFetcher->get('order_by');
            $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();
            $routeParams = array_merge($routeParams, $paramFetcher->all());
            $createLinkUrl = function ($targetPage) use ($route, $routeParams, $order_by, $filters) {

                $requestParams = ['page' => $targetPage];
                if ($order_by) {
                    $requestParams['order_by'] = $order_by;
                }
                if ($filters) {
                    $requestParams['filters'] = $filters;
                }
                return $this->router->generate($route, array_merge(
                    $routeParams, $requestParams
                ));
            };
            $paginatedCollection->addLink('self', $createLinkUrl($page));
            $paginatedCollection->addLink('first', $createLinkUrl(1));
            $paginatedCollection->addLink('last', $createLinkUrl($pagerfanta->getNbPages()));
            if ($pagerfanta->hasNextPage()) {
                $paginatedCollection->addLink('next', $createLinkUrl($pagerfanta->getNextPage()));
            }
            if ($pagerfanta->hasPreviousPage()) {
                $paginatedCollection->addLink('prev', $createLinkUrl($pagerfanta->getPreviousPage()));
            }

            return $paginatedCollection;
        } catch (\Exception $e) {
            return FOSView::create($e->getMessage(), Codes::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}