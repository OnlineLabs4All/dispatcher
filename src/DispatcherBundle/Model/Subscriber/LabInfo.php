<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 4/10/15
 * Time: 12:36 PM
 */

namespace DispatcherBundle\Model\Subscriber;

use SimpleXMLElement;

class LabInfo{

    public $timestamp;
    public $success;
    public $name;
    public $description;
    public $owner_institution;
    public $active;
    public $labConfiguration;
    public $errorMessage;

    public function getTimeStamp()
    {
        return $this->timestamp;
    }

    public function setTimeStamp()
    {
        $this->timestamp = date('Y-m-d\TH:i:sP');
    }
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getOwnerInstitution()
    {
        return $this->owner_institution;
    }

    public function setOwnerInstitution($owner_institution)
    {
        $this->owner_institution = $owner_institution;
    }

    public function getLabStatus()
    {
        return $this->active;
    }

    public function setLabStatus($active)
    {
        $this->active = $active;
    }

    public function getLabConfiguration()
    {
        return $this->labConfiguration;
    }

    public function setLabConfiguration($labConfiguration)
    {
        $this->labConfiguration = $labConfiguration;
    }

    public function getSuccess(){

        return $this->success;
    }

    public function setSuccess($success){

        $this->success = $success;
    }

    public function getErrorMessage(){

        return $this->errorMessage;
    }

    public function setErrorMessage($errorMessage){

        $this->errorMessage = $errorMessage;
    }

    public function serialize($format)
    {
        if ($format == 'xml'){

            $xml = new SimpleXMLElement('<LabInfo/>');

            $xml->addChild('timestamp', $this->getTimeStamp());
            $xml->addChild('success', $this->getSuccess());
            $xml->addChild('name', $this->getName());
            $xml->addChild('description', $this->getDescription());
            $xml->addChild('institution', $this->getOwnerInstitution());
            $xml->addChild('active', $this->getLabStatus());
            $xml->addChild('labConfiguration', $this->getLabConfiguration());
            $xml->addChild('errorMessage', $this->getErrorMessage());
            //array_walk_recursive($response_array, array ($xml, 'addChild'));
            return $xml->asXML();
        }

        $json_labInfo = json_encode($this);
        return $json_labInfo;

    }
}
