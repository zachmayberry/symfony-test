<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TrackLogRepository")
 * @ORM\Table(name="track_log")
 *
 * @ORM\HasLifecycleCallbacks
 */
class TrackLog
{
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Track")
     * @ORM\JoinColumn(name="track_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude()
     */
    private $track;

    /**
     * @ORM\Column(type="integer")
     */
    private $trackSavedId;

    /**
     * @ORM\Column(type="string")
     */
    private $trackTitle;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", nullable=true, name="artist_title")
     */
    private $artistTitle;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $user;

    /**
     * @ORM\Column(type="string", nullable=true, name="user_name")
     */
    private $userName;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Therapy", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="therapy_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude()
     */
    private $therapy;

    /**
     * @ORM\Column(type="string", nullable=true, name="therapy_title")
     */
    private $therapyTitle;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $duration;

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("therapyId")
     */
    public function virtualTherapyId()
    {
        return $this->therapy ? $this->therapy->getId() : null;
    }




    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getTrack()
    {
        return $this->track;
    }

    /**
     * @param mixed $track
     */
    public function setTrack($track)
    {
        $this->track = $track;
    }

    /**
     * @return mixed
     */
    public function getTrackSavedId()
    {
        return $this->trackSavedId;
    }

    /**
     * @param mixed $trackSavedId
     */
    public function setTrackSavedId($trackSavedId)
    {
        $this->trackSavedId = $trackSavedId;
    }

    /**
     * @return mixed
     */
    public function getTrackTitle()
    {
        return $this->trackTitle;
    }

    /**
     * @param mixed $trackTitle
     */
    public function setTrackTitle($trackTitle)
    {
        $this->trackTitle = $trackTitle;
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
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getArtistTitle()
    {
        return $this->artistTitle;
    }

    /**
     * @param mixed $artistTitle
     */
    public function setArtistTitle($artistTitle)
    {
        $this->artistTitle = $artistTitle;
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
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param mixed $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
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
     * @return mixed
     */
    public function getTherapyTitle()
    {
        return $this->therapyTitle;
    }

    /**
     * @param mixed $therapyTitle
     */
    public function setTherapyTitle($therapyTitle)
    {
        $this->therapyTitle = $therapyTitle;
    }

    /**
     * @return float|null
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param float $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
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