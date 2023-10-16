<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TherapyRepository")
 * @ORM\Table(name="therapy")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity("title")
 *
 * @Hateoas\Relation("binauralPlaylist",
 *     embedded = @Hateoas\Embedded(
 *      "expr(object.getBinauralPlaylist())",
 *      exclusion = @Hateoas\Exclusion(groups = {"Detail"}, excludeIf = "expr(object.getBinauralPlaylist() === null)")
 *     )
 * )
 *
 * @Hateoas\Relation("musicPlaylist",
 *     embedded = @Hateoas\Embedded(
 *      "expr(object.getMusicPlaylist())",
 *      exclusion = @Hateoas\Exclusion(groups = {"Detail"}, excludeIf = "expr(object.getMusicPlaylist() === null)")
 *     )
 * )
 */
class Therapy
{
    const STATUS_UNCOMPILED = 0;
    const STATUS_COMPILING = 1;
    const STATUS_COMPILED = 2;
    const STATUS_COMPILE_ERROR = 4;
    const STATUS_UNCOMPILED_HQ = 5;

    const TYPE_TONE_ONLY = 1;
    const TYPE_TONE_MUSIC = 2;
    const TYPE_ENVIRO_ONLY = 3;
    const TYPE_ENVIRO_TONE = 4;
    const TYPE_MUSIC_ONLY = 5;

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
     * Therapy type [see constants above)
     *
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $includesHq = false;

    /**
     * Many Users have Many Groups.
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\TherapyRecommendation", orphanRemoval=true)
     * @ORM\JoinTable(name="therapies_recommendations",
     *      joinColumns={@ORM\JoinColumn(name="therapy_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="recommendation_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     */
    private $recommendations;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Playlist", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="music_playlist_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude
     */
    private $musicPlaylist;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Playlist", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="binaural_playlist_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude
     */
    private $binauralPlaylist;

