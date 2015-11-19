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
                'labStatusMessage' => "1:Powered up");
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

    public function Submit($rlmsExpId, $experimentSpecification, $opaqueData, $priorityHint, $rlmsGuid){

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

        //calculate hash of experiment specification
        $exp_spec_hash = hash('sha256', $experimentSpecification);
        $expResults = null;
        $jobStatus = 1;
        $dataset = $this->labServer->getUseDataset();

        if ($dataset == true){

            $identicalJob = $this
                ->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findOneBy(array('expSpecChecksum' => $exp_spec_hash,
                    'labServerId' => $this->labServer->getId(),
                    'jobStatus' => 3));

            //if identical experiment if found double check if experiment specification is really identical to
            // avoid returning wrong results in the unlikely case of collision of the hash algorithm
            if (($identicalJob != null) && ($identicalJob->getExpSpecification() == $experimentSpecification)){
                $jobStatus = 3; //set job status to complete if identical record is found

                $jobRecord->createNewFromDataset($rlmsExpId, $experimentSpecification, $opaqueData, $queueLength, $this->labServer, $rlmsGuid, $exp_spec_hash, $jobStatus, $identicalJob);
            }
            else{
                $jobRecord->createNew($rlmsExpId, $experimentSpecification, $opaqueData, $queueLength, $this->labServer, $rlmsGuid, $exp_spec_hash, $jobStatus);
            }
        }
        else{
            $jobRecord->createNew($rlmsExpId, $experimentSpecification, $opaqueData, $queueLength, $this->labServer, $rlmsGuid, null, $jobStatus);
        }

        /*

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
        $jobRecord->setOpaqueData($opaqueData);
*/

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

    public function retrieveResult($experimentId, $providerId) //providerId can be RLMS GUID or internal ID
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId, 'providerId' => $providerId));

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

    public function retrieveExperimentSpecification($experimentId, $providerId) //providerId can be RLMS GUID or internal ID
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId, 'providerId' => $providerId));

        if ( $jobRecord != null){

            $statusCode = $jobRecord->getJobStatus();
            $response = array('statusCode' => $statusCode,
                              'experimentResults' => $jobRecord->getExpResults(),
                              'experimentSpecification' => $jobRecord->getExpSpecification());
        }
        else{
            $response = array('statusCode' => '',
                              'experimentResults' => '',
                              'errorMessage' => 'Experiment not found');
        }

        return $response;

    }

    public function cancelExperiment($experimentId)
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId));

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
        $response = array('cancelled' => $CancelResult);
        return $response;
    }

    public function getExperimentMetadata(JobRecord $jobRecord)
    {
        $labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $jobRecord->getLabServerId()));

        if ($jobRecord->getProcessingEngine() != -1){
        $engine = $this
            ->em
            ->getRepository('DispatcherBundle:ExperimentEngine')
            ->findOneBy(array('id' => $jobRecord->getProcessingEngine()));

            $processingEngineMetadata = array('name' => $engine->getName(),
                                              'id' => $engine->getId());
        }
        else{
            $processingEngineMetadata = array('name' => 'Not assigned',
                                              'id' => -1);
        }

       if($jobRecord != null){
            $jobMetadata = array('expId' => $jobRecord->getExpId(),
                                 'engine' => $processingEngineMetadata,
                                 'labServer' => array('name' => $labServer->getName(),
                                                      'id' => $labServer->getId(),
                                                      'cat_name' => $labServer->getExpCategory(),
                                                      'exp_name' => $labServer->getExpName()),
                                 'execElapsed' => $jobRecord->getExecElapsed(),
                                 'jobElapsed' => $jobRecord->getJobElapsed(),
                                 'queueAtInsert' => $jobRecord->getQueueAtInsert(),
                                 'errorOccurred' => $jobRecord->getErrorOccurred(),
                                 'errorReport' => $jobRecord->getErrorReport());
            return $jobMetadata;
        }
        return null;
    }

    public function getJobRecordById($experimentId, $rlmsId)
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId, 'providerId' => $rlmsId));

        return $jobRecord;
    }

}