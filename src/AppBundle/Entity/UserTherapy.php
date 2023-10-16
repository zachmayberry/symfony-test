<?php


namespace AppBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Faker\Provider\DateTime;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserTherapyRepository")
 * @ORM\Table(name="user_therapy")
 * @ORM\HasLifecycleCallbacks
 *
 * @Hateoas\Relation(
 *     "user",
 *     embedded = "expr(object.getEmbeddedUserData())",
 *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getUser() === null)")
 * )
 */
class UserTherapy
{
    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_MISSED = 2;

    const TONES_COLORS = [
        '#4aaaff',
        '#0074da',
        '#0356a5',
    ];

    const MUSIC_COLORS = [
        '#22cad0',
        '#1fb0b5',
        '#2b8a9a',
    ];

    const ENVIRO_COLORS = [
        '#c75090',
        '#d42181',
        '#9c1c60',
    ];

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
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * Therapy type [see constants in Therapy entity)
     *
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $includesHq = false;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Playlist", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="binaural_playlist_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     *
     * @Serializer\Exclude
     */
    private $binauralPlaylist;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dosage;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $rate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $days;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cycle;

    /**
     * @ORM\Column(type="string", name="cycle_type", nullable=true)
     */
    private $cycleType;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="userTherapies", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Therapy", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="therapy_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude
     */
    private $therapy;

    /**
     * One UserTherapy has many Sessions.
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\TherapySession", mappedBy="userTherapy", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude()
     */
    private $sessions;

