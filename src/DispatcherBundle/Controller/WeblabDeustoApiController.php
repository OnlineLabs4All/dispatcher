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
use DispatcherBundle\Entity\LabSession;

//This controller implements the WebLab Deusto HTTP Methods for batched laboratories
/**
 * @Route("/apis/weblab")
 */
class WeblabDeustoApiController extends Controller
{
    //This route accepts POST method and instantiate the SOAP server for BATCHED LABS
    /**
     * @Route("/login/json", name="weblabdeusto_login")
     * @Method({"GET", "POST"})
     *
     */
    public function weblabDeustoLoginAction(Request $request)
    {
        $requestString = $request->getContent();
        $requestJson = json_decode($requestString);


        $webLabAuthenticator = $this->get('webLabRlmsAuthenticator');
        //var_dump($requestJson);
        $username = $requestJson->params->username;
        $password = $requestJson->params->password;
        $authResp = $webLabAuthenticator->webLabLogin($username, $password);

        $responseJson = array('is_exception' => $authResp['is_exception'],
                          'result' => array('id' => $authResp['session_id']));
        $response = new Response();
        $response->headers->set('Set-Cookie', 'weblabsessionid='.$authResp['session_id']);
        $response->headers->set('Content-type', 'application/json');
        $response->setContent(json_encode($responseJson));
        Return $response;
    }

    /**
     * @Route("/json", name="weblabdeusto_login2")
     * @Method({"POST"})
     *
     */
    public function weblabDeustoAction(Request $request)
    {
        $requestJson = json_decode($request->getContent());
        $method = $requestJson->method;
        $webLabAuthenticator = $this->get('webLabRlmsAuthenticator');
        $webLabService = $this->get('webLabDeustoServices');
        $response = new Response();

        switch($method){
            case 'login':
                $webLabAuthenticator = $this->get('webLabRlmsAuthenticator');
                //var_dump($requestJson);
                $username = $requestJson->params->username;
                $password = $requestJson->params->password;
                $authResp = $webLabAuthenticator->webLabLogin($username, $password);

                $responseJson = array('is_exception' => $authResp['is_exception'],
                    'result' => array('id' => $authResp['session_id']));
                $response->headers->set('Set-Cookie', 'weblabsessionid='.$authResp['session_id']);
                break;
            case 'list_experiments':
                $session_id = $requestJson->params->session_id->id;

                $session = $webLabAuthenticator->validateSessionById($session_id);

                if ($session != null){

                    $responseJson = $webLabService->listExperiments($session);

                }
        }

        $response->headers->set('Content-type', 'application/json');
        $response->setContent(json_encode($responseJson));
        Return $response;

    }



}