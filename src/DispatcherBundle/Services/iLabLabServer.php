<?php
/**
 * Created by PhpStorm.
 * User: Danilo G. Zutin
 * Date: 5/1/15
 * Time: 11:17 AM
 */
// src/DispatcherBundle/Services/iLabLabServer.php
namespace DispatcherBundle\Services;
use DispatcherBundle\Entity\JobRecord;
use Doctrine\ORM\EntityManager;


class iLabLabServer
{
    private $em;
    private $labServer;//object of class LabServer
    private $rlmsGuid;
    //private $labServerId;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function AuthHeader($Header)
    {
        $this->rlmsGuid =  $Header->identifier;
        $passkey = $Header->passKey;


        //check the database for the SB GUID and PassKey
        //if (($sbGuid != "9954C5B79AEB432A94DE29E6EE44EB6") && ($passkey != "366497578876928") )
        //return new \SoapFault("Server", "Wrong SB identifier and/or Passkey" );
    }
    //This is not a SOAP Method
    public function setLabServerId($labServerId){
        //$this->labServerId = $labServerId;
        $this->labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labServerId));

    }

    public function GetLabInfo(){
        $response = array('GetLabInfoResult' => $this->labServer->getLabInfo());
        return $response;
    }

    public function GetLabStatus(){

        $response = array('GetLabStatusResult' => array(
                                                  'online' => $this->labServer->getActive(),
                                                  'labStatusMessage' => "1:Powered down"));
        return $response;
    }

    public function GetLabConfiguration($params){
        //$userGroup = $params->userGroup; //can be used to return different lab configurations depending on the user group.
        $response = array('GetLabConfigurationResult' => $this->labServer->getConfiguration());
        return $response;
    }

    public function Submit($params){

        $jobRecord = new JobRecord();

        $jobRecord->setLabServerId($this->labServer->getId());
        $jobRecord->setProviderId($this->labServer->getId());
        $jobRecord->setRlmsAssignedId($params->experimentID);
        $jobRecord->setPriority($params->priorityHint);
        $jobRecord->setJobStatus(1); //Status 1(QUEUED)
        $jobRecord->setSubmitTime(date('Y-m-d H:i:s'));
        $jobRecord->setEstExecTime(60); //replace with real estimation
        $jobRecord->setQueueAtInsert(1);//replace with real queue length
        $jobRecord->setExpSpecification($params->experimentSpecification);
        $jobRecord->setProviderId('testRLMS'); //ID of the RLMS requesting execution
        $jobRecord->setDownloaded(false);
        $jobRecord->setErrorOccurred(false);
        $jobRecord->setOpaqueData(json_encode(array('userGroup' => $params->userGroup)));

        $this->em->persist($jobRecord);
        $this->em->flush();

        //Create experiment submission report
        $response = array('SubmitResult' => array('vReport' => array('accepted' => true,
                                                                     'warningMessages' => array('string' =>''),
                                                                     'errorMessage' => '',
                                                                     'estRuntime' => 60),
                                                  'labExperimentID' => $jobRecord->getExpId(),
                                                  'minTimeToLive' => 7200,
                                                  'wait' => array('effectiveQueueLength' => 1,
                                                                  'estWait' => 120)));
        return $response;

    }


}