    /**
     * @ORM\Column(type="string", length=7, nullable=true)
     *
     * @var string
     */
    private $color;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var integer
     */
    private $status = self::STATUS_PENDING;

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("therapy")
     */
    public function virtualTherapy()
    {
        // check if therapy is defined, maybe it has been deleted...
        return $this->therapy ? $this->therapy->getId() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("user")
     */
    public function virtualUser()
    {
        return $this->user ? $this->user->getId() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("sessions")
     *
     * @return array
     */
    public function virtualSessions()
    {
        $sessions = array();

        /** @var TherapySession $session */
        foreach ($this->sessions as $session) {
            $sessions[$session->getId()] = [
                'user' => $this->getUserId(),
                'id' => $session->getId(),
                'total' => $this->virtualTotalSessions(),
                'nOfTotal' => $session->getNOfTotal(),
                'startDate' => $session->getStartDate(),
                'startTime' => $session->getStartTime(),
                'isCompleted' => $session->isCompleted(),
                'isCompiled' => $session->isCompiled(),
                'userTherapy' => $this->getId(),
                'therapyTitle' => $this->getTitle(),
                'therapyType' => $this->getType(),
            ];
        }

        return $sessions;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("completedSessions")
     *
     * @return array
     */
    public function virtualCompletedSessions()
    {
        return $this->getCompletedSessions();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("totalSessions")
     */
    public function virtualTotalSessions()
    {
        return count($this->sessions);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("percentCompleted")
     *
     * @return float
     */
    public function virtualPercentCompleted()
    {
        $completedSessionsCount = count($this->virtualCompletedSessions());
        $sessionsCount = count($this->sessions);

        return !$sessionsCount ? 0 : round($completedSessionsCount / $sessionsCount * 100);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("startDate")
     *
     * @return \DateTime
     */
    public function virtualStartDate()
    {
        return $this->hasSessions() ? $this->sessions->first()->getStartDate() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("endDate")
     *
     * @return \DateTime
     */
    public function virtualEndDate()
    {
        return $this->hasSessions() ? $this->sessions->last()->getStartDate() : null;
    }

    /**
     * Get if all sessions have been completed
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isCompleted")
     *
     * @return boolean
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get if all sessions have been missed
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isMissed")
     *
     * @return boolean
     */
    public function isMissed()
    {
        return $this->status === self::STATUS_MISSED;
    }

    /**
     * Get if therapy has pending sessions
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isPending")
     *
     * @return boolean
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get if at least one session is completed
     *
     * @param boolean $recheckAllSessions
     *
     * @return boolean
     */
    public function hasCompletedSessions($recheckAllSessions = false)
    {
        if (!$recheckAllSessions && $this->isCompleted()) {
            return true;
        }

        foreach ($this->sessions as $session) {
            if ($session->isCompleted()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get if at least one session is pending
     *
     * @param boolean $recheckAllSessions
     *
     * @return boolean
     */
    public function hasPendingSessions($recheckAllSessions = false)
    {
        if (!$recheckAllSessions && $this->isPending()) {
            return true;
        }

        foreach ($this->sessions as $session) {
            if ($session->isPending()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("hasTonesPlaylist")
     */
    public function virtualIsTone()
    {
        return $this->hasTonesPlaylist();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("searchString")
     *
     * @Serializer\Groups({"List"})
     */
    public function getSearchString()
    {
        $string = $this->title . ' ' . $this->user->getFullname();

        return strtolower($string);
    }


    public function __construct()
    {
        $this->sessions = new ArrayCollection();
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
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param integer $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getBinauralPlaylist()
    {
        // use parent binaural playlist as fallback for older therapies that have no own copy of the playlist
        if (!$this->binauralPlaylist) {
            return $this->getTherapy()->getBinauralPlaylist();
        }

        return $this->binauralPlaylist;
    }

    /**
     * @param mixed $binauralPlaylist
     */
    public function setBinauralPlaylist($binauralPlaylist)
    {
        $this->binauralPlaylist = $binauralPlaylist;
    }

    /**
     * @return integer
     */
    public function getDosage()
    {
        return $this->dosage;
    }

    /**
     * @param integer $dosage
     */
    public function setDosage($dosage)
    {
        $this->dosage = $dosage;
    }

    /**
     * @return integer
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param integer $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    /**
     * @return integer
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * @param integer $days
     */
    public function setDays($days)
    {
        $this->days = $days;
    }

    /**
     * @return integer
     */
    public function getCycle()
    {
        return $this->cycle;
    }

    /**
     * @param integer $cycle
     */
    public function setCycle($cycle)
    {
        $this->cycle = $cycle;
    }

    /**
     * @return integer
     */
    public function getCycleType()
    {
        return $this->cycleType;
    }

    /**
     * @param integer $cycleType
     */
    public function setCycleType($cycleType)
    {
        $this->cycleType = $cycleType;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return User
     */
    public function getUserId()
    {
        return $this->user->getId();
    }

    /**
     * @param User $user
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
     * @return ArrayCollection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @param ArrayCollection $sessions
     */
    public function setSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return boolean
     */
    public function hasSessions()
    {
        return count($this->getSessions());
    }

    /**
     * @return bool
     */
    public function hasTonesPlaylist()
    {
        return Therapy::checkIfTypeHasTonePlaylist($this->type);
    }

    /**
     * @return bool
     */
    public function hasMusicPlaylist()
    {
        return Therapy::checkIfTypeHasMusicPlaylist($this->type);
    }

    /**
     * @return boolean
     */
    public function getIncludesHq()
    {
        return $this->includesHq;
    }

    /**
     * @param boolean $includesHq
     */
    public function setIncludesHq($includesHq)
    {
        $this->includesHq = $includesHq;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getMissedSessions()
    {
        $array = [];
        foreach ($this->getSessions() as $session) {
            if ($session->isMissed()) {
                $array[] = $session->getId();
            }
        }

        return $array;
    }

    public function getMissedSessionsCount()
    {
        return count($this->getMissedSessions());
    }


    public function getCompletedSessions()
    {
        $array = [];
        foreach ($this->getSessions() as $session) {
            if ($session->isCompleted()) {
                $array[] = $session->getId();
            }
        }

        return $array;
    }

    public function getCompletedSessionsCount()
    {
        return count($this->getCompletedSessions());
    }

    public static function getColorSetByType($type)
    {
        switch ($type) {
            case Therapy::TYPE_ENVIRO_ONLY:
            case Therapy::TYPE_ENVIRO_TONE:
                return self::ENVIRO_COLORS;

            case Therapy::TYPE_TONE_MUSIC:
            case Therapy::TYPE_TONE_ONLY:
                return self::TONES_COLORS;

            default:
                return self::MUSIC_COLORS;
        }
    }

    public function getEmbeddedUserData()
    {
        $array = [
            'id' => $this->user->getId(),
            'name' => $this->user->getFullname(),
            'firstname' => $this->user->getFirstname(),
            'lastname' => $this->user->getLastname(),
        ];

        if ($this->user->getProfileImageName()) {
            $array['image'] = $this->user->getProfileImageName();
        }

        return $array;
    }


    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime();
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

        $this->updatedAt = $date;
    }
}