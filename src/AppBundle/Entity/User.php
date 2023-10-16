<?php

namespace AppBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use AuthBundle\Entity\User as BaseUser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

// * @ORM\EntityListeners({"AppBundle\EventListeners\UserChangeListener"})

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="`users`")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity("email")
 *
 * @Vich\Uploadable
 */
class User extends BaseUser
{

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     */
    private $zipcode;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     */
    private $location;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $phone;

//    /**
//     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Hospital")
//     * @ORM\JoinColumn(name="hospital_id", referencedColumnName="id", nullable=true)
//     *
//     * @Serializer\Exclude
//     */
//    private $hospital;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $hospital;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $country;

//    /**
//     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\MedicalScience")
//     * @ORM\JoinColumn(name="medical_science_id", referencedColumnName="id", nullable=true)
//     *
//     * @Serializer\Exclude
//     */
//    private $medicalScience;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     */
    private $medicalScience;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     */
    private $disease;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     */
    private $occupation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     */
    private $company;

    /**
     * @ORM\Column(type="date", nullable=true)
     *
     * @Serializer\Type("DateTime<'Y-m-d'>")
     * @Serializer\Exclude(if="!object.getDob()")
     * @Serializer\Groups({"Detail"})
     */
    private $dob;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="user_certificate", fileNameProperty="certificateFileName", size="certificateFileSize")
     *
     * @Serializer\Exclude
     *
     * @var File
     */
    private $certificateFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("certificateFile")
     * @Serializer\Type("array")
     * @Serializer\Exclude(if="!object.getCertificateFileName()")
     * @Serializer\Groups({"Detail"})
     *
     * @var string
     */
    private $certificateFileName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Serializer\Exclude
     *
     * @var integer
     */
    private $certificateFileSize;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Serializer\Exclude
     *
     * @var Object
     */
    private $uploadedCertificateFile;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="user_profile_image", fileNameProperty="profileImageName", size="profileImageSize")
     *
     * @Serializer\Exclude
     *
     * @var File
     */
    private $profileImageFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("image")
     * @Serializer\Exclude(if="!object.getProfileImageName()")
     */
    private $profileImageName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Serializer\Exclude
     *
     * @var integer
     */
    private $profileImageSize;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Serializer\Exclude
     *
     * @var Object
     */
    private $uploadedProfileImageFile;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Serializer\Exclude
     *
     * @var Object
     */
    private $croppedProfileImageData;

    /**
     * One User has many Therapies
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Therapy", mappedBy="user")
     *
     * @Serializer\Exclude
     *
     * @var ArrayCollection
     */
    private $therapies;

    /**
     * One User has many UserTherapies
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\UserTherapy", mappedBy="user", cascade={"remove", "persist"}, orphanRemoval=true)
     *
     * @Serializer\Exclude
     *
     * @var ArrayCollection
     */
    private $userTherapies;

    /**
     * One User has many favourite Therapies
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\FavouriteTherapy", mappedBy="user", cascade={"remove", "persist"}, orphanRemoval=true)
     *
     * @Serializer\Exclude
     *
     * @var ArrayCollection
     */
    private $favouriteTherapies;

    /**
     * Many Users have Many Doctors.
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\User", mappedBy="doctors")
     *
     * @Serializer\Exclude
     *
     * @var ArrayCollection
     */
    private $patients;

    /**
     * Many Users have many Users.
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\User", inversedBy="patients")
     * @ORM\JoinTable(name="doctors_users",
     *      joinColumns={@ORM\JoinColumn(name="patient_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="doctor_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     *
     * @Serializer\Exclude
     *
     * @var ArrayCollection
     */
    private $doctors;

    /**
     * Date/Time of the last activity
     *
     * @var \Datetime
     * @ORM\Column(name="last_activity_at", type="datetime", nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     */
    private $lastActivityAt;

    /**
     * Date/Time of the last activity
     *
     * @var array
     * @ORM\Column(name="last_used_color", type="array", nullable=true)
     *
     * @Serializer\Exclude
     */
    private $lastUsedColor;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $completedTherapiesCount = 0;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $completedSessionsCount = 0;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $missedSessionsCount = 0;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $totalPlayTime = 0;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\TherapySessionFeedback", mappedBy="user")
     *
     * @Serializer\Exclude
     */
    private $feedback;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Serializer\Groups({"Detail"})
     *
     * @var boolean
     */
    private $undeletable;

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("name")
     */
    public function virtualName()
    {
        return $this->getFullname();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("age")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function getAge()
    {
        $now = new \DateTime();
        $interval = $now->diff($this->dob);
        return $interval->y;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("doctors")
     */
    public function virtualDoctors()
    {
        $doctors = array();
        foreach ($this->getDoctors() as $doctor) {
            $doctors[] = $doctor->getId();
        }

        return $doctors;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("doctorsNames")
     */
    public function virtualDoctorsNames()
    {
        $doctors = array();
        foreach ($this->getDoctors() as $doctor) {
            $doctors[] = $doctor->getFullname();
        }

        return $doctors;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("patients")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function virtualPatients()
    {
        $patients = array();
        foreach ($this->getPatients() as $patient) {
            $patients[] = $patient->getId();
        }

        return $patients;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("medicalScience")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function getMedicalScienceId()
    {
        // just return something positive, so it doesn't break anything which relies on an id
        return $this->medicalScience ? 1 : null; //$this->medicalScience ? $this->medicalScience->getId() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("medicalScienceName")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function getMedicalScienceName()
    {
        return $this->medicalScience; // ? $this->medicalScience->getTitle() : '';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("hospital")
     */
    public function getHospitalId()
    {
        // just return something positive, so it doesn't break anything which relies on an id
        return $this->hospital ? 1 : null; //return $this->hospital ? $this->hospital->getId() : null;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("hospitalName")
     */
    public function getHospitalName()
    {
        return $this->hospital; // ? $this->hospital->getTitle() : '';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("hasProfileImage")
     *
     * @Serializer\Groups({"Detail"})
     */
    public function virtualHasProfileImage()
    {
        return !!$this->profileImageName;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("roleType")
     */
    public function virtualRoleType()
    {
        if ($this->hasRole('ROLE_ADMIN')) {
            return 'admin';
        }
        if ($this->hasRole('ROLE_DOCTOR')) {
            return 'doctor';
        }
        if ($this->hasRole('ROLE_PATIENT')) {
            return 'patient';
        }

        return 'user';
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

        if ($this->feedback) {
            foreach($this->feedback as $item) {
                $strType = strval($item->getType());
                if (isset($array[$strType])) {
                    $array[$strType] ++;
                }
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
        $string = $this->id
            . ' ' . $this->getFullname()
            . ' ' . $this->email
            . ' ' . $this->phone
            . ' ' . implode(' ', $this->virtualDoctorsNames())
            . ' ' . $this->getHospitalName()
        ;

        return strtolower($string);
    }


    public function __construct()
    {
        parent::__construct();

        $this->therapies = new ArrayCollection();
        $this->userTherapies = new ArrayCollection();
        $this->favouriteTherapies = new ArrayCollection();
        $this->doctors = new ArrayCollection();
        $this->patients = new ArrayCollection();
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
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @param string $zipcode
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return \AppBundle\Entity\Hospital
     */
    public function getHospital()
    {
        return $this->hospital;
    }

    /**
     * @param \AppBundle\Entity\Hospital $hospital
     */
    public function setHospital($hospital)
    {
        $this->hospital = $hospital;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getDisease()
    {
        return $this->disease;
    }

    /**
     * @param mixed $disease
     */
    public function setDisease($disease)
    {
        $this->disease = $disease;
    }

    /**
     * @return DateTime|null
     */
    public function getDob()
    {
        return $this->dob;
    }

    /**
     * @param \DateTime|string $date
     */
    public function setDob($dob)
    {
        $this->dob = $dob;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return User
     */
    public function setCertificateFile(File $file = null)
    {
        $this->certificateFile = $file;


        if ($file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();//new \DateTimeImmutable();

            $this->certificateFileSize = $file->getSize();
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getCertificateFile()
    {
        return $this->certificateFile;
    }

    /**
     * @param string $fileName
     *
     * @return User
     */
    public function setCertificateFileName($fileName)
    {
        $this->certificateFileName = $fileName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCertificateFileName()
    {
        return $this->certificateFileName;
    }

    /**
     * @param integer $certificateFileSize
     *
     * @return User
     */
    public function setCertificateFileSize($certificateFileSize)
    {
        $this->certificateFileSize = $certificateFileSize;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getCertificateFileSize()
    {
        return $this->certificateFileSize;
    }

    /**
     * @return string
     */
    public function getUploadedCertificateFile()
    {
        return $this->uploadedCertificateFile;
    }

    /**
     * @param string $uploadedCertificateFile
     */
    public function setUploadedCertificateFile($uploadedCertificateFile)
    {
        $this->uploadedCertificateFile = $uploadedCertificateFile;

        // serialize string
        $objFile = json_decode($uploadedCertificateFile);
        $tmpPath = realpath(dirname(__FILE__)) . '/../../../var/tmp/';

        if ($objFile) {
            $filePath = $tmpPath . $objFile->filename;

            // move uploaded file from tmp folder to correct destination
            $uploadableFile = new UploadedFile($filePath, $objFile->filename, null, null, null, true);
            $this->setCertificateFile($uploadableFile);

            // store original filename
            //$this->setOriginalFilename($objFile->original_filename);
        }

        return $this;
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
     * @return User
     */
    public function setProfileImageFile(File $image = null)
    {
        $this->profileImageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();//new \DateTimeImmutable();

            $this->profileImageSize = $image->getSize();
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getProfileImageFile()
    {
        return $this->profileImageFile;
    }

    /**
     * @param string $imageName
     *
     * @return User
     */
    public function setProfileImageName($imageName)
    {
        $this->profileImageName = $imageName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProfileImageName()
    {
        return $this->profileImageName;
    }

    /**
     * @param integer $imageSize
     *
     * @return User
     */
    public function setProfileImageSize($imageSize)
    {
        $this->profileImageSize = $imageSize;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getProfileImageSize()
    {
        return $this->profileImageSize;
    }

    /**
     * @return string
     */
    public function getUploadedProfileImageFile()
    {
        return $this->uploadedProfileImageFile;
    }

    /**
     * @param string $uploadedProfileImageFile
     *
     * @return self
     */
    public function setUploadedProfileImageFile($uploadedProfileImageFile)
    {
        $this->uploadedProfileImageFile = $uploadedProfileImageFile;

        // serialize string
        $objFile = json_decode($uploadedProfileImageFile);
        $tmpPath = realpath(dirname(__FILE__)) . '/../../../var/tmp/';

        if ($objFile) {
            $filePath = $tmpPath . $objFile->filename;

            // move uploaded file from tmp folder to correct destination
            $uploadableFile = new UploadedFile($filePath, $objFile->filename, null, null, null, true);
            $this->setProfileImageFile($uploadableFile);

            // store original filename
            //$this->setOriginalFilename($objFile->original_filename);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCroppedProfileImageData()
    {
        return $this->croppedProfileImageData;
    }

    /**
     * @param string $croppedProfileImageData
     *
     * @return self
     */
    public function setCroppedProfileImageData($croppedProfileImageData)
    {
        $data = $croppedProfileImageData;

        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);

        $tmpPath = realpath(dirname(__FILE__)) . '/../../../var/tmp/';
        $fileName = uniqid() . '.png';
        $filePath = $tmpPath . $fileName;

        file_put_contents($filePath, $data);

        // move uploaded file from tmp folder to correct destination
        $uploadableFile = new UploadedFile($filePath, $fileName, null, null, null, true);
        $this->setProfileImageFile($uploadableFile);

        return $this;
    }

    /**
     * @return \AppBundle\Entity\MedicalScience
     */
    public function getMedicalScience()
    {
        return $this->medicalScience;
    }

    /**
     * @param \AppBundle\Entity\MedicalScience $medicalScience
     */
    public function setMedicalScience($medicalScience)
    {
        $this->medicalScience = $medicalScience;
    }

    /**
     * @return ArrayCollection
     */
    public function getTherapies()
    {
        return $this->therapies;
    }

    /**
     * @param ArrayCollection $therapies
     */
    public function setTherapies($therapies)
    {
        $this->therapies = $therapies;
    }

    /**
     * @return ArrayCollection
     */
    public function getUserTherapies()
    {
        return $this->userTherapies;
    }

    /**
     * @param ArrayCollection $userTherapies
     */
    public function setUserTherapies($userTherapies)
    {
        $this->userTherapies = $userTherapies;
    }

    /**
     * @return ArrayCollection
     */
    public function getFavouriteTherapies()
    {
        return $this->favouriteTherapies;
    }

    /**
     * @param ArrayCollection $favouriteTherapies
     */
    public function setFavouriteTherapies($favouriteTherapies)
    {
        $this->favouriteTherapies = $favouriteTherapies;
    }

    /**
     * @return ArrayCollection
     */
    public function getDoctors()
    {
        return $this->doctors;
    }

    /**
     * @param ArrayCollection $doctors
     */
    public function setDoctors($doctors)
    {
        $this->doctors = $doctors;
    }

    /**
     * @return ArrayCollection
     */
    public function getPatients()
    {
        return $this->patients;
    }

    /**
     * @param ArrayCollection $patients
     */
    public function setPatients($patients)
    {
        $this->patients = $patients;
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
     * @return mixed
     */
    public function getOccupation()
    {
        return $this->occupation;
    }

    /**
     * @param mixed $occupation
     */
    public function setOccupation($occupation)
    {
        $this->occupation = $occupation;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @param \Datetime $lastActivityAt
     */
    public function setLastActivityAt($lastActivityAt)
    {
        $this->lastActivityAt = $lastActivityAt;
    }

    /**
     * @return \Datetime
     */
    public function getLastActivityAt()
    {
        return $this->lastActivityAt;
    }

    /**
     * @return Bool Whether the user is active or not
     */
    public function isActiveNow()
    {
        // Delay during wich the user will be considered as still active
        $delay = new \DateTime('2 minutes ago');

        return ( $this->getLastActivityAt() > $delay );
    }

    /**
     * @return mixed
     */
    public function getLastUsedColor()
    {
        return $this->lastUsedColor;
    }

    /**
     * @param mixed $lastUsedColor
     */
    public function setLastUsedColor($lastUsedColor)
    {
        $this->lastUsedColor = $lastUsedColor;
    }

    /**
     * @return integer
     */
    public function getCompletedTherapiesCount()
    {
        return $this->completedTherapiesCount;
    }

    /**
     * @param integer $completedTherapiesCount
     */
    public function setCompletedTherapiesCount($completedTherapiesCount)
    {
        $this->completedTherapiesCount = $completedTherapiesCount;
    }

    /**
     * @return integer
     */
    public function getCompletedSessionsCount()
    {
        return $this->completedSessionsCount;
    }

    /**
     * @param integer $completedSessionsCount
     */
    public function setCompletedSessionsCount($completedSessionsCount)
    {
        $this->completedSessionsCount = $completedSessionsCount;
    }

    /**
     * @return integer
     */
    public function getMissedSessionsCount()
    {
        return $this->missedSessionsCount;
    }

    /**
     * @param integer $missedSessionsCount
     */
    public function setMissedSessionsCount($missedSessionsCount)
    {
        $this->missedSessionsCount = $missedSessionsCount;
    }

    /**
     * @return integer
     */
    public function getTotalPlayTime()
    {
        return $this->totalPlayTime;
    }

    /**
     * @param integer $totalPlayTime
     */
    public function setTotalPlayTime($totalPlayTime)
    {
        $this->totalPlayTime = $totalPlayTime;
    }

    /**
     * @return mixed
     */
    public function getLastUsedColorByType($type)
    {
        if (isset($this->lastUsedColor[$type])) {
            return $this->lastUsedColor[$type];
        }

        $colorSet = UserTherapy::getColorSetByType($type);
        return $colorSet[0];
    }

    /**
     * @param mixed $lastUsedColor
     */
    public function setLastUsedColorByType($lastUsedColor, $type)
    {
        //$lastUsedColors = $this->lastUsedColor ? $this->lastUsedColor : [];
        //$lastUsedColors[$type] = $lastUsedColor;
        $this->lastUsedColor[$type] = $lastUsedColor;
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