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
?>
