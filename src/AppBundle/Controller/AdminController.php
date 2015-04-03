<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/12/15
 * Time: 2:37 PM
 */

namespace AppBundle\Controller;

use AppBundle\Entity\ExperimentEngine;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;

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
            ->getRepository('AppBundle:JobRecord');
        if ($expId == null){
            $records = $repository->findAll();
            //var_dump($records);
            return $this->render('default/expRecordsTableView.html.twig', array('viewName'=> 'Experiment Records', 'records' =>  $records));
        }
        $record = $repository->findOneBy(array('expId' => $expId));

        return $this->render('default/recordView.html.twig', array('viewName'=> 'Experiment Record','record' => (array)$record));

        //var_dump($expId);
        //return $this->render('default/expRecordsTableView.html.twig', array( 'jobRecords' => $jobRecords));
    }

    /**
     * @Route("/engines/{engineId}", name="engines", defaults={"engineId" = null})
     */
    public function EnginesAction($engineId)
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:ExperimentEngine');
        if ($engineId == null){
            $records = $repository->findAll();

            return $this->render('default/engineRecordsTableView.html.twig',
                                 array( 'viewName'=> 'Experiment Engines',
                                        'records' => (array)$records));
        }
        $record = $repository->findOneBy(array('id' => $engineId));

        //var_dump((array)$record);
        return $this->render('default/recordView.html.twig',
                             array('viewName'=> 'Experiment Engine',
                                   'record' => (array)$record));
        //var_dump($engine);

        //return $this->render('default/expRecordsTableView.html.twig', array( 'jobRecords' => $jobRecords));
    }

    /**
     * @Route("/labservers/{labserverId}", name="labservers", defaults={"labserverId" = null})
     */
    public function LabServersAction($labserverId)
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:LabServer');
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
     * @Route("/engine", name="add_edit_engine")
     */
    public function EngineAction(Request $request)
    {
        $labServers = $this->getLabServers();
        $form = $this->createFormBuilder()
            ->add('name', 'text', array('label' => 'Engine Name'))
            ->add('description', 'textarea', array('label' => 'Description'))
            ->add('institution', 'text', array('label' => 'Owner\'s Institution'))
            ->add('address', 'text', array('label' => 'Street address'))
            ->add('city', 'text', array('label' => 'City'))
            ->add('country', 'text', array('label' => 'Country'))
            ->add('username', 'text', array('label' => 'Username'))
            ->add('password','password', array('label' => 'Password'))
            ->add('api_key', 'text', array('label' => 'API Key'))
            ->add('labserverId','choice', array('label' => 'Subscribe for Lab Server',
                                                'choices' => $labServers))
            ->add('active', 'checkbox', array('label' => 'Active',
                                               'required' => false))
            ->add('visible_in_catalogue', 'checkbox', array('label' => 'Visible in the Catalogue',
                'required' => false))
            //->add('date', 'date', array('label' => 'Date'))
            ->add('submit','submit', array('label' => 'Add Experiment Engine'))
            ->getForm();

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

        return $this->render('default/addResource.html.twig', array(
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

    //internal controller methods

    private function getLabServers()
    {
        $repository = $this->getDoctrine()
            ->getRepository('AppBundle:LabServer')
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


}