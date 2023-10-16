<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Request\ParamFetcherInterface;

/**
 * MedicalScienceRepository
 *
 * For own custom repository methods.
 */
class MedicalScienceRepository extends EntityRepository
{
    public function findAllQueryBuilder(ParamFetcherInterface $paramFetcher)
    {
        $qb = $this->createQueryBuilder('medical_science');
        $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();

        foreach($filters as $key => $filter) {
            $qb->andWhere("medical_science.$key = :$key")
                ->setParameter($key, $filter);
        }

        // default sorting
        $qb->addOrderBy("medical_science.title", 'ASC');

        return $qb;
    }
}
