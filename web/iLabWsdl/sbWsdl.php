<?php
/**
 * Created by PhpStorm.
 * User: garbi
 * Date: 21.06.17
 * Time: 16:13
 */

$sbWsdl = file_get_contents('sbWsdl.wsdl');

$baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$sbWsdl = str_replace('{{baseUrl}}', $baseUrl, $sbWsdl);

header('Content-Type: text/xml');
echo $sbWsdl;