<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 21.04.17
 * Time: 18:46
 */
// src/DispatcherBundle/Services/iLabServiceBroker.php
namespace DispatcherBundle\Services;

use Doctrine\ORM\EntityManager;
use DispatcherBundle\Entity\LabSession;
use DispatcherBundle\Security\IsaRlmsAuthenticator;
use Symfony\Component\Security\Acl\Exception\Exception;


class iLabServiceBroker
{
    private $em;
    private $labServerId;//object of class LabServer
    private $rlmsGuid;
    private $brokerAuthenticator;
    private $serviceUrl;
    private $authorityId;
    private $lsServices;


    //private $labServerId;

    public function __construct(EntityManager $em, GenericLabServerServices $lsServices)
    {
        $this->em = $em;
        $this->lsServices = $lsServices;
    }

    public function  setServiceUrl($url)
    {
        $this->serviceUrl = $url;
    }

    public function getLabServerByGuid($labServerId)
    {
        $labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('Guid' => $labServerId));

        if ($labServer == null){
            return array('exception' => true,
                'message' => 'Provided lab server GUID is incorrect or you do not have permissions to access this lab server');

        }
        $mapping = $this
            ->em
            ->getRepository('DispatcherBundle:LsToRlmsMapping')
            ->findOneBy(array('rlmsId' => $this->authorityId, 'labServerId' => $labServer->getId()));

        if ($this->authorityId != null){
            if ($mapping == null){
                return array('exception' => true,
                    'message' => 'The authority for which this session was issued does not have permissions to access this lab server.');
            }
        }

