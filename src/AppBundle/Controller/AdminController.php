<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 3/12/15
 * Time: 2:37 PM
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
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


}