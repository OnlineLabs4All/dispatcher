<?php
/**
 * Created by: Danilo G. Zutin
 * Date: 29.07.15
 * Time: 17:47
 */

namespace DispatcherBundle\Model\Subscriber;

use SimpleXMLElement;

class Experiment{

    public $timestamp;
    public $success;
    public $expId;
    public $expSpecification;
    public $message;



    public function getTimeStamp()
    {
        return $this->timestamp;
    }

    public function setTimeStamp()
    {
        $this->timestamp = date('Y-m-d H:i:s');
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
            $xml->addChild('message', $this->getMessage());

            return $xml->asXML();
        }

        $json_status = json_encode($this);
        return $json_status;

    }
}