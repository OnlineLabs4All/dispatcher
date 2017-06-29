<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 21.06.17
 * Time: 16:13
 */

$sbWsdl1 = file_get_contents('sbWsdl1.wsdl');

$baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$sbWsdl1 = str_replace('{{baseUrl}}', $baseUrl , $sbWsdl1);

header('Content-Type: text/xml');
echo $sbWsdl1;