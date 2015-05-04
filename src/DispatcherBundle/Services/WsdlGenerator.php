<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 5/4/15
 * Time: 5:14 PM
 */

// src/DispatcherBundle/Services/WsdlGenerator.php
namespace DispatcherBundle\Services;

class WsdlGenerator
{
    function getBatchedLsWsdl(){

        $wsdl_batched = file_get_contents(getcwd()."/../src/DispatcherBundle/Utils/batchedLabServer.wsdl");
        return $wsdl_batched;
    }




}