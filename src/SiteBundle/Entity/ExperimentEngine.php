<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/11/15
 * Time: 3:20 PM
 */

// src/SiteBundle/Entity/ExperimentEngine.php
namespace SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    protected $eeName;

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
     * @ORM\Column(type="date")
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
     * Set httpAuthorization
     *
     * @param string $httpAuthorization
     * @return ExperimentEngine
     */
    public function setHttpAuthorization($httpAuthorization)
    {
        $this->httpAuthorization = $httpAuthorization;

        return $this;
    }

    /**
     * Get httpAuthorization
     *
     * @return string 
     */
    public function getHttpAuthorization()
    {
        return $this->httpAuthorization;
    }

    /**
     * Set eeName
     *
     * @param string $eeName
     * @return ExperimentEngine
     */
    public function setEeName($eeName)
    {
        $this->eeName = $eeName;

        return $this;
    }

    /**
     * Get eeName
     *
     * @return string 
     */
    public function getEeName()
    {
        return $this->eeName;
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
     * Set owner
     *
     * @param string $owner
     * @return ExperimentEngine
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return string 
     */
    public function getOwner()
    {
        return $this->owner;
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
