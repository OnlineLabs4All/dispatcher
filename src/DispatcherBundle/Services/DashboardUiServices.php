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
    public function getJobRecordsTable(User $user)
    {
        if ($user->getRole() == 'ROLE_ADMIN')
        {
            $jobRecords = $this->em
                              ->getRepository('DispatcherBundle:JobRecord')
                              ->findBy(array(/*'labServerId' => array()*/), array('expId'=> 'DESC'));
                //var_dump($records);
            return $jobRecords;
        }
        elseif ($user->getRole() == 'ROLE_USER')
        {//findBy(array('labServerOwnerId'=> $userToken->getId()), array('expId'=> 'DESC'));

            $jobRecords = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findBy(array('labServerOwnerId'=> $user->getId()), array('expId'=> 'DESC'));
                //var_dump($records);
                return $jobRecords;
        }
        return null;
        // return $this->render('default/recordView.html.twig', array('viewName'=> 'Experiment Record','record' => null));
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

}