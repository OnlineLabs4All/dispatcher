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

    private function sendSoapRequest(LabServer $labServer, $params, $method)
    {
        $headerParams = array(
            'identifier' => $labServer->getIsaIdentifier(), //Original Broker GUID authorized to make this request
            'passKey' => $labServer->getIsaPasskeyToLabServer());

        //Cache the WSDL so that it is not loaded again and again
        ini_set("soap.wsdl_cache_enabled", "1");
        //Create SOAP Client
        $client = new SoapClient($labServer->getIsaWsdlUrl(), array('soap_version' => SOAP_1_2,
            'trace' => 1,
            'encoding' => 'UTF-8'));

        $header = new SOAPHeader('http://ilab.mit.edu', 'AuthHeader', $headerParams);
        $client->__setSoapHeaders($header);
        $result = $client->__soapCall($method, array($params));

        if (is_soap_fault($result)) {

            return array(
                'exception' => true,
                'message' => 'Error federating SOAP request to Lab Server. '.$result->faultcode.':'.$result->faultstring);
        }
        return array(
            'exception' => false,
            'result' => $result);

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

    public function getLabInfo(LabServer $labServer)
    {
        //Set Body parameters
        $params =  array();

        $response = $this->sendSoapRequest($labServer, $params, 'GetLabInfo');

        return array(
        'exception' => false,
        'result' => $response['result']->GetLabInfoResult);
    }

    public function getLabConfiguration(LabServer $labServer, $userGroup = null)
    {
        //Set Body parameters
        $params =  array('userGroup' => $userGroup);

        $response = $this->sendSoapRequest($labServer, $params, 'GetLabConfiguration');

        if ($response['exception']){
            return $response;
        }

        return array(
            'exception' => false,
            'result' => $response['result']->GetLabConfigurationResult);
    }

    public function submit(LabServer $labServer, $expId, $expSpecification, $priorityHint, $userGroup = null)
    {
        //Set Body parameters
        $params =  array(
            'experimentID' => $expId,
            'experimentSpecification' => $expSpecification,
            'userGroup' => $userGroup,
            'priorityHint' => $priorityHint);

        $response = $this->sendSoapRequest($labServer, $params, 'Submit');

        if ($response['exception']){
            return $response;
        }

        return array(
            'exception' => false,
            'result' => $response['result']->SubmitResult);
    }

    public function getExperimentStatus(LabServer $labServer, $expId)
    {
        //Set Body parameters
        $params =  array(
            'experimentID' => $expId);

        $response = $this->sendSoapRequest($labServer, $params, 'GetExperimentStatus');

        if ($response['exception']){
            return $response;
        }

        return array(
            'exception' => false,
            'result' => $response['result']->GetExperimentStatusResult);
    }

    public function retrieveResult(LabServer $labServer, $expId)
    {
        //Set Body parameters
        $params =  array(
            'experimentID' => $expId);

        $response = $this->sendSoapRequest($labServer, $params, 'RetrieveResult');

        if ($response['exception']){
            return $response;
        }

        return array(
            'exception' => false,
            'result' => $response['result']->RetrieveResultResult);
    }


}