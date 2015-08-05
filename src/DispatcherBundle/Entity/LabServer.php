<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/11/15
 * Time: 3:20 PM
 */

// src/DispatcherBundle/Entity/LabServer.php
namespace DispatcherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="LabServer")
 */
class LabServer
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
    protected $Guid;

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
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="integer")
     */
    protected $owner_id;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $public_sub; //If true, anyone can subscribe to execute experiments for this lab server, otherwise only the owner is allowed to subscribe

    /**
     * @ORM\Column(type="boolean")
     */
    protected $active;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $visible_in_catalogue; //if true owners allow it to be visible in the Website's catalogue

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $configuration; //If necessary, stores configuration of the lab in JSON, XML, etc.

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $opaqueData; //Optional field, used to transfer additional information if necessary.

    /**
     * @ORM\Column(type="string", length=35)
     */
    protected $dateCreated;

    /**
     * @ORM\Column(type="string", length=64)
     */
    protected $passKey;

    /**
     * @ORM\Column(type="string", length=64)
     */
    protected $initialPassKey;

    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $labInfo;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $rlmsSpecificData; //Optional field, used to stope RLMS specific data


    public function setAll($data)
    {
        $this->Guid = $data['Guid'];
        $this->name = $data['name'];
        $this->institution = $data['institution'];
        $this->contact_name = $data['contact_name'];
        $this->contact_email = $data['contact_email'];
        $this->description = $data['description'];
        $this->owner_id = 1;//change after user database is created
        $this->active = $data['active'];
        //$this->visible_in_catalogue = $data['visible_in_catalogue'];
        $this->configuration = $data['configuration'];
        $this->public_sub = $data['public_sub'];
        $this->dateCreated = date('Y-m-d\TH:i:sP');
        $this->passKey = $data['passKey'];
        $this->initialPassKey = $data['initialPassKey'];
        $this->labInfo = $data['labInfo'];
        $this->type = $data['type'];
    }

    public function updateAll($data, $ownerId)
    {
        $this->name = $data['name'];
        $this->institution = $data['institution'];
        $this->contact_name = $data['contact_name'];
        $this->contact_email = $data['contact_email'];
        $this->description = $data['description'];
        $this->owner_id = $ownerId;//change after user database is created
        $this->active = $data['active'];
        //$this->visible_in_catalogue = $data['visible_in_catalogue'];
        $this->configuration = $data['configuration'];
        $this->public_sub = $data['public_sub'];
        $this->labInfo = $data['labInfo'];

    }

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
     * Set Guid
     *
     * @param string $guid
     * @return LabServer
     */
    public function setGuid($guid)
    {
        $this->Guid = $guid;

        return $this;
    }

    /**
     * Get Guid
     *
     * @return string 
     */
    public function getGuid()
    {
        return $this->Guid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return LabServer
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
     * @return LabServer
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
     * Set description
     *
     * @param string $description
     * @return LabServer
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
     * @return LabServer
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
     * Set public_sub
     *
     * @param boolean $publicSub
     * @return LabServer
     */
    public function setPublicSub($publicSub)
    {
        $this->public_sub = $publicSub;

        return $this;
    }

    /**
     * Get public_sub
     *
     * @return boolean 
     */
    public function getPublicSub()
    {
        return $this->public_sub;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return LabServer
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
     * @return LabServer
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
     * @return LabServer
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
     * Set configuration
     *
     * @param string $configuration
     * @return LabServer
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Get configuration
     *
     * @return string 
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Set opaqueData
     *
     * @param string $opaqueData
     * @return LabServer
     */
    public function setOpaqueData($opaqueData)
    {
        $this->opaqueData = $opaqueData;

        return $this;
    }

    /**
     * Get opaqueData
     *
     * @return string 
     */
    public function getOpaqueData()
    {
        return $this->opaqueData;
    }

    /**
     * Set contact_name
     *
     * @param string $contactName
     * @return LabServer
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
     * @return LabServer
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

    /**
     * Set passKey
     *
     * @param string $passKey
     * @return LabServer
     */
    public function setPassKey($passKey)
    {
        $this->passKey = $passKey;

        return $this;
    }

    /**
     * Get passKey
     *
     * @return string 
     */
    public function getPassKey()
    {
        return $this->passKey;
    }

    /**
     * Set labInfo
     *
     * @param string $labInfo
     * @return LabServer
     */
    public function setLabInfo($labInfo)
    {
        $this->labInfo = $labInfo;

        return $this;
    }

    /**
     * Get labInfo
     *
     * @return string 
     */
    public function getLabInfo()
    {
        return $this->labInfo;
    }

    /**
     * Set initialPassKey
     *
     * @param string $initialPassKey
     * @return LabServer
     */
    public function setInitialPassKey($initialPassKey)
    {
        $this->initialPassKey = $initialPassKey;

        return $this;
    }

    /**
     * Get initialPassKey
     *
     * @return string 
     */
    public function getInitialPassKey()
    {
        return $this->initialPassKey;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return LabServer
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set rlmsSpecificData
     *
     * @param string $rlmsSpecificData
     * @return LabServer
     */
    public function setRlmsSpecificData($rlmsSpecificData)
    {
        $this->rlmsSpecificData = $rlmsSpecificData;

        return $this;
    }

    /**
     * Get rlmsSpecificData
     *
     * @return string 
     */
    public function getRlmsSpecificData()
    {
        return $this->rlmsSpecificData;
    }
}
