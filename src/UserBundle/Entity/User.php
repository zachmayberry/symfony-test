<?php
namespace UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * User.
 * see: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/inheritance-mapping.html
 *
 * @ORM\MappedSuperclass
 */
class User extends BaseUser
{
    const USER_TYPE_USER    = 0;
    const USER_TYPE_PATIENT = 1;
    const USER_TYPE_DOCTOR  = 2;
    const USER_TYPE_ADMIN   = 3;

    const GENDER_TYPE_MALE      = 1;
    const GENDER_TYPE_FEMALE    = 2;
    const GENDER_TYPE_TRANS     = 3;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * [0 = user, 1 = patient, 2 = doctor, 3 = admin]
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $type = 0;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $gender;


    public function __construct()
    {
        parent::__construct();

        // custom logic
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
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * Return the full name of the user
     *
     * @return string
     */
    public function getFullname()
    {
        return trim($this->getFirstname() . ' ' . $this->getLastname());
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
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set gender of user [0 = undefined, 1 = male, 2 = female, 3 = transgender]
     *
     * @param mixed $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    public function isDoctor()
    {
        return $this->type === self::USER_TYPE_DOCTOR;
    }

    public function isAdmin()
    {
        return $this->type === self::USER_TYPE_ADMIN;
    }

    public function isPatient()
    {
        return $this->type === self::USER_TYPE_PATIENT;
    }

    public function isClient()
    {
        return $this->type === self::USER_TYPE_USER;
    }
}
