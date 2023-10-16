<?php


namespace AppBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PublisherRepository")
 * @ORM\Table(name="publisher")
 * @ORM\HasLifecycleCallbacks
 *
 * @Vich\Uploadable
 *
 * @UniqueEntity("name")
 */
class Publisher
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
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="Track", mappedBy="publishers", fetch="EXTRA_LAZY")
     *
     * @Serializer\Exclude
     */
    private $tracks;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="album_cover", fileNameProperty="imageName", size="imageSize")
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
     *
     * @var string
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
    private $uploadedImageFile;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Serializer\Exclude
     *
     * @var Object
     */
    private $croppedImageData;

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("tracks")
     */
    public function virtualTracks()
    {
        $tracks = array();
        foreach ($this->tracks as $track){
            $tracks[] = $track->getId();
        }
        return $tracks;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("tracksCount")
     */
    public function virtualTracksCount()
    {
        return count($this->tracks);
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("searchString")
     */
    public function getSearchString()
    {
        $string = $this->id
            . ' ' . $this->name
            . ' ' . $this->description
        ;

        return strtolower($string);
    }


    public function __construct() {
        $this->tracks = new ArrayCollection();
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = trim($name);
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return ArrayCollection
     */
    public function getTracks()
    {
        return $this->tracks;
    }

    /**
     * @param ArrayCollection $tracks
     */
    public function setTracks($tracks)
    {
        $this->tracks = $tracks;
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
     * @return Publisher
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();
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
     * @return Artist
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
     * @return Artist
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
     * @param string $uploadedImageFile
     */
    public function setUploadedFile($uploadedImageFile)
    {
        $this->uploadedImageFile = $uploadedImageFile;

        // serialize string
        $objFile = json_decode($uploadedImageFile);
        $tmpPath = realpath(dirname(__FILE__)) . '/../../../var/tmp/';

        if ($objFile) {
            $filePath = $tmpPath . $objFile->filename;

            // move uploaded file from tmp folder to correct destination
            $uploadableFile = new UploadedFile($filePath, $objFile->filename, null, null, null, true);
            $this->setImageFile($uploadableFile);
        }

        return $this;
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

