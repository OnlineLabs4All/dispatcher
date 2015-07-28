<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 7/3/15
 * Time: 3:51 PM
 */

namespace DispatcherBundle\Services;
use DispatcherBundle\Entity\JobRecord;
use DispatcherBundle\Model\Subscriber\Status;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Null;
use DispatcherBundle\Model\Subscriber\LabInfo;
use DispatcherBundle\Model\Subscriber\QueueLength;


class EngineServices
{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getQueueLength($api_key)
    {
       $engine = $this
            ->em
            ->getRepository('DispatcherBundle:ExperimentEngine')
            ->findOneBy(array('api_key' => $api_key));

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

    public function getLabInfo($api_key)
    {
        $engine = $this
            ->em
            ->getRepository('DispatcherBundle:ExperimentEngine')
            ->findOneBy(array('api_key' => $api_key));

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
        //$labInfoResponse->setName($labServer->getName());
        //$labInfoResponse->setDescription($labServer->getDescription());
        //$labInfoResponse->setOwnerInstitution($labServer->getInstitution());
        //$labInfoResponse->setLabStatus($labServer->getActive());
        //$labInfoResponse->setLabConfiguration($labServer->getConfiguration());
        $labInfoResponse->setErrorMessage('No experiment engine found for the provided key.');
        return $labInfoResponse;

    }

    public function getStatus($api_key, $test)
    {
        $engine = $this
            ->em
            ->getRepository('DispatcherBundle:ExperimentEngine')
            ->findOneBy(array('api_key' => $api_key));

        if ($engine != null){

            $repository = $this
                ->em
                ->getRepository('DispatcherBundle:JobRecord');
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
                $queueStatus->setExperimentId($IdNextExp);
                $queueStatus->setMessage('Cool, it seems you have some work to do. This experiment is now yours!');
                $queueStatus->setError(false);
                $queueStatus->setErrorMessage('');

            }
            else
            {
                $queueStatus = new Status();
                $queueStatus->setTimeStamp();
                $queueStatus->setSuccess(true); //set success to TRUE
                $queueStatus->setExperimentId(-1);
                $queueStatus->setMessage('Relax, the queue is empty. Thanks for asking.');
                $queueStatus->setError(false);
                $queueStatus->setErrorMessage('');
            }

            return $queueStatus;
        }

        $queueStatus = new Status();
        $queueStatus->setTimeStamp();
        $queueStatus->setSuccess(false); //set success to TRUE
        $queueStatus->setExperimentId(-1);
        $queueStatus->setMessage('Experiment not found');
        $queueStatus->setError(true);
        $queueStatus->setErrorMessage('No experiment engine found for the provided key.');
        return $queueStatus;

    }

}

