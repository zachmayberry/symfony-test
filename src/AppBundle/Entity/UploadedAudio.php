<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use FFMpeg\FFProbe;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UploadedAudioRepository")
 * @ORM\Table(name="uploaded_audio")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity("originalFileName")
 * @UniqueEntity("fileName")
 * @UniqueEntity("convertedFile")
 */
class UploadedAudio
{
    const STATUS_UNCOMPILED     = 0;
    const STATUS_COMPILING      = 1;
    const STATUS_COMPILED       = 2;
    const STATUS_COMPILE_ERROR  = 4;

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
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $originalFileName;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $fileName;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $baseName;

    /**
     * @ORM\Column(type="string", length=6)
     *
     * @var string
     */
    private $fileExtension;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $convertedFile;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $compileStatus = self::STATUS_UNCOMPILED;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Track", mappedBy="uploadedAudio")
     * @Serializer\Exclude()
     */
    private $track;

    /**
     * Check if the file has been compiled
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isCompiled")
     */
    public function virtualIsCompiled()
    {
        return $this->compileStatus === self::STATUS_COMPILED;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("track")
     */
    public function virtualTrack()
    {
        return $this->track ? $this->getTrack()->getId() : '';
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
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }

    /**
     * @param string $originalFileName
     */
    public function setOriginalFileName($originalFileName)
    {
        $this->originalFileName = $originalFileName;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getBaseName()
    {
        return $this->baseName;
    }

    /**
     * @param string $baseName
     */
    public function setBaseName($baseName)
    {
        $this->baseName = $baseName;
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    /**
     * @param string $fileExtension
     */
    public function setFileExtension($fileExtension)
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * @return string
     */
    public function getConvertedFile()
    {
        return $this->convertedFile;
    }

    /**
     * @param string $convertedFile
     */
    public function setConvertedFile($convertedFile)
    {
        $this->convertedFile = $convertedFile;
    }

    /**
     * @return int
     */
    public function getCompileStatus()
    {
        return $this->compileStatus;
    }

    /**
     * @param int $compileStatus
     */
    public function setCompileStatus($compileStatus)
    {
        $this->compileStatus = $compileStatus;
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
