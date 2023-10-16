<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\UserTherapy;
use Doctrine\ORM\Event\LifecycleEventArgs;

class UserTherapyListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->setTherapyColor($args);
    }

    public function setTherapyColor(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        // only act on some "UserTherapy" entity
        if (!$object instanceof UserTherapy) {
            return;
        }

        // set color if not already set
        if (!$object->getColor()) {

            $objectManager = $args->getObjectManager();

            $user = $object->getUser();
            $type = $object->getType();

            $colorSet = $object->getColorSetByType($type);
            $lastUsedColor = $user->getLastUsedColorByType($type);

            $colorIndex = array_search($lastUsedColor, $colorSet);

            // if not set, start with the first color
            if ($colorIndex === false) {
                $colorIndex = -1;
            }

            // set index to next color
            $colorIndex ++;

            // start with first color in set if reached end
            if ($colorIndex >= count($colorSet)) {
                $colorIndex = 0;
            }

            $color = $colorSet[$colorIndex];

            $user->setLastUsedColorByType($color, $type);
            $object->setColor($color);

            // only persist since we need no flush inside of the lifecycle
            $objectManager->persist($user);
        }
    }
}