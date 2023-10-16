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

// see: https://github.com/schmittjoh/serializer/issues/543
//use JMS\Serializer\Annotation\Type;
//* @Type("DateTimeImmutable<'Y-m-d'>")

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TrackRepository")
 * @ORM\Table(name="track")
 * @ORM\HasLifecycleCallbacks
 *
 * @Vich\Uploadable
 *
 * @UniqueEntity("title")
 * @UniqueEntity("sourceAudioId")
 */
class Track
{
    const STATUS_UNCOMPILED     = 0;
    const STATUS_COMPILING      = 1;
    const STATUS_COMPILED       = 2;
    const STATUS_OUTDATED       = 3;
    const STATUS_COMPILE_ERROR  = 4;

    const TYPE_TONE             = 1;
    const TYPE_ENVIRO           = 2;
    const TYPE_MUSIC            = 3;

    const TONE_TYPE_BINAURAL    = "bBeat";
    const TONE_TYPE_ISOCHRONIC  = "isochronic";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $sourceAudioId;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $toneType;

    /**
     * @ORM\Column(type="string", unique=true)
     *
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $album;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $description;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="track_file", fileNameProperty="fileName", size="fileSize")
     *
     * @Serializer\Exclude
     *
     * @var File
     */
    private $file;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     *
     * @var string
     */
    private $fileName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     *
     * @var integer
     */
    private $fileSize;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Serializer\Exclude
     *
     * @var Object
     */
    private $uploadedFile;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="track_file", fileNameProperty="fileNameHq", size="fileSizeHq")
     *
     * @Serializer\Exclude
     *
     * @var File
     */
    private $fileHq;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     *
     * @var string
     */
    private $fileNameHq;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     *
     * @var integer
     */
    private $fileSizeHq;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Serializer\Exclude
     *
     * @var Object
     */
    private $uploadedFileHq;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $originalFilename;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $duration;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $fLeft;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $fRight;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $ampValue;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $ampMod;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $includesHq = false;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $fLeftHq;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $fRightHq;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $ampValueHq;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $ampModHq;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Artist", inversedBy="tracks")
     * @ORM\JoinTable(name="tracks_artists")
     *
     * @Serializer\Exclude
     */
    private $artists;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Artist", inversedBy="composedTracks")
     * @ORM\JoinTable(name="tracks_composers")
     *
     * @Serializer\Exclude
     */
    private $composers;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Publisher", inversedBy="publishedTracks")
     * @ORM\JoinTable(name="tracks_publishers")
     *
     * @Serializer\Exclude
     */
    private $publishers;

    /**
     * Many Tracks have Many Symptoms.
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Genre", inversedBy="tracks")
     * @ORM\JoinTable(name="tracks_genre",
     *      joinColumns={@ORM\JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="genre_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     */
    private $genre;

    /**
     * Many Tracks have Many Symptoms.
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Symptom", inversedBy="tracks")
     * @ORM\JoinTable(name="tracks_symptoms",
     *      joinColumns={@ORM\JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="symptom_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     */
    private $symptoms;


    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $compileStatus = self::STATUS_UNCOMPILED;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $moods;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $dosage;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    private $therapyLength;

    /**
     * Many Users have Many Groups.
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\TherapyRecommendation", orphanRemoval=true)
     * @ORM\JoinTable(name="tracks_recommendations",
     *      joinColumns={@ORM\JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="recommendation_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     */
    private $recommendations;


    /**
     * @ORM\Column(type="integer")
     * @Serializer\Exclude
     *
     * @var integer
     */
    private $playCount = 0;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $playTime = 0;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $relatedTherapiesCount = 0;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $relatedSessionsCount = 0;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\UploadedAudio", inversedBy="track", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="uploaded_audio_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $uploadedAudio;

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("file")
     */
    public function getPublicFilePath()
    {
        return $this->fileName ? '/library/tracks/' . $this->fileName : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("fileHq")
     */
    public function getPublicFilePathHq()
    {
        return $this->fileNameHq ? '/library/tracks/' . $this->fileNameHq : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isTone")
     */
    public function isTone()
    {
        return $this->type === self::TYPE_TONE;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isEnviro")
     */
    public function virtualIsEnviro()
    {
        return $this->type === self::TYPE_ENVIRO;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isMusic")
     */
    public function virtualIsMusic()
    {
        return $this->type === self::TYPE_MUSIC;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("artists")
     */
    public function virtualArtists()
    {
        $array = [];
        foreach ($this->getArtists() as $artist) {
            $array[] = $artist->getId();
        }
        return $array;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("artistsString")
     */
    public function getArtistsString()
    {
        $array = [];
        foreach ($this->getArtists() as $artist) {
            $array[] = $artist->getName();
        }

        if (!count($array)) {
            return 'Unknown Artist';
        }

        return implode(', ', $array);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("publishers")
     */
    public function getPublisherIds()
    {
        $array = [];
        foreach ($this->getPublishers() as $publisher) {
            $array[] = $publisher->getId();
        }
        return $array;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("publishersString")
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    public function getPublishersString()
    {
        $array = [];
        foreach ($this->getPublishers() as $publisher) {
            $array[] = $publisher->getName();
        }
        return implode(', ', $array);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("composers")
     */
    public function virtualComposerIds()
    {
        $array = [];
        foreach ($this->getComposers() as $composer) {
            $array[] = $composer->getId();
        }
        return $array;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("composersString")
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    public function getComposersString()
    {
        $array = [];
        foreach ($this->getComposers() as $composer) {
            $array[] = $composer->getName();
        }
        return implode(', ', $array);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("genre")
     */
    public function virtualGenre()
    {
        return $this->genre ? $this->getGenre()->getId() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("genreTitle")
     */
    public function getGenreTitle()
    {
        return $this->genre ? $this->getGenre()->getTitle() : '';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("symptoms")
     */
    public function virtualSymptoms()
    {
        $symptoms = array();
        foreach ($this->symptoms as $symptom){
            $symptoms[] = $symptom->getId();
        }
        return $symptoms;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("symptomsString")
     */
    public function getSymptomsString()
    {
        $symptoms = array();
        foreach ($this->symptoms as $symptom){
            $symptoms[] = $symptom->getTitle();
        }
        return implode(', ', $symptoms);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("recommendations")
     */
    public function getRecommendationIds()
    {
        $arrIds = [];
        foreach($this->recommendations as $item) {
            $arrIds[] = $item->getId();
        }
        return $arrIds;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("recommendationsString")
     *
     * @Serializer\Groups({"Default", "Detail"})
     */
    public function getRecommendationsString()
    {
        $array = [];
        foreach($this->recommendations as $item) {
            $array[] = $item->getTitle();
        }
        return implode(', ', $array);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("playCount")
     */
    public function virtualPlayCount()
    {
        if ($this->isTone()) {
            return 'n/a';
        }

        return $this->playCount;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("searchString")
     *
     * @Serializer\Groups({"Default", "List"})
     */
    public function getSearchString()
    {
        $string = $this->id
            . ' ' . $this->sourceAudioId
            . ' ' . $this->title
            . ' ' . $this->album
            . ' ' . ($this->isTone() && $this->includesHq ? 'includesHQ' : 'audible')
            . ' ' . $this->getArtistsString()
            . ' ' . $this->getSymptomsString()
            . ' ' . $this->getGenreTitle()
            . ' ' . $this->getPublishersString()
            . ' ' . $this->getComposersString()
            . ' ' . $this->moods
            . ' ' . $this->description
            . ' ' . $this->dosage
            . ' ' . $this->therapyLength
            . ' ' . $this->getRecommendationsString()
        ;

        if ($this->isTone()) {
            $string = $string
                . ' ' . $this->fLeft . 'Hz'
                . ' ' . $this->fRight . 'Hz'
                . ' ' . $this->ampMod
                . ' ' . $this->ampValue
            ;

            if ($this->includesHq) {
                $string = $string
                    . ' ' . $this->fLeftHq . 'Hz'
                    . ' ' . $this->fRightHq . 'Hz'
                    . ' ' . $this->ampModHq
                    . ' ' . $this->ampValueHq
                ;
            }
        }

        return strtolower($string);
    }


    public function __construct() {
        $this->artists = new ArrayCollection();
        $this->composers = new ArrayCollection();
        $this->publishers = new ArrayCollection();
        $this->symptoms = new ArrayCollection();
        $this->recommendations = new ArrayCollection();
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
     * @return string
     */
    public function getToneType()
    {
        return $this->toneType;
    }

    /**
     * @param string $toneType
     */
    public function setToneType($toneType)
    {
        $this->toneType = $toneType;
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
        $this->title = trim($title);
    }

    /**
     * @return mixed
     */
    public function getAlbum()
    {
        return $this->album;
    }

    /**
     * @param mixed $album
     */
    public function setAlbum($album)
    {
        $this->album = trim($album);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = trim($description);
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return Track
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;

        if ($file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();//new \DateTimeImmutable();

            $this->fileSize = $file->getSize();

            // get duration
            $ffprobe = FFProbe::create();
//            $ffprobe = FFProbe::create([
//                'ffmpeg.binaries' => '/usr/bin/ffmpeg',
//                'ffprobe.binaries' => '/usr/bin/ffprobe',
//            ]);
            $duration = $ffprobe
                ->format($file->getPathname()) // extracts file informations
                ->get('duration');             // returns the duration property
            $this->duration = $duration;
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $fileName
     *
     * @return Track
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
     * @return Track
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
     * @return string
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    /**
     * @param string $uploadedFile (see AdminController::uploadAction)
     * @return self
     */
    public function setUploadedFile($uploadedFile)
    {
        $this->uploadedFile = $uploadedFile;

        // serialize string
        $objFile = json_decode($uploadedFile);
        $rootPath = realpath(dirname(__FILE__)) . '/../../..';
        $tmpPath = $rootPath . '/var/tmp';

        if ($objFile) {
            $filePath = $tmpPath . '/' . $objFile->filename;

            // create referenced UploadedAudio entity
            $uploadedFile = new UploadedFile($filePath, $objFile->filename, null, null, null, true);
            $uploadedFile->move($rootPath . '/uploads/database/tracks/unconverted', $uploadedFile->getFilename());

            $uploadedAudio = new UploadedAudio();
            $uploadedAudio->setOriginalFileName($objFile->original_filename);
            $uploadedAudio->setFileName($objFile->filename);
            $uploadedAudio->setBaseName($objFile->basename);
            $uploadedAudio->setFileExtension($objFile->extension);

            // UploadedAudio will be persisted when track gets saved (cascade)
            $this->setUploadedAudio($uploadedAudio);

            // Set compile status since we have to wait for response from API server
            $this->setCompileStatus(self::STATUS_COMPILING);

            // Unset old track information
            $this->setFileName('');
            $this->setFile(null);
            $this->setFileHq(null);
            $this->setFileSize(null);
            $this->setFileSizeHq(null);
            //$this->setDuration(null); // don't unset duration, otherwise statistics will fail for tracks with no file

            // store original filename
            $this->setOriginalFilename($objFile->original_filename);
        }

        return $this;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $fileHq
     *
     * @return Track
     */
    public function setFileHq(File $fileHq = null)
    {
        $this->fileHq = $fileHq;

        if ($fileHq) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();//new \DateTimeImmutable();

            $this->fileSizeHq = $fileHq->getSize();

            // TODO: save original filename (this throws error)
            // Attempted to call an undefined method named "getClientOriginalName" of clas
            //$this->setOriginalFilename($file->getClientOriginalName());
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getFileHq()
    {
        return $this->fileHq;
    }

    /**
     * @param string $fileName
     *
     * @return Track
     */
    public function setFileNameHq($fileName)
    {
        $this->fileNameHq = $fileName;

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
     * @param integer $fileSize
     *
     * @return Track
     */
    public function setFileSizeHq($fileSize)
    {
        $this->fileSizeHq = $fileSize;

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
     * @return string
     */
    public function getUploadedFileHq()
    {
        return $this->uploadedFileHq;
    }

    /**
     * @param string $uploadedFileHq
     */
    public function setUploadedFileHq($uploadedFileHq)
    {
        $this->uploadedFileHq = $uploadedFileHq;

        // serialize string
        $objFile = json_decode($uploadedFileHq);
        $tmpPath = realpath(dirname(__FILE__)) . '/../../../var/tmp/';

        if ($objFile) {
            $filePath = $tmpPath . $objFile->filename;

            // move uploaded file from tmp folder to correct destination
            $uploadedFileHq = new UploadedFile($filePath, $objFile->filename, null, null, null, true);
            $this->setFileHq($uploadedFileHq);
        }

        return $this;
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
     * @return float|null
     */
    public function getFLeft()
    {
        return $this->fLeft;
    }

    /**
     * @param float $fLeft
     */
    public function setFLeft($fLeft)
    {
        $this->fLeft = $fLeft;
    }

    /**
     * @return float|null
     */
    public function getFRight()
    {
        return $this->fRight;
    }

    /**
     * @param float $fRight
     */
    public function setFRight($fRight)
    {
        $this->fRight = $fRight;
    }

    /**
     * @return float|null
     */
    public function getAmpValue()
    {
        return $this->ampValue;
    }

    /**
     * @param float $ampValue
     */
    public function setAmpValue($ampValue)
    {
        $this->ampValue = $ampValue;
    }

    /**
     * @return string|null
     */
    public function getAmpMod()
    {
        return $this->toneType;
        //return $this->ampMod;
    }

    /**
     * @param string $ampMod
     */
    public function setAmpMod($ampMod)
    {
        $this->ampMod = $ampMod;
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
     * @return ArrayCollection
     */
    public function getSymptoms()
    {
        return $this->symptoms;
    }

    /**
     * @param ArrayCollection $symptoms
     */
    public function setSymptoms($symptoms)
    {
        $this->symptoms = $symptoms;
    }

    /**
     * @param Symptom $symptom
     */
    public function addSymptom($symptom)
    {
        if (!$this->symptoms->contains($symptom)) {
            $this->symptoms->add($symptom);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getArtists()
    {
        return $this->artists;
    }

    /**
     * @param ArrayCollection $artists
     */
    public function setArtists($artists)
    {
        $this->artists = $artists;
    }

    /**
     * @param Artist $artist
     */
    public function addArtist($artist)
    {
        if (!$this->artists->contains($artist)) {
            $this->artists->add($artist);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getComposers()
    {
        return $this->composers;
    }

    /**
     * @param ArrayCollection $composers
     */
    public function setComposers($composers)
    {
        $this->composers = $composers;
    }

    /**
     * @param Artist $composer
     */
    public function addComposer($composer)
    {
        if (!$this->composers->contains($composer)) {
            $this->composers->add($composer);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getPublishers()
    {
        return $this->publishers;
    }

    /**
     * @param ArrayCollection $publishers
     */
    public function setPublishers($publishers)
    {
        $this->publishers = $publishers;
    }

    /**
     * @param Publisher $publisher
     */
    public function addPublisher($publisher)
    {
        if (!$this->publishers->contains($publisher)) {
            $this->publishers->add($publisher);
        }
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
     * @return mixed
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * @param mixed $genre
     */
    public function setGenre($genre)
    {
        $this->genre = $genre;
    }

    /**
     * @return string
     */
    public function getOriginalFilename()
    {
        return $this->originalFilename;
    }

    /**
     * @param string $originalFilename
     */
    public function setOriginalFilename($originalFilename)
    {
        $this->originalFilename = $originalFilename;
    }

    /**
     * @return mixed
     */
    public function getSourceAudioId()
    {
        return $this->sourceAudioId;
    }

    /**
     * @param mixed $sourceAudioId
     */
    public function setSourceAudioId($sourceAudioId)
    {
        $this->sourceAudioId = trim($sourceAudioId);
    }

    /**
     * @return mixed
     */
    public function getFLeftHq()
    {
        return $this->fLeftHq;
    }

    /**
     * @param mixed $fLeftHq
     */
    public function setFLeftHq($fLeftHq)
    {
        $this->fLeftHq = $fLeftHq;
    }

    /**
     * @return mixed
     */
    public function getFRightHq()
    {
        return $this->fRightHq;
    }

    /**
     * @param mixed $fRightHq
     */
    public function setFRightHq($fRightHq)
    {
        $this->fRightHq = $fRightHq;
    }

    /**
     * @return mixed
     */
    public function getAmpValueHq()
    {
        return $this->ampValueHq;
    }

    /**
     * @param mixed $ampValueHq
     */
    public function setAmpValueHq($ampValueHq)
    {
        $this->ampValueHq = $ampValueHq;
    }

    /**
     * @return mixed
     */
    public function getAmpModHq()
    {
        return $this->toneType;
        //return $this->ampMod; // temporarily use ampMod since it is used in fixtures but is not writable in form
        //return $this->ampModHq;
    }

    /**
     * @param mixed $ampModHq
     */
    public function setAmpModHq($ampModHq)
    {
        $this->ampModHq = $ampModHq;
    }

    /**
     * @return mixed
     */
    public function getMoods()
    {
        return $this->moods;
    }

    /**
     * @param mixed $moods
     */
    public function setMoods($moods)
    {
        $this->moods = trim($moods);
    }

    /**
     * @return mixed
     */
    public function getDosage()
    {
        return $this->dosage;
    }

    /**
     * @param mixed $dosage
     */
    public function setDosage($dosage)
    {
        $this->dosage = trim($dosage);
    }

    /**
     * @return mixed
     */
    public function getTherapyLength()
    {
        return $this->therapyLength;
    }

    /**
     * @param mixed $therapyLength
     */
    public function setTherapyLength($therapyLength)
    {
        $this->therapyLength = trim($therapyLength);
    }

    /**
     * @return ArrayCollection
     */
    public function getRecommendations()
    {
        return $this->recommendations;
    }

    /**
     * @param ArrayCollection $recommendations
     */
    public function setRecommendations($recommendations)
    {
        $this->recommendations = $recommendations;
    }

    /**
     * @param TherapyRecommendation $recommendation
     */
    public function addRecommendation($recommendation)
    {
        if (!$this->recommendations->contains($recommendation)) {
            $this->recommendations->add($recommendation);
        }
    }

    /**
     * @return int
     */
    public function getPlayCount()
    {
        return $this->playCount;
    }

    /**
     * @param int $playCount
     */
    public function setPlayCount($playCount)
    {
        $this->playCount = $playCount;
    }

    /**
     * Increment playCount
     */
    public function incrementPlayCount()
    {
        $this->playCount ++;
    }

    /**
     * @return int
     */
    public function getPlayTime()
    {
        return $this->playTime;
    }

    /**
     * @param int $playTime
     */
    public function setPlayTime($playTime)
    {
        $this->playTime = $playTime;
    }

    /**
     * Increment playTime
     */
    public function incrementPlayTime($inc = 0)
    {
        $this->playTime += $inc;
    }

    /**
     * @return int
     */
    public function getRelatedTherapiesCount()
    {
        return $this->relatedTherapiesCount;
    }

    /**
     * @param int $relatedTherapiesCount
     */
    public function setRelatedTherapiesCount($relatedTherapiesCount)
    {
        $this->relatedTherapiesCount = $relatedTherapiesCount;
    }

    /**
     * Increment relatedTherapiesCount
     */
    public function incrementRelatedTherapiesCount()
    {
        $this->relatedTherapiesCount ++;
    }

    /**
     * @return int
     */
    public function getRelatedSessionsCount()
    {
        return $this->relatedSessionsCount;
    }

    /**
     * @param int $relatedSessionsCount
     */
    public function setRelatedSessionsCount($relatedSessionsCount)
    {
        $this->relatedSessionsCount = $relatedSessionsCount;
    }

    /**
     * Increment relatedTherapiesCount
     */
    public function incrementRelatedSessionsCount()
    {
        $this->relatedSessionsCount ++;
    }

    /**
     * @return mixed
     */
    public function getUploadedAudio()
    {
        return $this->uploadedAudio;
    }

    /**
     * @param mixed $uploadedAudio
     */
    public function setUploadedAudio($uploadedAudio)
    {
        $this->uploadedAudio = $uploadedAudio;
    }

    public static function getTypeString($type)
    {
        switch($type) {
            case self::TYPE_TONE:
                return "tone";
            case self::TYPE_ENVIRO:
                return "environment";
            default:
                return "music";
        }
    }

    public static function getToneTypeString($toneType)
    {
        switch($toneType) {
            case self::TONE_TYPE_BINAURAL:
                return "binaural beat";
            case self::TONE_TYPE_ISOCHRONIC:
                return "isochronic";
            default:
                return "music";
        }
    }


    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->hasChangedField("fLeft")
            || $eventArgs->hasChangedField("fRight")
            || $eventArgs->hasChangedField("ampValue")
            || $eventArgs->hasChangedField("ampMod")
            || $eventArgs->hasChangedField("includesHq")
            || $eventArgs->hasChangedField("fLeftHq")
            || $eventArgs->hasChangedField("fRightHq")
            || $eventArgs->hasChangedField("ampValueHq")
            || $eventArgs->hasChangedField("ampModHq")
        ) {
            $this->compileStatus = self::STATUS_UNCOMPILED;
            $this->updatedAt = new \DateTime();
        }

        // unset data if no filename set (except duration which is necessary for statistics)
        if ($eventArgs->hasChangedField("fileName") && $this->getFileName() == '') {
            $this->originalFilename = null;
            $this->fileSize = null;
            $this->fileSizeHq = null;
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

        $this->updatedAt = $date;
    }
}
