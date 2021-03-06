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
use DispatcherBundle\Entity\Rlms;

class DashboardUiServices
{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

    }
    //get JodRecords table based on user permissions and identity
    public function getJobRecordsTable(User $user, $length, $page, $status, $labServer)
    {
        $labServerNamesAndIds = $this->getLabServerNamesAndIdsForUser($user);

        $filter = array();
        $statuses = array(1, 2, 3, 4, 5); //list of all possible job statuses for the filtering
        $labServerIds = $this->getLabServerIds($user);

        if ($status != -1){
            $statuses = array($status);
            $filter['jobStatus'] = $status;
        }
        if ($labServer != -1){
            $labServerIds = array($labServer);
            $filter['labServerId'] = $labServer;
        }
        if ($user->getRole() != 'ROLE_ADMIN'){
            $filter['labServerOwnerId'] = $user->getId();

            $repository = $this
                ->em
                ->getRepository('DispatcherBundle:JobRecord');

            $jobRecordsCountTotal = $repository->createQueryBuilder('job')
                ->select('count(job.expId)')
                ->Where('job.jobStatus IN  (:statuses)')
                ->setParameter('statuses', $statuses)
                ->andWhere('job.labServerId IN (:labServerIds)')
                ->setParameter('labServerIds', $labServerIds)
                ->andWhere('job.labServerOwnerId = :labServerOwnerId')
                ->setParameter('labServerOwnerId', $user->getId())
                ->getQuery()
                ->getSingleScalarResult();
        }
        else{
            $repository = $this
                ->em
                ->getRepository('DispatcherBundle:JobRecord');

            $jobRecordsCountTotal = $repository->createQueryBuilder('job')
                ->select('count(job.expId)')
                ->Where('job.jobStatus IN  (:statuses)')
                ->setParameter('statuses', $statuses)
                ->andWhere('job.labServerId IN (:labServerIds)')
                ->setParameter('labServerIds', $labServerIds)
                ->getQuery()
                ->getSingleScalarResult();
        }

        $offset = ($page - 1) * $length;

        $jobRecords = $this->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findBy($filter, array('expId'=> 'DESC'), $length, $offset);

        $i = 0;
        foreach ($jobRecords as $jobRecord){
            $jobRecords[$i]->labServerName =  $labServerNamesAndIds[$jobRecord->getLabSErverId()];
            $i++;
        }

        $numberOfPages = ceil($jobRecordsCountTotal/$length);

        if ($page < $numberOfPages){
            $nextPage = $page + 1;
        }
        else{
            $nextPage = $numberOfPages;
        }

        if ($page > 1){
            $previousPage = $page - 1;
        }
        else{
            $previousPage = 1;
        }

        $pages = array();
        for ($pg=1; $pg <= $numberOfPages; $pg++){
            $pages[$pg] = $pg;
        }

            return array('totalNumberOfJobs' => $jobRecordsCountTotal,
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
        if ($user->getRole() == 'ROLE_ADMIN'){
            $jobRecord = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findOneBy(array('expId' => $expId));
            return $jobRecord;
        }
        elseif ($user->getRole() == 'ROLE_USER'){
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

        if ($user->getRole() != 'ROLE_ADMIN'){
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
        else{
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

    public function getClients(User $user)
    {
        $labServersNames = $this->getLabServerNamesAndIdsForUser($user);
        if ($user->getRole() != 'ROLE_ADMIN'){
            $clients = $this->em
                ->getRepository('DispatcherBundle:LabClient')
                ->findBy(array('owner_id' => $user->getId()));
        }
        else{
            $clients = $this->em
                ->getRepository('DispatcherBundle:LabClient')
                ->findAll();
        }
        $i = 0;
        foreach ($clients as $client){
            $clients[$i]->labServerName =  $labServersNames[$client->getLabserverId()];
            $i++;
        }

        return $clients;
    }

    public function getEnginesList(User $user)
    {
        $labServersNames = $this->getLabServerNamesAndIdsForUser($user);

        if ($user->getRole() != 'ROLE_ADMIN'){
            $engines = $this->em
                ->getRepository('DispatcherBundle:ExperimentEngine')
                ->findBy(array('owner_id' => $user->getId()));
        }
        else{
            $engines = $this->em
                ->getRepository('DispatcherBundle:ExperimentEngine')
                ->findAll();
        }
        $i = 0;
        foreach ($engines as $engine){
           $engines[$i]->labServerName =  $labServersNames[$engine->getLabserverId()];
            $i++;
        }
        return $engines;
    }

    public function getLabServersList(User $user)
    {
        if ($user->getRole() != 'ROLE_ADMIN'){
            $labServers = $this->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findBy(array('owner_id' => $user->getId()));

            return $labServers;
        }
        else{
            $labServers = $this->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findAll();
            return $labServers;
        }
    }

    public function getLabServerNamesAndIdsForUser($user)
    {
        if ($user->getRole() != 'ROLE_ADMIN'){
            $repository = $this->em->getRepository('DispatcherBundle:LabServer');
            $labServerIdsAndNames = $repository->createQueryBuilder('LabServer')
                ->where('LabServer.owner_id = :owner_id')
                ->setParameter('owner_id', $user->getId())
                ->select('LabServer.id, LabServer.name, LabServer.owner_id')
                ->getQuery()
                ->getArrayResult();
        }
        else{
            $repository = $this->em->getRepository('DispatcherBundle:LabServer');
            $labServerIdsAndNames = $repository->createQueryBuilder('LabServer')
                ->select('LabServer.id, LabServer.name, LabServer.owner_id')
                ->getQuery()
                ->getArrayResult();
        }
        if ($labServerIdsAndNames != null){

            foreach ($labServerIdsAndNames as $labServerIdAndName){
                $labServers[$labServerIdAndName['id']] = $labServerIdAndName['name'].' ('.$labServerIdAndName['id'].')';
            }
            return $labServers;
        }
        return null;
        //var_dump($labServers);
    }

	public function getUsersList(User $user)
	{
		if ($user->getRole() == 'ROLE_ADMIN') {
			$repository = $this->em->getRepository('DispatcherBundle:User');
			//$siteUsers = $repository->findAll(); //this one includes password
			$siteUsers = $repository
					->createQueryBuilder('u')
					->select('u.id, u.username, u.firstName, u.lastName, u.email, u.role, u.isActive')
					->getQuery()
					->getArrayResult();
			/*
			$siteUsersCountTotal = $repository
					->createQueryBuilder('users')
					->select('count(users.id)')
					->getQuery()
					->getSingleScalarResult();
			*/
			$siteUsersCountTotal = sizeof($siteUsers);
			return array(
					'userCount' => $siteUsersCountTotal,
					'users' => $siteUsers);

		}
		return NULL; //if not admin
	}
	
    private function getUserNameById($userId)
    {
        $repository = $this->em->getRepository('DispatcherBundle:User');
        $userName = $repository->createQueryBuilder('User')
            ->where('User.id = :id')
            ->setParameter('id', $userId)
            ->select('User.username, User.firstName, User.lastName')
            ->getQuery()
            ->getSingleResult();
        return $userName;
    }

    public function getUserById($userId)
    {
        $repository = $this->em->getRepository('DispatcherBundle:User');
        $user = $repository->createQueryBuilder('User')
            ->where('User.id = :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getSingleResult();
        return $user;
    }

    public function appendUserInfoToResourceArray($resourceArray)
    {
        $i = 0;
        foreach ($resourceArray as $resource){

            $ownerInfo = $this->getUserNameById($resource->getOwnerId());
            $resourceArray[$i]->ownerFirstName = $ownerInfo['firstName'];
            $resourceArray[$i]->ownerLastName = $ownerInfo['lastName'];
            $resourceArray[$i]->ownerUsername = $ownerInfo['username'];
            $i++;
        }
        return $resourceArray;
    }

    public function getLabServersListForRlmsOwner(Rlms $rlms)
    {
        $labServers = $this->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findBy(array('owner_id' => $rlms->getOwnerId()));

            return $labServers;
    }

    public function getRlmsList(User $user)
    {
        if ($user->getRole() != 'ROLE_ADMIN'){
            $RlmsList = $this->em
                ->getRepository('DispatcherBundle:Rlms')
                ->findBy(array('owner_id' => $user->getId()));

            return $RlmsList;
        }
        else{
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
        //$labServersWithMapping = $labServers;
        foreach ($labServers as $labServer){
            //$mappingResult[$labServer->getId()] = false;
            $labServer->mapped = false;
            foreach ($mappings as $mapping){
                if ($labServer->getId() == $mapping->getLabServerId()){
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
        if ($mapping != null){
            $this->em->remove($mapping);
            $this->em->flush();
        }
    }

	public function checkUserPermissionOnResource(User $user, $resource = null)
	{
		if ($user->getRole() == 'ROLE_ADMIN'){
			return array('granted' => true);
		}
		if ($resource != null){
			if ($user->getId() == $resource->getOwnerId()){
				return array('granted' => true);
			}
			return array('granted' => false,
						 'warning' =>'You do not have permissions to view/modify this resource');
		}
		return array('granted' => false,
					 'warning' =>'The resource does not exit');
	}

    public function deleteJobRecord($expId, User $user)
    {
        if ($user->getRole() != 'ROLE_ADMIN'){
            $jobRecord = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findOneBy(array('expId' => $expId, 'labServerOwnerId' =>$user->getId()));
        }
        else{
            $jobRecord = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findOneBy(array('expId' => $expId));
        }
        if ($jobRecord != null){
            $this->em->remove($jobRecord);
            $this->em->flush();

            return true;
        }
        return false;
    }

    public function changeJobStatus($expId, $newStatus, User $user)
    {
        if ($user->getRole() != 'ROLE_ADMIN'){
            $jobRecord = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findOneBy(array('expId' => $expId, 'labServerOwnerId' =>$user->getId()));
        }
        else{
            $jobRecord = $this->em
                ->getRepository('DispatcherBundle:JobRecord')
                ->findOneBy(array('expId' => $expId));
        }
        if ($jobRecord != null){
            $jobRecord->setJobStatus($newStatus);
            if ($newStatus == 1){
                $jobRecord->setProcessingEngine(-1);
            }
            $this->em->persist($jobRecord);
            $this->em->flush();
            return true;
        }
        return false;
    }

    private function getLabServerIds(User $user)
    {
        $labServers = $this->em
            ->getRepository('DispatcherBundle:LabServer');

        if ($user->getRole() != 'ROLE_ADMIN'){
            $labServerIds = $labServers->createQueryBuilder('labServers')
                ->where('labServers.owner_id = :userId')
                ->setParameter('userId',$user->getId())
                ->select('labServers.id')
                ->getQuery()
                ->getArrayResult();
        }
        else{

            $labServerIds = $labServers->createQueryBuilder('labServers')
                ->select('labServers.id')
                ->getQuery()
                ->getArrayResult();
        }

        return $labServerIds;
    }

}