<?php

	if ( ! isset( $_SESSION['userid'] )) {
		$savedurl = $_SERVER['SCRIPT_NAME'] . "?" . $_SERVER['QUERY_STRING'];
		setcookie( 'targeturl', $savedurl, time()+60 );
		header( "Location: /login.php" );
		exit;
	} else {
		$u = new Users();
		$u->UserID = $_SESSION['userid'];
		if ( ! $u->verifyLogin( $_SERVER['REMOTE_ADDR'] ) ) {
			header( "Location: /unauthorized.html" );
			exit;
		}
	}
?>
