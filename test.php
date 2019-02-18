#!/usr/bin/env php
<?php 
declare(strict_types=1);

require __DIR__ .'/vendor/autoload.php';


$simpleXML = simplexml_load_file('./src/monitor.xml');

foreach($simpleXML->loggers->logger as $xmlElement) {
    $array = [
    	'ID' => (string) $xmlElement->id, 
    	'CLASS' => (string) $xmlElement->class
    ];
}

var_dump($array);
