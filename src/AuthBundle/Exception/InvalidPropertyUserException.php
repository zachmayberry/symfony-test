<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AuthBundle\Exception;

/**
 * InvalidUserException is thrown when an User property/identifer is not valid.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class InvalidPropertyUserException extends UserException
{
    /**
     * Constructor.
     *
     * @param string          $message  The internal exception message
     * @param \Exception|null $previous The previous exception
     * @param int             $code     The internal exception code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(422, $message, $previous, [], $code);
    }
}