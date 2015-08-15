<?php
/**
 * User: Danilo G. Zutin
 * Date: 14.08.15
 * Time: 14:05
 */

namespace DispatcherBundle\Services;
use Doctrine\ORM\EntityManager;
use DispatcherBundle\Entity\User;

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

    //get JodRecord based on user permissions and identity
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

}