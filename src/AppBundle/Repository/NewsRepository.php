<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Request\ParamFetcherInterface;

/**
 * NewsRepository
 *
 * For own custom repository methods.
 */
class NewsRepository extends EntityRepository
{
    public function findAllQueryBuilder(ParamFetcherInterface $paramFetcher, User $user = null)
    {
        $qb = $this->createQueryBuilder('news');

        // Make sure that non-admins don't see unreleased news
        if (!$user || !$user->hasRole('ROLE_ADMIN')) {
            $qb->where("news.date <= :date");
            $qb->setParameter('date', new \DateTime());
        }

        // FILTER
        $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();
        foreach($filters as $key => $filter) {

            // don't allow date field for non admins
            if ($key !== 'date' || ($user && $user->hasRole('ROLE_ADMIN'))) {
                $qb->andWhere("news.$key = :$key")
                    ->setParameter($key, $filter);
            }
        }

        // ORDER BY
        $orderBy = !is_null($paramFetcher->get('order_by')) ? $paramFetcher->get('order_by') : array();
        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("news.$sort", $order);
        }

        return $qb;
    }
}
