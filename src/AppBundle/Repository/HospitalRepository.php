<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Request\ParamFetcherInterface;

/**
 * HospitalRepository
 *
 * For own custom repository methods.
 */
class HospitalRepository extends EntityRepository
{
    public function findAllQueryBuilder(ParamFetcherInterface $paramFetcher)
    {
        $qb = $this->createQueryBuilder('hospital');
        $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();

        foreach($filters as $key => $filter) {
            $qb->andWhere("hospital.$key = :$key")
                ->setParameter($key, $filter);
        }

        // default sorting
        $qb->addOrderBy("hospital.title", 'ASC');

        return $qb;
    }
}
