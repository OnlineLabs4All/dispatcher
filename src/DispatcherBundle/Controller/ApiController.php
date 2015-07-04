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
     * @ApiDoc(
     *  resource=true,
     *  resourceDescription="Returns the job queue length for a registered Experiment Engine.",
     *  description="Returns the current queue length for a registered Experiment Engine.",
     *  output ="DispatcherBundle\Model\LabInfo",
     *
     * statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * parameters={
     *      {"name"="X-apikey", "dataType"="header", "required"=true, "description"="category id"},
     *      {"name"="Authorization", "dataType"="header", "required"=true, "description"="Basic Http Authentication. username:password encoded as specified by RFC2045-MIME variant of Base64"},
     *  }
     * )
     *
     * @Route("/queueLength", name="eeQueueLength")
     * @Method({"GET"})
     */
    public function queueLengthAction(Request $request)
    {
        $engineService = $this->get('engineServices');
        $api_key = $request->headers->get('X-apikey');

        if ($api_key == null){
            $response = new response;
            $response->setStatusCode(401);
            return $response;
        }

        $format = $request->get('_format');

        $queueLength = $engineService->getQueueLength($api_key);
        $response = new response($queueLength->serialize($format));

        return $response;
    }

    /**
     * @Route("/labConfiguration", name="getLabConfiguration")
     * @Method({"GET"})
     */
    public function labConfiguration(Request $request)
    {

        $apikey = $request->headers->get('X-apikey');
        return new Response('Received key: '.$apikey);
    }

    /**
     * @Route("/labInfo", name="lLabInfo")
     * @Method({"GET"})
     *
     * @ApiDoc(
     *  resource=true,
     *  resourceDescription="Operations on Lab Servers",
     *  description="Returns the current information and status of the lab server",
     *  output ="DispatcherBundle\Model\LabInfo",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * )
     *
     */
    public function labInfo(Request $request)
    {
        $labInfo = new LabInfo();
        $labInfo->name = 'Lab Server 1';
        $labInfo->status = '1';
        $labInfo->owner_institution = '';
        $labInfo->description = 'This is the lab Info test';
        $format = $request->get('_format');
        $response = new response($labInfo->serialize($format));
        return $response;
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
    public function getExperiment()
    {
        return new Response('Dequeues an experiment, set status to "In Progress", returns a protocol(?). Accepts only GET method');
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
     *  description="Checks on the status of the job records and returns the ID of the queued experiment with highest priority ",
     *
     *  statusCodes={
     *         200="Returned when successful",
     *         401="Unauthorized"},
     * )
     */
    public function getStatus()
    {
        $response_array = array('found' => true,
                                'expId' => 2,
                                'errorMessage' => '');
        return new Response('');
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



}