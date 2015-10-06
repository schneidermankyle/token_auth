<?php
require './assets/classes/connection.class.php';
require './assets/classes/tokenAuth.class.php';

$db = array(
	'host' => 'localhost',
	'dbname' => 'golden_admin',
	'username' => 'root',
	'password' => 'root'
);

$connection = new connection($db);

if ($connection->conn) {
	$token = new tokenAuth();
	echo($token->getToken() . '<br/>');
	echo($token->sanitizeToken());
	// $token->createRequest('session');
	echo ('<br/>we are alive!');
}


?>