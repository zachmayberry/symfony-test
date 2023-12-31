<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Entity\UserTherapy;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Doctrine\ORM\Query\Expr\Join;

/**
 * TherapySessionRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class TherapySessionRepository extends EntityRepository
{
    public function findAllQueryBuilder(ParamFetcherInterface $paramFetcher)
    {
        $qb = $this->createQueryBuilder('therapy_session');

        // FILTER
        $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();
        foreach($filters as $key => $filter) {

            if (strrpos($key, 'IsIn') > 0) {
                $keyName = substr($key, 0, strlen($key) - 4);
                $arrayValues = explode(',', $filter);
                $qb->andWhere($qb->expr()->in("therapy_session.$keyName", ":$key"))
                    ->setParameter($key, $arrayValues);
            }
            // get range if from or to is set
            else if ($key == 'from') {
                $qb->andWhere("therapy_session.startDate >= :$key")
                    ->setParameter($key, $filter);
            }
            else if ($key == 'till') {
                $qb->andWhere("therapy_session.startDate < :$key")
                    ->setParameter($key, $filter);
            }
            else {
                $qb->andWhere("therapy_session.$key = :$key")
                    ->setParameter($key, $filter);
            }
        }

        // ORDER BY
        $orderBy = !is_null($paramFetcher->get('order_by')) ? $paramFetcher->get('order_by') : array();
        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("therapy_session.$sort", $order);
        }

        return $qb;
    }

    public function findAndSetMissedSessions()
    {
        // check if session has been missed in the latest timezone, which is GMT-14
        $date = new \DateTime("now", new \DateTimeZone('GMT-14'));
        $fDate = $date->format("Y-m-d");

//        var_dump($date);
//        echo "\r\n";
//        var_dump($fDate);
//        echo "\r\n";
//        die;

        $qb = $this->_em->createQueryBuilder();
        $q = $qb->update(TherapySession::class, 's')
            ->set('s.status', ':missedStatus')
            ->where('s.status = :pendingStatus')
            ->andWhere('s.startDate < :today')
            ->setParameter('missedStatus', TherapySession::STATUS_MISSED)
            ->setParameter('pendingStatus', TherapySession::STATUS_PENDING)
            ->setParameter('today', $fDate)
            ->getQuery();

        $p = $q->execute();

        return $p; // return number of updated rows
    }

    public function findOldestOutdatedTherapySession()
    {
        $qb = $this->createQueryBuilder('session')->select('session');

        // not compiled and not compiling (audible or hq version)
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->andX(
                $qb->expr()->neq('session.compileStatus', ':statusCompiled'),
                $qb->expr()->neq('session.compileStatus', ':statusCompiling')
            ),
            $qb->expr()->andX(
                $qb->expr()->eq('session.includesHq', 1),
                $qb->expr()->neq('session.compileStatusHq', ':statusCompiled'),
                $qb->expr()->neq('session.compileStatusHq', ':statusCompiling')
            )
        ));

        $qb->setParameter('statusCompiled', Therapy::STATUS_COMPILED);
        $qb->setParameter('statusCompiling', Therapy::STATUS_COMPILING);

        // only pending sessions
        $qb->andWhere('session.status = :status');
        $qb->setParameter('status', TherapySession::STATUS_PENDING);

        // only the oldest one
        $qb->orderBy('session.createdAt', 'ASC'); // don't use updatedAt, since it is updated on compile status change
        $qb->setMaxResults(1);

        // return single entity or null
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findPendingSiblingSession(TherapySession $session)
    {
        $qb = $this->createQueryBuilder('session');
        $qb->where('session.userTherapy = :userTherapy');
        $qb->setParameter('userTherapy', $session->getUserTherapy());

        $qb->andWhere('session.id != :currentSessionId');
        $qb->setParameter('currentSessionId', $session->getId());

        $qb->andWhere('session.status = :status');
        $qb->setParameter('status', TherapySession::STATUS_PENDING);

        return $qb->getQuery()->getResult();
    }

//    public function findSiblingSessionsWithSamePlaylist(TherapySession $therapySession)
//    {
//        $qb = $this->createQueryBuilder('session');
//        $qb->where('session.musicPlaylist = :playlist');
//        $qb->setParameter('playlist', $therapySession->getMusicPlaylist());
//        $qb->andWhere('session.userTherapy = :userTherapy');
//        $qb->setParameter('userTherapy', $therapySession->getUserTherapy());
//        $qb->andWhere('session.compileStatus != :compileStatus');
//        $qb->setParameter('compileStatus', Therapy::STATUS_COMPILED);
//        $qb->andWhere('session.compileStatus != :compileStatus2');
//        $qb->setParameter('compileStatus2', Therapy::STATUS_COMPILING);
//        $qb->setMaxResults(1);
//
//        return $qb->getQuery()->getResult();
//    }

//    public function findAllAfterSession($session)
//    {
//        $qb = $this->createQueryBuilder('session');
//        $qb->where('session.userTherapy = :userTherapy');
//        $qb->setParameter('userTherapy', $session->getUserTherapy());
//        $qb->andWhere('session.nOfTotal > :nOfTotal');
//        $qb->setParameter('nOfTotal', $session->getNOfTotal());
//
//        return $qb->getQuery()->getResult();
//    }
}
