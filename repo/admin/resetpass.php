<?php
	require_once "../db.inc.php";
	require_once "../repo.inc.php";

	// We only allow the hash to be used for the Password Reset, so sending it
	// via GET method is fine.  Now, if someone were to go to the link and then
	// fail to actually change their password, it could be snooped.  Such is the
	// nature of Stupid User Tricks.
	if ( !( isset( $_GET['userid'] ) && isset( $_GET['hash'] ) ) ) {
		header( "Location: /unauthorized.html" );
		exit;
	}

	$u = new Users;
	$u->UserID = $_GET['userid'];
	$c = $u->getUser( $u->UserID );

	if ( $_GET['hash'] != $c->TempHash ) {
		header( "Location: /unauthorized.html" );
		exit;
	}

	if ( isset($_POST['pass1']) && isset($_POST['pass2']) ) {
		if ( $_POST['pass1'] != $_POST['pass2'] ) {
			$content = "<h3>Passwords don't match.</h3>";
		} else {
			$c->setPassword( $_POST['pass1'] );
			$content = "<h3>Password has been reset.  You will be redirected to the login screen in 5 seconds...</h3><meta http-equiv='refresh' content='5; url=/login.php'>";
		}
	}
?>
<!doctype html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<title>openDCIM Repository Password Reset</title>

<link rel="stylesheet" href="../css/repo.css" type="text/css">
<link rel="stylesheet" href="../css/jquery-ui.min.css" type="text/css">
<script src="../scripts/jquery-1.10.1.min.js"></script>
<script src="../scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php print @$content; ?>
<h3>Enter your new password.  If you use a shitty password, it's on you.</h3>
<div>
<form method="post">
<div class="table">
<div>
	<div><label>New Password</label></div>
	<div><input type="password" size=80 name="pass1" id="pass1"></div>
</div>
<div>
	<div><label>Repeat</label></div>
	<div><input type="password" size=80 name="pass2" id="pass2"></div>
</div>
<div>
	<div></div>
	<div><button type="submit" name="action" value="Reset">Reset</button></div>
</div>
</div>
</form>
</div>
</body>
</html>

	
	
