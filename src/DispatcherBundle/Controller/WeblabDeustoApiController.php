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
     * @Route("/login/json/", name="weblabdeusto_login")
     * @Method({"GET", "POST"})
     *
     */
    public function weblabDeustoLoginAction(Request $request)
    {
        $myfile = fopen('login.txt','w') or die("Unable to open file");
        fwrite($myfile, $request->getContent());
        fclose($myfile);
        $requestString = $request->getContent();
        $requestJson = json_decode($requestString);


        $webLabAuthenticator = $this->get('webLabRlmsAuthenticator');
        //var_dump($requestJson);
        $username = $requestJson->params->username;
        $password = $requestJson->params->password;
        $authResp = $webLabAuthenticator->webLabLogin($username, $password);

        if ( $authResp['is_exception'] == true){
            $responseJson = array('is_exception' => true,
                                  'message' => $authResp['message'],
                                  'code' => $authResp['code']);
        }
        else{
            $responseJson = array('is_exception' => $authResp['is_exception'],
                'result' => array('id' => $authResp['session_id']));
        }

        $response = new Response();
        $response->headers->set('Set-Cookie', 'weblabsessionid='.$authResp['session_id']);
        $response->headers->set('Content-type', 'application/json');
        $response->setContent(json_encode($responseJson));
        Return $response;
    }

    /**
     * @Route("/json/", name="weblabdeusto_login2")
     * @Method({"POST"})
     *
     */
    public function weblabDeustoAction(Request $request)
    {
        $myfile = fopen('webservice.txt','w') or die("Unable to open file");
        fwrite($myfile, $request->getContent());
        fclose($myfile);

        $requestJson = json_decode($request->getContent());
        $method = $requestJson->method;
        $params = $requestJson->params;

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

                if ( $authResp['is_exception'] == true){
                    $responseJson = array('is_exception' => true,
                        'message' => $authResp['message'],
                        'code' => $authResp['code']);
                }
                else{
                    $responseJson = array('is_exception' => $authResp['is_exception'],
                        'result' => array('id' => $authResp['session_id']));
                }

                $response->headers->set('Set-Cookie', 'weblabsessionid='.$authResp['session_id']);
                break;
            case 'list_experiments':
                $session_id = $requestJson->params->session_id->id;
                $labSession = $webLabAuthenticator->validateSessionById($session_id);

                if ($labSession != null) {
                    $responseJson = $webLabService->listExperiment($labSession);
                }
                else{
                    $responseJson = array('is_exception' => true,
                        'message' => 'Session does not exist or has already expired. Please login again.',
                        'code' => 'Client.SessionNotFound');
                }
                break;
            case 'reserve_experiment':

                //$myfile = fopen('webservice.txt','w') or die("Unable to open file");
                //fwrite($myfile, $request->getContent());
                //fclose($myfile);

                $session_id = $requestJson->params->session_id->id;
                $labSession = $webLabAuthenticator->validateSessionById($session_id);

                if ($labSession != null) {
                    $rlmsId = $labSession->getRlmsId();
                    $responseJson = $webLabService->reserveExperiment($params, $rlmsId);
                }
                else{
                    $responseJson = array('is_exception' => true,
                        'message' => 'Session does not exist or has already expired. Please login again.',
                        'code' => 'Client.SessionNotFound');
                }
                break;
            case 'get_reservation_status':

                //$myfile = fopen('webservice.txt','w') or die("Unable to open file");
                //fwrite($myfile, $request->getContent());
                //fclose($myfile);

                $session_id = $request->cookies->get('weblabsessionid');
                $labSession = $webLabAuthenticator->validateSessionById($session_id);

                if ($labSession != null) {
                    $rlmsId = $labSession->getRlmsId();
                    $labServerId = $labSession->getLabServerId();
                    $responseJson = $webLabService->getReservationStatus($params, $rlmsId);
                }
                else{
                    $responseJson = array('is_exception' => true,
                        'message' => 'Session does not exist or has already expired. Please login again.',
                        'code' => 'Client.SessionNotFound');
                }
        }

        $response->headers->set('Content-type', 'application/json');
        $response->setContent(json_encode($responseJson));
        Return $response;

    }



}