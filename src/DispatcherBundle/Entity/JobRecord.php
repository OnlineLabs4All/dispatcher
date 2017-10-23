<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/29/15
 * Time: 11:12 PM
 */
// src/DispatcherBundle/Entity/ExperimentEngine.php
namespace DispatcherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="JobRecord")
 */
class JobRecord
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $expId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $federatedExpId;

    /**
     * @ORM\Column(type="integer")
     */
    protected $labServerId; //New field: specifies the ID of the "virtual" lab server

    /**
     * @ORM\Column(type="integer")
     */
    protected $labServerOwnerId; //New field: specifies the ID of the user owner of the LS the request was submitted to

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $providerId; //ID of the provider RLMS

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $rlmsAssignedId;

    /**
     * @ORM\Column(type="integer")
     */
    protected $priority;

    /**
     * @ORM\Column(type="integer")
     */
    protected $jobStatus; // (1)QUEUED , (2)IN PROGRESS, (3)COMPLETE, (4)COMPLETED WITH ERRORS, (5)CANCELLED

    /**
     * @ORM\Column(type="string", length=35, nullable=true)
     */
    protected $submitTime; //Time when the job was submitted

    /**
     * @ORM\Column(type="string", length=35, nullable=true)
     */
    protected $executionTime; //Time when execution starts, or when job is dequeued
    /**
     * @ORM\Column(type="string", length=35, nullable=true)
     */
    protected $endTime; //Time when execution finishes or is cancelled

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $execElapsed; //Time (sec) that the experiment needed to run

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $jobElapsed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $estExecTime; //Estimated time (sec) the experiment will need to run

    /**
     * @ORM\Column(type="integer")
     */
    protected $queueAtInsert; //Job position in the queue at insert time

    /**
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $processingEngine; //Id of the engine (experiment engine) executing the experiment

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $expSpecification;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $expSpecChecksum; //checksum of experment specification

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $isFromDataset;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $expResults;

    /**
     * @ORM\Column(type="string", length=2000, nullable=true)
     */
    protected $errorReport;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $errorOccurred;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $downloaded;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $opaqueData; //Optional field, used to transfer additional information if necessary.


    /**
     * Get expId
     *
     * @return integer 
     */
    public function getExpId()
    {
        return $this->expId;
    }

    /**
     * Set labServerId
     *
     * @param integer $labServerId
     * @return JobRecord
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
     * Set providerId
     *
     * @param string $providerId
     * @return JobRecord
     */
    public function setProviderId($providerId)
    {
        $this->providerId = $providerId;

        return $this;
    }

    /**
     * Get providerId
     *
     * @return string 
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    /**
     * Set rlmsAssignedId
     *
     * @param integer $rlmsAssignedId
     * @return JobRecord
     */
    public function setRlmsAssignedId($rlmsAssignedId)
    {
        $this->rlmsAssignedId = $rlmsAssignedId;

        return $this;
    }

    /**
     * Get rlmsAssignedId
     *
     * @return integer
     */
    public function getRlmsAssignedId()
    {
        return $this->rlmsAssignedId;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return JobRecord
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer 
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set jobStatus
     *
     * @param integer $jobStatus
     * @return JobRecord
     */
    public function setJobStatus($jobStatus)
    {
        $this->jobStatus = $jobStatus;

        return $this;
    }

    /**
     * Get jobStatus
     *
     * @return integer 
     */
    public function getJobStatus()
    {
        return $this->jobStatus;
    }

    /**
     * Set submitTime
     *
     * @param string $submitTime
     * @return JobRecord
     */
    public function setSubmitTime($submitTime)
    {
        $this->submitTime = $submitTime;

        return $this;
    }

    /**
     * Get submitTime
     *
     * @return string 
     */
    public function getSubmitTime()
    {
        return $this->submitTime;
    }

    /**
     * Set executionTime
     *
     * @param string $executionTime
     * @return JobRecord
     */
    public function setExecutionTime($executionTime)
    {
        $this->executionTime = $executionTime;

        return $this;
    }

    /**
     * Get executionTime
     *
     * @return string 
     */
    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    /**
     * Set endTime
     *
     * @param string $endTime
     * @return JobRecord
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return string 
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set execElapsed
     *
     * @param integer $execElapsed
     * @return JobRecord
     */
    public function setExecElapsed($execElapsed)
    {
        $this->execElapsed = $execElapsed;

        return $this;
    }

    /**
     * Get execElapsed
     *
     * @return integer 
     */
    public function getExecElapsed()
    {
        return $this->execElapsed;
    }

    /**
     * Set jobElapsed
     *
     * @param integer $jobElapsed
     * @return JobRecord
     */
    public function setJobElapsed($jobElapsed)
    {
        $this->jobElapsed = $jobElapsed;

        return $this;
    }

    /**
     * Get jobElapsed
     *
     * @return integer 
     */
    public function getJobElapsed()
    {
        return $this->jobElapsed;
    }

    /**
     * Set estExecTime
     *
     * @param integer $estExecTime
     * @return JobRecord
     */
    public function setEstExecTime($estExecTime)
    {
        $this->estExecTime = $estExecTime;

        return $this;
    }

    /**
     * Get estExecTime
     *
     * @return integer 
     */
    public function getEstExecTime()
    {
        return $this->estExecTime;
    }

    /**
     * Set queueAtInsert
     *
     * @param integer $queueAtInsert
     * @return JobRecord
     */
    public function setQueueAtInsert($queueAtInsert)
    {
        $this->queueAtInsert = $queueAtInsert;

        return $this;
    }

    /**
     * Get queueAtInsert
     *
     * @return integer 
     */
    public function getQueueAtInsert()
    {
        return $this->queueAtInsert;
    }

    /**
     * Set processingEngine
     *
     * @param integer $processingEngine
     * @return JobRecord
     */
    public function setProcessingEngine($processingEngine)
    {
        $this->processingEngine = $processingEngine;

        return $this;
    }

    /**
     * Get processingEngine
     *
     * @return integer 
     */
    public function getProcessingEngine()
    {
        return $this->processingEngine;
    }

    /**
     * Set expSpecification
     *
     * @param string $expSpecification
     * @return JobRecord
     */
    public function setExpSpecification($expSpecification)
    {
        $this->expSpecification = $expSpecification;

        return $this;
    }

    /**
     * Get expSpecification
     *
     * @return string 
     */
    public function getExpSpecification()
    {
        return $this->expSpecification;
    }

    /**
     * Set expResults
     *
     * @param string $expResults
     * @return JobRecord
     */
    public function setExpResults($expResults)
    {
        $this->expResults = $expResults;

        return $this;
    }

    /**
     * Get expResults
     *
     * @return string 
     */
    public function getExpResults()
    {
        return $this->expResults;
    }

    /**
     * Set errorReport
     *
     * @param string $errorReport
     * @return JobRecord
     */
    public function setErrorReport($errorReport)
    {
        $this->errorReport = $errorReport;

        return $this;
    }

    /**
     * Get errorReport
     *
     * @return string 
     */
    public function getErrorReport()
    {
        return $this->errorReport;
    }

    /**
     * Set errorOccurred
     *
     * @param boolean $errorOccurred
     * @return JobRecord
     */
    public function setErrorOccurred($errorOccurred)
    {
        $this->errorOccurred = $errorOccurred;

        return $this;
    }

    /**
     * Get errorOccurred
     *
     * @return boolean 
     */
    public function getErrorOccurred()
    {
        return $this->errorOccurred;
    }

    /**
     * Set downloaded
     *
     * @param boolean $downloaded
     * @return JobRecord
     */
    public function setDownloaded($downloaded)
    {
        $this->downloaded = $downloaded;

        return $this;
    }

    /**
     * Get downloaded
     *
     * @return boolean 
     */
    public function getDownloaded()
    {
        return $this->downloaded;
    }

    /**
     * Set opaqueData
     *
     * @param string $opaqueData
     * @return JobRecord
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
     * Set labServerOwnerId
     *
     * @param integer $labServerOwnerId
     * @return JobRecord
     */
    public function setLabServerOwnerId($labServerOwnerId)
    {
        $this->labServerOwnerId = $labServerOwnerId;

        return $this;
    }

    /**
     * Get labServerOwnerId
     *
     * @return integer 
     */
    public function getLabServerOwnerId()
    {
        return $this->labServerOwnerId;
    }

    /**
     * Set expSpecChecksum
     *
     * @param string $expSpecChecksum
     * @return JobRecord
     */
    public function setExpSpecChecksum($expSpecChecksum)
    {
        $this->expSpecChecksum = $expSpecChecksum;

        return $this;
    }

    /**
     * Get expSpecChecksum
     *
     * @return string 
     */
    public function getExpSpecChecksum()
    {
        return $this->expSpecChecksum;
    }

    public function createNew($expId, $expSpec, $opaqueData, $queueLength, LabServer $labServer, $rlmsGuid, $exp_spec_hash, $jobStatus, $federatedExpId = null)
    {
        $this->setLabServerId($labServer->getId());
        $this->setLabServerOwnerId($labServer->getOwnerId());
        $this->setProviderId($rlmsGuid); //ID of the RLMS requesting execution
        $this->setRlmsAssignedId($expId);
        $this->setFederatedExpId($federatedExpId);
        $this->setPriority(0); //Set priority 0 for the moment;
        $this->setSubmitTime(date('Y-m-d\TH:i:sP'));
        $this->setEstExecTime(20); //replace with real estimation
        $this->setExpSpecification($expSpec);
        $this->setQueueAtInsert($queueLength);
        $this->setExpSpecChecksum($exp_spec_hash);
        $this->setDownloaded(false);
        $this->setErrorOccurred(false);
        $this->setProcessingEngine(-1); //no processing Engine yet assigned (-1)
        $this->setOpaqueData($opaqueData);
        $this->setJobStatus($jobStatus); //Status
        $this->setIsFromDataset(false);
    }

    public function createNewFromDataset($expId, $expSpec, $opaqueData, $queueLength, LabServer $labServer, $rlmsGuid, $exp_spec_hash, $jobStatus, JobRecord $identicalJob)
    {
        $this->setLabServerId($labServer->getId());
        $this->setLabServerOwnerId($labServer->getOwnerId());
        $this->setProviderId($rlmsGuid); //ID of the RLMS requesting execution
        $this->setRlmsAssignedId($expId);
        $this->setPriority(0); //Set priority 0 for the moment;
        $this->setSubmitTime(date('Y-m-d\TH:i:sP'));
        $this->setEstExecTime(20); //replace with real estimation
        $this->setExpSpecification($expSpec);
        $this->setQueueAtInsert($queueLength);
        $this->setExpSpecChecksum($exp_spec_hash);
        $this->setDownloaded(false);
        $this->setErrorOccurred(false);
        $this->setOpaqueData($opaqueData);
        $this->setIsFromDataset(true);

        // Set experiment results if identical is found
        $this->setJobStatus($jobStatus); //Status
        $this->setFederatedExpId($identicalJob->getFederatedExpId());
        $this->setProcessingEngine($identicalJob->getProcessingEngine());
        $this->setExecElapsed($identicalJob->getExecElapsed());
        $this->setJobElapsed($identicalJob->getJobElapsed());
        $this->setExpResults($identicalJob->getExpResults());
        $this->setErrorOccurred($identicalJob->getErrorOccurred());
        $this->setErrorReport($identicalJob->getErrorReport());
        $this->setExecutionTime($identicalJob->getExecutionTime());
        $this->setEndTime($identicalJob->getEndTime());
    }

    /**
     * Set isFromDataset
     *
     * @param boolean $isFromDataset
     * @return JobRecord
     */
    public function setIsFromDataset($isFromDataset)
    {
        $this->isFromDataset = $isFromDataset;

        return $this;
    }

    /**
     * Get isFromDataset
     *
     * @return boolean 
     */
    public function getIsFromDataset()
    {
        return $this->isFromDataset;
    }

    /**
     * Set federatedExpId
     *
     * @param integer $federatedExpId
     * @return JobRecord
     */
    public function setFederatedExpId($federatedExpId)
    {
        $this->federatedExpId = $federatedExpId;

        return $this;
    }

    /**
     * Get federatedExpId
     *
     * @return integer 
     */
    public function getFederatedExpId()
    {
        return $this->federatedExpId;
    }
}
