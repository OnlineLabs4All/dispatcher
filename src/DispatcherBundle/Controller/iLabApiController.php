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
    public function batchedApiAction($labServerId)
    {
        ini_set("soap.wsdl_cache_enabled", "0");
        $wsdl_url = getcwd()."/../src/DispatcherBundle/Utils/batchedLabServer.wsdl";

        $soapServer = new \SoapServer($wsdl_url, array('soap_version' => SOAP_1_2));
        //$soapServer->setObject($this->get('BatchedLabServerApi'));
        //var_dump($soapServer);
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

    //This route accepts POST method and instantiate the SOAP server
    /**
     * @Route("/{labServerId}/ils/soap", name="isa_apiRoot_ils")
     * @Method({"POST"})
     *
     */
    public function interactiveApiAction(Request $request, $labServerId)
    {
        ini_set("soap.wsdl_cache_enabled", "0");
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
     * @Route("/{labServerId}/ils/soap/", name="interactive_ls_wsdl")
     * @Method({"GET"})
     *
     */
    public function getInteractiveWsdlAction(Request $request, $labServerId)
    {
        //$wsdl_gen = $this->get('wsdlGenerator');
        $service_url = $request->getScheme()."://".$request->getHttpHost()."/apis/isa/".$labServerId."/ils/soap";
        //returns the wsdl
        //echo "test";
        $response = $this->render('wsdl/interactiveLs.wsdl.twig', array('service_url'=> $service_url));
        //$response->headers->set('Content-Type', 'application/xml');
        //return $response;
        //$response = new Response($wsdl_gen->getBatchedLsWsdl());
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }

    //This route accepts only GET method and returns the WSDL for an specific Lab Server
    /**
     * @Route("/{labServerId}/soap/", name="batched_ls_wsdl")
     * @Method({"GET"})
     *
     */
    public function getBatchedWsdlAction(Request $request, $labServerId)
    {
        //$wsdl_gen = $this->get('wsdlGenerator');
        $service_url = $request->getScheme()."://".$request->getHttpHost()."/apis/isa/".$labServerId."/soap";
        //returns the wsdl
        //echo "test";
        $response = $this->render('wsdl/batchedLs.wsdl.twig', array('service_url'=> $service_url));
        //$response->headers->set('Content-Type', 'application/xml');
        //return $response;
        //$response = new Response($wsdl_gen->getBatchedLsWsdl());
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }

    /**
     * @Route("/test/{experimentID}", name="test_route")
     *
     */
    public function testAction(Request $request, $experimentID)
    {
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:JobRecord');

        $jobRecord =  $repository->findOneBy(array('rlmsAssignedId' => $experimentID, 'providerId' => '9954C5B79AEB432A94DE29E6EE44EB69'));
        $statusCode = $jobRecord->getJobStatus();
        $effectiveQueueLength = 2;
        $estWait = 32;
        $estRuntime = 15;
        $estRemainingRuntime = 54;
        $minTimetoLive= 7200;

        $opaque = json_decode($jobRecord->getOpaqueData());



        $response = array('GetExperimentStatusResult' => array(
            'statusReport' => array('statusCode' =>  $statusCode,
                'wait' => array('effectiveQueueLength' => $effectiveQueueLength,
                    'estWait' => $estWait),
                'estRuntime' => $estRuntime,
                'estRemainingRuntime' => $estRemainingRuntime),
            'minTimetoLive' => $minTimetoLive));

        return new Response($opaque->userGroup);
    }

}