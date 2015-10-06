<?php
/**
 * User: Danilo G. Zutin
 * Date: 04.10.15
 * Time: 22:12
 */

namespace DispatcherBundle\Controller;

use Doctrine\DBAL\Platforms\Keywords\ReservedKeywordsValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


//This controller implements the WebLab Deusto HTTP Methods for batched laboratories
/**
 * @Route("/apis/weblab")
 */
class WeblabDeustoApiController extends Controller
{
    //This route accepts POST method and instantiate the SOAP server for BATCHED LABS
    /**
     * @Route("/login/json/", name="weblabdeusto_login")
     * @Method({"GET", "POST"})
     *
     */
    public function weblabDeustoLoginAction(Request $request)
    {
        $requestJson = json_decode($request->getContent());

        $responseJson = array('is_exception' => false,
                          'result' => array('id' => 'resulting-session-identifier(string)'));

        $response = new Response();
        $response->headers->set('Set-Cookie', 'weblabsessionid=123456');
        $response->headers->set('Content-type', 'application/json');
        $response->setContent(json_encode($responseJson));
        Return $response;

    }

}