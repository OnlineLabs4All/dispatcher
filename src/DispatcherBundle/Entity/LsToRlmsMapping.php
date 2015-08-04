<?php
/**
 * User: Danilo G. Zutin
 * Date: 04.08.15
 * Time: 08:28
 */

// src/DispatcherBundle/Entity/LsToRlmsMapping.php
namespace DispatcherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="LsToRlmsMapping")
 */
class LsToRlmsMapping
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $labServerId; //New field: specifies the ID of the "virtual" lab server

    /**
     * @ORM\Column(type="integer")
     */
    protected $rlmsId; //New field: specifies the ID of the "virtual" lab server


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
     * Set labServerId
     *
     * @param integer $labServerId
     * @return LsToRlmsMapping
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
     * Set rlmsId
     *
     * @param integer $rlmsId
     * @return LsToRlmsMapping
     */
    public function setRlmsId($rlmsId)
    {
        $this->rlmsId = $rlmsId;

        return $this;
    }

    /**
     * Get rlmsId
     *
     * @return integer 
     */
    public function getRlmsId()
    {
        return $this->rlmsId;
    }
}
