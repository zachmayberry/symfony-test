<?php

namespace AuthBundle\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\AbstractStorage;

/**
 * Class JWTCreatedListener
 * see: https://github.com/lexik/LexikJWTAuthenticationBundle/blob/master/Resources/doc/2-data-customization.md#eventsjwt_created---adding-data-to-the-jwt-payload
 *
 * @package AuthBundle\EventListener
 */
class JWTCreatedListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Add custom data to JWT Token
     *
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        $payload       = $event->getData();
        $payload['ip'] = $request->getClientIp();


        // Add user data
        $user = $event->getUser();
        $payload['id'] = $user->getId();

        $payload['user'] = [
            'gender' => $user->getGender(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
        ];

        $event->setData($payload);
    }
}