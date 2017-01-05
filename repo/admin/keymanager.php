<?php
	require_once "../db.inc.php";
	require_once "../repo.inc.php";
	require_once "../vendor/autoload.php";
	require_once "auth.php";

	// Only Administrators are allowed to add keys
	if ( ! $u->Administrator ) {
		header( "Location: /unauthorized.html" );
		exit;
	}

	$content = "";
	$cUser = "";

	$uList = $u->getUser();

	if ( isset( $_GET['userid'] ) && $_GET['userid'] != "new" ) {
		$cUser = $u->getUser( urldecode( $_GET['userid'] ) );
	}

	if ( isset( $_POST['action'] ) ) {
		switch( $_POST['action'] ) {
			case "NewKey":
				$content .= "<h3>New key requested and emailed to the user.</h3>";
				$cUser->UserID = $_POST['userid'];
				$cUser->setAPIKey();
				// Pull in the full record now that it has changed
				$cUser = $cUser->getUser( $cUser->UserID );
				$mContent = "This email address has been associated with an API Key for submitting templates to the openDCIM Template Repository.  An administrator has generated a new API key for you to use, which is effective immediately.  Please update your installation to use this new key.

APIUser: " . $cUser->UserID . "
APIKey: " . $cUser->APIKey;
				$trans = Swift_SmtpTransport::newInstance('localhost',25);
				$mailer = Swift_Mailer::newInstance($trans);
				$message = Swift_Message::newInstance("openDCIM Repository API Key")
					->setFrom(array("scott@opendcim.org" => "openDCIM Administrator"))
					->setTo(array($cUser->UserID => $cUser->PrettyName ))
					->setBody( $mContent );
				$result = $mailer->send($message);
				break;
			case "ResetPass":
				$cUser->UserID = $_POST['userid'];
				$cUser->setRecovery();
				// Pull up the record to get the new recovery hash
				$cUser = $u->getUser( $cUser->UserID );
				if ( $cUser->Administrator || $cUser->Moderator ) {
					$content .= "<h3>Password reset requested.  A link has been emailed to the user.</h3>";
					$trans = Swift_SmtpTransport::newInstance('localhost',25);
					$mailer = Swift_Mailer::newInstance($trans);
					$mContent = "A password reset has been requested for this UserID.  If you did not make this request, please login using your normal credentials at https://repository.opendcim.org/login.php and it will clear the recovery state.

To recover your password, open https://repository.opendcim.org/admin/resetpass.php?userid=" . $cUser->UserID . "&hash=" . $cUser->TempHash;
					$message = Swift_Message::newInstance("Requested Password Reset")
						->setFrom(array("scott@opendcim.org" => "openDCIM Administrator" ))
						->setTo(array($cUser->UserID => $cUser->PrettyName ))
						->setBody( $mContent );

					$result = $mailer->send($message);
				} else {
					$content .= "<h3>User is not an Admin or Moderator, so there is no login allowed.  Password reset not initiated.</h3>";
				}
				break;
			case "Update":
				$content .= "<h3>Record update requested.</h3>";
				break;
			default:
		}
	}

	// No, I should haven't have add the associations, but I'm being language paranoid
	$bool = array( "0" => "No", "1" => "Yes" );
?>
<!doctype html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<title>openDCIM Repository User Administration</title>

<link rel="stylesheet" href="../css/repo.css" type="text/css">
<link rel="stylesheet" href="../css/jquery-ui.min.css" type="text/css">
<script src="../scripts/jquery-1.10.1.min.js"></script>
<script src="../scripts/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$('#userid').change(function(e) {
		location.href="?userid="+this.value;
	});
});
</script>
</head>
<body>
<div>
<?php print $content; ?>
<form method="post">
<div class="table">
<div>
	<div><label for="userid">User:</label></div>
<div>
<select name="userid" id="userid">
<option value="new">New User</option>
<?php
	foreach( $uList as $n ) {
		if ( is_object( $cUser ) && $cUser->UserID == $n->UserID ) {
			$selected = "SELECTED";
		} else {
			$selected = "";
		}
		print "<option value=\"" . $n->UserID . "\" $selected>" . $n->UserID . "</option>";
	}
?>
</select></div>
</div>
<div>
	<div label for="prettyname">Name</label></div>
	<div><input name="prettyname" id="prettyname" type="text" size="80" value="<?php print @$cUser->PrettyName; ?>"></div>
</div>
<div>
	<div><label>API Key</label></div>
	<div><?php print @$cUser->APIKey; ?></div>
</div>
<div>
	<div><label for="administrator">Administrator?</label></div>
	<div><select name="administrator" id="administrator">
<?php
	foreach( $bool as $val=>$label ) {
		if ( is_object( $cUser ) && $cUser->Administrator == $val ) {
			$selected = "SELECTED";
		} else {
			$selected = "";
		}
		print "<option value=\"$val\" $selected>$label</option>";
	}
?>
	</select></div>
</div>
<div>
	<div><label for="moderator">Moderator?</label></div>
	<div><select name="moderator" id="moderator">
<?php
        foreach( $bool as $val=>$label ) {
                if ( is_object( $cUser ) && $cUser->Moderator == $val ) {
                        $selected = "SELECTED";
                } else {
                        $selected = "";
                }
                print "<option value=\"$val\" $selected>$label</option>";
        }
?>
        </select></div>
</div>
<div>
	<div><label>Last Login Address</label></div>
	<div><?php print @$cUser->LastLoginAddress; ?></div>
</div>
<div>
	<div><label>Last Login</label></div>
	<div><?php if ( is_object( $cUser ) && $cUser->LastLogin!=null && $cUser->LastLogin!="0000-00-00 00:00:00" ) echo date( "Y-m-d H:i:s", strtotime( $cUser->LastLogin )); else echo "Never"; ?></div>
</div>
<div>
        <div><label>Last API Address</label></div>
        <div><?php print @$cUser->LastAPIAddress; ?></div>
</div>
<div>
        <div><label>Last API Access</label></div>
        <div><?php if ( is_object( $cUser ) && $cUser->LastAPILogin!=null && $cUser->LastAPILogin!="0000-00-00 00:00:00" ) echo date( "Y-m-d H:i:s", strtotime( $cUser->LastAPILogin )); else echo "Never"; ?></div>
</div>

<div>
	<div><label for="disabled">Disabled?</label></div>
	<div><select name="disabled" id="disabled">
<?php
        foreach( $bool as $val=>$label ) {
                if ( is_object( $cUser ) && $cUser->Disabled == $val ) {
                        $selected = "SELECTED";
                } else {
                        $selected = "";
                }
                print "<option value=\"$val\" $selected>$label</option>";
        }
?>
        </select></div>
</div>
<div>
	<div></div>
	<div><button type="submit" name="action" value="Update">Update</button>
		<button typ="submit" name="action" value="NewKey">New Key</button>
		<button type="submit" name="action" value="ResetPass">Reset Password</button>
	</div>
</div>

</div>
</form>
<h3><a href="index.php">Back to Admin Menu</a></h3>
</div>
</body>
</html>
