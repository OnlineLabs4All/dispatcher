<?php
/**
 * User: Danilo G. Zutin
 * Date: 03.08.15
 * Time: 22:48
 */

// src/DispatcherBundle/Entity/Rlms.php
namespace DispatcherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="Rlms")
 */
class Rlms
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $Guid;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $institution;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $contact_name;

    /**
     * @ORM\Column(type="string", length=35, nullable=true)
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
    protected $active;


    /**
     * @ORM\Column(type="string", length=35)
     */
    protected $dateCreated;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $opaqueData; //Optional field, used to transfer additional information if necessary.

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $authPassKey;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $authCouponId;


    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $rlmsType;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $serviceUrl;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $serviceDescriptionUrl;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $rlmsSpecificData; //Optional field, used to transfer additional information if necessary.

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $username; //Username to authenticate WebLab Deusto requests

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $password; //Password to authenticate WebLab Deusto requests

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
     * @return Rlms
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
     * @return Rlms
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
     * @return Rlms
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
     * Set contact_name
     *
     * @param string $contactName
     * @return Rlms
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
     * @return Rlms
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
     * Set description
     *
     * @param string $description
     * @return Rlms
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
     * @return Rlms
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
     * @return Rlms
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
     * Set dateCreated
     *
     * @param string $dateCreated
     * @return Rlms
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return string 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set opaqueData
     *
     * @param string $opaqueData
     * @return Rlms
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
     * Set rlmsType
     *
     * @param string $rlmsType
     * @return Rlms
     */
    public function setRlmsType($rlmsType)
    {
        $this->rlmsType = $rlmsType;

        return $this;
    }

    /**
     * Get rlmsType
     *
     * @return string 
     */
    public function getRlmsType()
    {
        return $this->rlmsType;
    }

    /**
     * Set serviceUrl
     *
     * @param string $serviceUrl
     * @return Rlms
     */
    public function setServiceUrl($serviceUrl)
    {
        $this->serviceUrl = $serviceUrl;

        return $this;
    }

    /**
     * Get serviceUrl
     *
     * @return string 
     */
    public function getServiceUrl()
    {
        return $this->serviceUrl;
    }

    /**
     * Set rlmsSpecificData
     *
     * @param string $rlmsSpecificData
     * @return Rlms
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

    /**
     * Set serviceDescriptionUrl
     *
     * @param string $serviceDescriptionUrl
     * @return Rlms
     */
    public function setServiceDescriptionUrl($serviceDescriptionUrl)
    {
        $this->serviceDescriptionUrl = $serviceDescriptionUrl;

        return $this;
    }

    /**
     * Get serviceDescriptionUrl
     *
     * @return string 
     */
    public function getServiceDescriptionUrl()
    {
        return $this->serviceDescriptionUrl;
    }

    public function setAll($data, $ownerId)
    {
        $this->Guid = $data['guid'];
        $this->name = $data['name'];
        $this->institution = $data['institution'];
        $this->contact_name = $data['contact_name'];
        $this->contact_email = $data['contact_email'];
        $this->description = $data['description'];
        $this->owner_id = $ownerId;
        $this->active = $data['active'];
        $this->dateCreated = date('Y-m-d\TH:i:sP');
        //$this->authCouponId = $data['authCouponId'];
        //$this->authPassKey = $data['authPassKey'];
        $this->rlmsType = $data['rlms_type'];
        $this->serviceUrl = $data['service_url'];
        $this->serviceDescriptionUrl = $data['service_description_url'];
        $this->password = md5($data['rlms_password']);
        $this->username = $data['rlms_username'];

    }

    public function updateAll($data)
    {
        $this->name = $data['name'];
        $this->institution = $data['institution'];
        $this->contact_name = $data['contact_name'];
        $this->contact_email = $data['contact_email'];
        $this->description = $data['description'];
        $this->active = $data['active'];
        $this->serviceUrl = $data['service_url'];
        $this->serviceDescriptionUrl = $data['service_description_url'];
        if ($data['rlms_password'] != null)
        {
            $this->password = md5($data['rlms_password']);
        }
    }



    /**
     * Set username
     *
     * @param string $username
     * @return Rlms
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Rlms
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set authPassKey
     *
     * @param string $authPassKey
     * @return Rlms
     */
    public function setAuthPassKey($authPassKey)
    {
        $this->authPassKey = $authPassKey;

        return $this;
    }

    /**
     * Get authPassKey
     *
     * @return string 
     */
    public function getAuthPassKey()
    {
        return $this->authPassKey;
    }

    /**
     * Set authCouponId
     *
     * @param string $authCouponId
     * @return Rlms
     */
    public function setAuthCouponId($authCouponId)
    {
        $this->authCouponId = $authCouponId;

        return $this;
    }

    /**
     * Get authCouponId
     *
     * @return string 
     */
    public function getAuthCouponId()
    {
        return $this->authCouponId;
    }
}
