<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Intl\Exception\MissingResourceException;


class UserService
{
    private $user;

    private $entityManager;

    /**
     * Inject stuff for use in this service
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
}