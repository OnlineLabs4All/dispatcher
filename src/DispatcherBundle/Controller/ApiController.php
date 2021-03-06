<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/12/15
 * Time: 2:44 PM
 */
namespace DispatcherBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use DispatcherBundle\Entity\ExperimentEngine;
use DispatcherBundle\Entity\LabServer;
use \DateTime;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use DispatcherBundle\Model\Subscriber\LabInfo;
use DispatcherBundle\Model\Subscriber\QueueLength;
use SoapClient;
use SoapHeader;
use SimpleXMLElement;


/**
 * @Route("/apis/engine")
 */
class ApiController extends Controller
{
    /**
     * @Route("/", name="apiRoot")
     *
     */

    public function indexAction()
    {
        return new Response('api root');
    }

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Returns the current information and status of the lab server for which this engine subscribes.",
     *  output ="DispatcherBundle\Model\LabInfo",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * )
     *
     * @Route("/queueLength", name="eeQueueLength")
     * @Method({"GET"})
     */
    public function queueLengthAction(Request $request)
    {
        $engine= $this->get('security.token_storage')->getToken()->getUser();
        $engineService = $this->get('engineServices');
        $format = $request->get('_format');
        $queueLength = $engineService->getQueueLength($engine);
        $response = new response($queueLength->serialize($format));

        return $response;
    }

    /*
    public function labConfiguration(Request $request)
    {
        $engine= $this->get('security.token_storage')->getToken()->getUser();
        $engineService = $this->get('engineServices');
        //getLabConfiguration
        $labConfiguration = $engineService->getLabConfiguration($engine);

        return new Response($labConfiguration);
    }
    */

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Returns the current information and status of the lab server for which this engine subscribes.",
     *  output ="DispatcherBundle\Model\LabInfo",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * )
     * @Route("/labInfo", name="labInfo")
     * @Method({"GET"})
     */
    public function labInfo(Request $request)
    {
        $engine= $this->get('security.token_storage')->getToken()->getUser();
        $engineService = $this->get('engineServices');
        //getLabInfo
        $labInfo = $engineService->getLabInfo($engine);
        $format = $request->get('_format');

        return new Response($labInfo->serialize($format));
    }

    /**
     * @Route("/labConfiguration", name="setLabConfiguration")
     * @Method({"PUT", "POST"})
     *
     *  @ApiDoc(
     *  resource=true,
     *  resourceDescription="Updates lab configuration of a lab server",
     *  description="Sends the lab configuration to the server. The request body must only contain the JSON encoded name/value pair 'labConfiguration'.",
     *
     *  requirements={
     *      {
     *          "name"="labConfiguration",
     *          "dataType"="string",
     *          "requirement"="mandatory",
     *          "description"="Contains lab configuration."
     *      }
     *  },
     *  output ="",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized",
     *         415="JSON Request not provided"},
     * )
     */
    public function setLabConfiguration(Request $request)
    {
        $engine = $this->get('security.token_storage')->getToken()->getUser();

        //check engine authorization (singleEngine)
        $labServer = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $engine->getLabServerId()));

        if (($labServer->getSingleEngine()) === false ){
            $response = new response;
            $response->setStatusCode(401);
            return $response;
        }

        //check, if request is of type JSON
        $jsonString = $request->getContent();
        $result = json_decode($jsonString);

        if ($result == null)
        {
            $response = new response;
            $response->setStatusCode(415);
            return $response;
        }

        //update labConfiguration
        $engineService = $this->get('engineServices');
        $experiment = $engineService->setLabConfiguration($engine, $result);
        
        //format and return
        $format = $request->get('_format');

        if ($format == 'xml'){
            $xml = new SimpleXMLElement('<LabConfiguration/>');
            $xml->addChild('success', $experiment->success);
            $xml->addChild('message', $experiment->message);
            return new Response($xml->asXML());
        }

        return new Response(json_encode($experiment));
    }

    /**
     * @Route("/experiment", name="dequeueExperiment")
     * @Method({"GET"})
     *
     *  @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Dequeues one submitted experiment by retrieving the experiment specification",
     *  output ="DispatcherBundle\Model\LabInfo",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * )
     */
    public function getExperiment(Request $request)
    {
        $engine= $this->get('security.token_storage')->getToken()->getUser();
        $engineService = $this->get('engineServices');
        //getExperiment
        $experiment = $engineService->getExperiment($engine);
        $format = $request->get('_format');

        return new Response($experiment->serialize($format));
    }

    /**
     * @Route("/experiment", name="postExperimentResults")
     * @Method({"PUT", "POST"})
     *
     *  @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Sends the experiment results to the server. The request body must contain a collection of JSON encoded name/value pairs as described below.",
     *
     *  requirements={
     *      {
     *          "name"="success",
     *          "dataType"="bool",
     *          "requirement"="mandatory",
     *          "description"="specifies if experiment was successfully executed "
     *      },
     *      {
     *          "name"="results",
     *          "dataType"="string",
     *          "requirement"="mandatory",
     *          "description"="Lab specific representation of experiment results"
     *      },
     *      {
     *          "name"="errorReport",
     *          "dataType"="string",
     *          "requirement"="optional",
     *          "description"="An optional error message"
     *      }
     *  },
     *  output ="DispatcherBundle\Model\LabInfo",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized",
     *         415="JSON Request not provided"},
     * )
     */

    public function setExperiment(Request $request)
    {
        $engine= $this->get('security.token_storage')->getToken()->getUser();
        $jsonString = $request->getContent();
        $result = json_decode($jsonString);

        if ($result == null)
        {
            $response = new response;
            $response->setStatusCode(415);
            return $response;
        }

        $engineService = $this->get('engineServices');
        $experiment = $engineService->setExperiment($engine, $result);
        $format = $request->get('_format');

        return new Response($experiment->serialize($format));
    }

    /**
     * @Route("/status", name="getStatus")
     * @Method({"GET"})
     *
     *  @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Checks on the status of the job records and returns the ID of the queued experiment that shall be executed next considering the priority. If found, claims ownership over the found experiment.
       After ownership is granted, the experiment will not be available for other engines subscribing for the same Lab server",
     *
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized: Wrong username/password",},
     *
     * )
     */
    public function getStatus(Request $request)
    {
        $engine= $this->get('security.token_storage')->getToken()->getUser();

        $engineService = $this->get('engineServices');
        //get Experiment Status
        $status = $engineService->getStatus($engine);
        $format = $request->get('_format');

        $response = new Response();
        $response->setContent($status->serialize($format));
        return $response;
    }

    /**
     * @Route("/release", name="setStatus")
     * @Method({"PUT"})
     *
     *  @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Allows Experiment Engine to release the experiment by revoking its ownership. Only the owner engine can call this service",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * )
     */
    public function releaseExperiment(Request $request)
    {
        $engine= $this->get('security.token_storage')->getToken()->getUser();

        $engineService = $this->get('engineServices');
        //getStatus
        $status = $engineService->getStatus($engine);
        $format = $request->get('_format');

        return new Response($status->serialize($format));
    }

    /**
     * @Route("/register", name="registerEngine")
     * @Method({"GET"})
     */
    public function register()
    {
        $engine = new ExperimentEngine();
        $engine->setActive('1');
        $engine->setApiKey('fistEngineKey');
        $engine->setDescription('This is a test Engine');
        $engine->setLabserverId('1');
        $engine->setName('Test Engine 1');
        $engine->setHttpAuthentication('test');
        $engine->setOwnerId('1');
        $now   = new DateTime(date('Y-m-d H:i:s'));
        $engine->setDateCreated($now);

        $em = $this->getDoctrine()->getManager();
        $em->persist($engine);
        $em->flush();

        return new Response('Creted experiment Engine Id:'.$engine->getId());
    }

    /**
     * @Route("/test", name="testAction")
     * @Method({"GET"})
     */
    public function test()
    {


    }

    /**
     * http://ilab.mit.edu/iLabs/Services/RedeemTicket
     *
     * @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Verifies is a coupon that identifies a ticket is valid and returns true/false.",
     *  output ="",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized",
     *         415="Json not set."},
     *
     * )
     * @Route("/verifyCoupon", name="verifyCoupon")
     * @Method({"POST"})
     */

    public function verifyCoupon(Request $request)
    {
        $api_key = $request->headers->get('X-apikey');

        $engine = $this->getDoctrine()
            ->getRepository('DispatcherBundle:ExperimentEngine')
            ->findOneBy(array('api_key' => $api_key));

        if ($engine == NULL ){
            $response = new response;
            $response->setStatusCode(401);
            return $response;
        }

        $jsonString = $request->getContent();
        $jsonRequest = json_decode($jsonString);

        //$payload = $iLabLabServer->redeemTicket($jsonRequest->couponId, $jsonRequest->passkey, $labServer, $broker );

        if ($jsonRequest == null)
        {
            $response = new response;
            $response->setStatusCode(415);
            return $response;
        }

        $engineService = $this->get('engineServices');
        //retrieve Ticket from Broker
        $ticket = $engineService->verifyExecuteExperimentCoupon($engine, $jsonRequest->couponId, $jsonRequest->passkey);

        $response = new Response();
        $response->setContent(json_encode($ticket));
        $response->headers->set('Content-Type', 'application/json');


        return $response;
    }

    /**
     *
     *
     * @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Retrieves a ticket Payload that includes reserved time and information about user.",
     *  output ="",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * parameters={
     *      {"name"="X-apikey", "dataType"="header", "required"=true, "description"="Registered API Key"},
     *      {"name"="Authorization", "dataType"="header", "required"=true, "description"="Basic Http Authentication. username:password encoded as specified by RFC2045-MIME variant of Base64"},
     *  }

     * )
     * @Route("/ticket", name="retrieveTicket")
     * @Method({"POST"})
     */

    public function retrieveTicket(Request $request)
    {
        $api_key = $request->headers->get('X-apikey');

        $engine = $this->getDoctrine()
            ->getRepository('DispatcherBundle:ExperimentEngine')
            ->findOneBy(array('api_key' => $api_key));

        if ($engine == NULL ){
            $response = new response;
            $response->setStatusCode(401);
            return $response;
        }

        $jsonString = $request->getContent();
        $jsonRequest = json_decode($jsonString);

        //$payload = $iLabLabServer->redeemTicket($jsonRequest->couponId, $jsonRequest->passkey, $labServer, $broker );

        if ($jsonRequest == null)
        {
            $response = new response;
            $response->setStatusCode(415);
            return $response;
        }

        $engineService = $this->get('engineServices');
        //retrieve Ticket from Broker
        $ticket = $engineService->retrieveExecuteExperimentTicket($engine, $jsonRequest->couponId, $jsonRequest->passkey);

        $response = new Response();
        $response->setContent(json_encode($ticket));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}