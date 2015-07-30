<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 7/3/15
 * Time: 3:51 PM
 */

namespace DispatcherBundle\Services;
use DispatcherBundle\Entity\ExperimentEngine;
use DispatcherBundle\Entity\JobRecord;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Null;
use DispatcherBundle\Model\Subscriber\Status;
use DispatcherBundle\Model\Subscriber\LabInfo;
use DispatcherBundle\Model\Subscriber\QueueLength;
use DispatcherBundle\Model\Subscriber\ExperimentGetResponse;
use DispatcherBundle\Model\Subscriber\ExperimentPostResponse;


class EngineServices
{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getQueueLength(ExperimentEngine $engine)
    {
       if ($engine != null){

            $repository = $this
               ->em
               ->getRepository('DispatcherBundle:JobRecord');
           //get the length of the queue
           $queueLength = $repository->createQueryBuilder('job')
               ->where('job.jobStatus = :jobStatus')
               ->andWhere('job.labServerId = :labServerId')
               ->setParameter('jobStatus', 1) //
               ->setParameter('labServerId',$engine->getLabserverId())
               ->select('COUNT(job)')
               ->getQuery()
               ->getSingleScalarResult();

           $queueLengthResponse = new QueueLength();
           $queueLengthResponse->setTimeStamp();
           $queueLengthResponse->setSuccess(true);
           $queueLengthResponse->setQueueLength($queueLength);
           $queueLengthResponse->setLabServerId($engine->getLabserverId());
           $queueLengthResponse->setErrorMessage('');
        }
        else{
            $queueLengthResponse = new QueueLength();
            $queueLengthResponse->setTimeStamp();
            $queueLengthResponse->setSuccess(false);
            $queueLengthResponse->setErrorMessage('No experiment engine found for the provided key.');
        }
        return $queueLengthResponse;
    }

    public function getLabInfo(ExperimentEngine $engine)
    {
        if ($engine != null){

            $labServer = $this
                ->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findOneBy(array('id' => $engine->getLabServerId()));

            $labInfoResponse = new LabInfo();
            $labInfoResponse->setTimeStamp();
            $labInfoResponse->setSuccess(true); //set success to TRUE
            $labInfoResponse->setName($labServer->getName());
            $labInfoResponse->setDescription($labServer->getDescription());
            $labInfoResponse->setOwnerInstitution($labServer->getInstitution());
            $labInfoResponse->setLabStatus($labServer->getActive());
            $labInfoResponse->setLabConfiguration($labServer->getConfiguration());
            $labInfoResponse->setErrorMessage('');

            return $labInfoResponse;
        }
        $labInfoResponse = new LabInfo();
        $labInfoResponse->setTimeStamp();
        $labInfoResponse->setSuccess(false); //set success to TRUE
        $labInfoResponse->setErrorMessage('No experiment engine found for the provided key.');
        return $labInfoResponse;

    }

    public function getStatus(ExperimentEngine $engine)
    {
        if ($engine != null){

            $repository = $this
                ->em
                ->getRepository('DispatcherBundle:JobRecord');

            //Checks if engine owns already an experiment that is NOT executed
            $IdOfOwnedExp = $repository->createQueryBuilder('job')
                ->select('job.expId')
                ->Where('job.processingEngine = :self')
                ->andWhere('job.jobStatus = :queued OR job.jobStatus = :inProgress')
                ->setParameter('self', $engine->getId())
                ->setParameter('queued', 1)
                ->setParameter('inProgress', 2)
                ->getQuery()
                ->getOneOrNullResult();


            if ($IdOfOwnedExp == null) //If engine does not own any experiment yet, enters this IF statement to search for next experiment
            {
                //First find the highest priority
                $highestPriority = $repository->createQueryBuilder('job')
                    ->select('MAX(job.priority)')
                    ->where('job.jobStatus = :jobStatus')
                    ->andWhere('job.labServerId = :labServerId')
                    ->andWhere('job.processingEngine = :notAssigned OR job.processingEngine = :self')
                    ->setParameter('jobStatus', 1)
                    ->setParameter('labServerId',$engine->getLabServerId())
                    ->setParameter('notAssigned', -1)
                    ->setParameter('self', $engine->getId())
                    ->getQuery()
                    ->getSingleScalarResult();

                //Now find the next experiment to be executed, namely the one with the smaller expId
                $IdNextExp = $repository->createQueryBuilder('job')
                    ->select('MIN(job.expId)')
                    ->where('job.jobStatus = :jobStatus')
                    ->andWhere('job.labServerId = :labServerId')
                    ->andWhere('job.priority = :priority')
                    ->andWhere('job.processingEngine = :notAssigned OR job.processingEngine = :self')
                    ->setParameter('jobStatus', 1)
                    ->setParameter('labServerId',$engine->getLabServerId())
                    ->setParameter('priority', $highestPriority)
                    ->setParameter('notAssigned', -1)
                    ->setParameter('self', $engine->getId())
                    ->getQuery()
                    ->getSingleScalarResult();
            }
            else
            {
                $IdNextExp = $IdOfOwnedExp['expId'];
            }

            //Enters this IF statement if a next experiment was found
            if ($IdNextExp != null)
            {
                $jobRecord = $repository->findOneBy(array('expId' => $IdNextExp));
                //Claim ownership over this experiment by setting the database processingEngine field
                $jobRecord->setProcessingEngine($engine->getId());
                $this->em->persist($jobRecord);
                $this->em->flush();

                $queueStatus = new Status();
                $queueStatus->setTimeStamp();
                $queueStatus->setSuccess(true); //set success to TRUE if not in test mode
                $queueStatus->setExperimentId((int)$IdNextExp);
                $queueStatus->setMessage('Cool, it seems you have some work to do. This experiment is now yours!');

            }
            else
            {
                $queueStatus = new Status();
                $queueStatus->setTimeStamp();
                $queueStatus->setSuccess(true); //set success to TRUE
                $queueStatus->setExperimentId(-1);
                $queueStatus->setMessage('Relax, the queue is empty. Thanks for asking.');
            }

            return $queueStatus;
        }

        $queueStatus = new Status();
        $queueStatus->setTimeStamp();
        $queueStatus->setSuccess(false); //set success to TRUE
        $queueStatus->setExperimentId(-1);
        $queueStatus->setMessage('Error: Experiment not found');
        return $queueStatus;

    }

