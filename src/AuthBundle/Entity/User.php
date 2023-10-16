<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AuthBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use UserBundle\Entity\User as BaseUser;

/**
 * User.
 *
 * implements AdvancedUserInferface for handling user security status flags
 * see: http://symfony.com/doc/current/security/entity_provider.html#forbid-inactive-users-advanceduserinterface
 *
 * @ORM\MappedSuperclass
 */
class User extends BaseUser //implements AdvancedUserInterface
{
    /**
     * @ORM\Column(name="facebook_id", type="string", length=255, nullable=true)
     * @Serializer\Exclude()
     */
    private $facebookId;

    /**
     * @Serializer\Exclude()
     */
    private $facebookAccessToken;

    /**
     * @ORM\Column(name="google_id", type="string", length=255, nullable=true)
     * @Serializer\Exclude()
     */
    private $googleId;

    /**
     * @Serializer\Exclude()
     */
    private $googleAccessToken;

    /**
     * @ORM\Column(name="approved", type="boolean")
     */
    private $approved = 1;

    /**
     * @return mixed
     */
    public function isApproved()
    {
        return $this->approved;
    }

    /**
     * @param mixed $approved
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

//    /**
//     * Checks whether the user is locked.
//     *
//     * Internally, if this method returns false, the authentication system
//     * will throw a LockedException and prevent login.
//     *
//     * @return bool true if the user is not locked, false otherwise
//     *
//     * @see LockedException
//     */
//    public function isAccountNonLocked()
//    {
//        return $this->isApproved();
//    }

    /**
     * @param string $facebookId
     * @return User
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    /**
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    /**
     * @param string $facebookAccessToken
     * @return User
     */
    public function setFacebookAccessToken($facebookAccessToken)
    {
        $this->facebookAccessToken = $facebookAccessToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getFacebookAccessToken()
    {
        return $this->facebookAccessToken;
    }

    /**
     * @param string $googleId
     * @return User
     */
    public function setGoogleId($googleId)
    {
        $this->googleId = $googleId;

        return $this;
    }

    /**
     * @return string
     */
    public function getGoogleId()
    {
        return $this->googleId;
    }

    /**
     * @param string $googleAccessToken
     * @return User
     */
    public function setGoogleAccessToken($googleAccessToken)
    {
        $this->googleAccessToken = $googleAccessToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getGoogleAccessToken()
    {
        return $this->googleAccessToken;
    }

    /**
     * Returns a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername() ?: 'Anonymous';
    }
}