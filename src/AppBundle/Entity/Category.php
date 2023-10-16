<?php


namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CategoryRepository")
 * @ORM\Table(name="category")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity("title")
 */
class Category
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
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Symptom", inversedBy="categories")
     * @ORM\JoinTable(name="categories_symptoms")
     *
     * @Serializer\Exclude
     */
    private $symptoms;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("symptoms")
     */
    public function virtualSymptoms()
    {
        $items = array();
        foreach ($this->symptoms as $item) {
            $items[] = $item->getId();
        }
        return $items;
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
        foreach($this->symptoms as $symptom) {

            $symptomsTherapyIds = $symptom->getTherapyIds();

            $published = array_merge($published, $symptomsTherapyIds['all']);
            $public = array_merge($public, $symptomsTherapyIds['all']);
        }

        return [
            'all' => $published,
            'public' => $public,
        ];
    }


    public function __construct()
    {
        $this->symptoms = new ArrayCollection();
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
        // set it on update database (since we added this column after category creation)
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }

}