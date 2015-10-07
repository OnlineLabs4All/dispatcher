<?php
/**
 * User: Danilo G. Zutin
 * Date: 14.08.15
 * Time: 14:05
 */

namespace DispatcherBundle\Services;
use DispatcherBundle\Entity\LsToRlmsMapping;
use Doctrine\ORM\EntityManager;
use DispatcherBundle\Entity\User;
use DispatcherBundle\Entity\LabServer;
use DispatcherBundle\Entity\ExperimentEngine;
use Symfony\Component\Form\FormBuilderInterface;

class DashboardUiServices
{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

    }
    //get JodRecords table based on user permissions and identity
    public function getJobRecordsTable(User $user, $length, $page, $status, $labServer)
    {
        $filter = array();
        if ($status != -1)
        {
            $filter['jobStatus'] = $status;
        }
        if ($labServer != -1)
        {
            $filter['labServerId'] = $labServer;
        }
        if ($user->getRole() != 'ROLE_ADMIN')
        {
            $filter['labServerOwnerId'] = $user->getId();
            $jobRecordsCountTotal = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findBy($filter, array('expId'=> 'DESC'));
        }
        else
        {
            $jobRecordsCountTotal = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findBy($filter, array('expId'=> 'DESC'));
        }

        $offset = ($page - 1) * $length;

        $jobRecords = $this->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findBy($filter, array('expId'=> 'DESC'), $length, $offset);
            //var_dump($records);

        $numberOfPages = ceil(count($jobRecordsCountTotal)/$length);

        if ($page < $numberOfPages) {$nextPage = $page + 1;}
        else {$nextPage = $numberOfPages;}

        if ($page > 1){ $previousPage = $page - 1;}
        else {$previousPage = 1;}

        $pages = array();
        for ($pg=1; $pg <= $numberOfPages; $pg++)
        {
            $pages[$pg] = $pg;
        }

            return array('totalNumberOfJobs' => count($jobRecordsCountTotal),
                         'numberOfPages' => $numberOfPages,
                         'length' => count($jobRecords),
                         'nextPage' => $nextPage,
                         'previousPage' => $previousPage,
                         'pages' => $pages,
                         'jobRecords' => $jobRecords);
    }

    //get Single JodRecord based on user permissions and identity
    public function getSingleJobRecord(User $user, $expId)
    {
        if ($user->getRole() == 'ROLE_ADMIN')
        {
            $jobRecord = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findOneBy(array('expId' => $expId));
            return $jobRecord;
        }
        elseif ($user->getRole() == 'ROLE_USER')
        {
            $jobRecord = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findOneBy(array('expId' => $expId, 'labServerOwnerId' => $user->getId()));
            return $jobRecord;

        }
        return null;
    }
    //provide Functionality for pagination feature
    public function getPagination(User $user, $length, $status, $labServerId)
    {
        $repository = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord');

        if ($user->getRole() != 'ROLE_ADMIN')
        {
            $numberOfJobs = $repository->createQueryBuilder('job')
                ->where('job.jobStatus = :jobStatus')
                ->andWhere('job.labServerId = :labServerId')
                ->andWhere('job.labServerOwnerId = :userId')
                ->setParameter('jobStatus', $status)
                ->setParameter('labServerId', $labServerId)
                ->setParameter('userId',$user->getId())
                ->select('COUNT(job)')
                ->getQuery()
                ->getSingleScalarResult();
        }
        else
        {
            $numberOfJobs = $repository->createQueryBuilder('job')
                ->where('job.jobStatus = :jobStatus')
                ->andWhere('job.labServerId = :labServerId')
                ->setParameter('jobStatus', $status)
                ->setParameter('labServerId', $labServerId)
                ->select('COUNT(job)')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return array('numberOfPages' => ceil($numberOfJobs/$length),
                     'numberOfJobs' => $numberOfJobs);

    }

    public function getEnginesList(User $user)
    {
        if ($user->getRole() != 'ROLE_ADMIN')
        {
            $engines = $this->em
                ->getRepository('DispatcherBundle:ExperimentEngine')
                ->findBy(array('owner_id' => $user->getId()));

            return $engines;
        }
        else
        {
            $engines = $this->em
                ->getRepository('DispatcherBundle:ExperimentEngine')
                ->findAll();

            return $engines;
        }
    }

    public function getLabServersList(User $user)
    {
        if ($user->getRole() != 'ROLE_ADMIN')
        {
            $labServers = $this->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findBy(array('owner_id' => $user->getId()));

            return $labServers;
        }
        else
        {
            $labServers = $this->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findAll();

            return $labServers;
        }
    }

    public function getRlmsList(User $user)
    {
        if ($user->getRole() != 'ROLE_ADMIN')
        {
            $RlmsList = $this->em
                ->getRepository('DispatcherBundle:Rlms')
                ->findBy(array('owner_id' => $user->getId()));

            return $RlmsList;
        }
        else
        {
            $RlmsList = $this->em
                ->getRepository('DispatcherBundle:Rlms')
                ->findAll();

            return $RlmsList;
        }
    }

    public function getMappingsForRlms($rlmsId)
    {
        $mappings = $this->em
                        ->getRepository('DispatcherBundle:LsToRlmsMapping')
                        ->findBy(array('rlmsId' =>$rlmsId));
        return $mappings;
    }

    public function getMappings($labServers, $mappings)
    {
        $labServersWithMapping = &$labServers;
        foreach ($labServers as $labServer)
        {
            //$mappingResult[$labServer->getId()] = false;
            $labServer->mapped = false;
            foreach ($mappings as $mapping)
            {
                if ($labServer->getId() == $mapping->getLabServerId())
                {
                    $labServer->mapped = true;
                   //$mappingResult[$labServer->getId()] = true;
                }
            }
        }
        return $labServers;

    }

    public function addRlmsLsMapping($rlmsId, $labServerId)
    {
        $mapping = new LsToRlmsMapping();
        $mapping->setRlmsId($rlmsId);
        $mapping->setLabServerId($labServerId);
        $this->em->persist($mapping);
        $this->em->flush();
    }
    public function removeRlmsLsMapping($rlmsId, $labServerId)
    {
        $mapping = $this->em
            ->getRepository('DispatcherBundle:LsToRlmsMapping')
            ->findOneBy(array('rlmsId' => $rlmsId, 'labServerId' => $labServerId));
        if ($mapping != null)
        {
            $this->em->remove($mapping);
            $this->em->flush();
        }
    }

    public function checkUserPermissionOnResource(User $user, $resource)
    {
        if ($resource != null)
        {
            if ($user->getRole() == 'ROLE_ADMIN')
            {
                return array('granted' => true);
            }
            if ($user->getId() == $resource->getOwnerId())
            {
                return array('granted' => true);
            }
            return array('granted' => false,
                         'warning' =>'You do not have permissions to view/modify this resource');
        }
        return array('granted' => false,
                     'warning' =>'The resource does not exit');

    }

}