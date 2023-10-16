<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use AppBundle\Entity\UserTherapy;
use Doctrine\ORM\EntityManager;


class UserTherapyService
{
    private $em;

    /**
     * Inject stuff for use in this service
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Iterate through pending user therapies and check if they are completed or missed
     */
    public function updateAllUserTherapyStatus(User $user = null)
    {
        $pendingUserTherapies = $this->em->getRepository('AppBundle:UserTherapy')->findAllPending($user);

        foreach($pendingUserTherapies as $userTherapy) {

            $hasPendingSessions = $userTherapy->hasPendingSessions(true);
            $hasCompletedSessions = $userTherapy->hasCompletedSessions(true);

            if (!$hasPendingSessions) {

                if ($hasCompletedSessions) {
                    $userTherapy->setStatus(UserTherapy::STATUS_COMPLETED);

                    // increment source therapy's completed therapies counter if it still exists
                    $therapy = $userTherapy->getTherapy();
                    if ($therapy) {
                        $therapy->incrementCompletedTherapiesCounter();
                        $this->em->persist($therapy);
                    }

                    $this->em->persist($userTherapy);
                }
                else {
                    $userTherapy->setStatus(UserTherapy::STATUS_MISSED);

                    // increment source therapy's missed therapies counter if it still exists
                    $therapy = $userTherapy->getTherapy();
                    if ($therapy) {
                        $therapy->incrementMissedTherapiesCounter();
                        $this->em->persist($therapy);
                    }

                    $this->em->persist($userTherapy);
                }
            }
        }

        $this->em->flush();
    }
}
