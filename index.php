<?php
require './assets/classes/demoAssets/connection.class.php';
require './assets/classes/tokenAuth.class.php';

$db = array(
	'host' => 'localhost',
	'dbname' => 'admin_test',
	'username' => 'root',
	'password' => 'root'
);

$connection = new connection($db);

if ($connection->conn) {
	$token = new tokenAuth();
	$token->initDb($connection->conn);
	echo($token->getToken() . '<br/>');
    
    $token->setOption('logging', TRUE);
    
    $token->debug('logging');

//    $token->createRequest();
  
//	var_dump($token->validateRequest() );
    
	echo ('<br/>we are alive!');
}


?>