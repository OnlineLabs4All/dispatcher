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

        $repository = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord');

        //get the length of the queue considering the job priority
        $queueLength = $repository->createQueryBuilder('job')
            ->where('job.jobStatus = :jobStatus')
            ->andWhere('job.labServerId = :labServerId')
            ->andWhere('job.priority >= :priority')
            ->setParameter('jobStatus', 1) //
            ->setParameter('labServerId',$this->labServer->getId())
            ->setParameter('priority', $params->priorityHint)
            ->select('COUNT(job)')
            ->getQuery()
            ->getSingleScalarResult();

        $jobRecord->setLabServerId($this->labServer->getId());
        $jobRecord->setProviderId($this->rlmsGuid);
        $jobRecord->setRlmsAssignedId($params->experimentID);
        $jobRecord->setPriority($params->priorityHint);
        $jobRecord->setJobStatus(1); //Status 1(QUEUED)
        $jobRecord->setSubmitTime(date('Y-m-d H:i:s'));
        $jobRecord->setEstExecTime(20); //replace with real estimation
        $jobRecord->setQueueAtInsert($queueLength);//replace with real queue length
        $jobRecord->setExpSpecification($params->experimentSpecification);
        $jobRecord->setProviderId($this->rlmsGuid); //ID of the RLMS requesting execution
        $jobRecord->setDownloaded(false);
        $jobRecord->setErrorOccurred(false);
        $jobRecord->setOpaqueData(json_encode(array('userGroup' => $params->userGroup)));

        $this->em->persist($jobRecord);
        $this->em->flush();

        //Create experiment submission report
        $accepted = true;
        $warningMessage =  '';
        $errorMessage = '';
        $estRuntime = $jobRecord->getEstExecTime();
        $experimentID = $jobRecord->getExpId();
        $minTimeToLive = 7200;
        $estWait = $queueLength * $jobRecord->getEstExecTime();

        $response = array('SubmitResult' => array('vReport' => array('accepted' => $accepted,
                                                                     'warningMessages' => array('string' => $warningMessage),
                                                                     'errorMessage' => $errorMessage,
                                                                     'estRuntime' => $estRuntime),
                                                  'experimentID' => $experimentID,
                                                  'minTimeToLive' => $minTimeToLive,
                                                  'wait' => array('effectiveQueueLength' => $queueLength,
                                                                  'estWait' => $estWait)));
        return $response;
    }

    public function GetExperimentStatus($params){

        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('rlmsAssignedId' => $params->experimentID, 'providerId' => $this->rlmsGuid));

        $repository = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord');

        //get the length of the queue considering the job priority
        $queueLength = $repository->createQueryBuilder('job')
            ->where('job.jobStatus = :jobStatus')
            ->andWhere('job.expId < :expId')
            ->andWhere('job.labServerId = :labServerId')
            ->andWhere('job.priority >= :priority')
            ->setParameter('jobStatus', 1)
            ->setParameter('expId', $jobRecord->getExpId())
            ->setParameter('labServerId', $this->labServer->getId())
            ->setParameter('priority', $jobRecord->getPriority())
            ->select('COUNT(job)')
            ->getQuery()
            ->getSingleScalarResult();

            $statusCode = $jobRecord->getJobStatus();
            $effectiveQueueLength = $queueLength; //get the length of the queue considering the job priority
            $estRuntime = $jobRecord->getEstExecTime();
            $estWait = $estRuntime * $queueLength;
            $estRemainingRuntime =  $jobRecord->getEstExecTime() -  $jobRecord->getExecElapsed();
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
            //$opaque = json_decode($jobRecord->getOpaqueData());
            $experimentResults = $jobRecord->getExpResults();
            $xmlResultExtension = 'SubmitTime='.$jobRecord->getSubmitTime().',
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

    public function GetEffectiveQueueLength($params){

        $repository = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord');

        //get the length of the queue considering the job priority
        $queueLength = $repository->createQueryBuilder('job')
            ->where('job.jobStatus = :jobStatus')
            ->andWhere('job.labServerId = :labServerId')
            ->andWhere('job.priority >= :priority')
            ->setParameter('jobStatus', 1)
            ->setParameter('labServerId', $this->labServer->getId())
            ->setParameter('priority', $params->priorityHint)
            ->select('COUNT(job)')
            ->getQuery()
            ->getSingleScalarResult();

        $estWait = '';

        $response = array('GetEffectiveQueueLengthResult' => array('effectiveQueueLength' => $queueLength,
                                                                   'estWait' => $estWait));
        return $response;
    }

    public function Cancel($params){

        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('rlmsAssignedId' => $params->experimentID, 'providerId' => $this->rlmsGuid));

        $statusCode = $jobRecord->getJobStatus();

        if ($statusCode != 2){ //what to do if experiment is not being executed (2 = IN PROGRESS)
            $CancelResult = true;
            $jobRecord->setJobStatus(5);
            $this->em->persist($jobRecord);
            $this->em->flush();
        }
        else{//what to do if experiment is running
            $CancelResult = false;
        }

        $response = array('CancelResult' => $CancelResult);
        return $response;
    }

    public function Validate($params){

        //Create experiment submission report
        $accepted = true;
        $warningMessage =  '';
        $errorMessage = '';
        $estRuntime = 20;

        $response = array('ValidateResult' => array('accepted' => $accepted,
                                                    'warningMessages' => array('string' => $warningMessage),
                                                    'errorMessage' => $errorMessage,
                                                    'estRuntime' => $estRuntime));
        return $response;
    }

}