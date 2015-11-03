<?php
/**
 * User: Danilo G. Zutin
 * Date: 29.10.15
 * Time: 14:23
 */

namespace DispatcherBundle\Security;
use Doctrine\ORM\EntityManager;
use DispatcherBundle\Entity\LabSession;


class WebLabRlmsAuthenticator
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function webLabLogin($username, $password)
    {
        $rlms = $this
            ->em
            ->getRepository('DispatcherBundle:Rlms')
            ->findOneBy(array('username' => $username, 'password' => md5($password)));

        if ($rlms != null){

            //check if lab session already exists and is valid
            $labSession = $this
                ->em
                ->getRepository('DispatcherBundle:LabSession')
                ->findOneBy(array('rlmsId' => $rlms->getId() ));

            if ($labSession != null){

                $now = date_create(date('Y-m-d\TH:i:sP'));
                $end = date_create($labSession->getEndDate());
                if ($now < $end){
                    $response = array('is_exception' => false,
                        'exception' => '',
                        'session_id' => $labSession->getSessionId());
                    return $response;
                }
            }
            $session_duration = '604800';
            $dateNow = date('Y-m-d\TH:i:sP');
            $startDate = date_create($dateNow);
            $endDate = $startDate->add( new \DateInterval('PT'.$session_duration.'S'));
            $labSession = new LabSession();
            $session_id = $labSession->createRlmsSession($rlms->getId(), $dateNow, $endDate->format('Y-m-d\TH:i:sP'));

            $this->em->persist($labSession);
            $this->em->flush();

            $response = array('is_exception' => false,
                'exception' => '',
                'session_id' => $session_id);
            return $response;
        }

        $response = array('is_exception' => true,
                          'message' => 'Invalid credentials',
                          'code' => 'JSON:Client.InvalidCredentials',
                          'session_id'=> '');
        return $response;
    }

    public function validateSessionById($session_id)
    {
        $labSession = $this
            ->em
            ->getRepository('DispatcherBundle:LabSession')
            ->findOneBy(array('session_id' => $session_id ));

        if ($labSession != null){
            $now = date_create(date('Y-m-d\TH:i:sP'));
            $end = date_create($labSession->getEndDate());
            if ($now < $end){
                return $labSession;
            }
            return null;
        }
        return null;
    }

    public function setSessionLabServer($session_id, $exp_cat, $exp_name)
    {
        $labSession = $this
            ->em
            ->getRepository('DispatcherBundle:LabSession')
            ->findOneBy(array('session_id' => $session_id ));

        if ($labSession != null){

            $lab = $this
                ->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findOneBy(array('exp_category' => $exp_cat, 'exp_name' => $exp_name));

            if ($lab != null){
                $labSession->setLabServerId($lab->getId());
                $this->em->persist($labSession);
                $this->em->flush();

                return true;
            }
            return false;
        }
        return false;

    }

}