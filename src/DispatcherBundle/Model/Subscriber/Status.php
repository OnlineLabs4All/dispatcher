<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 27.07.15
 * Time: 16:45
 */
namespace DispatcherBundle\Model\Subscriber;

use SimpleXMLElement;

class Status{

    public $timestamp;
    public $success;
    public $expId;
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

            $xml = new SimpleXMLElement('<status/>');

            $xml->addChild('timestamp', $this->getTimeStamp());
            $xml->addChild('success', $this->getSuccess());
            $xml->addChild('expId', $this->getExperimentId());
            $xml->addChild('message', $this->getMessage());
            return $xml->asXML();
        }

        $json_status = json_encode($this);
        return $json_status;

    }

}