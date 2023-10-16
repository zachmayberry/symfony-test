<?php


namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SymptomRepository")
 * @ORM\Table(name="symptom")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity("title")
 */
class Symptom
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Category", mappedBy="symptoms")
     *
     * @Serializer\Exclude()
     */
    private $categories;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Track", mappedBy="symptoms", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude
     */
    private $tracks;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Therapy", mappedBy="symptoms", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude
     */
    private $therapies;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;


    public function __construct()
    {
        $this->tracks = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->therapies = new ArrayCollection();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("tracks")
     */
    public function getTrackIds()
    {
        $array = [];
        foreach ($this->tracks as $track) {
            $array[] = $track->getId();
        }

        return $array;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("tracksCount")
     */
    public function getTracksCount()
    {
        return count($this->tracks);
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("categories")
     */
    public function getCategoriesMap()
    {
        $array = [
            'ids' => [],
            'titles' => [],
        ];

        foreach ($this->categories as $category) {
            $array['ids'][] = $category->getId();
            $array['titles'][] = $category->getTitle();
        }

        return $array;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("categoriesString")
     */
    public function getCategoriesString()
    {
        $array = [];
        foreach ($this->categories as $category) {
            $array[] = $category->getTitle();
        }

        return implode(', ', $array);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("searchString")
     */
    public function getSearchString()
    {
        $string = $this->id
            . ' ' . $this->title
            . ' ' . $this->getCategoriesString()
        ;

        return strtolower($string);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("therapyIds")
     */
    public function getTherapyIds()
    {
        $public = [];
        $published = [];

        // only count published therapies
        foreach ($this->therapies as $therapy) {

            if ($therapy->getPublished()) {
                $published[] = $therapy->getId();

                if ($therapy->getPublic()) {
                    $public[] = $therapy->getId();
                }

            }
        }

        return [
            'all' => $published,
            'public' => $public,
        ];
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
     * @return ArrayCollection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param ArrayCollection $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
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
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        // set it on update database (since we added this column after symptom creation)
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }

}