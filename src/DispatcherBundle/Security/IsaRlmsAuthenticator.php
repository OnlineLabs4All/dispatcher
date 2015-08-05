<?php
/**
 * User: Danilo G. Zutin
 * Date: 04.08.15
 * Time: 20:26
 */

namespace DispatcherBundle\Security;
use Doctrine\ORM\EntityManager;


class IsaRlmsAuthenticator{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    //Authenticate credentials of type AuthHeader
    public function authenticateBatchedMethod($sbGuid, $lsPaskey, $labserverId)
    {
        $broker = $this
            ->em
            ->getRepository('DispatcherBundle:Rlms')
            ->findOneBy(array('Guid' => $sbGuid));

        if ($broker != null) //if Broker exists, authenticate labServer
        {
            $labServer = $this
                ->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findOneBy(array('passKey' => $lsPaskey, 'id' => $labserverId));

            if ($labServer != null)
            {
                $mapping = $this
                    ->em
                    ->getRepository('DispatcherBundle:LsToRlmsMapping')
                    ->findOneBy(array('labServerId' => $labServer->getId(), 'rlmsId' => $broker->getId()));

                if ($mapping != null)
                {
                    return array('authenticated' => true, 'fault' => '');
                }
                return array('authenticated' => false, 'fault' => 'Broker and Lab Server  were found, but they are not mapped to each other. Contact your server administrator');
            }
            return array('authenticated' => false, 'fault' => 'Provided Lab Server passkey is incorrect');
        }
        return array('authenticated' => false, 'fault' => 'Provided Service Broker GUID is not registered');
    }

    //Authenticate credentials of type InitAuthHeader
    public function authenticateInstallDomainCredentialsMethod($initPasskey, $labserverId)
    {
        $labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('initialPassKey' => $initPasskey, 'id' => $labserverId));

        if ($labServer != null)
        {
            $mapping = $this
                ->em
                ->getRepository('DispatcherBundle:LsToRlmsMapping')
                ->findOneBy(array('labServerId' => $labServer->getId()));

            if ($mapping == null)
            {
                return array('authenticated' => true, 'fault' => '');
            }
            return array('authenticated' => false, 'fault' => 'Lab Server already associated with a domain Service Broker');
        }
        return array('authenticated' => false, 'fault' => 'Provided initial passkey is incorrect');

    }


}