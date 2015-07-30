<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 7/4/15
 * Time: 10:19 AM
 */

namespace DispatcherBundle\Model\Subscriber;

use SimpleXMLElement;

class QueueLength{

    public  $timestamp;
    public  $success; //true or false
    public  $labServerId;
    public  $queueLength;
    public  $errorMessage;

    public function getSuccess(){

        return $this->success;
    }

    public function setSuccess($success){

        $this->success = $success;
    }

    public function getLabServerId(){

        return $this->labServerId;
    }

    public function setLabServerId($labServerId){

        $this->labServerId = $labServerId;
    }

    public function getQueueLength(){

        return $this->queueLength;
    }

    public function setQueueLength($queueLength){

        $this->queueLength = $queueLength;
    }

    public function getErrorMessage(){

        return $this->errorMessage;
    }

    public function setErrorMessage($errorMessage){

        $this->errorMessage = $errorMessage;
    }

    public function setTimeStamp(){

        $this->timestamp = date('Y-m-d\TH:i:sP');
    }

    public function getTimeStamp(){

        return $this->timestamp;
    }

    public function serialize($format)
    {
        if ($format == 'xml'){

           //$response_array = array_flip((array)$this);

            $xml = new SimpleXMLElement('<queueLengthResponse/>');

            $xml->addChild('timestamp', $this->getTimeStamp());
            $xml->addChild('success', $this->getSuccess());
            $xml->addChild('labServerId', $this->getLabServerId());
            $xml->addChild('queueLength', $this->getQueueLength());
            $xml->addChild('errorMessage', $this->getErrorMessage());
            //array_walk_recursive($response_array, array ($xml, 'addChild'));
            return $xml->asXML();
        }
        $json_response = json_encode($this);
        return $json_response;

    }
}