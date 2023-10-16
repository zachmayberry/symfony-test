<?php

namespace AppBundle\Repository;

use AppBundle\Entity\UploadedAudio;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Request\ParamFetcherInterface;

/**
 * UploadedAudioRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class UploadedAudioRepository extends EntityRepository
{

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findAllQueryBuilder(ParamFetcherInterface $paramFetcher)
    {
        $qb = $this->createQueryBuilder('uploadedAudio');
        $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();

        foreach($filters as $key => $filter) {

            if (strrpos($key, 'IsIn') > 0) {
                $keyName = substr($key, 0, strlen($key) - 4);
                $arrayValues = explode(',', $filter);
                $qb->andWhere($qb->expr()->in("uploadedAudio.$keyName", ":$key"))
                    ->setParameter($key, $arrayValues);
            }
            else {
                $qb->andWhere("uploadedAudio.$key = :$key")
                    ->setParameter($key, $filter);
            }
        }

        // Order by createdAt by default
        $qb->orderBy('uploadedAudio.createdAt', 'DESC');

        return $qb;
    }


    /**
     * @return UploadedAudio
     */
    public function findOldestUnconvertedUploadedAudio()
    {
        $qb = $this->createQueryBuilder('uploadedAudio');
        $qb->where('uploadedAudio.compileStatus != :compileStatus');
        $qb->setParameter('compileStatus', UploadedAudio::STATUS_COMPILED);
        $qb->andWhere('uploadedAudio.compileStatus != :compileStatus2');
        $qb->setParameter('compileStatus2', UploadedAudio::STATUS_COMPILING);
        $qb->orderBy('uploadedAudio.updatedAt', 'ASC');
        $qb->setMaxResults(1);

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

}
