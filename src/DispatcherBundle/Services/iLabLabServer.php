<?php
/**
 * Created by PhpStorm.
 * User: Danilo G. Zutin
 * Date: 5/1/15
 * Time: 11:17 AM
 */
// src/DispatcherBundle/Services/iLabLabServer.php
namespace DispatcherBundle\Services;
use Doctrine\ORM\EntityManager;


class iLabLabServer
{
    private $em;
    private $labServer;//object of class LabServer
    //private $labServerId;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function AuthHeader($Header)
    {
        $sbGuid =  $Header->identifier;
        $passkey = $Header->passKey;

        //check the database for the SB GUID and PassKey
        //if (($sbGuid != "9954C5B79AEB432A94DE29E6EE44EB6") && ($passkey != "366497578876928") )
        //return new \SoapFault("Server", "Wrong SB identifier and/or Passkey" );
    }

    public function setLabServerId($labServerId)
    {
        //$this->labServerId = $labServerId;
        $this->labServer = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labServerId));

    }

    public function GetLabInfo(){


        $response = array('GetLabInfoResult' => $this->labServer->getLabInfo());
        return $response;
    }

    public function GetLabStatus(){

        $response = array('GetLabStatusResult' => array(
                                                  'online' => $this->labServer->getActive(),
                                                  'labStatusMessage' => ""));
        return $response;
    }


}