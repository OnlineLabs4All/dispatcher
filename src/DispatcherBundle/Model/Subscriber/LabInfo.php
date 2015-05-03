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

    public $name;

    public $description;

    public $owner_institution;

    public $status;

    public function serialize($format)
    {
        if ($format == 'xml'){

            $labInfo_array = array_flip((array)$this);

            $xml_labInfo = new SimpleXMLElement('<labInfo/>');
            array_walk_recursive($labInfo_array, array ($xml_labInfo, 'addChild'));

            return $xml_labInfo->asXML();
        }

        $json_labInfo = json_encode($this);
        return $json_labInfo;

    }
}