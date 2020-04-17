<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/12/15
 * Time: 2:37 PM
 */

namespace DispatcherBundle\Controller;

use DispatcherBundle\Entity\ExperimentEngine;
use DispatcherBundle\Entity\LabClient;
use DispatcherBundle\Entity\LabServer;
use DispatcherBundle\Entity\Rlms;
use DispatcherBundle\Entity\LabSession;

//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use DispatcherBundle\Entity\User;
use DispatcherBundle\Form\EngineForm;
use Symfony\Component\Form\Extension\Core\Type;

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
            array( 'viewName'=> 'Registered Authorities',
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
            'viewName'=>'View/Edit Authority',
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
            'viewName'=>'Register a new Authority',
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
     * @Route("/clients", name="clients")
     */
    public function ClientsAction()
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');

        $clients = $dashboadServices->getClients($user);

        return $this->render('default/clientsRecordsTableView.html.twig',
            array( 'viewName'=> 'Lab Clients',
                'records' => $clients));
    }

    /**
     * @Route("/addClient", name="addClient")
     */
    public function addClientAction(Request $request)
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');

        //Instantiate client object
        $client = new LabClient();
        $form = $this->buildEditClientForm(null);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data =$form->getData();

            $labServerId = $data['labserverId'];
            $labServer = $this->getDoctrine()
                ->getRepository('DispatcherBundle:LabServer')
                ->findOneBy(array('id' => $labServerId));

            $client->setUpdateAll($data, $labServer->getOwnerId());
            $em->persist($client);
            $em->flush();
            return $this->redirectToRoute('addEditClient');
        }

        return $this->render('default/addEditResource.html.twig', array(
            'viewName' => 'View/Edit Lab Client',
            'form' => $form->createView(),
            'exception' => false
        ));
    }

    /**
     * @Route("/clients/{clientId}", name="addEditClient", defaults={"clientId" = null})
     */
    public function clientAction($clientId, Request $request)
    {
        //retrieve user data
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $dashboadServices = $this->get('dashboardUiServices');

        if ($clientId == -1){

            //Instantiate client object
            $client = new LabClient();
            $form = $this->buildEditClientForm(null);
           // $ownerId = $user->getId();
        }
        else{
            //retrieve client from database
            $repository = $this->getDoctrine()
                ->getRepository('DispatcherBundle:LabClient');
            $client = $repository->findOneBy(array('id' => $clientId));

            //Verify user's permissions to access resource
            $permissions = $dashboadServices->checkUserPermissionOnResource($user, $client);
            if ($permissions['granted'] == false){
                return $this->render('default/warning.html.twig', array(
                    'warning' => $permissions['warning'],
                    'viewName' => 'Something went wrong'));
            }
            $form = $this->buildEditClientForm($client);
        }
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data =$form->getData();

            $labServerId = $data['labserverId'];
            $labServer = $this->getDoctrine()
                ->getRepository('DispatcherBundle:LabServer')
                ->findOneBy(array('id' => $labServerId));

            if ($clientId == -1){
                $ownerId = $user->getId();
            }
            else{
                $ownerId = $labServer->getOwnerId();
            }

            $client->setUpdateAll($data, $ownerId);

            $em->persist($client);
            $em->flush();
            return $this->redirectToRoute('addEditClient');
        }

        return $this->render('default/addEditResource.html.twig', array(
            'viewName'=>'View/Edit Lab Client',
            'form' => $form->createView(),
            'exception' => false
        ));
    }

	/**
	 * @Route("/users/", name="users")
	 */
	public function UsersAction()
	{
		//retrieve user data
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$dashboadServices = $this->get('dashboardUiServices');
		
		//Verify user's permissions to access resource
		$permissions = $dashboadServices->checkUserPermissionOnResource($user);
		if ( $permissions['granted'] == false) {
			return $this->render('default/warning.html.twig', array(
					'warning' => $permissions['warning'],
					'viewName' => 'Something went wrong'));
		}
		
		$response = $dashboadServices->getUsersList($user);
		
		return $this->render('default/siteUsersTableView.html.twig',
			array(	'viewName'  => 'User List',
					'userCount' => $response['userCount'],
					'users'     => $response['users']));
	}

	/**
	 * @Route("/users/add/", name="addUser", methods={"GET", "POST"})
	 */
	public function addUserAction(Request $request)
	{
		//retrieve user data
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$dashboadServices = $this->get('dashboardUiServices');
		
		//Verify user's permissions to access resource
		$permissions = $dashboadServices->checkUserPermissionOnResource($user);
		if ($permissions['granted'] == false) {
			return $this->render('default/warning.html.twig', array(
					'warning' => $permissions['warning'],
					'viewName' => 'Something went wrong'));
		}
		
		$form = $this->buildAddUserForm();
        $form->handleRequest($request);
		
		//POST
		if ($form->isValid()) {
			
			//get form data
			$data = $form->getData();
			
			//check if username already exists
			$user_count = count($this->getDoctrine()
					->getRepository('DispatcherBundle:User')
					->findOneBy(array('username' => $data['username'])));
			
			if ( $user_count > 0 )
			{
				return $this->render('default/addEditUser.html.twig', array(
						'viewName'=>'Add a new User',
						'form' => $form->createView(),
						'exception' => true,
						'message' => 'Username already exists!'
				));
			}
			
			//Create new user (if username doesn't exist)
			$user = new User();
			$user->setAll($data);

			$em = $this->getDoctrine()->getManager();
			$em->persist($user);
			$em->flush();
			return $this->redirectToRoute('users');
		}
		
		//GET
		return $this->render('default/addEditUser.html.twig', array(
				'viewName'=>'Add a new User',
				'form' => $form->createView()));
	}
	
	private function buildAddUserForm()
	{
		$form = $this->createFormBuilder()
			->add('username', Type\TextType::class, array('label' => 'Username', 'required' => true, 'attr'=>array('help'=>'text help')))
			->add('email', Type\EmailType::class, array('label' => 'Email', 'required' => true))
			->add('password', Type\RepeatedType::class, array(
					'type' => Type\PasswordType::class,
					'invalid_message' => 'The password fields must match.',
					'options' => array('attr' => array('class' => 'password-field')),
					'required' => true,
					'first_options'  => array('label' => 'Password'),
					'second_options' => array('label' => 'Repeat Password')))
			
			->add('firstName', Type\TextType::class, array('label' => 'First Name', 'required' => true, 'attr'=>array('help'=>'text help')))
			->add('lastName', Type\TextType::class, array('label' => 'Last Name', 'required' => true, 'attr'=>array('help'=>'text help')))
			->add('role', Type\ChoiceType::class,
					array('label' => 'User Role',
							'required' => true,
							'choices_as_values' => true,
							'choices' => array('User' => 'ROLE_USER', 'Admin' => 'ROLE_ADMIN')))
			->add('isActive', Type\ChoiceType::class,
					array('label' => 'User Account Status',
							'required' => true,
							'choices_as_values' => true,
							'choices' => array('User is active' => '1', 'User is NOT active' => '0')))
			->add('submit', Type\SubmitType::class, array('label' => 'Add User','attr' => array('class'=>'btn btn-success')))
			->getForm();
		return $form;
	}
	
	/**
     * @Route("/users/{userId}", name="user", methods={"GET", "POST"})
     */
	public function userAction(Request $request, $userId)
	{
		//retrieve user data
		$user = $this->get('security.token_storage')->getToken()->getUser();
		$dashboadServices = $this->get('dashboardUiServices');
		
		//Verify user's permissions to access resource
		$permissions = $dashboadServices->checkUserPermissionOnResource($user);
		if ($permissions['granted'] == false) {
			return $this->render('default/warning.html.twig', array(
					'warning' => $permissions['warning'],
					'viewName' => 'Something went wrong'));
		}
		
		$userData = $this->getDoctrine()
            ->getRepository('DispatcherBundle:User')
            ->findOneBy(array('id' => $userId));
		
		$form = $this->buildEditUserForm($userData); //buildEditLabServerForm
        $form->handleRequest($request);
		
		//POST
		if ($form->isValid()) {
			//get form data
			$data = $form->getData();
			$userData->updateAll($data);

			$em = $this->getDoctrine()->getManager();
			$em->persist($userData);
			$em->flush();
			return $this->redirectToRoute('users');
		}
		
		//GET
		return $this->render('default/addEditUser.html.twig', array(
				'viewName'=>'View/Edit "'.$user->getUsername().'"',
				'form' => $form->createView()));
	}
	
	private function buildEditUserForm(User $userData)
	{
		$form = $this->createFormBuilder()
			->add('username', Type\TextType::class, array('label' => 'Username', 'required' => true, 'attr'=>array(
					'help'     => 'text help',
					'value'    => $userData->getUsername(),
					'readonly' => true
					)))
			->add('email', Type\EmailType::class, array('label' => 'Email', 'required' => true, 'attr'=>array(
					'help'     => 'text help',
					'value'    => $userData->getEmail(),
					'readonly' => false
					)))
			->add('password', Type\RepeatedType::class, array(
					'type' => Type\PasswordType::class,
					'invalid_message' => 'The password fields must match.',
					'options' => array('attr' => array('class' => 'password-field')),
					'required' => false,
					'first_options'  => array('label' => 'Password'),
					'second_options' => array('label' => 'Repeat Password')))
			
			->add('firstName', Type\TextType::class, array('label' => 'First Name', 'required' => true, 'attr'=>array(
					'help'     => 'text help',
					'value'    => $userData->getFirstName(),
					'readonly' => false
					)))
			->add('lastName', Type\TextType::class, array('label' => 'Last Name', 'required' => true, 'attr'=>array(
					'help'     => 'text help',
					'value'    => $userData->getLastName(),
					'readonly' => false
					)))
			->add('role', Type\ChoiceType::class,
					array(	'label'				=> 'User Role',
							'required'			=> true,
							'choices_as_values'	=> true,
							'choices'			=> array('User' => 'ROLE_USER', 'Admin' => 'ROLE_ADMIN'),
							'data'				=> $userData->getRole()))
			->add('isActive', Type\ChoiceType::class,
					array(	'label'				=> 'User Account Status',
							'required'			=> true,
							'choices_as_values'	=> true,
							'choices'			=> array('User is active' => '1', 'User is NOT active' => '0'),
							'data'				=> $userData->getIsActive()))
			->add('submit', Type\SubmitType::class, array('label' => 'Save changes','attr' => array('class'=>'btn btn-success')))
			->getForm();
		return $form;
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
     * @Route("/newEngine", name="add_engine", methods={"GET", "POST"})
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
            $engine->setAll($data, $labserver->getOwnerId());

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
     * @Route("/labservers/{labserverId}", name="labserver", methods={"GET", "POST"})
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
     * @Route("/labservers", name="labservers", methods={"GET"})
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
     * @Route("/labserver", name="add_edit_labserver", methods={"GET", "POST"})
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
     * @Route("/launchLabClient/{labserverId}/{clientId}", name="launchLabClient")
     */
    public function launchLabClientAction(Request $request, $labserverId, $clientId)
    {
        $session_duration = '604800';
        $startDate = new \DateTime();
        $endDate = new \DateTime();
        $endDate->add( new \DateInterval('PT'.$session_duration.'S'));
        $labSession = new LabSession();
        $session = $labSession->createSession($labserverId,null, $startDate, $endDate);

        try{
            $em = $this->getDoctrine()->getManager();
            $em->persist($labSession);
            $em->flush();
        }
        catch (Exception $e){
            return $this->render('default/warning.html.twig', array(
                'warning' => $e->getMessage(),
                'viewName' => 'Something went wrong'));
        }

        $labServer = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('id' => $labserverId));

        $labClient= $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabClient')
            ->findOneBy(array('id' => $clientId));


        $clientUrl = $labClient->getClientUrl();
        $delimiter = (strpos($clientUrl,'?') > -1) ? '&' : '?';
        $url = $clientUrl.$delimiter.'coupon_id='.$session['couponId'].'&passkey='.$session['passkey'].'&labServerGuid='.$labServer->getGuid();

        return $this->redirect($url);
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
                                  'client_wsdl' => $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/iLabWsdl/sbWsdl.php",
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
            ->add('labserverId', Type\ChoiceType::class, array(
					'label' => 'Subscribe for Lab Server',
					'choices_as_values' => true,
					'choices' => array_flip($labServers)))
            ->add('name', Type\TextType::class, array('label' => 'Engine Name'))
            ->add('description', Type\TextareaType::class, array('label' => 'Description'))
            ->add('contact_name', Type\TextType::class, array('label' => 'Contact\'s name'))
            ->add('contact_email', Type\EmailType::class, array('label' => 'Contact\'s Email'))
            ->add('institution', Type\TextType::class, array('label' => 'Institution'))
            ->add('address', Type\TextType::class, array('label' => 'Street address'))
            ->add('city', Type\TextType::class, array('label' => 'City'))
            ->add('country', Type\CountryType::class, array('label' => 'Country'))
            ->add('username', Type\TextType::class, array('label' => 'Username'))
            ->add('password', Type\RepeatedType::class, array(
                                             'type' => Type\PasswordType::class,
                                             'invalid_message' => 'The password fields must match.',
                                             'options' => array('attr' => array('class' => 'password-field')),
                                             'required' => true,
                                             'first_options'  => array('label' => 'Password'),
                                             'second_options' => array('label' => 'Repeat Password')))
            ->add('api_key', Type\TextType::class, array('label' => 'API Key', 'data'=> $key))
            ->add('active', Type\CheckboxType::class, array(
					'label' => 'Active',
					'required' => false,
					'data'=> false ))
            ->add('visible_in_catalogue', Type\CheckboxType::class, array(
					'label' => 'Visible in the Catalogue',
					'required' => false,
					'data'=> false))
            //->add('date', 'date', array('label' => 'Date'))
            ->add('submit', Type\SubmitType::class, array('label' => 'Add Experiment Engine', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();

        return $form;
    }

    //generate form to EDIT a subscriber Engine
    private function buildEditEngineForm(ExperimentEngine $engine){

            $form = $this->createFormBuilder()

                ->add('labserverId', Type\TextType::class, array('label' => 'Subscribe for Lab Server (ID)', 'attr' => array('value'=>$engine->getLabServerId(), 'readonly' => true)))
                ->add('id', Type\TextType::class, array('label' => 'Engine ID', 'attr' => array('value'=>$engine->getId(), 'readonly' => true)))
                ->add('dateCreated', Type\TextType::class, array('label' => 'Created', 'attr' => array('value'=>$engine->getDateCreated(), 'readonly' => true)))
                ->add('name', Type\TextType::class, array('label' => 'Engine Name', 'attr' => array('value'=>$engine->getName(), 'readonly' => false)))
                ->add('description', Type\TextareaType::class, array('label' => 'Description','data'=>$engine->getDescription()))
                ->add('contact_name', Type\TextType::class, array('label' => 'Contact\'s name', 'attr' => array('value'=>$engine->getContactName(), 'readonly' => false)))
                ->add('contact_email', Type\EmailType::class, array('label' => 'Contact\'s Email', 'attr' => array('value'=>$engine->getContactEmail(), 'readonly' => false)))
                ->add('institution', Type\TextType::class, array('label' => 'Institution', 'attr' => array('value'=>$engine->getInstitution(), 'readonly' => false)))
                ->add('address', Type\TextType::class, array('label' => 'Street address', 'attr' => array('value'=>$engine->getAddress(), 'readonly' => false)))
                ->add('city', Type\TextType::class, array('label' => 'City', 'attr' => array('value'=>$engine->getCity(), 'readonly' => false)))
                ->add('country', Type\CountryType::class, array('label' => 'Country', 'data'=> $engine->getCountry()))
                ->add('basic_auth', Type\TextType::class, array('label' => 'Basic Http Authentication', 'attr' => array('value'=>$engine->getHttpAuthentication(), 'readonly' => true)))
                ->add('api_key', Type\TextType::class, array('label' => 'API Key', 'attr' => array('value'=>$engine->getApiKey(), 'readonly' => true)))
                ->add('active', Type\CheckboxType::class, array(
						'label' => 'Active',
						'required' => false,
						'data'=> $engine->getActive() ))
                ->add('visible_in_catalogue', Type\CheckboxType::class, array(
						'label' => 'Visible in the Catalogue',
						'required' => false,
						'data' => $engine->getVisibleInCatalogue()))
                ->add('submit', Type\SubmitType::class, array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
                ->getForm();
        return $form;
    }

    //generate form to EDIT a subscriber Engine
    private function buildEditClientForm($client){

        if ($client != null){
            $form = $this->createFormBuilder()
                ->add('labserverId', Type\TextType::class, array('label' => 'Lab Server (ID)', 'attr' => array('value'=>$client->getLabServerId(), 'readonly' => true)))
                //->add('id', Type\TextType::class, array('label' => 'Client ID', 'attr' => array('value'=>$client->getId(), 'readonly' => true)))
                ->add('dateCreated', Type\TextType::class, array('label' => 'Created', 'attr' => array('value'=>$client->getDateCreated()->format('d/m/Y H:i:s'), 'readonly' => true)))
                ->add('name', Type\TextType::class, array('label' => 'Client Name', 'attr' => array('value'=>$client->getName(), 'readonly' => false)))
                ->add('Guid', Type\TextType::class, array('label' => 'Guid', 'attr' => array('value'=>$client->getGuid(), 'readonly' => false)))
                ->add('url', Type\TextType::class, array('label' => 'ISA compliant client URL (coupon_id, passkey and labServerGuid will be added to this URL)', 'attr' => array('value'=>$client->getClientUrl(), 'readonly' => false)))
                ->add('description', Type\TextareaType::class, array('label' => 'Description','data'=>$client->getDescription()))
                ->add('submit', Type\SubmitType::class, array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
                ->getForm();
        }
        else{
            $gen_guid = md5(microtime().rand());
            $dashboadServices = $this->get('dashboardUiServices');
            //$user = $dashboadServices->getUserById($labServers->getOwnerId());
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $labServers = $this->getLabServers($user);

            $form = $this->createFormBuilder()
                ->add('labserverId', Type\ChoiceType::class, array(
						'label' => 'Subscribe for Lab Server',
						'choices_as_values' => true,
						'choices' => array_flip($labServers)))
                //->add('id', Type\TextType::class, array('label' => 'Client ID', 'attr' => array('value'=>'', 'readonly' => true)))
                //->add('dateCreated', Type\TextType::class, array('label' => 'Created', 'attr' => array('value'=>$client->getDateCreated()->format('d/m/Y H:i:s'), 'readonly' => true)))
                ->add('name', Type\TextType::class, array('label' => 'Client Name', 'attr' => array('value'=>'', 'readonly' => false)))
                ->add('Guid', Type\TextType::class, array('label' => 'Guid', 'data'=> $gen_guid, 'required' => true))
                ->add('url', Type\TextType::class, array('label' => 'ISA compliant client URL (coupon_id, passkey and labServerGuid will be added to this URL)', 'attr' => array('required' => true, 'readonly' => false)))
                ->add('description', Type\TextareaType::class, array('label' => 'Description','data'=>''))
                ->add('submit', Type\SubmitType::class, array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
                ->getForm();
        }

        return $form;
    }

    //generate form to EDIT a RLMS Credentials
    private function buildEditRlmsForm(Rlms $rlms)
    {
        $form = $this->createFormBuilder()
          //  ->add('rlms_type', Type\TextType::class, array('label' => 'Authority Type', 'attr' => array('value'=> $rlms->getRlmsType(), 'readonly' => true)))
            ->add('name', Type\TextType::class, array('label' => 'Name', 'attr' => array('value'=>$rlms->getName(), 'readonly' => false)))
            ->add('description', Type\TextareaType::class, array('label' => 'Description','data'=>$rlms->getDescription()))
            ->add('contact_name', Type\TextType::class, array('label' => 'Contact\'s name', 'attr' => array('value'=>$rlms->getContactName(), 'readonly' => false)))
            ->add('contact_email', Type\EmailType::class, array('label' => 'Contact\'s Email', 'attr' => array('value'=>$rlms->getContactEmail(), 'readonly' => false)))
            ->add('institution', Type\TextType::class, array('label' => 'Institution', 'attr' => array('value'=>$rlms->getInstitution(), 'readonly' => false)))
            ->add('guid', Type\TextType::class, array('label' => 'GUID', 'required' => true, 'attr' => array('value'=>$rlms->getGuid(), 'readonly' => true)))
            ->add('couponId', Type\TextType::class, array('label' => 'couponId', 'required' => true, 'attr' => array('value'=>$rlms->getAuthCouponId(), 'readonly' => false)))
            ->add('passkey', Type\TextType::class, array('label' => 'passkey', 'required' => true, 'attr' => array('value'=>$rlms->getAuthPassKey(), 'readonly' => false)))
            //->add('passkey_to_rlms', Type\TextType::class, array('label' => 'Passkey to RLMS', 'required' => false, 'attr' => array('value'=>$rlms->getPassKeyToRlms(), 'readonly' => true)))
            ->add('service_url', Type\TextType::class, array('label' => 'Service URL', 'required' => false, 'attr' => array('value'=>$rlms->getServiceUrl(), 'readonly' => true)))
            ->add('service_description_url', Type\TextType::class, array('label' => 'URL of a parsable description of Authority API (Ex.: RLMS WSDL, Swagger, etc)', 'required' => false, 'attr' => array('value'=>$rlms->getServiceDescriptionUrl(), 'readonly' => false)))
            ->add('rlms_username', Type\TextType::class, array('label' => 'Username',  'required' => false, 'attr' => array('value'=>$rlms->getUsername(), 'readonly' => false)))
            ->add('rlms_password', Type\RepeatedType::class, array(
                'type' => Type\PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => false,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),))
            ->add('active', Type\CheckboxType::class, array('label' => 'Active',
                'required' => false,
                'data'=> $rlms->getActive() ))
            ->add('submit', Type\SubmitType::class, array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();
        return $form;
    }

    //generate form to CREATE a RLMS Credentials
    private function createAddRlmsForm($username)
    {
        $gen_passkey = md5(microtime().rand());
        $gen_couponId = $this->couponId = mt_rand(0, 9999);
        //$passkey = md5(microtime().rand());
        //$rlms_username = md5(microtime().rand());
        $form = $this->createFormBuilder()

            ->add('name', Type\TextType::class, array('label' => 'Name', 'attr' => array('readonly' => false)))
            ->add('description', Type\TextareaType::class, array('label' => 'Description'))
            ->add('contact_name', Type\TextType::class, array('label' => 'Contact\'s name', 'attr' => array('readonly' => false)))
            ->add('contact_email', Type\EmailType::class, array('label' => 'Contact\'s Email', 'attr' => array('readonly' => false)))
            ->add('institution', Type\TextType::class, array('label' => 'Institution', 'attr' => array('readonly' => false)))
            ->add('guid', Type\TextType::class, array('label' => 'GUID', 'required' => false, 'attr' => array('readonly' => false)))
            ->add('couponId', Type\TextType::class, array('label' => 'couponId', 'required' => true, 'attr' => array('value'=>$gen_couponId, 'readonly' => false)))
            ->add('passkey', Type\TextType::class, array('label' => 'passkey', 'required' => true, 'attr' => array('value'=>$gen_passkey, 'readonly' => false)))
       //     ->add('rlms_type', Type\ChoiceType::class,
       //         array('label' => 'Authority Type',
       //               'required' => true,
       //               'choices' => array('ISA_SOAP'=>'ISA Service Broker (SOAP)',
       //                                  'ISA_JSON'=>'ISA Service Broker (JSON)',
       //                                  'WEBLAB_DEUSTO' => 'WebLab Deusto')))
            //->add('passkey_to_rlms', Type\TextType::class, array('label' => 'Passkey to RLMS', 'required' => false, 'attr' => array('value' => $passkey,'readonly' => false)))
            ->add('service_url', Type\TextType::class, array('label' => 'Service URL', 'required' => false, 'attr' => array('readonly' => false)))
            ->add('service_description_url', Type\TextType::class, array('label' => 'URL of a parsable description of Authority API (Ex.: RLMS WSDL, Swagger, etc)', 'required' => false, 'attr' => array('value'=>'', 'readonly' => false)))
            ->add('rlms_username', Type\TextType::class, array('label' => 'Username',  'required' => false, 'attr' => array('value' => '','readonly' => false)))
            ->add('rlms_password', Type\RepeatedType::class, array(
                'type' => Type\PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => false,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),))
            ->add('active', Type\CheckboxType::class, array('label' => 'Active',
                'required' => false))
            ->add('submit', Type\SubmitType::class, array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
            ->getForm();
        return $form;
    }

    private function buildCreateLabServerForm()
    {
        $gen_guid = md5(microtime().rand());
        $gen_passKey = md5(microtime().rand());
        $gen_initPassKey = md5(microtime().rand());
        $form = $this->createFormBuilder()
            ->add('name', Type\TextType::class, array('label' => 'Lab Server name', 'required' => true, 'attr'=>array('help'=>'text help')))
            ->add('exp_category', Type\TextType::class, array('label' => 'Experiment category', 'required' => true, 'attr'=>array('help'=>'text help')))
            ->add('exp_name', Type\TextType::class, array('label' => 'Experiment name', 'required' => true, 'attr'=>array('help'=>'text help')))
            ->add('description', Type\TextareaType::class, array('label' => 'Description', 'required' => false))
            ->add('contact_name', Type\TextType::class, array('label' => 'Contact\'s name', 'required' => true))
            ->add('contact_email', Type\EmailType::class, array('label' => 'Contact\'s Email', 'required' => true))
            ->add('institution', Type\TextType::class, array('label' => 'Institution', 'required' => true))
            ->add('Guid', Type\TextType::class, array('label' => 'Guid', 'data'=> $gen_guid, 'required' => true))
            ->add('passKey', Type\TextType::class, array('label' => 'Authentication PassKey ', 'data'=> $gen_passKey, 'required' => true))
            ->add('type', Type\ChoiceType::class,
                array('label' => 'Type',
                    'required' => true,
					'choices_as_values' => true,
                    'choices' => array('Batched Lab Server' => 'BLS', 'Interactive Lab Server' => 'ILS')))
            ->add('initialPassKey', Type\TextType::class, array('label' => 'Initial PassKey (used once to install domain credentials in a SB)', 'data'=> $gen_initPassKey, 'required' => true))
            ->add('active', Type\ChoiceType::class,
                array('label' => 'Active',
                    'required' => true,
					'choices_as_values' => true,
                    'choices' => array('Lab Server is active' => '1', 'Lab Server is NOT active' => '0')))
            ->add('useDataset', Type\CheckboxType::class, array('label' => 'Retrieve results from dataset when available',
                'required' => false))
            ->add('configuration', Type\TextareaType::class, array('label' => 'Lab Configuration', 'required' => false))
            ->add('singleEngine', Type\CheckboxType::class, array('label' => 'Allow only one experiment engine to connect to the lab server', 'required' => false))
            ->add('labInfo', Type\TextType::class, array('label' => 'Lab Info', 'required' => true))

            ->add('federate', Type\CheckboxType::class, array('label' => 'Federated lab server', 'required' => false))
            ->add('isaWsdlUrl', Type\TextType::class, array('label' => 'WSDL of federated ISA lab server', 'required' => false))
            ->add('isaIdentifier', Type\TextType::class, array('label' => 'Identifier (GUID of the Broker contacting LS)', 'required' => false))
            ->add('isaPasskeyToLabServer', Type\TextType::class, array('label' => 'ISA Passkey to lab server', 'required' => false))

            ->add('submit', Type\SubmitType::class, array('label' => 'Add New Lab Server','attr' => array('class'=>'btn btn-success')))
            ->getForm();

       return $form;
    }

    private function buildEditLabServerForm(LabServer $labServer)
    {
        $form = $this->createFormBuilder()

            ->add('name', Type\TextType::class, array('label' => 'Lab Server name', 'required' => true, 'attr' => array('value'=>$labServer->getName(), 'readonly' => false)))
            ->add('exp_category', Type\TextType::class, array('label' => 'Experiment category', 'required' => true, 'attr'=>array('value' => $labServer->getExpCategory(), 'help'=>'text help')))
            ->add('exp_name', Type\TextType::class, array('label' => 'Experiment name', 'required' => true, 'attr'=>array('value' => $labServer->getExpName(), 'help'=>'text help')))
            ->add('description', Type\TextareaType::class, array('label' => 'Description', 'required' => false, 'data'=> $labServer->getDescription(), 'attr' => array('readonly'=> false)))
            ->add('contact_name', Type\TextType::class, array('label' => 'Contact\'s name', 'required' => true, 'attr' => array('value'=>$labServer->getContactName(), 'readonly' => false)))
            ->add('contact_email', Type\EmailType::class, array('label' => 'Contact\'s Email', 'required' => true, 'attr' => array('value'=>$labServer->getContactEmail(), 'readonly' => false)))
            ->add('institution', Type\TextType::class, array('label' => 'Institution', 'required' => true,  'attr' => array('value'=>$labServer->getInstitution(), 'readonly' => false)))
            ->add('Guid', Type\TextType::class, array('label' => 'Guid', 'required' => true,  'attr' => array('value'=>$labServer->getGuid(), 'readonly' => true)))
            ->add('passKey', Type\TextType::class, array('label' => 'Authentication PassKey ', 'required' => true, 'attr' => array('value'=>$labServer->getPasskey(), 'readonly' => true)))
            ->add('type', Type\TextType::class, array('label' => 'Type ', 'required' => true, 'attr' => array('value'=>$labServer->getType(), 'readonly' => true)))
            ->add('initialPassKey', Type\TextType::class, array('label' => 'Initial PassKey ', 'required' => true, 'attr' => array('value'=>$labServer->getInitialPasskey(), 'readonly' => true)))
            ->add('configuration', Type\TextareaType::class, array('label' => 'Lab Configuration', 'required' => false, 'data'=>$labServer->getConfiguration()))
            ->add('singleEngine', Type\CheckboxType::class, array('label' => 'Allow only one experiment engine to connect to the lab server', 'required' => false, 'data' => $labServer->getSingleEngine()))
            ->add('labInfo', Type\TextType::class, array('label' => 'Lab Info', 'required' => true,  'attr' => array('value'=>$labServer->getLabInfo(), 'readonly' => false)))
            ->add('useDataset', Type\CheckboxType::class, array('label' => 'Retrieve results from dataset when available',
                'required' => false,
                'data' => $labServer->getUseDataset()))
            ->add('active', Type\CheckboxType::class, array('label' => 'Active',
                'required' => false,
                'data'=> $labServer->getActive() ))

            ->add('federate', Type\CheckboxType::class, array('label' => 'Federated lab server', 'required' => false, 'data' => $labServer->getFederate()))
            ->add('isaWsdlUrl', Type\TextType::class, array('label' => 'WSDL of federated ISA lab server', 'required' => false, 'attr' => array('value'=>$labServer->getIsaWsdlUrl(), 'readonly' => false)))
            ->add('isaIdentifier', Type\TextType::class, array('label' => 'Identifier (GUID of the Broker contacting LS)', 'required' => false, 'attr' => array('value'=>$labServer->getIsaIdentifier(), 'readonly' => false)))
            ->add('isaPasskeyToLabServer', Type\TextType::class, array('label' => 'ISA Passkey to lab server', 'required' => false, 'attr' => array('value'=>$labServer->getIsaPasskeyToLabServer(), 'readonly' => false)))

            ->add('submit', Type\SubmitType::class, array('label' => 'Save changes', 'attr' => array('class'=>'btn btn-success')))
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

