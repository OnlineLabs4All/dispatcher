<?php
/**
 * User: Danilo G. Zutin
 * Date: 05.08.15
 * Time: 2202
 */

// src/DispatcherBundle/Services/SoapClientIsa.php
namespace DispatcherBundle\Services;

use DispatcherBundle\Entity\LabServer;
use DispatcherBundle\Entity\Rlms;
use Doctrine\ORM\EntityManager;
use SoapClient;
use SoapHeader;

class SoapClientIsa
{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function redeemTicket($couponId, $passkey, LabServer $labServer, Rlms $broker, $ticketType)
    {

        $wsdl_url = $broker->getServiceDescriptionUrl();

        $sbAuthData = json_decode($labServer->getRlmsSpecificData());

        //var_dump($labServer);

        //Set SOAP Headers
        $headerParams = array('coupon' => array('couponId' => $sbAuthData->outIdentCoupon->couponId, //SB IdentOut
            'issuerGuid' => $sbAuthData->outIdentCoupon->issuerGuid, //SB GUID
            'passkey' => $sbAuthData->outIdentCoupon->passkey), //SB IdentOut
            'agentGuid' => $labServer->getGuid());
//Set Body parameters
        $params =  array('coupon' => array('couponId' => $couponId,
            'issuerGuid' => $broker->getGuid(),
            'passkey' => $passkey),
            'type' => $ticketType,
            'redeemerGuid' => $labServer->getGuid());

//Create SOAP Client
        $client = new SoapClient($wsdl_url, array('soap_version' => SOAP_1_2,
            'trace' => 1,
            'encoding' => 'UTF-8'));

        $header = new SOAPHeader('http://ilab.mit.edu/iLabs/type', 'AgentAuthHeader', $headerParams);
        $client->__setSoapHeaders($header);
        $result = $client->__soapCall('RedeemTicket', array($params));

        return $result->RedeemTicketResult;

    }



}