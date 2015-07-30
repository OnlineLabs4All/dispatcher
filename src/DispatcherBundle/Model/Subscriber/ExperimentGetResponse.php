<?php
/**
 * Created by: Danilo G. Zutin
 * Date: 29.07.15
 * Time: 17:47
 */

namespace DispatcherBundle\Model\Subscriber;

use SimpleXMLElement;

class ExperimentGetResponse{

    public $timestamp;
    public $success;
    public $expId;
    public $jobStatus;
    public $expSpecification;
    public $message;



    public function getTimeStamp()
    {
        return $this->timestamp;
    }

    public function setTimeStamp()
    {
        $this->timestamp = date('Y-m-d\TH:i:sP');
    }

    public function setSuccess($success)
    {
        $this->success = $success;
    }
    public function getSuccess()
    {
        return $this->success;
    }
    public function setExperimentId($expId)
    {
        $this->expId = $expId;
    }

    public function getExperimentId()
    {
        return $this->expId;
    }
    public function setExpSpecification($expSpec)
    {
        $this->expSpecification = $expSpec;
    }

    public function getExpSpecification()
    {
        return $this->expSpecification;
    }

    public function setJobStatus($jobStatus)
    {
        $this->jobStatus = $jobStatus;
    }

    public function getJobStatus()
    {
        return $this->jobStatus;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function serialize($format)
    {
        if ($format == 'xml'){

            $xml = new SimpleXMLElement('<experiment/>');

            $xml->addChild('timestamp', $this->getTimeStamp());
            $xml->addChild('success', $this->getSuccess());
            $xml->addChild('expId', $this->getExperimentId());
            $xml->addChild('expSpecification', $this->getExpSpecification());
            $xml->addChild('jobStatus', $this->getJobStatus());
            $xml->addChild('message', $this->getMessage());

            return $xml->asXML();
        }

        $json_status = json_encode($this);
        return $json_status;

    }
}