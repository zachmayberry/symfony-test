<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\TherapySession;
use AppBundle\Entity\UserTherapy;
use Doctrine\ORM\Event\OnFlushEventArgs;

class TherapySessionListener
{
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $keyEntity => $entity) {
            if ($entity instanceof TherapySession) {

                // update status of session's UserTherapy
                $userTherapy = $entity->getUserTherapy();
                if ($userTherapy) {
                    $this->updateUserTherapyStatus($em, $userTherapy);
                }

                // update stats of sessions's User
                $this->updateUserStats($em, $entity);
            }
        }
    }

    public function updateUserTherapyStatus($em, $userTherapy)
    {
        $arrCompletedSessions = [];
        $arrMissedSessions = [];
        $arrPendingSessions = [];

        foreach ($userTherapy->getSessions() as $session) {
            if ($session->isCompleted()) {
                $arrCompletedSessions[] = $session->getId();
            }
            else if ($session->isMissed()) {
                $arrMissedSessions[] = $session->getId();
            }
            else {
                $arrPendingSessions[] = $session->getId();
            }
        }

        if (count($arrMissedSessions) && !count($arrCompletedSessions) && !count($arrPendingSessions)) {
            $userTherapy->setStatus(UserTherapy::STATUS_MISSED);
        }
        else if (count($arrCompletedSessions) && !count($arrPendingSessions) && !count($arrMissedSessions)) {
            $userTherapy->setStatus(UserTherapy::STATUS_COMPLETED);
        }
        else {
            $userTherapy->setStatus(UserTherapy::STATUS_PENDING);
        }

        // only persist since we need no flush inside of the lifecycle
        $em->persist($userTherapy);

        // now we have to compute the changeset to make the persist work
        // see: https://stackoverflow.com/questions/30734814/persisting-other-entities-inside-preupdate-of-doctrine-entity-listener
        $classMetadata = $em->getClassMetadata('AppBundle\Entity\UserTherapy');
        $em->getUnitOfWork()->computeChangeSet($classMetadata, $userTherapy);

        return $userTherapy;
    }

    private function updateUserStats($em, $therapySession)
    {
        // count completed therapies and completed and missed sessions
        if ($user = $therapySession->getUser()) {

            $completedTherapiesCountTotal = 0;
            $missedSessionsCountTotal = 0;
            $completedSessionsCountTotal = 0;
            $totalPlayTime = 0;

            foreach($user->getUserTherapies() as $userTherapy) {

                if ($userTherapy->isCompleted()) {
                    $completedTherapiesCountTotal ++;
                }

                $completedSessionsCount = $userTherapy->getCompletedSessionsCount();

                $missedSessionsCountTotal += $userTherapy->getMissedSessionsCount();
                $completedSessionsCountTotal += $completedSessionsCount;
                $totalPlayTime += ($completedSessionsCount * $userTherapy->getDosage() * 60); // in seconds
            }

            // update user entity
            $user->setCompletedSessionsCount($completedSessionsCountTotal);
            $user->setMissedSessionsCount($missedSessionsCountTotal);
            $user->setCompletedTherapiesCount($completedTherapiesCountTotal);
            $user->setTotalPlayTime($totalPlayTime);

            // only persist since we need no flush inside of the lifecycle
            $em->persist($user);

            // now we have to compute the changeset to make the persist work
            // see: https://stackoverflow.com/questions/30734814/persisting-other-entities-inside-preupdate-of-doctrine-entity-listener
            $classMetadata = $em->getClassMetadata('AppBundle\Entity\User');
            $em->getUnitOfWork()->computeChangeSet($classMetadata, $user);
        }

        return $therapySession;
    }
}