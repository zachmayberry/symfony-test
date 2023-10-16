<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Therapy;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Request\ParamFetcherInterface;

/**
 * MediaRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class TherapyRepository extends EntityRepository
{
    public function findAllQueryBuilder(ParamFetcherInterface $paramFetcher, User $user = null)
    {
        $qb = $this->createQueryBuilder('therapy');

        $filters = !is_null($paramFetcher->get('filters')) ? $paramFetcher->get('filters') : array();

        $isPublishedFilterSet = false;

        // allow users to fetch parent therapies of their userTherapies
        $fetchForTherapyPlayer = isset($filters['forPlayer']);
        if ($fetchForTherapyPlayer) {
            unset($filters['forPlayer']);
        }

        // TODO: make secure
//        if ($fetchForTherapyPlayer && $user && $user->isPatient()) {
//
//            $userTherapies = $user->getUserTherapies()->getIds(); //implement getIds
//            $qb->where('therapy.id IN (:userTherapies)')
//                ->setParameter('userTherapies', $userTherapies);
//
//            return $qb;
//        }

        // Make sure that patients can only see public therapies
        if (!$fetchForTherapyPlayer &&(!$user || (!$user->isDoctor() && !$user->isAdmin()))) {

            $qb->where("therapy.public = 1");
        }

        // Make sure that non-admins don't see admins templates
        if (!$user || !$user->hasRole('ROLE_ADMIN')) {

            $isPublishedFilterSet = true;

            $qb->andWhere("therapy.published = 1");
        }

        // FILTER
        foreach($filters as $key => $filter) {

            // ignore public filter for patients/users
            if ($key === 'public' && (!$user || (!$user->hasRole('ROLE_DOCTOR') && !$user->hasRole('ROLE_ADMIN')))) {
                continue;
            }

            // handle published filter
            if ($key === 'published') {

                // only allow published filter for admins
                if (!$user || !$user->hasRole('ROLE_ADMIN')) {
                    continue;
                }

                $isPublishedFilterSet = true;
            }

            if (strrpos($key, 'IsIn') > 0) {
                $keyName = substr($key, 0, strlen($key) - 4);
                $arrayValues = explode(',', $filter);
                $qb->andWhere($qb->expr()->in("therapy.$keyName", ":$key"))
                    ->setParameter($key, $arrayValues);
            }
            else {
                $qb->andWhere("therapy.$key = :$key")
                    ->setParameter($key, $filter);
            }

        }

        // if published filter is still not set, apply it to always filter out therapy templates
        if (!$isPublishedFilterSet) {
            $qb->andWhere("therapy.published = 1");
        }

        // ORDER BY
        $orderBy = !is_null($paramFetcher->get('order_by')) ? $paramFetcher->get('order_by') : array();
        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("therapy.$sort", $order);
        }

        return $qb;
    }

    /**
     * Get oldest published therapy which is not compiled and not currently compiling
     *
     * @return mixed
     */
    public function findOldestOutdatedTherapy()
    {
        $qb = $this->createQueryBuilder('therapy');

        // published
        $qb->andWhere('therapy.published = :published');
        $qb->setParameter('published', true);

        // not compiled and not compiling (audible or hq version)
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->andX(
                $qb->expr()->neq('therapy.compileStatus', ':statusCompiled'),
                $qb->expr()->neq('therapy.compileStatus', ':statusCompiling')
            ),
            $qb->expr()->andX(
                $qb->expr()->eq('therapy.includesHq', 1),
                $qb->expr()->neq('therapy.compileStatusHq', ':statusCompiled'),
                $qb->expr()->neq('therapy.compileStatusHq', ':statusCompiling')
            )
        ));

        $qb->setParameter('statusCompiled', Therapy::STATUS_COMPILED);
        $qb->setParameter('statusCompiling', Therapy::STATUS_COMPILING);

        // only the oldest one
        $qb->orderBy('therapy.createdAt', 'ASC'); // don't use updatedAt, since it is updated on compile status change
        $qb->setMaxResults(1);

        // return single entity or null
        return $qb->getQuery()->getOneOrNullResult();
    }
}
