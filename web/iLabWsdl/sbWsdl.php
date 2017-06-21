<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 21.06.17
 * Time: 16:13
 */

$sbWsdl = file_get_contents('sbWsdl.wsdl');

$soap_endpoint = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$sbWsdl = str_replace('{{soap_endpoint}}', $soap_endpoint.'/apis/isa/soap/client' , $sbWsdl);

header('Content-Type: text/xml');
echo $sbWsdl;