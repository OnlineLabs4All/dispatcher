<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/12/15
 * Time: 2:44 PM
 */
namespace ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

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
    public function labConfiguration()
    {
        return new Response('Returns the lab configuration');
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


}