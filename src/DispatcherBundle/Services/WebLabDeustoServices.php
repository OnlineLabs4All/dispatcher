<?php
/**
 * User: Danilo G. Zutin
 * Date: 29.10.15
 * Time: 20:14
 */
namespace DispatcherBundle\Services;
use Doctrine\ORM\EntityManager;

class WebLabDeustoServices
{
    public function __construct(EntityManager $em, GenericLabServerServices $labServerServices)
    {
        $this->em = $em;
        $this->labServerServices = $labServerServices;
    }

    public function listExperiments($session)
    {
        $mappings = $this
            ->em
            ->getRepository('DispatcherBundle:LsToRlmsMapping')
            ->findBy(array('rlmsId' => $session->getRlmsId()));

        $result = array();
        foreach ($mappings as $mapping){

            $lab = $this
                ->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findOneBy(array('id' => $mapping->getLabServerId()));

            array_push($result,  array('time_allowed' => '604800',
                'experiment' => array('category' => array('name' => $lab->getExpCategory()),
                    'name' => $lab->getExpName(),
                    'start_date' => $session->getStartDate(),
                    'end_date' => $session->getEnddate())));
        }

        $responseJson = array('is_exception' => false,
                              'result' => $result);
        return $responseJson;
    }

    public function reserveExperiment($params, $rlmsId)
    {
        $lab = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('exp_category' => $params->experiment_id->cat_name, 'exp_name' => $params->experiment_id->exp_name));

        if ($lab != null){

            $experimentSpecification = $params->client_initial_data;
            $consumer_data = $params->consumer_data;

            $this->labServerServices->setLabServerId($lab->getId());
            $submissionReport = $this->labServerServices->Submit(null, $experimentSpecification, $consumer_data, 0, $rlmsId);


            return array('is_exception' => false,
                'result' => array('reservation_id' => array('id' => (string)$submissionReport['experimentID']),
                    'status' => 'Reservation::waiting',
                    'url' => 'http://localhost:8000/apis/weblab/',
                    'position' => $submissionReport['wait']['effectiveQueueLength']));
        }

        return array('is_exception' => true,
                     'message' => "Experiment name and/or category not found");
    }

    public function getReservationStatus($params, $rlmsId)
    {
        $reservation_id = $params->reservation_id->id;

        $jobRecord = $this
            ->em
            ->getRepository('DispatcherBundle:JobRecord')
            ->findOneBy(array('expId' => $reservation_id, 'providerId' => $rlmsId));

        if ($jobRecord == null){
            return array('is_exception' => true,
                'message' => "Reservation (exp ID) not found",
                'code' => 'Client.NoCurrentReservation');
        }

        $this->labServerServices->setLabServerId($jobRecord->getLabserverId());
        $expStatus = $this->labServerServices->getExperimentStatus($reservation_id, $rlmsId);

        $statusCode = $expStatus['statusCode'];
        switch($statusCode){
            case 1: //experiment has been queued

                return array('is_exception' => false,
                    'result' => array('reservation_id' => array('id' => (string)$reservation_id),
                        'status' => 'Reservation::waiting',
                        'url' => 'http://localhost:8000/apis/weblab/',
                        'position' => $expStatus['effectiveQueueLength']));//TODO: Change this, not nice!
                break;
            case 2: //Experiment is running

                return array('is_exception' => false,
                    'result' => array('reservation_id' => array('id' => (string)$reservation_id),
                        'status' => 'Reservation::waiting_confirmation',
                        'url' => 'http://localhost:8000/apis/weblab/'));
                break;
            case 3: //Experiment has completed

                $results = $this->labServerServices->retrieveResult($reservation_id, $rlmsId);
                return array('is_exception' => false,
                    'result' => array('reservation_id' => array('id' => (string)$reservation_id),
                        'status' => 'Reservation::post_reservation',
                        'initial_data' => $results['experimentResults'],
                        'end_data' => $results['experimentResults'],
                        'finished' => true));
                break;
            default:
                return array('is_exception' => true,
                    'message' => "status code not available");
        }
    }
}