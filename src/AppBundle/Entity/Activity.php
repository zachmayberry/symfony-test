<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ActivityRepository")
 * @ORM\Table(name="activities")
 *
 * @ORM\HasLifecycleCallbacks
 */
class Activity
{
    const TYPE_THERAPY = 1;
    const TYPE_NEWS = 2;
    const TYPE_ACADEMIC = 3;
    const TYPE_EASY_READ = 4;

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
     *
     * @Serializer\Exclude()
     */
    private $title;

    /**
     * @ORM\Column(type="datetime", name="published_at", nullable=true)
     */
    private $publishedAt;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     * @Serializer\Exclude()
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Therapy")
     * @ORM\JoinColumn(name="therapy_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     *
     * @Serializer\Exclude()
     */
    private $therapy;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\News")
     * @ORM\JoinColumn(name="news_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     *
     * @Serializer\Exclude()
     */
    private $news;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Reference")
     * @ORM\JoinColumn(name="reference_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     *
     * @Serializer\Exclude()
     */
    private $reference;

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("itemId")
     *
     * @return integer|null
     */
    public function getItemId()
    {
        switch ($this->type) {
            case self::TYPE_EASY_READ:
            case self::TYPE_ACADEMIC:
                return $this->reference ? $this->reference->getId() : null;
            case self::TYPE_NEWS:
                return $this->news ? $this->news->getId() : null;
            case self::TYPE_THERAPY:
                return $this->therapy ? $this->therapy->getId() : null;
        }
        return null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("referenceLink")
     */
    public function getReferenceLink() {
        return $this->reference ? $this->reference->getLink() : '';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("title")
     */
    public function virtualTitle() {
        return $this->getTitle();
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
        switch ($this->type) {
          case self::TYPE_EASY_READ:
          case self::TYPE_ACADEMIC:
            return $this->reference ? $this->reference->getTitle() : $this->title . ' (deleted)';
          case self::TYPE_NEWS:
            return $this->news ? $this->news->getTitle() : $this->title . ' (deleted)';
          case self::TYPE_THERAPY:
            return $this->therapy ? $this->therapy->getTitle() : $this->title . ' (deleted)';
        }
        return '';$this->title;
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
    public function getPublishedAt()
    {
        return !$this->publishedAt ? $this->getCreatedAt() : $this->publishedAt;
    }

    /**
     * @param mixed $publishedAt
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;
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
     * @return mixed
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * @param mixed $news
     */
    public function setNews($news)
    {
        $this->news = $news;
    }

    /**
     * @return mixed
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param mixed $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
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
        if (!$this->publishedAt) {
            $this->publishedAt = $date;
        }
    }
}