<?php

namespace DispatcherBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use DispatcherBundle\Entity\ExperimentEngine;
use DispatcherBundle\Entity\LabClient;
use DispatcherBundle\Entity\LabServer;
use DispatcherBundle\Entity\Rlms;
use DispatcherBundle\Entity\LabSession;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->redirectToRoute('loginpage');
    }

    /**
     * @Route("/launchLabClient/{labserverId}/{clientId}", name="publicLaunchLabClient")
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



}
