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
 * @Route("/secured/admin")
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
        return $this->render('default/adminHome.html.twig');
    }

    /**
     * @Route("/expRecords/{expId}", name="expRecords", defaults={"expId" = null})
     */
    public function expRecordsAction($expId)
    {
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:JobRecord');
        if ($expId == null){
            $records = $repository->findAll();
            //var_dump($records);
            return $this->render('default/expRecordsTableView.html.twig', array('viewName'=> 'Experiment Records', 'records' =>  $records));
        }
        $record = $repository->findOneBy(array('expId' => $expId));

        return $this->render('default/recordView.html.twig', array('viewName'=> 'Experiment Record','record' => (array)$record));
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
        $em = $this->getDoctrine()->getManager();
        $form = $this->buildEditEngineForm($engine);
        $form->handleRequest($request);

        if ($form->isValid()) {
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
        $labServers = $this->getLabServers();
        //$form = $this->createFormBuilder();

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
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/labservers/{labserverId}", name="labservers", defaults={"labserverId" = null})
     * @Method({"GET"})
     */
    public function LabServersAction($labserverId)
    {
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer');
        if ($labserverId == null){
            $records = $repository->findAll();

            return $this->render('default/labServersRecordsTableView.html.twig',
                                 array( 'viewName'=> 'Registered Lab Servers',
                                        'records' => (array)$records));
        }
        $record = $repository->findOneBy(array('id' => $labserverId));

        //var_dump((array)$record);
        return $this->render('default/recordView.html.twig',
                             array('viewName'=> 'Lab Server',
                                   'record' => (array)$record));
        //var_dump($engine);
        //return $this->render('default/expRecordsTableView.html.twig', array( 'jobRecords' => $jobRecords));
    }

    /**
     * @Route("/labserver", name="add_edit_labserver")
     * @Method({"GET", "POST"})
     */
    public function labServerAction(Request $request)
    {
        $gen_guid = md5(microtime().rand());
        $gen_passKey = md5(microtime().rand());

        $form = $this->createFormBuilder()
            ->add('name', 'text', array('label' => 'Lab Server name', 'required' => true, 'attr'=>array('help'=>'text help')))
            ->add('description', 'textarea', array('label' => 'Description', 'required' => true))
            ->add('contact_name', 'text', array('label' => 'Contact\'s name', 'required' => true))
            ->add('contact_email', 'email', array('label' => 'Contact\'s Email', 'required' => true))
            ->add('institution', 'text', array('label' => 'Institution', 'required' => true))
            ->add('Guid', 'text', array('label' => 'Guid', 'data'=> $gen_guid, 'required' => true))
            ->add('passKey', 'text', array('label' => 'Authentication PassKey ', 'data'=> $gen_passKey, 'required' => true))
            ->add('active', 'choice',
                  array('label' => 'Active',
                        'required' => true,
                        'choices' => array('1'=>'Lab Server is active', '0'=>'Lab Server is NOT active')))
            ->add('visible_in_catalogue', 'choice',
                   array('label' => 'Visible in the Catalogue',
                         'required' => true,
                         'choices' => array('1'=>'Lab Server is visible', '0'=>'Lab Server is NOT visible')))

            ->add('configuration', 'textarea', array('label' => 'Configuration', 'required' => false))
            ->add('labInfo', 'text', array('label' => 'Lab Info', 'required' => true))
            ->add('public_sub','choice', array('label' => 'Permission for subscribers',
                'choices' => array('1'=>'Public (anyone can subscribe)', '0'=>'Private (only owner can subscribe)')))
            ->add('submit','submit', array('label' => 'Add New Lab Server'))
            ->getForm();

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
            'form' => $form->createView(),
        ));

    }


    /**
     * @Route("/createUser", name="createUser")
     */
    public function createUserAction()
    {
        $user = new User();
        $user->setUsername('dgzutin2');
        $user->setPassword('pass');
        $user->setEmail('dgzutin@gmail.org');
        $user->setIsActive(true);

        $em = $this->getDoctrine()->getManager();

        $em->persist($user);
        $em->flush();

        return new Response('User Created!');

    }
    /**
     * @Route("/apis/{labServerId}", name="apis_to_rlms")
     */
    public function showApisAction(Request $request, $labServerId)
    {
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer');

        $labServer =  $repository->findOneBy(array('id' => $labServerId));
        $service_url = $request->getScheme()."://".$request->getHttpHost()."/apis/isa/".$labServerId."/soap";
        $wsdl_url = $request->getScheme()."://".$request->getHttpHost()."/apis/isa/".$labServerId."/soap";
        return $this->render('default/apiEndpoints.html.twig', array(
            'viewName' => 'My APIs',
            'labServerName' => $labServer->getName(),
            'apis' => array(
                            array('name' => 'ISA Batched Lab Server API (SOAP)',
                                  'description' => $wsdl_url,
                                  'endpoint' => $service_url,
                                  'guid' => $labServer->getGuid(),
                                  'passkey' => $labServer->getPassKey(),
                                  'info' => 'Implements the iLab Shared Architecture batched lab server API. Use the service endpoint, GUID and passKey to install the Lab Server process agent in your iLab Service Broker',
                                  'documentation' => 'Not Available')
            )
        ));
    }

    //internal controller methods

    private function getLabServers()
    {
        $repository = $this->getDoctrine()
            ->getRepository('DispatcherBundle:LabServer')
            ->findAll();
        if ($repository != null){

             foreach ($repository as $labServer){
                    $labServers[(string)$labServer->getId()] = $labServer->getName().' (ID = '.$labServer->getId().')';
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
    private function buildEditEngineForm($engine){

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
                ->add('submit','submit', array('label' => 'Save', 'attr' => array('class'=>'btn btn-success')))
                ->getForm();
        return $form;

    }


}