        return array('exception' => false,
            'labServer' => $labServer,
            'message' => 'Lab Server found');
    }

    // Authenticate call to the SB batch API
    public function sbAuthHeader($sbHeader)
    {
        $labSession = $this
            ->em
            ->getRepository('DispatcherBundle:LabSession')
            ->findOneBy(array('couponId' => $sbHeader->couponID, 'passkey' => $sbHeader->couponPassKey));

        if ($labSession == null){

            return new \SoapFault("Server", 'Invalid session: Provided credentials are not valid' );
        }

        $now = new \DateTime();
        if ($now < $labSession->getStartDate() OR $now > $labSession->getEndDate()){
            return new \SoapFault("Server", 'Invalid session: Session has expired' );
        }
        $this->labServerId = $labSession->getLabServerId();
        $this->authorityId = $labSession->getAuthorityId();
        //$this->couponID = $sbHeader->couponID;
        //$this->couponPassKey = $sbHeader->couponPassKey;
    }

    // Authenticate requests to LaunchLab service
    public function OperationAuthHeader($header)
    {
        $couponId = $header->coupon->couponId;
        $passkey = $header->coupon->passkey;

        $authority = $this
            ->em
            ->getRepository('DispatcherBundle:Rlms')
            ->findOneBy(array('authPassKey' => $passkey, 'authCouponId' => $couponId));


        if ($authority == null){
            return new \SoapFault("Server", 'Could not authenticate authority. CouponId or passkey are incorrect' );
        }
        //set authority ID
        $this->authorityId = $authority->getId();

    }

    public function GetLabInfo($params)
    {
        //get the lab server for GUID
        $labServerResp = $this->getLabServerByGuid($params->labServerID);
        if ($labServerResp['exception']){
            return new \SoapFault("Server", $labServerResp['message'] );
        }

        $labInfoRes = $this->lsServices->getLabInfo($labServerResp['labServer']->getId());

        if ($labInfoRes['exception']){
            return new \SoapFault("Server", $labInfoRes['message'] );
        }

        return array('GetLabInfoResult' => $labInfoRes['result']);
    }

    public function GetLabStatus($params){

        //get the lab server for GUID
        $labServerResp = $this->getLabServerByGuid($params->labServerID);
        if ($labServerResp['exception']){
            return new \SoapFault("Server", $labServerResp['message'] );
        }

        $response = array('GetLabStatusResult' => array(
            'online' => $labServerResp['labServer']->getActive(),
            'labStatusMessage' => "1:Powered up"));
        return $response;
    }

    public function GetLabConfiguration($params){

        //get the lab server for GUID
        $labServerResp = $this->getLabServerByGuid($params->labServerID);
        if ($labServerResp['exception']){
            return new \SoapFault("Server", $labServerResp['message'] );
        }

        //$response = array('GetLabConfigurationResult' => $labServerResp['labServer']->getConfiguration());
        $labConfigResp = $this->lsServices->getLabConfiguration($labServerResp['labServer']->getId());
        
        if ($labConfigResp['exception']){
            return new \SoapFault("Server", $labConfigResp['message'] );
        }

        $response = array('GetLabConfigurationResult' => $labConfigResp['labConfiguration']['labConfiguration']);

        return $response;
    }
    
    public function Submit($params)
    {
        //get the lab server for GUID
        $labServerResp = $this->getLabServerByGuid($params->labServerID);
        if ($labServerResp['exception']){
            return new \SoapFault("Server", $labServerResp['message'] );
        }

        $submitReport = $this->lsServices->submit(null, $params->experimentSpecification, 'Authority name', $params->priorityHint, $this->authorityId, $labServerResp['labServer']->getId());

        return array('SubmitResult' => $submitReport);
    }

    public function GetExperimentStatus($params)
    {
        $statusReport = $this->lsServices->getExperimentStatus($params->experimentID);

        if ($statusReport['exception']){
            return new \SoapFault("Server", $statusReport['message'] );
        }

        $response = array('GetExperimentStatusResult' => array(
            'statusReport' => array('statusCode' =>  $statusReport['statusCode'],
                'wait' => array('effectiveQueueLength' => $statusReport['effectiveQueueLength'],
                    'estWait' => $statusReport['estWait']),
                'estRuntime' => $statusReport['estRuntime'],
                'estRemainingRuntime' => $statusReport['estRemainingRuntime']),
            'minTimetoLive' => $statusReport['minTimetoLive']));
        return $response;
    }

    public function RetrieveResult($params)
    {
        $resultsReport = $this->lsServices->retrieveResult($params->experimentID);

        if ($resultsReport['exception']){
            return new \SoapFault("Server", $resultsReport['message'] );
        }

        return array('RetrieveResultResult' => array('statusCode' => $resultsReport['statusCode'],
            'experimentResults' => $resultsReport['experimentResults'],
            'xmlResultExtension' => $resultsReport['xmlResultExtension'],
            'xmlBlobExtension' => $resultsReport['xmlBlobExtension'],
            'warningMessages' => $resultsReport['warningMessages'],
            'errorMessage' => $resultsReport['errorMessage'])
        );
    }

    public function GetEffectiveQueueLength($params)
    {
        //get the lab server for GUID
        $labServerResp = $this->getLabServerByGuid($params->labServerID);
        if ($labServerResp['exception']){
            return new \SoapFault("Server", $labServerResp['message'] );
        }

        $repository = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord');

        //get the length of the queue considering the job priority
        $queueLength = $repository->createQueryBuilder('job')
            ->where('job.jobStatus = :jobStatus')
            ->andWhere('job.labServerId = :labServerId')
            ->andWhere('job.priority >= :priority')
            ->setParameter('jobStatus', 1)//STATUS 1 = QUEUED
            ->setParameter('labServerId', $labServerResp['labServer']->getId())
            ->setParameter('priority', $params->priorityHint)
            ->select('COUNT(job)')
            ->getQuery()
            ->getSingleScalarResult();

        $estWait = '';

        $response = array('GetEffectiveQueueLengthResult' => array('effectiveQueueLength' => $queueLength,
            'estWait' => $estWait));
        return $response;
    }

    public function Cancel($params)
    {
        $cancelResult = $this->lsServices->cancelExperiment($params->experimentID);
        return array('CancelResult' => $cancelResult['cancelled']);
    }

    public function Validate($params)
    {

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

    public function LaunchLabClient($params)
    {

        //try to find the lab client for the provided GUID
        $labClient = $this
            ->em
            ->getRepository('DispatcherBundle:LabClient')
            ->findOneBy(array('Guid' => $params->clientGuid));

        if ($labClient == null){
            return new \SoapFault("Server", 'Lab Client not found' );
        }

        $labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labClient->getLabServerId()));

        if ($labServer == null){
            return new \SoapFault("Server", 'The lab server you are trying to access does not exist' );
        }

        $mapping = $this
            ->em
            ->getRepository('DispatcherBundle:LsToRlmsMapping')
            ->findOneBy(array('rlmsId' => $this->authorityId, 'labServerId' => $labClient->getLabServerId()));

        if ($mapping == null){
            return new \SoapFault("Server", 'This authority does not have permissions to use this lab server' );
        }

        if ($params->duration == '-1'){
            $session_duration = '604800';
        }
        else{
            $session_duration = (int)$params->duration;
        }

        $startDate = new \DateTime();
        $endDate = new \DateTime();
        $endDate->add( new \DateInterval('PT'.$session_duration.'S'));
        $labSession = new LabSession();
        $session = $labSession->createSession($labClient->getLabServerId(), $this->authorityId, $startDate, $endDate);

        try{
            $this->em->persist($labSession);
            $this->em->flush();
        }
        catch (Exception $e){
            return new \SoapFault("Server", $e->getMessage() );
        }

        $response = array('LaunchLabClientResult' => array(
            'id' => $session['couponId'],
            'tag' => $labClient->getClientUrl().'?coupon_id='.$session['couponId'].'&passkey='.$session['passkey'].'&labServerGuid='.$labServer->getGuid()));

        return $response;
    }

    }