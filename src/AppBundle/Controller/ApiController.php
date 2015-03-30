<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/12/15
 * Time: 2:44 PM
 */
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\ExperimentEngine;
use \DateTime;

/**
 * @Route("/apis/engine")
 */
class ApiController extends Controller
{
    /**
     * @Route("/", name="apiRoot")
     */
    public function indexAction()
    {
        return new Response('api root');
    }

    /**
     * @Route("/queueLength", name="eeQueueLength")
     * @Method({"GET"})
     */
    public function queueLengthAction()
    {
        return new Response('Returns the Length of the queue!');
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
     * @Route("/labInfo", name="getLabInfo")
     */
    public function labInfo()
    {
        return new Response('Returns the lab info');
    }

    /**
     * @Route("/experiment", name="dequeueExperiment")
     * @Method({"GET"})
     */
    public function getExperiment()
    {
        return new Response('Dequeues an experiment, set status to "In Progress", returns a protocol(?). Accepts only GET method');
    }

    /**
     * @Route("/experiment", name="postExperimentResults")
     * @Method({"POST"})
     */
    public function setExperiment()
    {
        return new Response('Saves experiment results to DB. Accepts only POST method');
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
     * @Route("/show", name="showEngine")
     * @Method({"GET"})
     */
    public function show()
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:ExperimentEngine');
        $engine = $repository->findOneBy(array('api_key' => 'fistEngineKey'));

        return new Response('Experiment Engine Id:'.$engine->getId());
    }



}