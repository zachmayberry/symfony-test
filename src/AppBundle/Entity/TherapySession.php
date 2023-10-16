<?php


namespace AppBundle\Entity;


use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TherapySessionRepository")
 * @ORM\Table(name="therapy_session")
 * @ORM\HasLifecycleCallbacks
 */

//*
//* @Hateoas\Relation(
// *     "userTherapy",
// *     embedded = "expr(object.getUserTherapy())",
// *     exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getUserTherapy() === null)")
//    * )

class TherapySession
{
    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_MISSED = 2;

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
     * Many Sessions have One UserTherapy.
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\UserTherapy", inversedBy="sessions", fetch="EXTRA_LAZY", cascade={"remove", "persist"})
     * @ORM\JoinColumn(name="user_therapy_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @Serializer\Exclude
     */
    private $userTherapy;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="userTherapies", cascade={"remove"})
     *
     * @Serializer\Exclude
     */
    private $user;

    /**
     * @ORM\Column(type="date")
     */
    private $startDate;

    /**
     * @ORM\Column(type="time")
     */
    private $startTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $nOfTotal;

    /**
     * Status [0 = pending, 1 = compleded, 2 = missed]
     * @ORM\Column(type="integer")
     *
     * @Serializer\Exclude
     */
    private $status = self::STATUS_PENDING;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Playlist", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="music_playlist_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude
     */
    private $musicPlaylist;

    /**
     * @ORM\Column(type="integer")
     */
    private $compileStatus = Therapy::STATUS_UNCOMPILED;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $includesHq = false;

