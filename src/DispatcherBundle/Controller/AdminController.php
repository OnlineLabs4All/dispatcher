<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/12/15
 * Time: 2:37 PM
 */

namespace DispatcherBundle\Controller;

use DispatcherBundle\Entity\ExperimentEngine;
use DispatcherBundle\Entity\LabServer;
use DispatcherBundle\Entity\Rlms;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use DispatcherBundle\Entity\User;
use DispatcherBundle\Form\EngineForm;

/**
 * @Route("/secured")
 */
class AdminController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {

        return $this->render('default/adminHome.html.twig');
    }

    /**
     * @Route("/home", name="adminHome")
     */
    public function adminAction()
    {
        $userToken= $this->get('security.token_storage')->getToken()->getUser();

        return $this->render('default/adminHome.html.twig', array('userName'=> $userToken->getUsername()));
    }

    /**
     * @Route("/rlms", name="rlms")
     */
    public function RlmsCredentialsAction()
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');

        $rlms_list = $dashboadServices->getRlmsList($user);
        $rlms_list = $dashboadServices->appendUserInfoToResourceArray($rlms_list);

        return $this->render('default/rlmsRecordsTableView.html.twig',
            array( 'viewName'=> 'Remote Laboratory Management Systems',
                'records' => (array)$rlms_list));
    }

    /**
     * @Route("/rlms/{rlmsId}", name="edit_rlms", defaults={"rlmsId" = null})
     */
    public function EditRlmsAction(Request $request, $rlmsId)
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');

        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:Rlms');
        $rlms = $repository->findOneBy(array('id' => $rlmsId));

        //Verify user's permissions to access resource
        $permissions = $dashboadServices->checkUserPermissionOnResource($user, $rlms);
        if ( $permissions['granted'] == false){
            return $this->render('default/warning.html.twig', array(
                'warning' => $permissions['warning'],
                'viewName' => 'Something went wrong'));
        }
        $form = $this->buildEditRlmsForm($rlms);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data =$form->getData();
            $rlms->updateAll($data);
            $em->flush();
            return $this->redirectToRoute('rlms');
        }

        return $this->render('default/addEditRlms.html.twig', array(
            'viewName'=>'View/Edit RLMS',
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/addRlms", name="add_rlms")
     */
    public function AddRlmsAction(Request $request)
    {
        //retrieve user data
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $form =  $this->createAddRlmsForm($user->getUsername());
        $form->handleRequest($request);

        if ($form->isValid()) {

            $rlms = new Rlms();
            $data =$form->getData();
            $rlms->setAll($data, $user->getId());

            $em = $this->getDoctrine()->getManager();
            $em->persist($rlms);
            $em->flush();
            return $this->redirectToRoute('rlms');
        }
        return $this->render('default/addEditRlms.html.twig', array(
            'viewName'=>'Register a new RLMS',
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/mapping/{rlmsId}", name="rlms_ls_mapping", defaults={"rlmsId" = null})
     */
    public function rlmsToLsMappingAction(Request $request, $rlmsId)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:Rlms');
        $rlms = $repository->findOneBy(array('id' => $rlmsId));
        $dashboadServices = $this->get('dashboardUiServices');

        //Verify user's permissions to access resource
        $permissions = $dashboadServices->checkUserPermissionOnResource($user, $rlms);
        if ( $permissions['granted'] == false){
            return $this->render('default/warning.html.twig', array(
                'warning' => $permissions['warning'],
                'viewName' => 'Something went wrong'));
        }

        $labServerId = $request->query->getInt('labServerId', null);
        $newMapping = $request->query->getInt('newMapping', null);

        if ($labServerId != null){
            $dashboadServices = $this->get('dashboardUiServices');

            if ($newMapping == '1'){ //add new mapping
                 $dashboadServices->addRlmsLsMapping($rlmsId, $labServerId);
            }
            elseif ($newMapping == '0'){ //remove mapping
                $dashboadServices->removeRlmsLsMapping($rlmsId, $labServerId);
            }
            return $this->redirectToRoute('rlms_ls_mapping', array('rlmsId' => $rlmsId));
        }
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');
        //$labServers = $dashboadServices->getLabServersList($user);
        $labServers = $dashboadServices->getLabServersListForRlmsOwner($rlms);
        $mappings = $dashboadServices->getMappingsForRlms($rlmsId);
        $mappingResults = $dashboadServices->getMappings($labServers, $mappings);

        $mappingResults = $dashboadServices->appendUserInfoToResourceArray($mappingResults);

        return $this->render('default/rlmsLsMapping.html.twig',
            array( 'viewName'=> 'Associate Lab Servers with "'.$rlms->getName().'"',
                   'rlmsId' => $rlmsId,
                   'labservers' => $mappingResults));
    }

    /**
     * @Route("/expRecords/{expId}", name="expRecords", defaults={"expId" = null})
     */
    public function expRecordsAction($expId, Request $request)
    {
        $page = $request->query->getInt('page', 1);
        $length = $request->query->getInt('length', 20);
        $status = (int)$request->query->getInt('status', 1);
        $labServerId = (int)$request->query->getInt('labServerId', -1);

        $user= $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');

        //$paginationInfo = $dashboadServices->getPagination($user, $length, $status, $labServerId);

        if ($expId == null){
            $base_url =  $request->getScheme()."://".$request->getHttpHost().$request->getBasePath();
            $current_url = $request->getUri();

            $response = $dashboadServices->getJobRecordsTable($user, $length, $page, $status, $labServerId);
            return $this->render('default/expRecordsTableView.html.twig', array('viewName'=> 'Experiment Records',
                                                                                'baseUrl'=> $base_url,
                                                                                'numberOfJobs' => $response['totalNumberOfJobs'],
                                                                                'numberOfPages' => $response['numberOfPages'],
                                                                                'currentPage' => $page,
                                                                                'nextPage' => $response['nextPage'],
                                                                                'previousPage' => $response['previousPage'],
                                                                                'length' => $response['length'],
                                                                                'status' => $status,
                                                                                'labServerId' => $labServerId,
                                                                                'pages' => $response['pages'],
                                                                                'records' =>  $response['jobRecords']));
        }

        $jobRecord = $dashboadServices->getSingleJobRecord($user, $expId);
            return $this->render('default/recordView.html.twig', array('viewName'=> 'Experiment Record','record' => (array)$jobRecord));

       // return $this->render('default/recordView.html.twig', array('viewName'=> 'Experiment Record','record' => null));
    }

    /**
     * @Route("/engines", name="engines")
     */
    public function EnginesAction()
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');

        $engines = $dashboadServices->getEnginesList($user);

            return $this->render('default/engineRecordsTableView.html.twig',
                array( 'viewName'=> 'Subscriber Engines',
                       'records' => $engines));
    }

    /**
     * @Route("/engines/{engineId}", name="engine", defaults={"engineId" = null})
     */
    public function EngineAction($engineId, Request $request)
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:ExperimentEngine');
        $engine = $repository->findOneBy(array('id' => $engineId));

        $dashboadServices = $this->get('dashboardUiServices');
        //Verify user's permissions to access resource
        $permissions = $dashboadServices->checkUserPermissionOnResource($user, $engine);
        if ( $permissions['granted'] == false){
            return $this->render('default/warning.html.twig', array(
                'warning' => $permissions['warning'],
                'viewName' => 'Something went wrong'));
        }

        $form = $this->buildEditEngineForm($engine);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data =$form->getData();
            $engine->updateAll($data);
            $em->flush();
            return $this->redirectToRoute('engine');
        }

        return $this->render('default/addEditResource.html.twig', array(
            'viewName'=>'View/Edit Subscriber Engine',
            'form' => $form->createView(),
            'exception' => false
        ));
    }

    /**
     * @Route("/newEngine", name="add_engine")
     * @Method({"GET", "POST"})
     */
    public function NewEngineAction(Request $request)
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $form =  $this->createEngineForm($user);
        $form->handleRequest($request);
        $data =$form->getData();

        //form submitted
        if ($form->isValid()) {
            //check if engine is allowed to subscribe to lab server
            $labserver = $this->getDoctrine()
                ->getRepository('DispatcherBundle:LabServer')
                ->findOneBy(array('id' => $data['labserverId']));

            $engine_count = count($this->getDoctrine()
                ->getRepository('DispatcherBundle:ExperimentEngine')
                ->findBy(array('labserverId' => $data['labserverId'])));

            if ( ($labserver->getSingleEngine()) && ($engine_count > 0) )
            {
                return $this->render('default/addEditResource.html.twig', array(
                    'viewName'=>'Register a new Subscriber Engine',
                    'form' => $form->createView(),
                    'exception' => true,
                    'message' => 'Selected lab server is limited to one engine only!'
                    ));
            }

            //else: add engine and redirect
            $engine = new ExperimentEngine();
            $engine->setAll($data, $user->getId());

            $em = $this->getDoctrine()->getManager();
            $em->persist($engine);
            $em->flush();
            return $this->redirectToRoute('engines');
        }

        //render view
        return $this->render('default/addEditResource.html.twig', array(
            'viewName'=>'Register a new Subscriber Engine',
            'form' => $form->createView(),
            'exception' => false
        ));
    }

    /**
     * @Route("/labservers/{labserverId}", name="labserver")
     * @Method({"GET", "POST"})
     */
    public function LabServerAction(Request $request, $labserverId)
    {
        //get user of current session
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $labServer = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labserverId));

        $dashboadServices = $this->get('dashboardUiServices');
        //Verify user's permissions to access resource
        $permissions = $dashboadServices->checkUserPermissionOnResource($user, $labServer);
        if ( $permissions['granted'] == false) {
            return $this->render('default/warning.html.twig', array(
                                          'warning' => $permissions['warning'],
                                          'viewName' => 'Something went wrong'));
        }

        $form = $this->buildEditLabServerForm($labServer);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data =$form->getData();
            $labServer->updateAll($data);
            $em->flush();
            return $this->redirectToRoute('labservers');
        }

        return $this->render('default/addEditLabServer.html.twig', array(
            'viewName'=>'View/Edit "'.$labServer->getName().'"',
            'form' => $form->createView()));
    }
    /**
     * @Route("/labservers", name="labservers")
     * @Method({"GET"})
     */
    public function LabServersAction()
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');
        $labServers = $dashboadServices->getLabServersList($user);
        $labServers = $dashboadServices->appendUserInfoToResourceArray($labServers);

        return $this->render('default/labServersRecordsTableView.html.twig',
            array( 'viewName'=> 'Registered Lab Servers',
                'records' => (array)$labServers));
    }

    /**
     * @Route("/labserver", name="add_edit_labserver")
     * @Method({"GET", "POST"})
     */
    public function saveLabServerAction(Request $request)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $form = $this->buildCreateLabServerForm();
        $form->handleRequest($request);

        if ($form->isValid()) {

            $labserver = new LabServer();
            $data =$form->getData();
            $labserver->setAll($data, $user->getId());

            $em = $this->getDoctrine()->getManager();
            $em->persist($labserver);
            $em->flush();
            return $this->redirectToRoute('labservers');
        }

        return $this->render('default/addEditLabServer.html.twig', array(
            'viewName'=>'Register a new Lab Server',
            'form' => $form->createView()));
    }

    /**
     * @Route("/createUser", name="createUser")
     */
    public function createUserAction()
    {
        $user = $this->getUser();
        var_dump($this->getUser());

        return new Response($user->getUserName());
    }

    /**
     * @Route("/apis/{labServerId}", name="apis_to_rlms")
     */
    public function showApisAction(Request $request, $labServerId)
    {
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer');

        $labServer =  $repository->findOneBy(array('id' => $labServerId));

        if ($labServer->getType() == 'ILS'){
            $soap_service_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/ils/soap";
            $json_service_url = "";
            $wsdl_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/ils/soap";
        }
        else{
            $soap_service_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/soap";
            $json_service_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/json";
            $wsdl_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/soap";
        }

        return $this->render('default/apiEndpoints.html.twig', array(
            'viewName' => 'Lab Server APIs',
            'labServerName' => $labServer->getName(),
            'apis' => array(
                            array('name' => 'Available APIs',
                                  'wsdl' => $wsdl_url,
                                  'soap_endpoint' => $soap_service_url,
                                  'json_endpoint' => $json_service_url,
                                  'guid' => $labServer->getGuid(),
                                  'passkey' => $labServer->getPassKey(),
                                  'initialPasskey' => $labServer->getInitialPassKey(),
                                  'client_soap_endpoint' => $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/soap/client",
                                  'client_wsdl' => $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/iLabWsdl/sbWsdl.wsdl",
                                  'info' => 'Use the service endpoint, GUID and passKey to install the Lab Server process agent in your iLab Service Broker.
                                             For interactive lab server, use the Initial Passkey to install the domain credentials.',
                                  'documentation' => 'Not Available')
            )
        ));
    }

    /**
     * @Route("/enginesLocation", name="engines_location")
     */
    public function enginesLocationAction()
    {
        return $this->render('default/enginesLocation.html.twig');
    }


    //internal controller methods

    private function getLabServers($user)
    {
        $dashboadServices = $this->get('dashboardUiServices');
        $labServers = $dashboadServices->getLabServerNamesAndIdsForUser($user);

        if ($labServers != null){
            return $labServers;
        }
        return null;
        //var_dump($labServers);
    }

    //generate form for a new subscriber Engine
    private function createEngineForm($user){

        $key = md5(microtime().rand());
        $labServers = $this->getLabServers($user);
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('add_engine'))
            ->add('labserverId','choice', array('label' => 'Subscribe for Lab Server',
                'choices' => $labServers))
            ->add('name', 'text', array('label' => 'Engine Name'))
            ->add('description', 'textarea', array('label' => 'Description'))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name'))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email'))
            ->add('institution', 'text', array('label' => 'Institution'))
            ->add('address', 'text', array('label' => 'Street address'))
            ->add('city', 'text', array('label' => 'City'))
            ->add('country', 'country', array('label' => 'Country'))
            ->add('username', 'text', array('label' => 'Username'))
            ->add('password', 'repeated', array(
                                             'type' => 'password',
                                             'invalid_message' => 'The password fields must match.',
                                             'options' => array('attr' => array('class' => 'password-field')),
                                             'required' => true,
                                             'first_options'  => array('label' => 'Password'),
                                             'second_options' => array('label' => 'Repeat Password')))
            ->add('api_key', 'text', array('label' => 'API Key', 'data'=> $key))
            ->add('active', 'checkbox', array('label' => 'Active',
                'required' => false,
                'data'=> false ))
            ->add('visible_in_catalogue', 'checkbox', array('label' => 'Visible in the Catalogue',
                'required' => false, 'data'=> false))
            //->add('date', 'date', array('label' => 'Date'))
            ->add('submit','submit', array('label' => 'Add Experiment Engine', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();

        return $form;
    }

    //generate form to EDIT a subscriber Engine
    private function buildEditEngineForm(ExperimentEngine $engine){

            $form = $this->createFormBuilder()

                ->add('labserverId','text', array('label' => 'Subscribe for Lab Server (ID)', 'attr' => array('value'=>$engine->getLabServerId(), 'readonly' => true)))
                ->add('id', 'text', array('label' => 'Engine ID', 'attr' => array('value'=>$engine->getId(), 'readonly' => true)))
                ->add('dateCreated', 'text', array('label' => 'Created', 'attr' => array('value'=>$engine->getDateCreated(), 'readonly' => true)))
                ->add('name', 'text', array('label' => 'Engine Name', 'attr' => array('value'=>$engine->getName(), 'readonly' => false)))
                ->add('description', 'textarea', array('label' => 'Description','data'=>$engine->getDescription()))
                ->add('contact_name', 'text', array('label' => 'Contact\'s name', 'attr' => array('value'=>$engine->getContactName(), 'readonly' => false)))
                ->add('contact_email', 'email', array('label' => 'Contact\'s Email', 'attr' => array('value'=>$engine->getContactEmail(), 'readonly' => false)))
                ->add('institution', 'text', array('label' => 'Institution', 'attr' => array('value'=>$engine->getInstitution(), 'readonly' => false)))
                ->add('address', 'text', array('label' => 'Street address', 'attr' => array('value'=>$engine->getAddress(), 'readonly' => false)))
                ->add('city', 'text', array('label' => 'City', 'attr' => array('value'=>$engine->getCity(), 'readonly' => false)))
                ->add('country', 'country', array('label' => 'Country', 'data'=> $engine->getCountry()))
                ->add('basic_auth', 'text', array('label' => 'Basic Http Authentication', 'attr' => array('value'=>$engine->getHttpAuthentication(), 'readonly' => true)))
                ->add('api_key', 'text', array('label' => 'API Key', 'attr' => array('value'=>$engine->getApiKey(), 'readonly' => true)))
                ->add('active', 'checkbox', array('label' => 'Active',
                    'required' => false,
                    'data'=> $engine->getActive() ))
                ->add('visible_in_catalogue', 'checkbox', array('label' => 'Visible in the Catalogue',
                    'required' => false, 'data'=> $engine->getVisibleInCatalogue()))
                ->add('submit','submit', array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
                ->getForm();
        return $form;
    }

    //generate form to EDIT a RLMS Credentials
    private function buildEditRlmsForm(Rlms $rlms)
    {
        $form = $this->createFormBuilder()
            ->add('name', 'text', array('label' => 'Name', 'attr' => array('value'=>$rlms->getName(), 'readonly' => false)))
            ->add('description', 'textarea', array('label' => 'Description','data'=>$rlms->getDescription()))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name', 'attr' => array('value'=>$rlms->getContactName(), 'readonly' => false)))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email', 'attr' => array('value'=>$rlms->getContactEmail(), 'readonly' => false)))
            ->add('institution', 'text', array('label' => 'Institution', 'attr' => array('value'=>$rlms->getInstitution(), 'readonly' => false)))
            ->add('guid', 'text', array('label' => 'GUID', 'required' => true, 'attr' => array('value'=>$rlms->getGuid(), 'readonly' => true)))
            ->add('rlms_type', 'text', array('label' => 'RLMS Type', 'attr' => array('value'=> $rlms->getRlmsType(), 'readonly' => true)))
            //->add('passkey_to_rlms', 'text', array('label' => 'Passkey to RLMS', 'required' => false, 'attr' => array('value'=>$rlms->getPassKeyToRlms(), 'readonly' => true)))
            ->add('service_url', 'text', array('label' => 'Service URL', 'required' => false, 'attr' => array('value'=>$rlms->getServiceUrl(), 'readonly' => true)))
            ->add('service_description_url', 'text', array('label' => 'URL of a parsable description of RLMS API (WSDL, Swagger, etc)', 'required' => false, 'attr' => array('value'=>$rlms->getServiceDescriptionUrl(), 'readonly' => true)))
            ->add('rlms_username', 'text', array('label' => 'Username',  'required' => false, 'attr' => array('value'=>$rlms->getUsername(), 'readonly' => true)))
            ->add('rlms_password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => false,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),))
            ->add('active', 'checkbox', array('label' => 'Active',
                'required' => false,
                'data'=> $rlms->getActive() ))
            ->add('submit','submit', array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();
        return $form;
    }

    //generate form to CREATE a RLMS Credentials
    private function createAddRlmsForm($username)
    {
        //$passkey = md5(microtime().rand());
        //$rlms_username = md5(microtime().rand());
        $form = $this->createFormBuilder()

            ->add('name', 'text', array('label' => 'Name', 'attr' => array('readonly' => false)))
            ->add('description', 'textarea', array('label' => 'Description'))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name', 'attr' => array('readonly' => false)))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email', 'attr' => array('readonly' => false)))
            ->add('institution', 'text', array('label' => 'Institution', 'attr' => array('readonly' => false)))
            ->add('guid', 'text', array('label' => 'GUID', 'required' => false, 'attr' => array('readonly' => false)))
            ->add('rlms_type', 'choice',
                array('label' => 'Choose a supported RLMS',
                      'required' => true,
                      'choices' => array('ISA_SOAP'=>'ISA Service Broker (SOAP)',
                                         'ISA_JSON'=>'ISA Service Broker (JSON)',
                                         'WEBLAB_DEUSTO' => 'WebLab Deusto')))
            //->add('passkey_to_rlms', 'text', array('label' => 'Passkey to RLMS', 'required' => false, 'attr' => array('value' => $passkey,'readonly' => false)))
            ->add('service_url', 'text', array('label' => 'Service URL', 'required' => false, 'attr' => array('readonly' => false)))
            ->add('service_description_url', 'text', array('label' => 'URL of a parsable description of RLMS API (WSDL, Swagger, etc)', 'required' => false, 'attr' => array('readonly' => false)))
            ->add('rlms_username', 'text', array('label' => 'Username',  'required' => false, 'attr' => array('value' => '','readonly' => false)))
            ->add('rlms_password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => false,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),))
            ->add('active', 'checkbox', array('label' => 'Active',
                'required' => false))
            ->add('submit','submit', array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();
        return $form;
    }

    private function buildCreateLabServerForm()
    {
        $gen_guid = md5(microtime().rand());
        $gen_passKey = md5(microtime().rand());
        $gen_initPassKey = md5(microtime().rand());
        $form = $this->createFormBuilder()
            ->add('name', 'text', array('label' => 'Lab Server name', 'required' => true, 'attr'=>array('help'=>'text help')))
            ->add('exp_category', 'text', array('label' => 'Experiment category', 'required' => true, 'attr'=>array('help'=>'text help')))
            ->add('exp_name', 'text', array('label' => 'Experiment name', 'required' => true, 'attr'=>array('help'=>'text help')))
            ->add('description', 'textarea', array('label' => 'Description', 'required' => false))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name', 'required' => true))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email', 'required' => true))
            ->add('institution', 'text', array('label' => 'Institution', 'required' => true))
            ->add('Guid', 'text', array('label' => 'Guid', 'data'=> $gen_guid, 'required' => true))
            ->add('passKey', 'text', array('label' => 'Authentication PassKey ', 'data'=> $gen_passKey, 'required' => true))
            ->add('type', 'choice',
                array('label' => 'Type',
                    'required' => true,
                    'choices' => array('BLS'=>'Batched Lab Server', 'ILS'=>'Interactive Lab Server')))
            ->add('initialPassKey', 'text', array('label' => 'Initial PassKey (used once to install domain credentials in a SB)', 'data'=> $gen_initPassKey, 'required' => true))
            ->add('active', 'choice',
                array('label' => 'Active',
                    'required' => true,
                    'choices' => array('1'=>'Lab Server is active', '0'=>'Lab Server is NOT active')))
            ->add('useDataset', 'checkbox', array('label' => 'Retrieve results from dataset when available',
                'required' => false))
            ->add('configuration', 'textarea', array('label' => 'Lab Configuration', 'required' => false))
            ->add('singleEngine', 'checkbox', array('label' => 'Allow only one experiment engine to connect to the lab server', 'required' => false))
            ->add('labInfo', 'text', array('label' => 'Lab Info', 'required' => true))
            ->add('submit','submit', array('label' => 'Add New Lab Server','attr' => array('class'=>'btn btn-success')))
            ->getForm();

       return $form;
    }

    private function buildEditLabServerForm(LabServer $labServer)
    {
        $form = $this->createFormBuilder()

            ->add('name', 'text', array('label' => 'Lab Server name', 'required' => true, 'attr' => array('value'=>$labServer->getName(), 'readonly' => false)))
            ->add('exp_category', 'text', array('label' => 'Experiment category', 'required' => true, 'attr'=>array('value' => $labServer->getExpCategory(), 'help'=>'text help')))
            ->add('exp_name', 'text', array('label' => 'Experiment name', 'required' => true, 'attr'=>array('value' => $labServer->getExpName(), 'help'=>'text help')))
            ->add('description', 'textarea', array('label' => 'Description', 'required' => false, 'data'=> $labServer->getDescription(), 'attr' => array('readonly'=> false)))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name', 'required' => true, 'attr' => array('value'=>$labServer->getContactName(), 'readonly' => false)))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email', 'required' => true, 'attr' => array('value'=>$labServer->getContactEmail(), 'readonly' => false)))
            ->add('institution', 'text', array('label' => 'Institution', 'required' => true,  'attr' => array('value'=>$labServer->getInstitution(), 'readonly' => false)))
            ->add('Guid', 'text', array('label' => 'Guid', 'required' => true,  'attr' => array('value'=>$labServer->getGuid(), 'readonly' => true)))
            ->add('passKey', 'text', array('label' => 'Authentication PassKey ', 'required' => true, 'attr' => array('value'=>$labServer->getPasskey(), 'readonly' => true)))
            ->add('type', 'text', array('label' => 'Type ', 'required' => true, 'attr' => array('value'=>$labServer->getType(), 'readonly' => true)))
            ->add('initialPassKey', 'text', array('label' => 'Initial PassKey ', 'required' => true, 'attr' => array('value'=>$labServer->getInitialPasskey(), 'readonly' => true)))
            ->add('configuration', 'textarea', array('label' => 'Lab Configuration', 'required' => false, 'data'=>$labServer->getConfiguration()))
            ->add('singleEngine', 'checkbox', array('label' => 'Allow only one experiment engine to connect to the lab server', 'required' => false, 'data' => $labServer->getSingleEngine()))
            ->add('labInfo', 'text', array('label' => 'Lab Info', 'required' => true,  'attr' => array('value'=>$labServer->getLabInfo(), 'readonly' => false)))
            ->add('useDataset', 'checkbox', array('label' => 'Retrieve results from dataset when available',
                'required' => false,
                'data' => $labServer->getUseDataset()))
            ->add('active', 'checkbox', array('label' => 'Active',
                'required' => false,
                'data'=> $labServer->getActive() ))
            ->add('submit','submit', array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();

        return $form;
    }

    private function buildEditRlmsToLsMappingForm($labServers, $rlmsLsMapping, $rlmsId)
    {
        $form = $this->createFormBuilder();

        foreach ($labServers as $labServer) {
            $form->add((string)$labServer->getId(), 'checkbox', array('label' => $labServer->getName(),
                                                                      'required' => true,
                                                                      'attr' => array('readonly' => false)));
        }

        $form->add('submit','submit', array('label' => 'Save changes',
                                            'attr' => array('class'=>'btn btn-success')))
             ->getForm();
        return $form;
    }
}

