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
	$token->initDb($connection->conn);
	echo($token->getToken() . '<br/>');
    
    $token->setOption('action', 'update');
    $token->createRequest();
  
	var_dump($token->validateRequest() );
    

	echo ('<br/>we are alive!');
}


?>