<?php
/**
 * User: Danilo G. Zutin
 * Date: 15.10.15
 * Time: 13:56
 */
namespace DispatcherBundle\Services;
use DispatcherBundle\Entity\JobRecord;
use DispatcherBundle\Entity\LabServer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Null;
use SimpleXMLElement;
use DOMDocument;


class GenericLabServerServices
{
    private $labServer;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $labServer = new LabServer;
    }

    public function setLabServerId($labServerId){
        //$this->labServerId = $labServerId;
        $this->labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labServerId));
    }

    public function getLabServerGuid()
    {
        return $this->labServer->getGuid();
    }

    public function getLabInfo(){
        $response = array('labInfo' => $this->labServer->getLabInfo());
        return $response;
    }

    public function getLabConfiguration(){
        //$userGroup = $params->userGroup; //can be used to return different lab configurations depending on the user group.
        $response = array('labConfiguration' => array('navmenuPhoto' => array(array('image'=> array('https://github.com/OnlineLabs4All/dispatcher/blob/master/web/img/logo_100px.png?raw=true'))),
                                                      'labConfiguration' => $this->labServer->getConfiguration()));
        //{'navmenuPhoto': [{'image': ['http://cliparts.co/cliparts/piq/Kn7/piqKn7y7T.png']}]}
        return $response;
    }

    public function getLabStatus(){

        if ($this->labServer->getActive() == true)
        {
            $response = array(
                'online' => $this->labServer->getActive(),
                'labStatusMessage' => "1:Powered up",
                'guid' => $this->labServer->getGuid());
            return $response;
        }

        $response = array(
            'online' => $this->labServer->getActive(),
            'labStatusMessage' => "0:Powered down",
            'labGuid' => $this->labServer->getGuid());
        return $response;
    }

    public function getEffectiveQueueLength($priority){

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
            ->setParameter('priority', $priority) //usually set to zero
            ->select('COUNT(job)')
            ->getQuery()
            ->getSingleScalarResult();

        $estWait = 20*$queueLength;

        $response = array('effectiveQueueLength' => $queueLength,
                          'estWait' => $estWait);
        return $response;
    }

    public function Submit($rlmsExpId, $experimentSpecification, $userGoup, $priorityHint, $rlmsGuid){

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
            ->setParameter('priority', $priorityHint)
            ->select('COUNT(job)')
            ->getQuery()
            ->getSingleScalarResult();

        $jobRecord->setLabServerId($this->labServer->getId());
        $jobRecord->setLabServerOwnerId($this->labServer->getOwnerId());
        $jobRecord->setRlmsAssignedId($rlmsExpId);
        $jobRecord->setPriority(0); //TODO: For the future, this should be configured with the RLMS to Lab Server Mapping table
        $jobRecord->setJobStatus(1); //Status 1(QUEUED)
        $jobRecord->setSubmitTime(date('Y-m-d\TH:i:sP'));
        $jobRecord->setEstExecTime(20); //replace with real estimation
        $jobRecord->setQueueAtInsert($queueLength);
        $jobRecord->setExpSpecification($experimentSpecification);
        $jobRecord->setProviderId($rlmsGuid); //ID of the RLMS requesting execution
        $jobRecord->setDownloaded(false);
        $jobRecord->setErrorOccurred(false);
        $jobRecord->setProcessingEngine(-1); //no processing Engine yet assigned (-1)
        $jobRecord->setOpaqueData(json_encode(array('userGroup' => $userGoup)));

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

        $response = array('vReport' => array('accepted' => $accepted,
            'warningMessages' => array('string' => $warningMessage),
            'errorMessage' => $errorMessage,
            'estRuntime' => $estRuntime),
            'experimentID' => $experimentID,
            'minTimeToLive' => $minTimeToLive,
            'wait' => array('effectiveQueueLength' => $queueLength,
                'estWait' => $estWait));
        return $response;
    }

    public function getExperimentStatus($experimentId, $rlmsGuid)
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId, 'providerId' => $rlmsGuid));

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

        $response = array('statusCode'=> $statusCode,
                          'effectiveQueueLength'=> $effectiveQueueLength);
        return $response;
    }

    public function retrieveResult($experimentId, $rlmsGuid)
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId, 'providerId' => $rlmsGuid));

        $statusCode = $jobRecord->getJobStatus();

        if ($statusCode != 3){
            $experimentResults = $jobRecord->getExpResults();
            $xmlResultExtension = Null;
            $xmlBlobExtension = Null;
            $warningMessages = Null;
            $errorMessage = Null;
            $errorMessage = 'Results not available. Experiment is not completed or has been cancelled. See status code.';
        }
        else{
            //$opaque = json_decode($jobRecord->getOpaqueData());
            $experimentResults = $jobRecord->getExpResults();
            $xmlBlobExtension = Null;
            $warningMessages = Null;
            $errorMessage = Null;

            //set job record as downloaded
            $jobRecord->setDownloaded(true);
            $this->em->persist($jobRecord);
            $this->em->flush();
        }

        $response = array('statusCode' => $statusCode,
                          'experimentResults' => $experimentResults,
                         'errorMessage' => $errorMessage);
        return $response;

    }
    //TODO: Retunr experiment medatada, like elapsed time, execution engine, etc.
    public function getExperimentMetadata($experimentId, $rlmsId)
    {

    }

}