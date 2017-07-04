<?php
/**
 * User: Danilo G. Zutin
 * Date: 29.10.15
 * Time: 20:14
 */
namespace DispatcherBundle\Services;
use DispatcherBundle\Entity\JobRecord;
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
            ->findBy(array('rlmsId' => $session->getAuthorityId()));

        $result = array();
        foreach ($mappings as $mapping){

            $lab = $this
                ->em
                ->getRepository('DispatcherBundle:LabServer')
                ->findOneBy(array('id' => $mapping->getLabServerId()));

            array_push($result,  array('time_allowed' => '604800',
                'experiment' => array('category' => array('name' => $lab->getExpCategory()),
                    'name' => $lab->getExpName(),
                    'start_date' => $session->getStartDate()->format('Y-m-d H:i:s'),
                    'end_date' => $session->getEnddate()->format('Y-m-d H:i:s'))));
        }

        $responseJson = array('is_exception' => false,
                              'result' => $result);
        return $responseJson;
    }

    public function reserveExperiment($params, $authorityId)
    {
        $lab = $this
            ->em
            ->getRepository('DispatcherBundle:LabServer')
            ->findOneBy(array('exp_category' => $params->experiment_id->cat_name, 'exp_name' => $params->experiment_id->exp_name));

        if ($lab != null){

            $experimentSpecification = $params->client_initial_data;
            $consumer_data = $params->consumer_data;//save it to the opaque data field

            $this->labServerServices->setLabServerId($lab->getId());
            $submissionReport = $this->labServerServices->Submit(null, $experimentSpecification, $consumer_data, 0, $authorityId);

            $baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

            return array('is_exception' => false,
                'result' => array('reservation_id' => array('id' => (string)$submissionReport['experimentID']),
                    'status' => 'Reservation::waiting',
                    'url' => $baseUrl.'/apis/weblab/',
                    'position' => $submissionReport['wait']['effectiveQueueLength']));
        }

        return array('is_exception' => true,
                     'message' => "Experiment name and/or category not found");
    }

    public function getReservationStatus($params, $authorityId)
    {
        $reservation_id = $params->reservation_id->id;

        $jobRecord = $this->labServerServices->getJobRecordById($reservation_id, $authorityId);

        if ($jobRecord == null){
            return array('is_exception' => true,
                'message' => "Reservation (exp ID) not found",
                'code' => 'Client.NoCurrentReservation');
        }

        $statusCode = $jobRecord->getJobStatus();
        switch($statusCode){
            case 1: //experiment has been queued

                $this->labServerServices->setLabServerId($jobRecord->getLabserverId());
                $expStatus = $this->labServerServices->getExperimentStatus($reservation_id, $authorityId);

                return array('is_exception' => false,
                    'result' => array('reservation_id' => array('id' => (string)$reservation_id),
                        'status' => 'Reservation::waiting',
                        'url' => 'http://localhost:8000/apis/weblab/',
                        'position' => $expStatus['effectiveQueueLength']));
                break;
            case 2: //Experiment is running

                return array('is_exception' => false,
                    'result' => array('reservation_id' => array('id' => (string)$reservation_id),
                        'status' => 'Reservation::waiting_confirmation',
                        'url' => 'http://localhost:8000/apis/weblab/'));
                break;
            case 3: //Experiment has completed

                //$expResults = $this->labServerServices->retrieveResult($reservation_id, $rlmsId);
                //$expSpecification = $this->labServerServices->retrieveExperimentSpecification($reservation_id, $rlmsId);
                return array('is_exception' => false,
                    'result' => array('reservation_id' => array('id' => (string)$reservation_id),
                                      'status' => 'Reservation::post_reservation',
                                      'initial_data' => $jobRecord->getExpSpecification(),
                                      'end_data' => $jobRecord->getExpResults(),
                                      'finished' => true));
                break;
            case 4: //Experiment has completed with errors

                //$expResults = $this->labServerServices->retrieveResult($reservation_id, $rlmsId);
                //$expSpecification = $this->labServerServices->retrieveExperimentSpecification($reservation_id, $rlmsId);
                return array('is_exception' => false,
                             'result' => array('reservation_id' => array('id' => (string)$reservation_id),
                             'status' => 'Reservation::post_reservation',
                             'initial_data' => $jobRecord->getExpSpecification(),
                             'end_data' => $jobRecord->getExpResults(),
                             'finished' => true));
                break;
            case 5: //Experiment has completed

                //$expResults = $this->labServerServices->retrieveResult($reservation_id, $rlmsId);
                //$expSpecification = $this->labServerServices->retrieveExperimentSpecification($reservation_id, $rlmsId);
                return array('is_exception' => false,
                             'result' => array('reservation_id' => array('id' => (string)$reservation_id),
                             'status' => 'Reservation::post_reservation',
                             'initial_data' => $jobRecord->getExpSpecification(),
                              'end_data' => $jobRecord->getExpResults(),
                              'finished' => true));
                break;
            default:
                return array('is_exception' => true,
                    'message' => "status code not available");
        }
    }

    public function getExperimentUseById($reservation_id, $authorityId)
    {
        $jobRecord = $this->labServerServices->getJobRecordById($reservation_id, $authorityId);

        if ($jobRecord == null){
            return $result = array('status' => 'forbidden');
        }

        $statusCode = $jobRecord->getJobStatus();
        switch($statusCode){
            case 1: //experiment has been queued

                $this->labServerServices->setLabServerId($jobRecord->getLabserverId());
                $result = array('status' => 'alive',
                                'running' => false);
                break;
            case 2: //Experiment is running

                $result = array('status' => 'alive',
                                'running' => true);
                break;
            case 3: //Experiment has completed

                $experimentUse = $this->assembleExperimentUse($jobRecord);

                $result = array('status' => 'finished',
                                'experiment_use' => $experimentUse);
                break;
            case 4:
                $result = array('status' => 'cancelled');

                break;
            case 5:
                $result = array('status' => 'cancelled');

                break;
            default:
                $result = array('status' => 'forbidden');
        }
        return $result;
    }

    public function getExperimentUsesById($reservation_ids, $authorityId)
    {
        $results = array();
        foreach ($reservation_ids as $reservation_id){
            $result = $this->getExperimentUseById($reservation_id->id, $authorityId);
            array_push($results, $result);
        }
        return $results;
    }

    private function assembleExperimentUse(JobRecord $jobRecord)
    {
        $expMetadata = $this->labServerServices->getExperimentMetadata($jobRecord);

        $commands = array(array('command' => array('commandstring' => '@@@initial::request@@@'),
                                'timestamp_before' => strtotime($jobRecord->getSubmitTime()),
                                'timestamp_after' => strtotime($jobRecord->getExecutionTime()),
                                'response' => array('commandstring' => $jobRecord->getExpSpecification())),
                          array('command' => array('commandstring' => '@@@initial::response@@@'),
                                'timestamp_before' => strtotime($jobRecord->getExecutionTime()),
                                'timestamp_after' => strtotime($jobRecord->getEndTime()),
                                'response' => array('commandstring' => $jobRecord->getExpResults())),
                          array('command' => array('commandstring' => ' @@@finish@@@'),
                                'timestamp_before' => strtotime($jobRecord->getSubmitTime()),
                                'timestamp_after' => strtotime($jobRecord->getEndTime()),
                                'response' => array('commandstring' => json_encode($expMetadata))));


        $experimentUse = array('commands' => $commands,
                               'end_date' => strtotime($jobRecord->getEndTime()),
                               'experiment_use_id' => $jobRecord->getExpId(),
                               'request_info' => json_decode($jobRecord->getOpaqueData()),
                               'from_ip' => '127.0.0.1',
                               'sent_files' => array(),
                               'experiment_id' => array('exp_name' => $expMetadata['labServer']['exp_name'],
                                                        'cat_name' => $expMetadata['labServer']['cat_name']),
                               'coord_address' => array('process' => '',
                                                        'host' => $expMetadata['labServer']['name'],
                                                        'component' => $expMetadata['engine']['name']),
                               'reservation_id' => $jobRecord->getExpId(),
                               'start_date' => strtotime($jobRecord->getSubmitTime())
        );
        return $experimentUse;
    }
}