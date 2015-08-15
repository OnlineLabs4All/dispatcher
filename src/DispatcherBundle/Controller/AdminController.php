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

        if ($expId == null)
        {
            $base_url =  $request->getScheme()."://".$request->getHttpHost().$request->getBasePath();
            $current_url = $request->getUri();
            //'?page='.$page.'&length='.$length.'&status='.$status.'&labServer='.$labServerId;

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
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:ExperimentEngine');
            $records = $repository->findAll();

            return $this->render('default/engineRecordsTableView.html.twig',
                array( 'viewName'=> 'Subscriber Engines',
                    'records' => (array)$records));
    }

    /**
     * @Route("/engines/{engineId}", name="engine", defaults={"engineId" = null})
     */
    public function EngineAction($engineId, Request $request)
    {
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:ExperimentEngine');
        $engine = $repository->findOneBy(array('id' => $engineId));
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
        ));
    }

    /**
     * @Route("/newEngine", name="add_engine")
     * @Method({"GET", "POST"})
     */
    public function NewEngineAction(Request $request)
    {
        $form =  $this->createEngineForm();
        $form->handleRequest($request);

        if ($form->isValid()) {

            $engine = new ExperimentEngine();
            $data =$form->getData();
            $engine->setAll($data);

            $em = $this->getDoctrine()->getManager();
            $em->persist($engine);
            $em->flush();
            return $this->redirectToRoute('engines');
        }
        return $this->render('default/addEditResource.html.twig', array(
            'viewName'=>'Register a new Subscriber Engine',
            'form' => $form->createView()
        ));
    }
    /**
     * @Route("/labservers/{labserverId}", name="labserver")
     * @Method({"GET", "POST"})
     */
    public function LabServerAction(Request $request, $labserverId)
    {
       $labServer = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labserverId));
        $form = $this->buildEditLabServerForm($labServer);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data =$form->getData();
            $labServer->updateAll($data, 1);
            $em->flush();
            return $this->redirectToRoute('labservers');
        }

        return $this->render('default/addEditResource.html.twig', array(
            'viewName'=>'View/Edit "'.$labServer->getName().'"',
            'form' => $form->createView()));
    }
    /**
     * @Route("/labservers", name="labservers")
     * @Method({"GET"})
     */
    public function LabServersAction()
    {
        $repository = $this->getDoctrine()->getRepository('DispatcherBundle:LabServer');
        $labServers = $repository->findAll();
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
        $form = $this->buildCreateLabServerForm();
        $form->handleRequest($request);

        if ($form->isValid()) {

            $labserver = new LabServer();
            $data =$form->getData();
            $labserver->setAll($data);

            $em = $this->getDoctrine()->getManager();
            $em->persist($labserver);
            $em->flush();
            return $this->redirectToRoute('labservers');
        }

        return $this->render('default/addEditResource.html.twig', array(
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

        if ($labServer->getType() == 'ILS')
        {
            $service_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/ils/soap";
            $wsdl_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/ils/soap";
        }
        else
        {
            $service_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/soap";
            $wsdl_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/apis/isa/".$labServerId."/soap";
        }

        return $this->render('default/apiEndpoints.html.twig', array(
            'viewName' => 'My APIs',
            'labServerName' => $labServer->getName(),
            'apis' => array(
                            array('name' => 'ISA Lab Server API Endpoints and credentials to register with Service Broker',
                                  'description' => $wsdl_url,
                                  'endpoint' => $service_url,
                                  'guid' => $labServer->getGuid(),
                                  'passkey' => $labServer->getPassKey(),
                                  'initialPasskey' => $labServer->getInitialPassKey(),
                                  'info' => 'Implements the iLab Shared Architecture lab server API. Use the service endpoint, GUID and passKey to install the Lab Server process agent in your iLab Service Broker.
                                             For interactive lab server, use the Initial Paysskey to install the domain credentials.',
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

    private function getLabServers()
    {
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer')
            ->findAll();
        if ($repository != null){

             foreach ($repository as $labServer){
                    $labServers[(string)$labServer->getId()] = $labServer->getName().' (ID: '.$labServer->getId().')';
                 }
        return $labServers;
        }
        return null;

        //var_dump($labServers);

    }

    //generate form for a new subscriber Engine
    private function createEngineForm(){

        $key = md5(microtime().rand());
        $labServers = $this->getLabServers();
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('add_engine'))
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
            ->add('labserverId','choice', array('label' => 'Subscribe for Lab Server',
                'choices' => $labServers))
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
                ->add('labserverId','text', array('label' => 'Subscribe for Lab Server (ID)', 'attr' => array('value'=>$engine->getLabServerId(), 'readonly' => true)))
                ->add('active', 'checkbox', array('label' => 'Active',
                    'required' => false,
                    'data'=> $engine->getActive() ))
                ->add('visible_in_catalogue', 'checkbox', array('label' => 'Visible in the Catalogue',
                    'required' => false, 'data'=> $engine->getVisibleInCatalogue()))
                ->add('submit','submit', array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
                ->getForm();
        return $form;
    }

    private function buildCreateLabServerForm(){
        $gen_guid = md5(microtime().rand());
        $gen_passKey = md5(microtime().rand());
        $gen_initPassKey = md5(microtime().rand());
        $form = $this->createFormBuilder()
            ->add('name', 'text', array('label' => 'Lab Server name', 'required' => true, 'attr'=>array('help'=>'text help')))
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
            ->add('initialPassKey', 'text', array('label' => 'Initial PassKey ', 'data'=> $gen_initPassKey, 'required' => true))
            ->add('active', 'choice',
                array('label' => 'Active',
                    'required' => true,
                    'choices' => array('1'=>'Lab Server is active', '0'=>'Lab Server is NOT active')))

            ->add('configuration', 'textarea', array('label' => 'Configuration', 'required' => false))
            ->add('labInfo', 'text', array('label' => 'Lab Info', 'required' => true))
            ->add('public_sub','choice', array('label' => 'Permission for subscribers',
                'choices' => array('1'=>'Public (anyone can subscribe)', '0'=>'Private (only owner can subscribe)')))
            ->add('submit','submit', array('label' => 'Add New Lab Server','attr' => array('class'=>'btn btn-success')))
            ->getForm();

       return $form;
    }

    private function buildEditLabServerForm(LabServer $labServer){

        $form = $this->createFormBuilder()
            ->add('id', 'text', array('label' => 'Lab Server ID', 'required' => true, 'attr' => array('value'=>$labServer->getId(), 'readonly' => true)))
            ->add('name', 'text', array('label' => 'Lab Server name', 'required' => true, 'attr' => array('value'=>$labServer->getName(), 'readonly' => false)))
            ->add('description', 'textarea', array('label' => 'Description', 'required' => false, 'data'=> $labServer->getDescription(), 'attr' => array('readonly'=> false)))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name', 'required' => true, 'attr' => array('value'=>$labServer->getContactName(), 'readonly' => false)))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email', 'required' => true, 'attr' => array('value'=>$labServer->getContactEmail(), 'readonly' => false)))
            ->add('institution', 'text', array('label' => 'Institution', 'required' => true,  'attr' => array('value'=>$labServer->getInstitution(), 'readonly' => false)))
            ->add('Guid', 'text', array('label' => 'Guid', 'required' => true,  'attr' => array('value'=>$labServer->getGuid(), 'readonly' => true)))
            ->add('passKey', 'text', array('label' => 'Authentication PassKey ', 'required' => true, 'attr' => array('value'=>$labServer->getPasskey(), 'readonly' => true)))
            ->add('type', 'text', array('label' => 'Type ', 'required' => true, 'attr' => array('value'=>$labServer->getType(), 'readonly' => true)))
            ->add('initialPassKey', 'text', array('label' => 'Initial PassKey ', 'required' => true, 'attr' => array('value'=>$labServer->getInitialPasskey(), 'readonly' => true)))
            ->add('configuration', 'textarea', array('label' => 'Configuration', 'required' => false, 'data'=>$labServer->getConfiguration()))
            ->add('labInfo', 'text', array('label' => 'Lab Info', 'required' => true,  'attr' => array('value'=>$labServer->getLabInfo(), 'readonly' => false)))
            ->add('public_sub','choice', array('label' => 'Permission for subscribers',
                                               'data' => $labServer->getPublicSub(),
                'choices' => array('1'=>'Public (anyone can subscribe)', '0'=>'Private (only owner can subscribe)')))
            ->add('active', 'checkbox', array('label' => 'Active',
                'required' => false,
                'data'=> $labServer->getActive() ))
            ->add('submit','submit', array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();

        return $form;
    }
}

