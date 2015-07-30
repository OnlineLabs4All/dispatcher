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
use \DateTime;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use DispatcherBundle\Model\Subscriber\LabInfo;
use DispatcherBundle\Model\Subscriber\QueueLength;


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
     * @Route("/queueLength", name="eeQueueLength")
     * @Method({"GET"})
     */
    public function queueLengthAction(Request $request)
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
        $engineService = $this->get('engineServices');
        $format = $request->get('_format');
        $queueLength = $engineService->getQueueLength($engine);
        $response = new response($queueLength->serialize($format));

        return $response;
    }

    public function labConfiguration(Request $request)
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
        $engineService = $this->get('engineServices');
        //getLabConfiguration
        $labConfiguration = $engineService->getLabConfiguration($engine);

        return new Response($labConfiguration);
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
     * parameters={
     *      {"name"="X-apikey", "dataType"="header", "required"=true, "description"="Registered API Key"},
     *      {"name"="Authorization", "dataType"="header", "required"=true, "description"="Basic Http Authentication. username:password encoded as specified by RFC2045-MIME variant of Base64"},
     *  }

     * )
     * @Route("/labInfo", name="labInfo")
     * @Method({"GET"})
     */

    public function labInfo(Request $request)
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
        $engineService = $this->get('engineServices');
        //getLabConfiguration
        $labInfo = $engineService->getLabInfo($engine);
        $format = $request->get('_format');

        return new Response($labInfo->serialize($format));
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
        $api_key = $request->headers->get('X-apikey');

        $engine = $this->getDoctrine()
            ->getRepository('DispatcherBundle:ExperimentEngine')
            ->findOneBy(array('api_key' => $api_key));

        if ($engine == NULL ){
            $response = new response;
            $response->setStatusCode(401);
            return $response;
        }
        $engineService = $this->get('engineServices');
        //getLabConfiguration
        $status = $engineService->getExperiment($engine);
        $format = $request->get('_format');

        return new Response($status->serialize($format));
    }

    /**
     * @Route("/experiment", name="postExperimentResults")
     * @Method({"POST"})
     *
     *  @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Sends the experiment results to the server",
     *  output ="DispatcherBundle\Model\LabInfo",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * )
     */
    public function setExperiment()
    {
        return new Response('Saves experiment results to DB. Accepts only POST method');
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
     *         401="Unauthorized"},
     *
     *
     * )
     */
    public function getStatus(Request $request)
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
        $engineService = $this->get('engineServices');
        //getLabConfiguration
        $status = $engineService->getStatus($engine);
        $format = $request->get('_format');

        return new Response($status->serialize($format));
    }

    /**
     * @Route("/status", name="setStatus")
     * @Method({"POST"})
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
    public function setStatus(Request $request)
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
        $engineService = $this->get('engineServices');
        //getLabConfiguration
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
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:JobRecord');

        $query = $repository->createQueryBuilder('job')
            ->where('job.expId <= :expId')
            ->andWhere('job.labServerId = :labServerId')
            ->andWhere('job.priority >= :priority')
            ->setParameter('expId', 23)
            ->setParameter('labServerId', 1)
            ->setParameter('priority', 0)
            ->orderBy('job.expId', 'ASC')
            ->select('COUNT(job)')
            ->getQuery()
            ->getSingleScalarResult();

        //$jobRecords = $query->getResult();

        return new Response('Number of exp: '.$query);
    }

    /**
     *
     *
     * @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Verifies is a coupon that identifies a ticket is valid and returns true/false.",
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
     * @Route("/verifyCoupon", name="verifyCoupon")
     * @Method({"POST"})
     */

    public function verifyCoupon(Request $request)
    {
        $jsonString = $request->getContent();

        $jsonRequest = json_decode($jsonString);

        if ($jsonRequest->couponId == '12345' & $jsonRequest->passkey == '67890')
        {
            $jsonResponse = array('valid' => true,
                'couponId' => $jsonRequest->couponId,
                'passkey' => $jsonRequest->passkey,
                'type' => 'EXECUTE EXPERIMENT');
        }
        else{
            $jsonResponse = array('valid' => false,
                'couponId' => '',
                'passkey' => '',
                'type' => 'EXECUTE EXPERIMENT');
        }

        $response = new Response();
        $response->setContent(json_encode($jsonResponse));
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

    public function retrieveCoupon(Request $request)
    {
        $jsonString = $request->getContent();

        $jsonRequest = json_decode($jsonString);

        if ($jsonRequest->couponId == '12345' & $jsonRequest->passkey == '67890')
        {
            $jsonResponse = array('success' => true,
                                  'startExecution' => date('Y-m-d\TH:i:sP'),//.substr((string)microtime(), 1, 8),//'2015-07-20T08:11:47.7149599Z',
                                  'duration' => rand(300, 7200),
                                  'userID' => rand(1, 100),
                                  'groupID' => rand(1, 10),
                                  'groupName' => 'Experiment_Group',
                                  'sbGuid' => '7954C5B79876532A94DE29E6EE44EB69',
                                  'experimentID' => 2729,
                                  'userTZ' => date_default_timezone_get());
        }
        else{
            $jsonResponse = array('success' => false,
                'errorMessage' => 'invalid couponID and/or passkey');
        }

        $response = new Response();
        $response->setContent(json_encode($jsonResponse));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }





}