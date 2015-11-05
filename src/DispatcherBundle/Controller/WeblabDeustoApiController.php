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
        //$myfile = fopen('webservice.txt','w') or die("Unable to open file");
        //fwrite($myfile, $request->getContent());
        //fclose($myfile);

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

                $session_id = $requestJson->params->session_id->id;
                $validationResponse = $webLabAuthenticator->validateSessionById($session_id);

                if ($validationResponse['is_exception']) {
                    $responseJson = $validationResponse;
                }
                else{
                    $labSession = $validationResponse['labSession'];
                    $rlmsId = $labSession->getRlmsId();
                    $responseJson = $webLabService->reserveExperiment($params, $rlmsId);
                }
                break;
            case 'get_reservation_status':

                $session_id = $request->cookies->get('weblabsessionid');
                $validationResponse = $webLabAuthenticator->validateSessionById($session_id);

                if ($validationResponse['is_exception']) {
                    $responseJson = $validationResponse;
                }
                else{
                    $labSession = $validationResponse['labSession'];
                    $rlmsId = $labSession->getRlmsId();
                    $responseJson = $webLabService->getReservationStatus($params, $rlmsId);
                }
                break;
            case 'finished_experiment':
                $session_id = $request->cookies->get('weblabsessionid');
                $validationResponse = $webLabAuthenticator->validateSessionById($session_id);

                if ($validationResponse['is_exception']) {
                    $responseJson = $validationResponse;
                }
                else{
                    $responseJson = array('result' => array(),
                        'is_exception' => false);
                }

                break;
            case 'get_experiment_use_by_id':
                $session_id = $requestJson->params->session_id->id;
                $validationResponse = $webLabAuthenticator->validateSessionById($session_id);

                if ($validationResponse['is_exception']) {
                    $responseJson = $validationResponse;
                }
                else{ //session is valid, process request
                    $reservation_id = $requestJson->params->reservation_id->id;
                    $labSession = $validationResponse['labSession'];
                    $rlmsId = $labSession->getRlmsId();
                    $responseJson = array('is_exception' => false,
                                           'result' => $webLabService->getExperimentUseById($reservation_id, $rlmsId));
                }
                break;
            case 'get_experiment_uses_by_id':

                $session_id = $requestJson->params->session_id->id;
                $validationResponse = $webLabAuthenticator->validateSessionById($session_id);

                if ($validationResponse['is_exception']) {
                    $responseJson = $validationResponse;
                }
                else{ //session is valid, process request
                    $reservation_ids = $requestJson->params->reservation_ids; //array of reservation IDs or experiment ID in iLab parlance
                    $labSession = $validationResponse['labSession'];
                    $rlmsId = $labSession->getRlmsId();
                    $responseJson = array('is_exception' => false,
                        'result' => $webLabService->getExperimentUsesById($reservation_ids, $rlmsId));
                }

                break;
            default:
                $responseJson = array('is_exception' => true,
                    'message' => 'Could not parse request',
                    'code' => 'Server.UserProcessing');
                break;

        }

        $response->headers->set('Content-type', 'application/json');
        $response->setContent(json_encode($responseJson));
        Return $response;

    }



}