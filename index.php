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
	$token->authType = 'cookie';

	var_dump($token->createRequest());
	echo ('<br/>we are alive!');
}


?>