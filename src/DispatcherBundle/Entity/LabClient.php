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
 * @ORM\Table(name="LabClient")
 */
class LabClient
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
     * @ORM\Column(type="integer")
     */
    protected $labServerId;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $clientUrl;

    /**
     * @ORM\Column(type="integer")
     */
    protected $owner_id;

    /**
     * @ORM\Column(type="datetime")
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
     * Set Guid
     *
     * @param string $guid
     * @return LabClient
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
     * @return LabClient
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
     * Set description
     *
     * @param string $description
     * @return LabClient
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
     * @return LabClient
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
     * Set dateCreated
     *
     * @param string $dateCreated
     * @return LabClient
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
     * Set labServerId
     *
     * @param integer $labServerId
     * @return LabClient
     */
    public function setLabServerId($labServerId)
    {
        $this->labServerId = $labServerId;

        return $this;
    }

    /**
     * Get labServerId
     *
     * @return integer 
     */
    public function getLabServerId()
    {
        return $this->labServerId;
    }

    /**
     * Set clientUrl
     *
     * @param string $clientUrl
     * @return LabClient
     */
    public function setClientUrl($clientUrl)
    {
        $this->clientUrl = $clientUrl;

        return $this;
    }

    /**
     * Get clientUrl
     *
     * @return string 
     */
    public function getClientUrl()
    {
        return $this->clientUrl;
    }

    public function setUpdateAll($data, $ownerId = null)
    {
        $this->labServerId = $data['labserverId'];
        $this->name = $data['name'];
        $this->Guid = $data['Guid'];
        $this->description = $data['description'];
        $this->dateCreated = new \DateTime();
        $this->clientUrl = $data['url'];

        if ($ownerId != null){
            $this->owner_id = $ownerId;
        }
    }
}
