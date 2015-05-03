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
use SoapServer;


/**
 * @Route("/apis/isa/soap")
 */
class iLabApiController extends Controller
{

    /**
     * @Route("/wsdl/", name="isa_batched_ls_wsdl")
     *
     */
    public function getWsdlAction()
    {
        //returns the wsdl
        //echo "test";
        $response = $this->render('wsdl/batchedLs.wsdl.twig');
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }

    /**
     * @Route("/", name="isa_apiRoot")
     *
     */
    public function indexAction(Request $request)
    {
        //ini_set("soap.wsdl_cache_enabled", "0");
        //$wsdl_url = "http://".$request->getHttpHost()."/apis/isa/soap/wsdl/";
        $wsdl_url = "http://localhost/batchedLabServer.wsdl";

        $soapServer = new \SoapServer($wsdl_url);
        //$soapServer->setObject($this->get('BatchedLabServerApi'));
        //var_dump($soapServer);
        $iLabBatched = $this->get('BatchedLabServerApi');
        $soapServer->setObject($iLabBatched);

        $response = new Response();
        $response->headers->set('Content-Type','application/soap+xml; charset=utf-8');
        ob_start();
        $soapServer->handle();

        $response->setContent(ob_get_clean());

        return $response;

        //$result = $iLabBatched->GetLabInfo();
        //$result2 = $iLabBatched->GetLabStatus();

        //var_dump($result2);

        //$serviceObj = new BatchedLabServerApi();



        //return $response;


        //$wsdl_url = "http://".$request->getHttpHost()."/wsdl/batchedLabServer.wsdl";
        //$server = new \SoapServer($wsdl_url);
        //return new Response($result['GetLabInfoResult']);

        //echo $result['GetLabInfoResult'];

        //$response = $this->render('wsdl/batchedLs.wsdl.twig');
        //$response->headers->set('Content-Type', 'application/xml');
        //return $response;

    }



}