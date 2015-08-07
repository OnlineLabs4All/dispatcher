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
use DispatcherBundle\Entity\LabServer;
use DispatcherBundle\Entity\LsToRlmsMapping;
use DispatcherBundle\Entity\Rlms;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Null;
use DispatcherBundle\Security\IsaRlmsAuthenticator;


class iLabLabServer
{
    private $em;
    private $labServer;//object of class LabServer
    private $rlmsGuid;
    private $brokerAuthenticator;
    private $serviceUrl;
    //private $labServerId;

    public function __construct(EntityManager $em, IsaRlmsAuthenticator $brokerAuthenticator)
    {
        $this->em = $em;
        $this->brokerAuthenticator = $brokerAuthenticator;
    }

    //This is not a SOAP Method
    public function setLabServerId($labServerId){
        //$this->labServerId = $labServerId;
        $this->labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labServerId));
    }

    public function  setServiceUrl($url)
    {
        $this->serviceUrl = $url;
    }

    public function AuthHeader($Header)
    {

        $authResponse = $this->brokerAuthenticator->authenticateBatchedMethod($Header->identifier, $Header->passKey, $this->labServer->getId());
        if ($authResponse['authenticated'] == false)
        {
            return new \SoapFault("Server", $authResponse['fault'] );
        }
        $this->rlmsGuid = $Header->identifier;

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
        $jobRecord->setSubmitTime(date('Y-m-d\TH:i:sP'));
        $jobRecord->setEstExecTime(20); //replace with real estimation
        $jobRecord->setQueueAtInsert($queueLength);//replace with real queue length
        $jobRecord->setExpSpecification($params->experimentSpecification);
        $jobRecord->setProviderId($this->rlmsGuid); //ID of the RLMS requesting execution
        $jobRecord->setDownloaded(false);
        $jobRecord->setErrorOccurred(false);
        $jobRecord->setProcessingEngine(-1); //no processing Engine yet assigned (-1)
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

            //set job record as downloaded
            $jobRecord->setDownloaded(true);
            $this->em->persist($jobRecord);
            $this->em->flush();
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
            ->setParameter('jobStatus', 1)//STATUS 1 = QUEUED
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

// ========================================================================
//          ISA Generic Process Agent Services
//=========================================================================

    public function InitAuthHeader($header)
    {
        //$initPasskey = $header->initPasskey;
        $authResponse = $this->brokerAuthenticator->authenticateInstallDomainCredentialsMethod($header->initPasskey, $this->labServer->getId());
        if ($authResponse['authenticated'] == false)
        {
            return new \SoapFault("Server", $authResponse['fault'] );
        }
    }

    public function AgentAuthHeader($header)
    {
        $authResponse = $this->brokerAuthenticator->authenticateAgent($header->agentGuid, $this->labServer->getId());
        if ($authResponse['authenticated'] == false)
        {
            return new \SoapFault("Server", $authResponse['fault'] );
        }
    }

    public function InstallDomainCredentials($params)
    {
        $broker = $this
            ->em
            ->getRepository('DispatcherBundle:Rlms')
            ->findOneBy(array('Guid' => $params->service->agentGuid, 'owner_id' => $this->labServer->getOwnerId()));

        if ($broker != null)
        {
            $broker->setActive(true);
        }
        else
        {
            $broker = new Rlms();
            $broker->setGuid($params->service->agentGuid);
            $broker->setName($params->service->agentName);
            $broker->setOwnerId($this->labServer->getOwnerId());
            $broker->setActive(true);
            $broker->setDateCreated(date('Y-m-d\TH:i:sP'));
            $broker->setRlmsType('ISA');
            $broker->setServiceUrl($params->service->webServiceUrl);

        }
        //Persist Broker to the database
        $this->em->persist($broker);
        $this->em->flush();
        //Set mapping info between service broker and lab server
        $newLsBrokerMapping = new LsToRlmsMapping();
        $newLsBrokerMapping->setRlmsId($broker->getId());
        $newLsBrokerMapping->setLabServerId($this->labServer->getId());
        $this->em->persist($newLsBrokerMapping);
        $this->em->flush();

        //Save RLMS specific date into the database of the Lab Server
        $rlmsSpecificData = array('type' => $params->service->type,
            'domainGuid' => $params->service->domainGuid,
            'codeBaseUrl' => $params->service->codeBaseUrl,
            'inIdentCoupon' => $params->inIdentCoupon,
            'outIdentCoupon' => $params->outIdentCoupon);

        $this->labServer->setRlmsSpecificData(json_encode($rlmsSpecificData));
        //persist the date to lab server db
        $this->em->persist($this->labServer);
        $this->em->flush();


        //assemble response of SOAP method
        $response = array('InstallDomainCredentialsResult'=>array('agentGuid'=> $this->labServer->getGuid(),
                                                                  'agentName'=> $this->labServer->getName(),
                                                                  'type'=> 'LAB SERVER',
                                                                  'domainGuid'=> $this->rlmsGuid,
                                                                  'codeBaseUrl'=> $this->serviceUrl,
                                                                  'webServiceUrl'=> $this->serviceUrl));
        return $response;
    }

    // @TODO - Implement Register method: update service broker DB. etc
    public function Register($param)
    {


        $response = array();
    }

}
/*
 * <?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Header>
    <AgentAuthHeader xmlns="http://ilab.mit.edu/iLabs/type">
      <agentGuid>string</agentGuid>
    </AgentAuthHeader>
  </soap:Header>
  <soap:Body>
    <Register xmlns="http://ilab.mit.edu/iLabs/Services">
      <registerGuid>string</registerGuid>
      <info>
        <ServiceDescription>
          <serviceProviderInfo xmlns="http://ilab.mit.edu/iLabs/type">string</serviceProviderInfo>
          <coupon xmlns="http://ilab.mit.edu/iLabs/type">
            <couponId>long</couponId>
            <issuerGuid>string</issuerGuid>
            <passkey>string</passkey>
          </coupon>
          <consumerInfo xmlns="http://ilab.mit.edu/iLabs/type">string</consumerInfo>
        </ServiceDescription>
        <ServiceDescription>
          <serviceProviderInfo xmlns="http://ilab.mit.edu/iLabs/type">string</serviceProviderInfo>
          <coupon xmlns="http://ilab.mit.edu/iLabs/type">
            <couponId>long</couponId>
            <issuerGuid>string</issuerGuid>
            <passkey>string</passkey>
          </coupon>
          <consumerInfo xmlns="http://ilab.mit.edu/iLabs/type">string</consumerInfo>
        </ServiceDescription>
      </info>
    </Register>
  </soap:Body>
</soap:Envelope>
 */
