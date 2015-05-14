<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/11/15
 * Time: 3:20 PM
 */

// src/DispatcherBundle/Entity/ExperimentEngine.php
namespace DispatcherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="ExperimentEngine")
 */
class ExperimentEngine
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $labserverId;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $httpAuthentication;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $api_key;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $institution;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $contact_name;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $contact_email;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $address; //address of the institution where the EE is hosted

    /**
     * @ORM\Column(type="string", length=45)
     */
    protected $city; //city of the institution where the EE is hosted

    /**
     * @ORM\Column(type="string", length=45)
     */
    protected $country; //country of the institution where the EE is hosted

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $geolocation; //JSON representation of the experiment engine location

    /**
     * @ORM\Column(type="string", length=500)
     */
    protected $description;

    /**
     * @ORM\Column(type="integer")
     */
    protected $owner_id;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $active;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $visible_in_catalogue; //if true owners allow it to be visible in the Website's catalogue

    /**
     * @ORM\Column(type="string", length=35)
     */
    protected $dateCreated;


    /**
     * Get id
     *
     * @return integer
     */

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set labserverId
     *
     * @param string $labserverId
     * @return ExperimentEngine
     */
    public function setLabserverId($labserverId)
    {
        $this->labserverId = $labserverId;

        return $this;
    }

    /**
     * Get labserverId
     *
     * @return string 
     */
    public function getLabserverId()
    {
        return $this->labserverId;
    }

    /**
     * Set httpAuthentication
     *
     * @param string $httpAuthentication
     * @return ExperimentEngine
     */
    public function setHttpAuthentication($httpAuthentication)
    {
        $this->httpAuthentication = $httpAuthentication;

        return $this;
    }

    /**
     * Get httpAuthentication
     *
     * @return string 
     */
    public function getHttpAuthentication()
    {
        return $this->httpAuthentication;
    }

    /**
     * Set api_key
     *
     * @param string $apiKey
     * @return ExperimentEngine
     */
    public function setApiKey($apiKey)
    {
        $this->api_key = $apiKey;

        return $this;
    }

    /**
     * Get api_key
     *
     * @return string 
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ExperimentEngine
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set institution
     *
     * @param string $institution
     * @return ExperimentEngine
     */
    public function setInstitution($institution)
    {
        $this->institution = $institution;

        return $this;
    }

    /**
     * Get institution
     *
     * @return string 
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return ExperimentEngine
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return ExperimentEngine
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return ExperimentEngine
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set geolocation
     *
     * @param string $geolocation
     * @return ExperimentEngine
     */
    public function setGeolocation($geolocation)
    {
        $this->geolocation = $geolocation;

        return $this;
    }

    /**
     * Get geolocation
     *
     * @return string 
     */
    public function getGeolocation()
    {
        return $this->geolocation;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ExperimentEngine
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set owner_id
     *
     * @param integer $ownerId
     * @return ExperimentEngine
     */
    public function setOwnerId($ownerId)
    {
        $this->owner_id = $ownerId;

        return $this;
    }

    /**
     * Get owner_id
     *
     * @return integer 
     */
    public function getOwnerId()
    {
        return $this->owner_id;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return ExperimentEngine
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set visible_in_catalogue
     *
     * @param boolean $visibleInCatalogue
     * @return ExperimentEngine
     */
    public function setVisibleInCatalogue($visibleInCatalogue)
    {
        $this->visible_in_catalogue = $visibleInCatalogue;

        return $this;
    }

    /**
     * Get visible_in_catalogue
     *
     * @return boolean 
     */
    public function getVisibleInCatalogue()
    {
        return $this->visible_in_catalogue;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return ExperimentEngine
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set contact_name
     *
     * @param string $contactName
     * @return ExperimentEngine
     */
    public function setContactName($contactName)
    {
        $this->contact_name = $contactName;

        return $this;
    }

    /**
     * Get contact_name
     *
     * @return string 
     */
    public function getContactName()
    {
        return $this->contact_name;
    }

    /**
     * Set contact_email
     *
     * @param string $contactEmail
     * @return ExperimentEngine
     */
    public function setContactEmail($contactEmail)
    {
        $this->contact_email = $contactEmail;

        return $this;
    }

    /**
     * Get contact_email
     *
     * @return string 
     */
    public function getContactEmail()
    {
        return $this->contact_email;
    }

    public function setAll($data)
    {
        $this->labserverId = $data['labserverId'];

        $this->httpAuthentication = 'Basic '.base64_encode($data['username'].':'.$data['password']);
        $this->api_key = $data['api_key'];
        $this->name = $data['name'];
        $this->institution = $data['institution'];
        $this->contact_name = $data['contact_name'];
        $this->contact_email = $data['contact_email'];
        $this->address = $data['address'];
        $this->city = $data['city'];
        $this->country = $data['country'];
        $this->geolocation = '';
        $this->description = $data['description'];
        $this->owner_id = 1;//change after user database is created
        $this->active = $data['active'];
        $this->visible_in_catalogue = $data['visible_in_catalogue'];
        $this->dateCreated = date('Y-m-d H:i:s');

    }

    public function updateAll($data)
    {
        $this->labserverId = $data['labserverId'];

        $this->httpAuthentication = $data['basic_auth'];
        $this->api_key = $data['api_key'];
        $this->name = $data['name'];
        $this->institution = $data['institution'];
        $this->contact_name = $data['contact_name'];
        $this->contact_email = $data['contact_email'];
        $this->address = $data['address'];
        $this->city = $data['city'];
        $this->country = $data['country'];
        $this->geolocation = '';
        $this->description = $data['description'];
        $this->owner_id = 1;//change after user database is created
        $this->active = $data['active'];
        $this->visible_in_catalogue = $data['visible_in_catalogue'];
        //$this->dateCreated = date('Y-m-d H:i:s');

    }
}
