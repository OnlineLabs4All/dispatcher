<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 5/1/15
 * Time: 12:22 PM
 */

namespace DispatcherBundle\Controller;

use Doctrine\DBAL\Platforms\Keywords\ReservedKeywordsValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use DispatcherBundle\Entity\ExperimentEngine;
use \DateTime;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use DispatcherBundle\Model\Subscriber\LabInfo;
use Symfony\Component\Console\Output\OutputInterface;
//use SoapServer;


/**
 * @Route("/apis/isa")
 */
class iLabApiController extends Controller
{
    //This route accepts POST method and instantiate the SOAP server for BATCHED LABS
    /**
     * @Route("/{labServerId}/soap", name="isa_apiRoot")
     * @Method({"POST"})
     *
     */
    public function batchedApiAction(Request $request, $labServerId)
    {
        ini_set("soap.wsdl_cache_enabled", "0");
        $wsdl_url = getcwd()."/../src/DispatcherBundle/Utils/batchedLabServer.wsdl";

        $soapServer = new \SoapServer($wsdl_url, array('soap_version' => SOAP_1_2));
        $iLabBatched = $this->get('iLabLabServer');
        $iLabBatched->setLabServerId($labServerId);
        $soapServer->setObject($iLabBatched);
        $response = new Response();
        $response->headers->set('Content-Type','application/soap+xml; charset=utf-8');
        ob_start();
        $soapServer->handle();
        $response->setContent(ob_get_clean());
        return $response;
    }

    //This route accepts POST method and instantiate the SOAP server for Interactive labs
    /**
     * @Route("/{labServerId}/ils/soap", name="isa_apiRoot_ils")
     * @Method({"POST"})
     *
     */
    public function interactiveApiAction(Request $request, $labServerId)
    {
        ini_set("soap.wsdl_cache_enabled", "1");
        $wsdl_url = getcwd()."/../src/DispatcherBundle/Utils/interactiveLabServer.wsdl";

        $soapServer = new \SoapServer($wsdl_url, array('soap_version' => SOAP_1_2));
        //$soapServer->setObject($this->get('BatchedLabServerApi'));
        //var_dump($soapServer);
        $iLabLabServer = $this->get('iLabLabServer');
        $iLabLabServer->setLabServerId($labServerId);
        $iLabLabServer->setServiceUrl($service_url = $request->getScheme()."://".$request->getHttpHost()."/apis/isa/".$labServerId."/ils/soap");
        $soapServer->setObject($iLabLabServer);
        $response = new Response();
        $response->headers->set('Content-Type','application/soap+xml; charset=utf-8');
        ob_start();
        $soapServer->handle();
        $response->setContent(ob_get_clean());
        return $response;
    }

    //This route accepts only GET method and returns the WSDL for an specific Lab Server
    /**
     * @Route("/{labServerId}/ils/soap", name="interactive_ls_wsdl")
     * @Method({"GET"})
     *
     */
    public function getInteractiveWsdlAction(Request $request, $labServerId)
    {
        $service_url = $request->getScheme()."://".$request->getHttpHost()."/apis/isa/".$labServerId."/ils/soap";
        //returns the wsdl
        $response = $this->render('wsdl/interactiveLs.wsdl.twig', array('service_url'=> $service_url));
        //return $response;
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }

    //This route accepts only GET method and returns the WSDL for an specific Lab Server
    /**
     * @Route("/{labServerId}/soap", name="batched_ls_wsdl")
     * @Method({"GET"})
     *
     */
    public function getBatchedWsdlAction(Request $request, $labServerId)
    {
        $service_url = $request->getScheme()."://".$request->getHttpHost()."/apis/isa/".$labServerId."/soap";
        //returns the wsdl
        $response = $this->render('wsdl/batchedLs.wsdl.twig', array('service_url'=> $service_url));
        //return $response;
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }

    // ========== ISA Json API - University of Queensland =====================