    /**
     * @ORM\Column(type="integer")
     */
    private $dosage;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"Detail"})
     */
    private $rate;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"Detail"})
     */
    private $days;

    /**
     * @ORM\Column(type="integer")
     */
    private $cycle;

    /**
     * @ORM\Column(type="string", name="cycle_type")
     */
    private $cycleType;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="therapies")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude
     */
    private $user;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Symptom")
     * @ORM\JoinTable(name="therapies_symptoms",
     *      joinColumns={@ORM\JoinColumn(name="therapy_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="symptom_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     */
    private $symptoms;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\News", inversedBy="relatedTherapies", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="therapies_related_news",
     *      joinColumns={@ORM\JoinColumn(name="therapy_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="news_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     */
    private $relatedNews;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Reference", inversedBy="relatedTherapies", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="therapies_related_references",
     *      joinColumns={@ORM\JoinColumn(name="therapy_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="reference_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     */
    private $relatedReferences;

    /**
     * @ORM\Column(type="integer")
     */
    private $compileStatus = self::STATUS_UNCOMPILED;

    /**
     * @ORM\Column(type="integer")
     */
    private $compileStatusHq = self::STATUS_UNCOMPILED;

    /**
     * @ORM\Column(type="integer")
     */
    private $previewCompileStatus = self::STATUS_UNCOMPILED;

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
     * @Serializer\Groups({"Detail"})
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
     * @Serializer\Groups({"Detail"})
     *
     * @var integer
     */
    private $fileSizeHq;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $public = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $published = false;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Therapy")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude()
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\TherapySessionFeedback", mappedBy="therapy")
     * @ORM\JoinTable(name="therapies_feedback",
     *      joinColumns={@ORM\JoinColumn(name="therapy_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="feedback_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     */
    private $feedback;

    /**
     * @ORM\Column(type="integer")
     */
    private $completedTherapiesCounter = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $missedTherapiesCounter = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $completedSessionsCounter = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $missedSessionsCounter = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $totalPlayedTime = 0;

//    /**
//     * @Serializer\VirtualProperty
//     * @Serializer\SerializedName("binauralPlaylist")
//     *
//     * @Serializer\Groups({"Detail"})
//     */
//    public function virtualBinauralPlaylist()
//    {
//        return $this->getBinauralPlaylist();
//    }
//
//    /**
//     * @Serializer\VirtualProperty
//     * @Serializer\SerializedName("musicPlaylist")
//     *
//     * @Serializer\Groups({"Detail"})
//     */
//    public function virtualMusicPlaylist()
//    {
//        return $this->getMusicPlaylist();
//    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("recommendations")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function getRecommendationIds()
    {
        $arrIds = [];
        foreach ($this->recommendations as $item) {
            $arrIds[] = $item->getId();
        }
        return $arrIds;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("recommendationsString")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function getRecommendationsString()
    {
        $array = [];
        foreach ($this->recommendations as $item) {
            $array[] = $item->getTitle();
        }
        return implode(', ', $array);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("relatedNews")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function virtualRelatedNews()
    {
        $arrIds = [];
        foreach ($this->relatedNews as $item) {
            $arrIds[] = $item->getId();
        }
        return $arrIds;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("relatedReferences")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function virtualRelatedReferences()
    {
        $arrIds = [];
        foreach ($this->relatedReferences as $item) {
            $arrIds[] = $item->getId();
        }
        return $arrIds;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("symptoms")
     */
    public function virtualSymptoms()
    {
        $arrIds = [];

        foreach ($this->symptoms as $item) {
            $arrIds[] = $item->getId();
        }
        return $arrIds;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("categories")
     */
    public function virtualCategories()
    {
        $arrIds = [];

        foreach ($this->symptoms as $symptom) {

            foreach ($symptom->getCategories() as $category) {

                if (!in_array($category->getId(), $arrIds)) {
                    $arrIds[] = $category->getId();
                }
            }
        }
        return $arrIds;
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
     * Check if all necessary files of the therapy have been compiled
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isCompiled")
     */
    public function virtualIsCompiled()
    {
        return $this->isCompiled() && ($this->isCompiledHq() || !$this->includesHq);
    }

    /**
     * Check if at least one files of the therapy is still uncompiled
     *
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
     *
     * @Serializer\Groups({"Detail"})
     */
    public function getPublicFilePath()
    {
        return $this->fileName ? '/library/therapies/' . $this->fileName : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("fileHq")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function getPublicFilePathHq()
    {
        return $this->fileNameHq ? '/library/therapies/' . $this->fileNameHq : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("previewFile")
     */
    public function getPublicPreviewFilePath()
    {
        $publicFilePath = $this->getPublicFilePath();

        return $publicFilePath ? self::getPreviewFileName($publicFilePath) : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("creator")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function virtualCreator()
    {
        return $this->user ? $this->getUser()->getFullname() : 'Unknown User';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("symptomsList")
     */
    public function virtualSymptomsList()
    {
        $arrIds = [];
        foreach ($this->symptoms as $item) {
            $arrIds[] = $item->getTitle();
        }
        return implode(', ', $arrIds);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("feedback")
     */
    public function virtualFeedback()
    {
        $array = [];
        $array[strval(TherapySessionFeedback::TYPE_WORSE)] = 0;
        $array[strval(TherapySessionFeedback::TYPE_GOOD)] = 0;
        $array[strval(TherapySessionFeedback::TYPE_BETTER)] = 0;

        foreach ($this->feedback as $item) {
            $strType = strval($item->getType());
            if (isset($array[$strType])) {
                $array[$strType]++;
            }
        }

        return $array;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("searchString")
     *
     * @Serializer\Groups({"List"})
     */
    public function getSearchString()
    {
        $string = $this->title
            . ' ' . ($this->includesHq ? 'includesHQ' : 'audible')
            . ' ' . $this->description
            . ' ' . $this->virtualCreator()
            . ' ' . $this->virtualSymptomsList()
            . ' ' . $this->getRecommendationsString()
            . ' ' . $this->dosage . 'min'
            . ' ' . $this->days . 'days'
            . ' ' . $this->cycle . $this->cycleType;

        return strtolower($string);
    }


    public function __construct()
    {
        $this->symptoms = new ArrayCollection();
        $this->recommendations = new ArrayCollection();
        $this->relatedNews = new ArrayCollection();
        $this->relatedReferences = new ArrayCollection();
        $this->feedback = new ArrayCollection();
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
        $this->description = $description;
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
        if (!in_array($cycleType, array("month", "week"))) {
            throw new \InvalidArgumentException("Only value 'month' or 'week' is allowed for cycleType, got '$cycleType'.");
        }

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
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
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
        return $this->compileStatus === self::STATUS_COMPILED;
    }

    /**
     * @return bool
     */
    public function isUncompiled()
    {
        return $this->compileStatus === self::STATUS_UNCOMPILED;
    }

    /**
     * @return bool
     */
    public function isCompiling()
    {
        return $this->compileStatus === self::STATUS_COMPILING;
    }

    /**
     * @return bool
     */
    public function hasCompileError()
    {
        return $this->compileStatus === self::STATUS_COMPILE_ERROR;
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
        return $this->compileStatusHq === self::STATUS_COMPILED;
    }

    /**
     * @return bool
     */
    public function isUncompiledHq()
    {
        return $this->compileStatusHq === self::STATUS_UNCOMPILED;
    }

    /**
     * @return bool
     */
    public function isCompilingHq()
    {
        return $this->compileStatusHq === self::STATUS_COMPILING;
    }

    /**
     * @return bool
     */
    public function hasCompileErrorHq()
    {
        return $this->compileStatusHq === self::STATUS_COMPILE_ERROR;
    }

    /**
     * @return mixed
     */
    public function getPreviewCompileStatus()
    {
        return $this->previewCompileStatus;
    }

    /**
     * @param mixed $previewCompileStatus
     */
    public function setPreviewCompileStatus($previewCompileStatus)
    {
        $this->previewCompileStatus = $previewCompileStatus;
    }

    /**
     * @param string $fileName
     *
     * @return Therapy
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
     * @return Therapy
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
     * @return Therapy
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
     * @return Therapy
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
     * @return boolean
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * @param boolean $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param boolean $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * @return ArrayCollection
     */
    public function getRelatedReferences()
    {
        return $this->relatedReferences;
    }

    /**
     * @param $relatedReference
     */
    public function addRelatedReference($relatedReference)
    {
        if ($this->relatedReferences->contains($relatedReference)) {
            return;
        }
        $this->relatedReferences->add($relatedReference);
    }

    /**
     * @param $relatedReference
     */
    public function removeRelatedReference($relatedReference)
    {
        if ($this->relatedReferences->contains($relatedReference)) {
            $this->relatedReferences->removeElement($relatedReference);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getRelatedNews()
    {
        return $this->relatedNews;
    }

    /**
     * @param $relatedNews
     */
    public function addRelatedNews($relatedNews)
    {
        if ($this->relatedNews->contains($relatedNews)) {
            return;
        }
        $this->relatedNews->add($relatedNews);
    }

    /**
     * @param $relatedNews
     */
    public function removeRelatedNews($relatedNews)
    {
        if ($this->relatedNews->contains($relatedNews)) {
            $this->relatedNews->removeElement($relatedNews);
        }
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
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
     * @return mixed
     */
    public function getCompletedSessionsCounter()
    {
        return $this->completedSessionsCounter;
    }

    /**
     * @param mixed $completedSessionsCounter
     */
    public function setCompletedSessionsCounter($completedSessionsCounter)
    {
        $this->completedSessionsCounter = $completedSessionsCounter;
    }

    /**
     * Increment completedSessionsCounter
     */
    public function incrementCompletedSessionsCounter()
    {
        $this->completedSessionsCounter++;
    }

    /**
     * @return mixed
     */
    public function getCompletedTherapiesCounter()
    {
        return $this->completedTherapiesCounter;
    }

    /**
     * @param mixed $completedTherapiesCounter
     */
    public function setCompletedTherapiesCounter($completedTherapiesCounter)
    {
        $this->completedTherapiesCounter = $completedTherapiesCounter;
    }

    /**
     * Increment completedTherapiesCounter
     */
    public function incrementCompletedTherapiesCounter()
    {
        $this->completedTherapiesCounter++;
    }

    /**
     * @return mixed
     */
    public function getMissedSessionsCounter()
    {
        return $this->missedSessionsCounter;
    }

    /**
     * @param mixed $missedSessionsCounter
     */
    public function setMissedSessionsCounter($missedSessionsCounter)
    {
        $this->missedSessionsCounter = $missedSessionsCounter;
    }

    /**
     * Increment missedSessionsCounter
     */
    public function incrementMissedSessionsCounter()
    {
        $this->missedSessionsCounter++;
    }

    /**
     * @return mixed
     */
    public function getMissedTherapiesCounter()
    {
        return $this->missedTherapiesCounter;
    }

    /**
     * @param mixed $missedTherapiesCounter
     */
    public function setMissedTherapiesCounter($missedTherapiesCounter)
    {
        $this->missedTherapiesCounter = $missedTherapiesCounter;
    }

    /**
     * Increment missedTherapiesCounter
     */
    public function incrementMissedTherapiesCounter()
    {
        $this->missedTherapiesCounter++;
    }

    /**
     * @return mixed
     */
    public function getTotalPlayedTime()
    {
        return $this->totalPlayedTime;
    }

    /**
     * @param mixed $totalPlayedTime
     */
    public function setTotalPlayedTime($totalPlayedTime)
    {
        $this->totalPlayedTime = $totalPlayedTime;
    }

    /**
     * Increment totalPlayedTime
     */
    public function incrementTotalPlayedTime()
    {
        $this->totalPlayedTime += $this->dosage;
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
            $this->compileStatus = self::STATUS_UNCOMPILED;
            $this->compileStatusHq = self::STATUS_UNCOMPILED;
        }

        //$this->updatedAt = new \DateTime(); // only update in controller
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

    /**
     * @param $type
     * @return bool
     */
    public static function checkIfTypeHasTonePlaylist($type)
    {
        return $type === self::TYPE_TONE_ONLY || $type === self::TYPE_TONE_MUSIC || $type === self::TYPE_ENVIRO_TONE;
    }

    /**
     * @param $type
     * @return bool
     */
    public static function checkIfTypeHasMusicPlaylist($type)
    {
        return $type !== self::TYPE_TONE_ONLY;
    }

    /**
     * @param $filePath
     * @return mixed
     */
    public static function getPreviewFileName($filePath)
    {
        return str_replace('.mp4', '_preview.mp3', $filePath);
    }
}
