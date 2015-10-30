<?php
/**
 * User: Danilo G. Zutin
 * Date: 05.10.15
 * Time: 18:10
 */

// src/DispatcherBundle/Entity/LabSession.php
namespace DispatcherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="LabSession")
 */
class LabSession
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $session_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $labServerId;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    protected $rlmsId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $start_date;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $end_date;


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
     * Set session_id
     *
     * @param string $sessionId
     * @return LabSession
     */
    public function setSessionId($sessionId)
    {
        $this->session_id = $sessionId;
        return $this;
    }

    /**
     * Get session_id
     *
     * @return string 
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Set labServerId
     *
     * @param integer $labServerId
     * @return LabSession
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
     * Set labServer_guid
     *
     * @param string $labServerGuid
     * @return LabSession
     */
    public function setLabServerGuid($labServerGuid)
    {
        $this->labServer_guid = $labServerGuid;

        return $this;
    }

    /**
     * Get labServer_guid
     *
     * @return string 
     */
    public function getLabServerGuid()
    {
        return $this->labServer_guid;
    }

    /**
     * Set start_date
     *
     * @param string $startDate
     * @return LabSession
     */
    public function setStartDate($startDate)
    {
        $this->start_date = $startDate;

        return $this;
    }

    /**
     * Get start_date
     *
     * @return string 
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Set end_date
     *
     * @param string $endDate
     * @return LabSession
     */
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;
        return $this;
    }

    /**
     * Get end_date
     *
     * @return string 
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    //additional class methods
    public function createRlmsSession($rlmsId, $startDate, $endDate)
    {
        $this->end_date = $endDate;
        $this->start_date = $startDate;
        $this->rlmsId  = $rlmsId;
        $this->session_id = md5(microtime().rand());
        return $this->session_id;
    }


    /**
     * Set rlmsId
     *
     * @param string $rlmsId
     * @return LabSession
     */
    public function setRlmsId($rlmsId)
    {
        $this->rlmsId = $rlmsId;

        return $this;
    }

    /**
     * Get rlmsId
     *
     * @return string 
     */
    public function getRlmsId()
    {
        return $this->rlmsId;
    }
}
