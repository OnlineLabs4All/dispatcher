<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 7/3/15
 * Time: 3:51 PM
 */

namespace DispatcherBundle\Services;
use DispatcherBundle\Entity\JobRecord;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Null;
use DispatcherBundle\Model\Subscriber\Status;
use DispatcherBundle\Model\Subscriber\LabInfo;
use DispatcherBundle\Model\Subscriber\QueueLength;
use DispatcherBundle\Model\Subscriber\Experiment;


class EngineServices
{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getQueueLength($engine)
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

    public function getLabInfo($engine)
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

    public function getStatus($engine)
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
                $queueStatus->setErrorMessage('');

            }
            else
            {
                $queueStatus = new Status();
                $queueStatus->setTimeStamp();
                $queueStatus->setSuccess(true); //set success to TRUE
                $queueStatus->setExperimentId(-1);
                $queueStatus->setMessage('Relax, the queue is empty. Thanks for asking.');
                $queueStatus->setErrorMessage('');
            }

            return $queueStatus;
        }

        $queueStatus = new Status();
        $queueStatus->setTimeStamp();
        $queueStatus->setSuccess(false); //set success to TRUE
        $queueStatus->setExperimentId(-1);
        $queueStatus->setMessage('Experiment not found');
        $queueStatus->setErrorMessage('No experiment engine found for the provided key.');
        return $queueStatus;

    }

    public function getExperiment($engine)
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
            $experiment = new Experiment();
            $experiment->setTimeStamp();
            $experiment->setSuccess(true);
            $experiment->setExperimentId($OwnedJob->getExpId());
            $experiment->setExpSpecification($OwnedJob->getExpSpecification());
            $experiment->setErrorMessage("");
        }
        else
        {
            $experiment = new Experiment();
            $experiment->setTimeStamp();
            $experiment->setSuccess(false);
            $experiment->setExperimentId(-1);
            $experiment->setExpSpecification("");
            $experiment->setErrorMessage("Engine does not own an experiment yet.");
        }

        return $experiment;

    }

}

