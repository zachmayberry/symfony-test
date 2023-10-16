<?php

namespace AppBundle\EventListener;


use AppBundle\Entity\UserLog;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Doctrine\ORM\Event\LifecycleEventArgs;

class SecurityListener
{
    protected $tokenStorage;
    protected $userManager;

    public function __construct(TokenStorage $tokenStorage, EntityManager $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $entityManager;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        // to only write logs for real logins and not for refresh token logins, we have to
        // check the token to be of type "UsernamePasswordToken" and not "JWTUserToken"
        $token = $event->getAuthenticationToken();

        if (strpos($token, 'UsernamePasswordToken') === 0) {

            // write log for login event
            $userLog = new UserLog();
            $userLog->setUser($user);
            $userLog->setEventType(UserLog::EVENT_TYPE_LOGIN);

            $this->em->persist($userLog);
            $this->em->flush();
        }
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $user = $args->getObject();
        $objectManager = $args->getObjectManager();

        //var_dump($user instanceof UserInterface);die;

        // only act on "User" entity
        if (!$user instanceof UserInterface) {
            return;
        }

        // write log for register event
        $userLog = new UserLog();
        $userLog->setUser($user);
        $userLog->setEventType(UserLog::EVENT_TYPE_REGISTER);

        $objectManager->persist($userLog);
    }
}