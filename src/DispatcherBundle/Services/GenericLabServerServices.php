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
use SimpleXMLElement;
use DOMDocument;

class GenericLabServerServices
{
    const ES_QUEUED = 1;
    const ES_PROGRESS = 2;
    const ES_COMPLETED = 3;
    const ES_COMPETED_ERRORS = 4;
    const ES_CANCELLED = 5;

    private $labServer;

    public function __construct(EntityManager $em, SoapclientIsa $soapClientIsa)
    {
        $this->em = $em;
        $this->soapClientIsa = $soapClientIsa;
        $labServer = new LabServer;
    }

    public function setLabServerId($labServerId){
        //$this->labServerId = $labServerId;
        $this->labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labServerId));

        return $this->labServer;
    }

    public function getLabServerGuid($labServerId)
    {
        $labServer = $this->setLabServerId($labServerId);

        if ($labServer != null){

            return array(
                'exception' => false,
                'guid' => $labServer->getGuid());
        }
        return array(
            'exception' => true,
            'message' => 'Lab Server not found');
    }

    public function getLabInfo($labServerId){

        $labServer = $this->setLabServerId($labServerId);

        if ($labServer != null){

            //is it a federated lab server?
            if ($labServer->getFederate() == true){

                $labInfoRes = $this->soapClientIsa->getLabInfo($labServer);

                if ($labInfoRes['exception']){
                    return array(
                        'exception' => true,
                        'message' => $labInfoRes['message']);
                }
                return array(
                    'exception' => false,
                    'result' => $labInfoRes['result']);
            }
            return array(
                'exception' => false,
                'result' => $labServer->getLabInfo());
        }
        return array(
            'exception' => true,
            'message' => 'Lab Server not found');
    }

    public function getLabConfiguration($labServerId = null){

        $labServer = $this->setLabServerId($labServerId);

        if ($labServer != null){

            //is it a federated lab server?
            if ($labServer->getFederate() == true){

                $labConfRes = $this->soapClientIsa->getLabConfiguration($labServer);

                if ($labConfRes['exception']){
                    return array(
                        'exception' => true,
                        'message' => $labConfRes['message']);
                }

                return array(
                    'exception' => false,
                    'labConfiguration' => array('navmenuPhoto' => array(array('image'=> array('https://github.com/OnlineLabs4All/dispatcher/blob/master/web/img/logo_100px.png?raw=true'))),
                        'labConfiguration' => $labConfRes['result']));
            }

            return array(
                'exception' => false,
                'labConfiguration' => array('navmenuPhoto' => array(array('image'=> array('https://github.com/OnlineLabs4All/dispatcher/blob/master/web/img/logo_100px.png?raw=true'))),
                'labConfiguration' => $labServer->getConfiguration()));
        }
        return array(
            'exception' => true,
            'message' => 'Lab Server not found');

        //$userGroup = $params->userGroup; //can be used to return different lab configurations depending on the user group.
        //{'navmenuPhoto': [{'image': ['http://cliparts.co/cliparts/piq/Kn7/piqKn7y7T.png']}]}
    }

    public function getLabStatus($labServerId = null){

        $labServer = $this->setLabServerId($labServerId);

        if ($labServer != null){

            if ($labServer->getActive() == true)
            {
                $response = array(
                    'exception' => false,
                    'online' => $this->labServer->getActive(),
                    'labStatusMessage' => "1:Powered up");
                return $response;
            }

            $response = array(
                'exception' => false,
                'online' => $this->labServer->getActive(),
                'labStatusMessage' => "0:Powered down",
                'labGuid' => $this->labServer->getGuid());
        }
        else{
            $response = array(
                'exception' => true,
                'online' => false,
                'labStatusMessage' => "Lab server not found");
        }

        return $response;
    }

    public function getEffectiveQueueLength($priority, $labServerId = null){

        $labServer = $this->setLabServerId($labServerId);
        
        if ($labServer != null){

            $repository = $this
                ->em
                ->getRepository('DispatcherBundle:JobRecord');

            //get the length of the queue considering the job priority
            $queueLength = $repository->createQueryBuilder('job')
                ->where('job.jobStatus = :jobStatus')
                ->andWhere('job.labServerId = :labServerId')
                ->andWhere('job.priority >= :priority')
                ->setParameter('jobStatus', self::ES_QUEUED)//STATUS 1 = QUEUED
                ->setParameter('labServerId', $labServer->getId())
                ->setParameter('priority', $priority) //usually set to zero
                ->select('COUNT(job)')
                ->getQuery()
                ->getSingleScalarResult();

            $estWait = 20*$queueLength;

            return array(
                'exception' => false,
                'effectiveQueueLength' => $queueLength,
                'estWait' => $estWait);
        }

        return array(
            'exception' => true,
            'message' => 'Lab Server not found');
    }

    public function Submit($rlmsExpId, $experimentSpecification, $opaqueData, $priorityHint, $rlmsGuid, $labServerId = null){

        $labServer = $this->setLabServerId($labServerId);
        
        if ($labServer != null){

            $jobRecord = new JobRecord();
            $repository = $this
                ->em
                ->getRepository('DispatcherBundle:JobRecord');

            //get the length of the queue considering the job priority
            $queueLength = $repository->createQueryBuilder('job')
                ->where('job.jobStatus = :jobStatus')
                ->andWhere('job.labServerId = :labServerId')
                ->andWhere('job.priority >= :priority')
                ->setParameter('jobStatus', self::ES_QUEUED) //
                ->setParameter('labServerId', $labServer->getId())
                ->setParameter('priority', $priorityHint)
                ->select('COUNT(job)')
                ->getQuery()
                ->getSingleScalarResult();

            //calculate hash of experiment specification
            $exp_spec_hash = hash('sha256', $experimentSpecification);
            $expResults = null;
            $jobStatus = 1;
            $dataset = $labServer->getUseDataset();

            if ($dataset == true){

                $identicalJob = $this
                    ->em
                    ->getRepository('DispatcherBundle:JobRecord')
                    ->findOneBy(array('expSpecChecksum' => $exp_spec_hash,
                        'labServerId' => $labServer->getId(),
                        'jobStatus' => self::ES_COMPLETED));

                //if identical experiment if found double check if experiment specification is really identical to
                // avoid returning wrong results in the unlikely case of collision of the hash algorithm
                if (($identicalJob != null) && ($identicalJob->getExpSpecification() == $experimentSpecification)){
                    $jobStatus = 3; //set job status to complete if identical record is found

                    $jobRecord->createNewFromDataset($rlmsExpId, $experimentSpecification, $opaqueData, $queueLength, $labServer, $rlmsGuid, $exp_spec_hash, $jobStatus, $identicalJob);
                }
                else{
                    $jobRecord->createNew($rlmsExpId, $experimentSpecification, $opaqueData, $queueLength, $labServer, $rlmsGuid, $exp_spec_hash, $jobStatus, null);
                }
            }
            else{
                $jobRecord->createNew($rlmsExpId, $experimentSpecification, $opaqueData, $queueLength, $labServer, $rlmsGuid, null, $jobStatus, null);
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

            //is it a federated lab server?
            if ($labServer->getFederate() == true && $jobRecord->getIsFromDataset() == false) {

                $submissionReport = $this->soapClientIsa->submit($labServer, $experimentID, $jobRecord->getExpSpecification(), 0, 'Experiment_Group');

                if ($submissionReport['exception']){

                    return $submissionReport;
                }
                $accepted = $submissionReport['result']->vReport->accepted;
                $warningMessage = '';//$submissionReport['result']->vReport->warningMessages->string;
                $errorMessage = '';//$submissionReport['result']->vReport->errorMessage;
                $estRuntime = $submissionReport['result']->vReport->estRuntime;
                $experimentID = $jobRecord->getExpId(); //the returned experiment ID must be the local one!!
                $minTimeToLive = $submissionReport['result']->minTimeToLive;
                $estWait = $submissionReport['result']->wait->estWait;
                $queueLength = $submissionReport['result']->wait->effectiveQueueLength;

                $jobRecord->setFederatedExpId($submissionReport['result']->experimentID);
                $this->em->persist($jobRecord);
                $this->em->flush();
            }

            return array(
                'exception' => false,
               // 'SubmitResult_soap' => $submissionReport['SubmitResult'],
                'vReport' => array('accepted' => $accepted,
                'warningMessages' => array('string' => $warningMessage),
                'errorMessage' => $errorMessage,
                'estRuntime' => $estRuntime),
                'experimentID' => $experimentID,
                'minTimeToLive' => $minTimeToLive,
                'wait' => array('effectiveQueueLength' => $queueLength,
                    'estWait' => $estWait));
        }

        return array(
            'exception' => true,
            'message' => 'Lab Server not found');
    }

    public function getExperimentStatus($experimentId)
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId));

        if ($jobRecord == null){
            return array(
                'exception' => true,
                'message' => 'Experiment not found');
        }

        $labServer = $this->setLabServerId($jobRecord->getLabServerId());

        if ($labServer == null){
            return array(
                'exception' => true,
                'message' => 'Lab Server not found');
        }

        //is it a federated lab server?
        if ($labServer->getFederate() == true && $jobRecord->getIsFromDataset() == false) {

            $status = $this->soapClientIsa->getExperimentStatus($labServer, $experimentId);

            if ($status['exception']){
                return $status;
            }



            $jobRecord->setJobStatus($status['result']->statusReport->statusCode);
            $this->em->persist($jobRecord);
            $this->em->flush();

            return array(
                'exception' => false,
                'statusCode'=> $status['result']->statusReport->statusCode,
                'effectiveQueueLength'=> $status['result']->statusReport->wait->effectiveQueueLength,
                'estWait' => $status['result']->statusReport->wait->estWait,
                'estRuntime' => $status['result']->statusReport->estRuntime,
                'estRemainingRuntime' => $status['result']->statusReport->estRemainingRuntime,
                'minTimetoLive' => $status['result']->minTimetoLive);
        }

        $repository = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord');

        //get the length of the queue considering the job priority
        $queueLength = $repository->createQueryBuilder('job')
            ->where('job.jobStatus = :jobStatus')
            ->andWhere('job.expId < :expId')
            ->andWhere('job.labServerId = :labServerId')
            ->andWhere('job.priority >= :priority')
            ->setParameter('jobStatus', self::ES_QUEUED)
            ->setParameter('expId', $jobRecord->getExpId())
            ->setParameter('labServerId', $jobRecord->getLabServerId())
            ->setParameter('priority', $jobRecord->getPriority())
            ->select('COUNT(job)')
            ->getQuery()
            ->getSingleScalarResult();

        $statusCode = $jobRecord->getJobStatus();
       // $effectiveQueueLength = $queueLength; //get the length of the queue considering the job priority

        return array(
            'exception' => false,
            'statusCode'=> $statusCode,
            'effectiveQueueLength'=> $queueLength,
            'estWait' => 20*$queueLength,
            'estRuntime' => 20,
            'estRemainingRuntime' => 20*$queueLength,
            'minTimetoLive' => 7200);
    }

    public function retrieveResult($experimentId)
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId));

        if ($jobRecord == null){
            $exception = true;
            $experimentResults = $jobRecord->getExpResults();
            $xmlResultExtension = Null;
            $xmlBlobExtension = Null;
            $warningMessages = Null;
            $errorMessage = 'Experiment ID not found';
            $statusCode = null;
        }
        else{

            $labServer = $this->setLabServerId($jobRecord->getLabServerId());

            if ($labServer == null){
                return array(
                    'exception' => true,
                    'message' => 'Lab Server not found');
            }

            //is it a federated lab server?
            if ($labServer->getFederate() == true && $jobRecord->getIsFromDataset() == false) {

                $status = $this->soapClientIsa->retrieveResult($labServer, $experimentId);

                if ($status['exception']){
                    return $status;
                }
                $jobRecord->setJobStatus($status['result']->statusCode);
                $jobRecord->setExpResults($status['result']->experimentResults);
                $this->em->persist($jobRecord);
                $this->em->flush();

                return array(
                    'exception' => false,
                    'statusCode' => $status['result']->statusCode,
                    'experimentResults' => $status['result']->experimentResults,
                    'errorMessage' => '',
                    'xmlResultExtension' => '',
                    'xmlBlobExtension' => '',
                    'warningMessages' => '');
            }


            $statusCode = $jobRecord->getJobStatus();

            if ($statusCode != self::ES_COMPLETED){
                $exception = false;
                $experimentResults = $jobRecord->getExpResults();
                $xmlResultExtension = Null;
                $xmlBlobExtension = Null;
                $warningMessages = Null;
                $errorMessage = Null;
                $errorMessage = 'Results not available. Experiment is not completed or has been cancelled. See status code.';
            }
            else{
                //$opaque = json_decode($jobRecord->getOpaqueData());
                $exception = false;
                $experimentResults = $jobRecord->getExpResults();
                $xmlBlobExtension = Null;
                $warningMessages = Null;
                $errorMessage = Null;
                $xmlResultExtension = 'SubmitTime='.$jobRecord->getSubmitTime().',
                                   ExecutionTime='.$jobRecord->getExecutionTime().',
                                   EndTime='.$jobRecord->getExecutionTime().',
                                   ElapsedExecutionTime='.$jobRecord->getExecElapsed().',
                                   ElapsedJobTime='.$jobRecord->getJobElapsed();

                //set job record as downloaded
                $jobRecord->setDownloaded(true);
                $this->em->persist($jobRecord);
                $this->em->flush();
            }
        }

        $response = array(
            'exception' => $exception,
            'statusCode' => $statusCode,
            'experimentResults' => $experimentResults,
            'errorMessage' => $errorMessage,
            'xmlResultExtension' => $xmlResultExtension,
            'xmlBlobExtension' => $xmlBlobExtension,
            'warningMessages' => $warningMessages);

        return $response;
    }

    public function retrieveExperimentSpecification($experimentId) //providerId can be RLMS GUID or internal ID
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId));

        if ($jobRecord != null){

            $statusCode = $jobRecord->getJobStatus();
            $response = array(
                'exception' => false,
                'statusCode' => $statusCode,
                'experimentResults' => $jobRecord->getExpResults(),
                'experimentSpecification' => $jobRecord->getExpSpecification());
        }
        else{
            $response = array(
                'exception' => true,
                'statusCode' => '',
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

        if ($jobRecord != null){

            $statusCode = $jobRecord->getJobStatus();

            if ($statusCode != self::ES_PROGRESS){ //what to do if experiment is not being executed (2 = IN PROGRESS)
                $CancelResult = true;
                $jobRecord->setJobStatus(self::ES_CANCELLED);
                $this->em->persist($jobRecord);
                $this->em->flush();
            }
            else{//what to do if experiment is running
                $CancelResult = false;
            }
            return array(
                'exception' => false,
                'cancelled' => $CancelResult);
        }
        return array(
            'exception' => true,
            'message' => 'Experiment not found');
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

    public function getJobRecordById($experimentId)
    {
        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $experimentId));

        if ($jobRecord != null){

            return array('exception' => false,
                'jobRecord' => $jobRecord);
        }

        return array(
            'exception' => true,
            'message' => 'Experiment not found');
    }

}
