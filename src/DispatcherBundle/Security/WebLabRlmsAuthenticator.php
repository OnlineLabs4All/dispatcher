<?php
/**
 * User: Danilo G. Zutin
 * Date: 29.10.15
 * Time: 14:23
 */

namespace DispatcherBundle\Security;
use Doctrine\ORM\EntityManager;
use DispatcherBundle\Entity\LabSession;
use Symfony\Component\Validator\Constraints\DateTime;


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
                ->findOneBy(array('authorityId' => $rlms->getId() ));

            if ($labSession != null){

                $now = date_create(date('Y-m-d\TH:i:sP'));
                $end = $labSession->getEndDate();
                if ($now < $end){
                    $response = array('is_exception' => false,
                        'exception' => '',
                        'session_id' => $labSession->getSessionId());
                    return $response;
                }
            }
            $session_duration = '604800';
            $startDate = new \DateTime();
            $endDate = new \DateTime();
            $endDate->add( new \DateInterval('PT'.$session_duration.'S'));
            $labSession = new LabSession();
            $session = $labSession->createSession($rlms->getId(), $startDate, $endDate);

            $this->em->persist($labSession);
            $this->em->flush();

            $response = array('is_exception' => false,
                'exception' => '',
                'session_id' => $session['session_id']);
            return $response;
        }

        $response = array('is_exception' => true,
                          'message' => 'Invalid credentials',
                          'code' => 'Client.InvalidCredentials',
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
            $now = new \DateTime();
            $end = $labSession->getEndDate();
            if ($now < $end){
                return array('is_exception' => false,
                             'labSession' => $labSession );
            }
            return array('is_exception' => true,
                         'message' => 'Session has already expired. Please login again.',
                         'code' => 'Client.SessionNotFound');
        }
        return array('is_exception' => true,
                     'message' => 'Session does not exist. Please login.',
                     'code' => 'Client.SessionNotFound');
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