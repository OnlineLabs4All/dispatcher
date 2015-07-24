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

}

