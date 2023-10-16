<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Request\ParamFetcherInterface;

/**
 * UserRepository
 *
 * For own custom repository methods.
 */
class UserRepository extends EntityRepository
{
    public function findAllQueryBuilder(ParamFetcherInterface $paramFetcher, User $user)
    {
        $qb = $this->createQueryBuilder('user');

        // Make sure that a non-admin can only see himself, his patients, so normal
        // users can also only see themselves because they don't have patients
        if (!$user->hasRole('ROLE_ADMIN')) {

//            $qb->andWhere($qb->expr()->isMemberOf(":adminFilter", "user.doctors"))
//                ->setParameter("adminFilter", $user->getId());

            $qb->where($qb->expr()->orX(
                $qb->expr()->isMemberOf(":adminFilter", "user.doctors"),
                $qb->expr()->eq("user.id", ':userSelf'),
                $qb->expr()->eq("user.type", ':typeDoctor')
            ));
            $qb->setParameter("adminFilter", $user->getId())
                ->setParameter('userSelf', $user->getId())
                ->setParameter('typeDoctor', User::USER_TYPE_DOCTOR);
        }

        // Hide disabled accounts for non doctors/admins
        if (!$user->hasRole('ROLE_ADMIN') && !$user->hasRole('ROLE_DOCTOR')) {
            $qb->andWhere("user.enabled = 1");
        }

        // FILTER
        $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();
        foreach($filters as $key => $filter) {

            if (strrpos($key, 'IsIn') > 0) {
                $keyName = substr($key, 0, strlen($key) - 4);
                $arrayValues = explode(',', $filter);
                $qb->andWhere($qb->expr()->in("user.$keyName", ":$key"))
                    ->setParameter($key, $arrayValues);
            }
            else if (strrpos($key, '__') === 0) {

                $keyName = substr($key, 2);

                $qb->orWhere("user.$keyName = :$key")
                    ->setParameter($key, $filter);
            }
            else if ($key === 'doctors') {

                $qb->andWhere($qb->expr()->isMemberOf(":$key", "user.doctors"))
                    ->setParameter($key, $filter);
            }
            else if ($key === 'patients') {

                $qb->andWhere($qb->expr()->isMemberOf(":$key", "user.patients"))
                    ->setParameter($key, $filter);
            }
            else {
                $qb->andWhere("user.$key = :$key")
                    ->setParameter($key, $filter);
            }
        }

        //var_dump($qb->getQuery()->getDQL());
        #$params = $paramFetcher->all();
        #$sort = $params['order_by'];
        #unset($params['order_by']);
        #$qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        #$qb = $this->get('app.queryparamservice')->buildSelectQuery($qb, $params, 'User', $sort);


        // ORDER BY
        $orderBy = !is_null($paramFetcher->get('order_by')) ? $paramFetcher->get('order_by') : array();
        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("user.$sort", $order);
        }

        return $qb;
    }


    /**
     * Get active users (optionally filtered by type)
     * @param $type
     * @return array
     */
    /*public function getActive($type)
    {
        $delay = new \DateTime();
        $delay->setTimestamp(strtotime('2 minutes ago'));

        $qb = $this->createQueryBuilder('u')
            ->where('u.lastActivityAt > :delay')
            ->setParameter('delay', $delay)
        ;

        if ($type) {
            $qb->andWhere('u.type = :type');
            $qb->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }*/


    /**
     * Get active users (optionally filtered by type)
     *
     * @param $type
     * @return array
     */
    public function getActiveCount($type = null)
    {
        $delay = new \DateTime();
        $delay->modify('-15 minute');

        $qb = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.lastActivityAt > :delay')
            ->setParameter('delay', $delay)
        ;

        if (isset($type)) {
            $qb->andWhere('u.type = :type');
            $qb->setParameter('type', $type);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }


    /**
     * Get registered users (optionally filtered by type)
     *
     * @param $type
     * @return array
     */
    public function getRegisteredCount($type = null)
    {
        // For admin we get all users
        $qb = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            //->where('u.enabled = enabled')
            //->setParameter('enabled', true)
        ;

        if (isset($type)) {
            $qb->andWhere('u.type = :type');
            $qb->setParameter('type', $type);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
