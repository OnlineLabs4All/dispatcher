<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 5/1/15
 * Time: 12:22 PM
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
//use SoapServer;


/**
 * @Route("/apis/isa")
 */
class iLabApiController extends Controller
{
    //This route accepts POST method and instantiate the SOAP server
    /**
     * @Route("/{labServerId}/soap", name="isa_apiRoot")
     * @Method({"POST"})
     *
     */
    public function batchedApiAction($labServerId)
    {
        //ini_set("soap.wsdl_cache_enabled", "0");
        $wsdl_url = getcwd()."/../src/DispatcherBundle/Utils/batchedLabServer.wsdl";

        $soapServer = new \SoapServer($wsdl_url);
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

    //This route accepts only GET method and returns the WSDL for an specific Lab Server
    /**
     * @Route("/{labServerId}/soap/", name="batched_ls_wsdl")
     * @Method({"GET"})
     *
     */
    public function indexAction(Request $request, $labServerId)
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


}