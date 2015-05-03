<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 5/1/15
 * Time: 11:17 AM
 */
// src/DispatcherBundle/Services/HelloService.php
namespace DispatcherBundle\Services;
use Doctrine\ORM\EntityManager;


class BatchedLabServer
{
    private $em;
    private $labServer;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    public function AuthHeader($Header)
    {
        $sbGuid =  $Header->identifier;
        $passkey = $Header->passKey;

        $this->labServer = $this
                        ->em
                        ->getRepository('DispatcherBundle:LabServer')
                        ->findOneBy(array('passKey' => $passkey));

        //check the database for the SB GUID and PassKey
        //if (($sbGuid != "9954C5B79AEB432A94DE29E6EE44EB6") && ($passkey != "366497578876928") )
        //return new \SoapFault("Server", "Wrong SB identifier and/or Passkey" );

    }

    public function GetLabInfo(){


        $response = array('GetLabInfoResult' => $this->labServer->getLabInfo());
        return $response;
    }

    public function GetLabStatus(){

        $response = array('GetLabStatusResult' => array(
                                                  'online' => $this->labServer->getActive(),
                                                  'labStatusMessage' => "The Lab is Online and ready to use"));
        return $response;
    }


}