    public function getExperiment(ExperimentEngine $engine)
    {
        $repository = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord');

        //Checks if engine owns already an experiment that is NOT executed
        $OwnedJob = $repository->createQueryBuilder('job')
            ->select('job')
            ->Where('job.processingEngine = :self')
            ->andWhere('job.jobStatus = :queued OR job.jobStatus = :inProgress')
            ->setParameter('self', $engine->getId())
            ->setParameter('queued', 1)
            ->setParameter('inProgress', 2)
            ->getQuery()
            ->getOneOrNullResult();

        if ($OwnedJob != null)
        {
            $OwnedJob->setExecutionTime(date('Y-m-d\TH:i:sP'));
            $OwnedJob->setJobStatus(2); //set job status to IN PROGRESS (2)

            $experiment = new ExperimentGetResponse();
            $experiment->setTimeStamp();
            $experiment->setSuccess(true);
            $experiment->setExperimentId($OwnedJob->getExpId());
            $experiment->setExpSpecification($OwnedJob->getExpSpecification());
            $experiment->setJobStatus($OwnedJob->getJobStatus());
            $experiment->setMessage("Experiment Specification retrieved.");

            $this->em->persist($OwnedJob);
            $this->em->flush();

        }
        else
        {
            $experiment = new ExperimentGetResponse();
            $experiment->setTimeStamp();
            $experiment->setSuccess(false);
            $experiment->setExperimentId(-1);
            $experiment->setExpSpecification("");
            $experiment->setJobStatus(-1);
            $experiment->setMessage("Error: Engine does not own an experiment. Call status to be assigned a job.");
        }

        return $experiment;

    }

    /*
     * {
        "success":true,
        "results":"experiment results XML, JSON, etc..",
        "errorReport": "No error occurred"
        }
     *
     */

    public function setExperiment(ExperimentEngine $engine, $requestString)
    {
        $results = json_decode($requestString);

        $repository = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord');

        //Checks if engine owns already an experiment that is NOT executed
        $OwnedJob = $repository->createQueryBuilder('job')
            ->select('job')
            ->Where('job.processingEngine = :self')
            ->andWhere('job.jobStatus = :inProgress')
            ->setParameter('self', $engine->getId())
            ->setParameter('inProgress', 2)
            ->getQuery()
            ->getOneOrNullResult();

        if ($OwnedJob != NULL)
        {
            $OwnedJob->setEndTime(date('Y-m-d\TH:i:sP'));

            $execTime = date_create($OwnedJob->getExecutionTime());
            $submitTime = date_create($OwnedJob->getSubmitTime());
            $endTime = date_create($OwnedJob->getEndTime());

            $execElapsed = date_diff($execTime,$endTime );
            $jobElapsed = date_diff($submitTime,$endTime);

            if ($results->success == true)
            {
                $OwnedJob->setJobStatus(3); //set job Status as COMPLETED
                $OwnedJob->setExecElapsed($execElapsed->s);
                $OwnedJob->setJobElapsed($jobElapsed->s);
                $OwnedJob->setExpResults($results->results);
                $OwnedJob->setErrorOccurred(false);
                $OwnedJob->setErrorReport($results->errorReport);
            }
            else
            {
                $OwnedJob->setJobStatus(4); //set job Status as COMPLETED WITH ERRORS
                $OwnedJob->setExecElapsed($execElapsed->s);
                $OwnedJob->setJobElapsed($jobElapsed->s);
                $OwnedJob->setErrorOccurred(true);
                $OwnedJob->setErrorReport($results->errorReport);
            }

            $this->em->persist($OwnedJob);
            $this->em->flush();

            $response = new ExperimentPostResponse();
            $response->setTimeStamp();
            $response->setSuccess(true);
            $response->setExperimentId($OwnedJob->getExpId());
            $response->setJobStatus($OwnedJob->getJobStatus());
            $response->setMessage('Record was updated.');

        }
        else
        {
            $response = new ExperimentPostResponse();
            $response->setTimeStamp();
            $response->setSuccess(false);
            $response->setExperimentId(-1);
            $response->setJobStatus(-1);
            $response->setMessage('Error: Engine does not own a job or the job status is not set to IN PROGRESS.');

        }

        return $response;
    }

}

