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
use Symfony\Component\Validator\Constraints\Null;


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
        $jobRecord->setProviderId($this->rlmsGuid); //ID of the RLMS requesting execution
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
                                                  'experimentID' => $jobRecord->getExpId(),
                                                  'minTimeToLive' => 7200,
                                                  'wait' => array('effectiveQueueLength' => 1,
                                                                  'estWait' => 120)));
        return $response;
    }

    public function GetExperimentStatus($params){

        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('rlmsAssignedId' => $params->experimentID, 'providerId' => $this->rlmsGuid));

            $statusCode = $jobRecord->getJobStatus();
            $effectiveQueueLength = 2;
            $estWait = 32;
            $estRuntime = 15;
            $estRemainingRuntime = 54;
            $minTimetoLive= 7200;

            $response = array('GetExperimentStatusResult' => array(
                'statusReport' => array('statusCode' =>  $statusCode,
                                        'wait' => array('effectiveQueueLength' => $effectiveQueueLength,
                                                        'estWait' => $estWait),
                                        'estRuntime' => $estRuntime,
                                        'estRemainingRuntime' => $estRemainingRuntime),
                'minTimetoLive' => $minTimetoLive));
            return $response;
    }

    public function RetrieveResult($params){

        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('rlmsAssignedId' => $params->experimentID, 'providerId' => $this->rlmsGuid));

        $statusCode = $jobRecord->getJobStatus();

        if ($statusCode != 3){
            $experimentResults = $jobRecord->getExpResults();
            $xmlResultExtension = Null;
            $xmlBlobExtension = Null;
            $warningMessages = Null;
            $errorMessage = Null;
            $errorMessage = 'Results not available. Experiment is not completed or has been cancelled.';
        }
        else{
            $opaque = json_decode($jobRecord->getOpaqueData());
            $experimentResults = $jobRecord->getExpResults();
            $xmlResultExtension = 'UserGroup='.$opaque['userGroup'].' ,
                                   SubmitTime='.$jobRecord->getSubmitTime().',
                                   ExecutionTime='.$jobRecord->getExecutionTime().',
                                   EndTime='.$jobRecord->getExecutionTime().',
                                   ElapsedExecutionTime='.$jobRecord->getExecElapsed().',
                                   ElapsedJobTime='.$jobRecord->getJobElapsed();
            $xmlBlobExtension = Null;
            $warningMessages = Null;
            $errorMessage = Null;
        }

        $response = array('RetrieveResultResult' => array('statusCode' => $statusCode,
                                                          'experimentResults' => $experimentResults,
                                                          'xmlResultExtension' => $xmlResultExtension,
                                                          'xmlBlobExtension' => $xmlBlobExtension,
                                                          'warningMessages' => $warningMessages,
                                                          'errorMessage' => $errorMessage)

                         );
        return $response;
    }

}