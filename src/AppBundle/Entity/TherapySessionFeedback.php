<?php


namespace AppBundle\Entity;

use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TherapySessionFeedbackRepository")
 * @ORM\Table(name="therapy_session_feedback")
 *
 * @ORM\HasLifecycleCallbacks
 */
class TherapySessionFeedback
{
    const TYPE_NONE = 0;
    const TYPE_WORSE = 1;
    const TYPE_GOOD = 2;
    const TYPE_BETTER = 3;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $feedback;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\TherapySession", inversedBy="feedback")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude()
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="feedback")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude()
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Therapy", inversedBy="feedback")
     * @ORM\JoinColumn(name="therapy_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude()
     */
    private $therapy;


    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("session")
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->session ? $this->session->getId() : null;
    }


    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("user")
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user ? $this->user->getId() : null;
    }


    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("therapy")
     *
     * @return integer
     */
    public function getTherapyId()
    {
        return $this->therapy ? $this->therapy->getId() : null;
    }


    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return array
     * @throws InvalidArgumentException
     */
    public function setType($type)
    {
        $allowedTypes = [self::TYPE_NONE, self::TYPE_WORSE, self::TYPE_GOOD, self::TYPE_BETTER];
        if (!in_array($type, $allowedTypes)) {
            throw new InvalidArgumentException("Invalid feedback type give. Expecting one of " . implode(", ", $allowedTypes) . " but got $type");
        }
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * @param mixed $feedback
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getTherapy()
    {
        return $this->therapy;
    }

    /**
     * @param mixed $therapy
     */
    public function setTherapy($therapy)
    {
        $this->therapy = $therapy;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $date = new \DateTime();

        if (!$this->createdAt) {
            $this->createdAt = $date;
        }
    }
}