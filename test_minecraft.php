<?php
require_once 'gsquery.php';
error_reporting(E_ALL);

try {	
	$info = array(
		'host' => 'your.server.example.com',
		'port' => 25565,
		'rcon' => array(
			'password' => 'password'
		)
	);
	$test = GSQuery::create('minecraft', $info);
	print_r($test->queryInfo());
	print_r($test->queryPlayers());
	var_dump($test->queryRcon('list'));
} catch(Exception $e) {
	echo $e->getMessage();
}