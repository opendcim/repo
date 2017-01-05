<?php

	require_once "../db.inc.php";
	require_once "../repo.inc.php";
	require_once "auth.php";

	// At this point we know that the user at least has authenticated and is valid.
	// It is up to each individual page to determine which users have access to their functions
?>
	<div id="menu">
	<h2>Admin Functions</h2>
	<ul>
		<li><a href="keymanager.php">Create or Reset Users and API Keys</a></li>
		<li><a href="editor.php">Repository Template Editor</a></li>
		<li><a href="pending_mfg.php">Approve/Reject Pending Manufacturer Names</a></li>
		<li><a href="pending_tmp.php">Approve/Reject Pending Templates</a></li>
	</ul>
