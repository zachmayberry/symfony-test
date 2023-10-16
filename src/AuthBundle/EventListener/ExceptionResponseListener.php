<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AuthBundle\EventListener;

use AuthBundle\Exception\UserException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Listens for exceptions and transform Response.
 *
 * @author Robin Chalas <rchalas@sutunam.com>
 */
class ExceptionResponseListener
{
    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelResponse(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof UserException) {
            return;
        }

        $response = $this->createJsonResponseForException($exception);

        $event->setResponse($response);
    }

    /**
     * Create JsonResponse for Exception.
     *
     * @param UserException $exception
     *
     * @return JsonResponse
     */
    protected function createJsonResponseForException(UserException $exception)
    {
        $message = $exception->getMessage();
        $statusCode = $exception->getStatusCode();
        $content = ['error' => str_replace('"', '\'', $message)];

        return new JsonResponse($content, $statusCode);
    }
}