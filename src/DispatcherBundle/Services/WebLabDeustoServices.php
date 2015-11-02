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
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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

}