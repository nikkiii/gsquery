<?php
require_once 'gsquery.php';

error_reporting(E_ALL);
try {	
	$info = array(
		'host' => 'dustbowl.probablyaserver.com',
		'port' => 27015
	);
	$test = GSQuery::create('halflife2', $info);
	print_r($test->queryInfo());
	print_r($test->queryPlayers());
} catch(Exception $e) {
	echo $e->getMessage();
}