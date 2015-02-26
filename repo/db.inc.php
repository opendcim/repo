<?php

	$dbhost = "localhost";
	$dbuser = "dcim";
	$dbpass = "dcim";
	$dbname = "dcim_repo";

	$locale = "en_US";
	$codeset = "UTF-8";

	try {
			$pdoconnect = sprintf( "mysql:host=%s;dbname=%s", $dbhost, $dbname );
			$dbh = new PDO( $pdoconnect, $dbuser, $dbpass );
	} catch ( PDOException $e ) {
			printf( "Error!  %s\n", $e->getMessage() );
			die();
	}	try {
			$pdoconnect = sprintf( "mysql:host=%s;dbname=%s", $dbhost, $dbname );
			$dbh = new PDO( $pdoconnect, $dbuser, $dbpass );
	} catch ( PDOException $e ) {
			printf( "Error!  %s\n", $e->getMessage() );
			die();
	}

	// OAuth 2.0 data
        $ClientID = '493405997271-lsefoa9cbvo0rmb0id18cc67l6upt1ah.apps.googleusercontent.com';
        $ClientSecret = 'feohssBYVMH6mHDMM97ZpmOG';

	session_start();
?>
