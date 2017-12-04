<?php
	require_once "db.inc.php";
	require_once "repo.inc.php";
	require_once "vendor/autoload.php";

	$content = "";
	$u = new Users();

	if ( isset( $_GET['logout'])) {
		session_unset();
		$_SESSION = array();
		unset($_SESSION['userid']);
		session_destroy();
		session_commit();
		$content = "<h3>Logout successful.</h3>";
	}

	if ( isset($_POST['submit']) && $_POST['submit']=="Recover Password" ) {
		if ( isset( $_POST['username'] ) && filter_var( $_POST['username'], FILTER_VALIDATE_EMAIL)) {
			error_log( "Recovery requested for UserID=" . $_POST['username'] );
			$content = "<h3>If the entered email address is valid, instructions have been sent to it.</h3>";
			$cUser = new Users();
                        $cUser->UserID = $_POST['username'];
                        $cUser->setRecovery();
                        // Pull up the record to get the new recovery hash
                        $cUser = $u->getUser( $cUser->UserID );
			// Only Admins and Mods can login
			if ( $cUser->Administrator || $cUser->Moderator ) {
	                        $trans = Swift_SmtpTransport::newInstance('localhost',25);
        	                $mailer = Swift_Mailer::newInstance($trans);
        	                $mContent = "A password reset has been requested for this UserID.  If you did not make this request, please login using your normal credentials at https://repository.opendcim.org/login.php and it will clear the recovery state.

To recover your password, open https://repository.opendcim.org/admin/resetpass.php?userid=" . $cUser->UserID . "&hash=" . $cUser->TempHash;
	                        $message = Swift_Message::newInstance("Requested Password Reset")
	                                ->setFrom(array("scott@opendcim.org" => "openDCIM Administrator" ))
	                                ->setTo(array($cUser->UserID => $cUser->PrettyName ))
	                                ->setBody( $mContent );

	                        $result = $mailer->send($message);
			}
		} else {
			$content = "<h3>You must enter a username (email address).</h3>";
		}
	} elseif ( isset( $_POST['username']) && isset($_POST['password'])) {
		$u->UserID = $_POST['username'];
		if ( ! $u->VerifyPassword( $_POST['password'] ) ) {
			$content = "<h3>Login failed.</h3>";
		} else {
			// Valid user credentials
			$_SESSION['userid'] = $u->UserID;
			$_SESSION['LoginTime'] = time();
			session_commit();

			if ( isset( $_COOKIE['targeturl'] )) {
				header( 'Location: ' . html_entity_decode($_COOKIE['targeturl']));
			} else {
				header( 'Location: /admin/index.php');
			}
			exit;
		}
	}
?>
<!doctype html>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<title>openDCIM Template Repository</title>
	<link rel="stylesheet" href="css/repo.css" type="text/css">
	<link rel="stylesheet" href="css/jquery-ui.min.css" type="text/css">
	<script src="scripts/jquery-1.10.1.min.js"></script>
	<script src="scripts/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$("#username").focus();
});
</script>
</head>
<body>
<div>
<?php echo $content; ?>

<form method="post">
<div class="table">
	<div>
		<div><label for="username">Username:</label></div>
		<div><input type="text" id="username" name="username"></div>
	</div>
	<div>
		<div><label for="password">Password:</label></div>
		<div><input type="password" name="password"></div>
	</div>
	<div>
		<div></div>
		<div><input type="submit" name="submit" value="Submit"><input type="submit" name="submit" value="Recover Password"></div>
	</div>
</div>
</form>

</div>
</body>
</html>
	
		