    /**
     * @ORM\Column(type="integer")
     */
    private $compileStatusHq = Therapy::STATUS_UNCOMPILED;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Exclude
     *
     * @var string
     */
    private $fileName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Serializer\Exclude
     *
     * @var integer
     */
    private $fileSize;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Exclude
     *
     * @var string
     */
    private $fileNameHq;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Serializer\Exclude
     *
     * @var integer
     */
    private $fileSizeHq;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\TherapySessionFeedback", mappedBy="session")
     * @ORM\JoinColumn(name="feedback_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $feedback;

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("endTime")
     */
    public function virtualEndTime()
    {
        $endTime = clone $this->startTime;
        $endTime->add(new \DateInterval('PT' . $this->userTherapy->getDosage() . 'M'));

        return $endTime;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("userTherapy")
     */
    public function virtualUserTherapy()
    {
        return $this->userTherapy->getId();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("total")
     */
    public function virtualTotal()
    {
        return count($this->userTherapy->getSessions());
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isPending")
     */
    public function virtualIsPending()
    {
        return $this->isPending();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("dosage")
     */
    public function virtualDosage()
    {
        return $this->userTherapy->getDosage();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("rate")
     * @Serializer\Groups({"Detail"})
     */
    public function virtualRate()
    {
        return $this->userTherapy->getRate();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("days")
     * @Serializer\Groups({"Detail"})
     */
    public function virtualDays()
    {
        return $this->userTherapy->getDays();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("cycle")
     * @Serializer\Groups({"Detail"})
     */
    public function virtualCycle()
    {
        return $this->userTherapy->getCycle();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("cycleType")
     * @Serializer\Groups({"Detail"})
     */
    public function virtualCycleType()
    {
        return $this->userTherapy->getCycleType();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("user")
     */
    public function virtualUser()
    {
        return $this->userTherapy->getUser()->getId();
    }

    // OBSOLETE SINCE WE CALCULATE THIS ON CLIENT SIDE WITH CLIENTS TIMEZONE
//    /**
//     * @Serializer\VirtualProperty
//     * @Serializer\SerializedName("isToday")
//     */
//    public function virtualIsToday()
//    {
//        $today = new \DateTime();
//        return $today->format('Y-m-d') === $this->startDate->format('Y-m-d');
//    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isCompleted")
     */
    public function virtualIsCompleted()
    {
        return $this->isCompleted();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isMissed")
     */
    public function virtualIsMissed()
    {
        return $this->isMissed();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("therapy")
     * @Serializer\Groups({"Detail"})
     */
    public function virtualTherapyId()
    {
        return $this->userTherapy->virtualTherapy();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("therapyType")
     */
    public function virtualTherapyType()
    {
        return $this->userTherapy->getType();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("therapyTitle")
     */
    public function virtualTherapyTitle()
    {
        return $this->userTherapy->getTitle();
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
     * @Serializer\SerializedName("isCompiled")
     */
    public function virtualIsCompiled()
    {
        return $this->isCompiled() && ($this->isCompiledHq() || !$this->includesHq);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isUncompiled")
     */
    public function virtualIsUncompiled()
    {
        return $this->isUncompiled() || $this->isUncompiledHq();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("hasCompileError")
     */
    public function virtualHasCompileError()
    {
        return $this->hasCompileError() || $this->hasCompileErrorHq();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("file")
     * @Serializer\Groups({"Detail"})
     */
    public function getPublicFilePath()
    {
        return $this->fileName ? '/library/therapies/' . $this->fileName : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("fileHq")
     * @Serializer\Groups({"Detail"})
     */
    public function getPublicFilePathHq()
    {
        return $this->fileNameHq ? '/library/therapies/' . $this->fileNameHq : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("musicPlaylist")
     * @Serializer\Groups({"Detail"})
     */
    public function getMusicPlaylistOrFallback()
    {
        return $this->musicPlaylist;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("binauralPlaylist")
     * @Serializer\Groups({"Detail"})
     */
    public function getBinauralPlaylist()
    {
        return $this->userTherapy->getBinauralPlaylist();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("color")
     */
    public function getUserTherapyColor()
    {
        return $this->userTherapy
            ? $this->userTherapy->getColor()
            : '#000000';
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
     * @return UserTherapy
     */
    public function getUserTherapy()
    {
        return $this->userTherapy;
    }

    /**
     * @param UserTherapy $userTherapy
     */
    public function setUserTherapy($userTherapy)
    {
        $this->userTherapy = $userTherapy;
    }

    /**
     * @return TherapySession
     */
    public function getBaseTherapy()
    {
        return $this->userTherapy->getTherapy();
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param \DateTime $startTime
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * @return mixed
     */
    public function getMusicPlaylist()
    {
        return $this->musicPlaylist;
    }

    /**
     * @param mixed $musicPlaylist
     */
    public function setMusicPlaylist($musicPlaylist)
    {
        $this->musicPlaylist = $musicPlaylist;
    }

    /**
     * @return integer
     */
    public function getNOfTotal()
    {
        return $this->nOfTotal;
    }

    /**
     * @param integer $nOfTotal
     */
    public function setNOfTotal($nOfTotal)
    {
        $this->nOfTotal = $nOfTotal;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return boolean
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * @return boolean
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * @return boolean
     */
    public function isMissed()
    {
        return $this->status === self::STATUS_MISSED;
    }

    /**
     * @return integer
     */
    public function getCompileStatus()
    {
        return $this->compileStatus;
    }

    /**
     * @param integer $compileStatus
     */
    public function setCompileStatus($compileStatus)
    {
        $this->compileStatus = $compileStatus;
    }

    /**
     * @return bool
     */
    public function isCompiled()
    {
        return $this->compileStatus === Therapy::STATUS_COMPILED;
    }

    /**
     * @return bool
     */
    public function isUncompiled()
    {
        return $this->compileStatus === Therapy::STATUS_UNCOMPILED;
    }

    /**
     * @return bool
     */
    public function isCompiling()
    {
        return $this->compileStatus === Therapy::STATUS_COMPILING;
    }

    /**
     * @return bool
     */
    public function hasCompileError()
    {
        return $this->compileStatus === Therapy::STATUS_COMPILE_ERROR;
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
     * @return integer
     */
    public function getCompileStatusHq()
    {
        return $this->compileStatusHq;
    }

    /**
     * @param integer $compileStatusHq
     */
    public function setCompileStatusHq($compileStatusHq)
    {
        $this->compileStatusHq = $compileStatusHq;
    }

    /**
     * @return bool
     */
    public function isCompiledHq()
    {
        return $this->compileStatusHq === Therapy::STATUS_COMPILED;
    }

    /**
     * @return bool
     */
    public function isUncompiledHq()
    {
        return $this->compileStatusHq === Therapy::STATUS_UNCOMPILED;
    }

    /**
     * @return bool
     */
    public function isCompilingHq()
    {
        return $this->compileStatusHq === Therapy::STATUS_COMPILING;
    }

    /**
     * @return bool
     */
    public function hasCompileErrorHq()
    {
        return $this->compileStatusHq === Therapy::STATUS_COMPILE_ERROR;
    }

    /**
     * @param string $fileName
     *
     * @return TherapySession
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param integer $fileSize
     *
     * @return TherapySession
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param string $fileNameHq
     *
     * @return TherapySession
     */
    public function setFileNameHq($fileNameHq)
    {
        $this->fileNameHq = $fileNameHq;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFileNameHq()
    {
        return $this->fileNameHq;
    }

    /**
     * @param integer $fileSizeHq
     *
     * @return TherapySession
     */
    public function setFileSizeHq($fileSizeHq)
    {
        $this->fileSizeHq = $fileSizeHq;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getFileSizeHq()
    {
        return $this->fileSizeHq;
    }

    /**
     * @return bool
     */
    public function hasTonesPlaylist()
    {
        return Therapy::checkIfTypeHasTonePlaylist($this->userTherapy->getType());
    }

    /**
     * @return bool
     */
    public function hasOwnPlaylist()
    {
        return $this->musicPlaylist !== null && count($this->musicPlaylist->getPlaylistTracks());
    }

    /**
     * @return integer
     */
    public function getDosage()
    {
        return $this->userTherapy->getDosage();
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
     * Check if this is the last session of this therapy
     */
    public function isLastSessionOfTherapy()
    {
        return $this->virtualTotal() === $this->nOfTotal;
    }


    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(PreUpdateEventArgs $eventArgs)
    {
        //if ($eventArgs->hasChangedField("binauralPlaylist") || $eventArgs->hasChangedField("musicPlaylist")) {
        //    $this->compileStatus = Therapy::STATUS_UNCOMPILED;
        //    $this->compileStatusHq = Therapy::STATUS_UNCOMPILED;
        //}

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