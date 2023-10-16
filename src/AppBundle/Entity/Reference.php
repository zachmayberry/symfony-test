<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use JMS\Serializer\Annotation as Serializer;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ReferenceRepository")
 * @ORM\Table(name="reference")
 * @ORM\HasLifecycleCallbacks
 *
 * @Vich\Uploadable
 *
 * @Hateoas\Relation(
 *     "relatedTherapies",
 *     embedded = "expr(object.getEmbeddedRelatedTherapies())",
 * )
 */
class Reference
{
    const TYPE_EASY_READ = 1;
    const TYPE_ACADEMIC  = 2;

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
     * @ORM\Column(type="integer", length=1, nullable=false)
     */
    private $category;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     */
    private $teaser;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank()
     * @Assert\Url(
     *    message = "The url '{{ value }}' is not a valid url",
     *    protocols = {"http", "https"},
     * )
     */
    private $link;

    /**
     * @ORM\Column(type="date")
     *
     * @Assert\NotBlank()
     *
     * @Serializer\Type("DateTime<'Y-m-d'>")
     */
    private $date;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hidden;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="reference_image", fileNameProperty="imageName", size="imageSize")
     *
     * @Serializer\Exclude
     *
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("image")
     * @Serializer\Exclude(if="!object.getImageName()")
     * )
     */
    private $imageName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Serializer\Exclude
     *
     * @var integer
     */
    private $imageSize;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Serializer\Exclude
     *
     * @var Object
     */
    private $croppedImageData;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Therapy", mappedBy="relatedReferences", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude
     */
    private $relatedTherapies;


    public function __construct()
    {
        $this->relatedTherapies = new ArrayCollection();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("itemType")
     */
    public function getItemType() {
      return "reference";
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("relatedTherapies")
     */
    public function virtualRelatedNews()
    {
        $arrIds = [];
        foreach($this->relatedTherapies as $item) {
            $arrIds[] = $item->getId();
        }
        return $arrIds;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("searchString")
     */
    public function getSearchString()
    {
        $string = $this->title
            . ' ' . $this->teaser
            . ' ' . $this->link
        ;

        return strtolower($string);
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
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param integer $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
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
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * @param mixed $teaser
     */
    public function setTeaser($teaser)
    {
        $this->teaser = $teaser;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Reference
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();//new \DateTimeImmutable();

            $this->imageSize = $image->getSize();
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * @param string $imageName
     *
     * @return Reference
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @param integer $imageSize
     *
     * @return Reference
     */
    public function setImageSize($imageSize)
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getImageSize()
    {
        return $this->imageSize;
    }

    /**
     * @return string
     */
    public function getCroppedImageData()
    {
        return $this->croppedImageData;
    }

    /**
     * @param string $croppedImageData
     *
     * @return self
     */
    public function setCroppedImageData($croppedImageData)
    {
        $data = $croppedImageData;

        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);

        $tmpPath = realpath(dirname(__FILE__)) . '/../../../var/tmp/';
        $fileName = uniqid() . '.png';
        $filePath = $tmpPath . $fileName;

        file_put_contents($filePath, $data);

        // move uploaded file from tmp folder to correct destination
        $uploadableFile = new UploadedFile($filePath, $fileName, null, null, null, true);
        $this->setImageFile($uploadableFile);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime|string $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @param mixed $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * @return mixed
     */
    public function getRelatedTherapies()
    {
        return $this->relatedTherapies;
    }

    /**
     * @param mixed $relatedTherapies
     */
    public function addRelatedTherapy(Therapy $relatedTherapy)
    {
        if ($this->relatedTherapies->contains($relatedTherapy)) {
            return;
        }

        $this->relatedTherapies->add($relatedTherapy);
        $relatedTherapy->addRelatedReference($this);

        // update for live update in react
        $this->updatedAt = new \DateTime();
    }

    /**
     * @param mixed $relatedTherapies
     */
    public function removeRelatedTherapy(Therapy $relatedTherapy)
    {
        if (!$this->relatedTherapies->contains($relatedTherapy)) {
            return;
        }

        $this->relatedTherapies->removeElement($relatedTherapy);
        $relatedTherapy->removeRelatedReference($this);

        // update for live update in react
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get necessary data of related therapies
     */
    public function getEmbeddedRelatedTherapies()
    {
        $array = [];

        foreach ($this->relatedTherapies as $therapy) {

            // only count published therapies
            if ($therapy->getPublished()) {

                $array[] = [
                    'id' => $therapy->getId(),
                    'title' => $therapy->getTitle(),
                    'type' => $therapy->getType(),
                    'description' => $therapy->getDescription(),
                    'public' => $therapy->getPublic(),
                    'symptoms' => $therapy->virtualSymptoms(),
                    'symptomsList' => $therapy->virtualSymptomsList(),
                ];
            }
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

        if (!$this->date && !$this->hidden) {
            $this->date = $date;
        }

        $this->updatedAt = $date;
    }

}