    /**
     * @Route("/{labServerId}/json", name="isa_json_api")
     * @Method({"POST", "GET"})
     *
     */
    public function batchedJsonApiAction(Request $request, $labServerId)
    {
        //read request
        $jsonRequestString = $request->getContent();
        $jsonRequest = json_decode($jsonRequestString);

        //Authenticate request
        $iLabAuthenticator = $this->get('IsaRlmsAuthenticator');
        $auth = $iLabAuthenticator->authenticateMethodUqBroker($jsonRequest, $jsonRequest->token, $jsonRequest->guid, $labServerId);

        if ($auth['authenticated'] == true){

            $iLabBatched = $this->get('genericLabServerServices');
            $iLabBatched->setLabServerId($labServerId);
            $action = $jsonRequest->action;

            switch ($action){
                case 'getLabConfiguration':
                    $jsonResponse = $iLabBatched->getLabConfiguration();
                    break;
                case 'getLabStatus':
                    $jsonResponse = $iLabBatched->getLabStatus();
                    break;
                case 'getLabInfo':
                    $jsonResponse = $iLabBatched->getLabInfo();
                    break;
                case 'getEffectiveQueueLength':
                    $priorityHint = $jsonRequest->params->priorityHint;
                    $jsonResponse = $iLabBatched->getEffectiveQueueLength($priorityHint);
                    break;
                case 'submit':
                    $rlmsExpId = $jsonRequest->params->experimentID;
                    $experimentSpecification = $jsonRequest->params->experimentSpecification;
                    $opaqueData = $jsonRequest->params->userGroup; //stored in the opaque data field
                    $priorityHint = $jsonRequest->params->priorityHint;
                    $rlmsGuid = $jsonRequest->guid;
                    $jsonResponse = $iLabBatched->submit($rlmsExpId, $experimentSpecification, $opaqueData, $priorityHint, $rlmsGuid);
                    break;
                case 'registerBroker': //TODO: Actually register new RLMS on Dispatcher
                    $jsonResponse = array('labGUID' => $iLabBatched->getLabServerGuid());
                    break;
                case 'getExperimentStatus':
                    $experimentId = $jsonRequest->params->experimentID;
                    $rlmsGuid = $jsonRequest->guid;
                    $jsonResponse = $iLabBatched->getExperimentStatus($experimentId, $rlmsGuid);
                    break;
                case 'retrieveResult':
                    $experimentId = $jsonRequest->params->experimentID;
                    $rlmsGuid = $jsonRequest->guid;
                    $jsonResponse = $iLabBatched->retrieveResult($experimentId, $rlmsGuid);
                    break;
                case 'cancel':
                    $experimentId = $jsonRequest->params->experimentID;
                    //$rlmsGuid = $jsonRequest->guid;
                    $jsonResponse = $iLabBatched->cancelExperiment($experimentId);
                    break;
                default:
                    $jsonResponse = array();
                    break;
            }
            $response = new Response(json_encode($jsonResponse));
            $response->headers->set('Content-Type','application/json; charset=utf-8');
            return $response;
        }
        $response = new response;
        $response = new Response(json_encode($auth));
        $response->headers->set('Content-Type','application/json; charset=utf-8');
        return $response;
    }

    //The following method is to help the developer to calculate the token for a request, assuming a lab server exists.

    /**
     * @Route("/calculateToken/{labServerId}", name="calculateToken")
     * @Method({"POST"})
     *
     */
    public function calculateTokenAction(Request $request, $labServerId)
    {
        //read request
        $jsonRequestString = $request->getContent();
        $jsonRequest = json_decode($jsonRequestString);

        //Authenticate request
        $iLabAuthenticator = $this->get('IsaRlmsAuthenticator');
        $result = $iLabAuthenticator->calculateToken($jsonRequest, $jsonRequest->token, $jsonRequest->guid, $labServerId);

        return new Response($result);
    }

}