<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PlaylistRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class PlaylistRepository extends EntityRepository
{
    public function findAllQueryBuilder(ParamFetcherInterfac $paramFetcher)
    {
        $qb = $this->createQueryBuilder('playlist');

        // FILTER
        $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();
        foreach($filters as $key => $filter) {
            $qb->andWhere("playlist.$key = :$key")
                ->setParameter($key, $filter);
        }

        // ORDER BY
        $orderBy = !is_null($paramFetcher->get('order_by')) ? $paramFetcher->get('order_by') : array();
        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("playlist.$sort", $order);
        }

        return $qb;
    }
}
