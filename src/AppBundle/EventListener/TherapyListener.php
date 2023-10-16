<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Therapy;
use AppBundle\Entity\TherapySession;
use AppBundle\Entity\UserTherapy;
use AppBundle\Service\TherapyService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class TherapyListener
{
    protected $em;
    protected $ts;

    public function __construct(EntityManager $em, TherapyService $ts)
    {
        $this->em = $em;
        $this->ts = $ts;
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $therapy = $args->getObject();

        if ($therapy instanceof Therapy && $therapy->getCompileStatus() === Therapy::STATUS_UNCOMPILED) {

            // generate the therapy file
            $this->ts->generateTherapyFileForTherapy($therapy);
        }
    }
}