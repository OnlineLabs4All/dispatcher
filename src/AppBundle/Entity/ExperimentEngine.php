<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/11/15
 * Time: 3:20 PM
 */

// src/AppBundle/Entity/ExperimentEngine.php
namespace AppBundle\Entity;

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
     * @ORM\Column(type="string", length=500)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $owner_id;


    /**
     * @ORM\Column(type="boolean")
     */
    protected $active;

    /**
     * @ORM\Column(type="datetimetz")
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
     * @param string $ownerId
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
     * @return string 
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
}
