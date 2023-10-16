<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TempTherapyRepository")
 * @ORM\Table(name="temp_therapy")
 * @ORM\HasLifecycleCallbacks
 */
class TempTherapy
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
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $includesHq = false;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Playlist", cascade={"persist"})
     * @ORM\JoinColumn(name="music_playlist_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @Serializer\Exclude
     */
    private $musicPlaylist;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Playlist", cascade={"persist"})
     * @ORM\JoinColumn(name="binaural_playlist_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @Serializer\Exclude
     */
    private $binauralPlaylist;

    /**
     * @ORM\Column(type="integer")
     */
    private $dosage;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="therapies")
     *
     * @Serializer\Exclude
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $compileStatus = Therapy::STATUS_UNCOMPILED;

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
     * @ORM\Column(type="float", nullable=true)
     */
    private $duration;

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
        return $this->compileStatus === Therapy::STATUS_COMPILED;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isUncompiledHq")
     */
    public function isUncompiledHq()
    {
        return $this->compileStatus === Therapy::STATUS_UNCOMPILED_HQ;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("file")
     */
    public function getPublicFilePath()
    {
        return $this->fileName ? '/library/previews/' . $this->fileName : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("fileHq")
     */
    public function getPublicFilePathHq()
    {
        return $this->fileNameHq ? '/library/previews/' . $this->fileNameHq : null;
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
     * @return string
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
     * @return mixed
     */
    public function getBinauralPlaylist()
    {
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
     * @param string $fileName
     *
     * @return TempTherapy
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
     * @return TempTherapy
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
     * @return TempTherapy
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
     * @return TempTherapy
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
     * @return bool
     */
    public function hasTonesPlaylist()
    {
        return Therapy::checkIfTypeHasTonePlaylist($this->type);
    }


    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->hasChangedField("dosage")
            || $eventArgs->hasChangedField("binauralPlaylist")
            || $eventArgs->hasChangedField("musicPlaylist")
        ) {
            $this->compileStatus = Therapy::STATUS_UNCOMPILED;
        }
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
