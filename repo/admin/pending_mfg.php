<?php
	require_once "../db.inc.php";
	require_once "../repo.inc.php";
	require_once "../vendor/autoload.php";
	require_once "auth.php";


	$u = new Users();
	$u->UserID = $_SESSION['userid'];

	if ( ! $u->verifyLogin( $_SERVER["REMOTE_ADDR"] ) ) {
		header( "Location: /unauthorized.html" );
		exit;
	}

	if ( ! $u->Administrator && ! $u->Moderator ) {
		header( "Location: /unauthorized.html" );
		exit;
	}

	if ( isset( $_POST['action'] ) ) {
	}
	$st = $dbh->prepare( "select count(*) as Total from Manufacturers" );
	$st->execute();
	$mfg = $st->fetch();

	$st = $dbh->prepare( "select count(*) as Total from DeviceTemplates" );
	$st->execute();
	$dt = $st->fetch();

	$st = $dbh->prepare( "select count(*) as Total from CDUTemplates" );
	$st->execute();
	$ct = $st->fetch();
?>
<!DOCTYPE html>
<html>
<head>
<script src="/scripts/jquery-1.10.1.min.js"></script>
<link rel="stylesheet" href="/css/repo.css" type="text/css">
<title>Pending Administrative Requests</title>
</head>
<body>
	<div id="header">
		<div id="logo">
			<a href="/"><img src="../opendcim.png"></a>
		</div>
		<div id="title">
			<h1>Pending Manufacturer Names</h1>
		</div>
		<div id="stats">
			<h1>Template Information</h1>
<?php echo '
			<div>
				Total Manufacturers = '.$mfg["Total"].'
			</div>
			<div>
				Total Device Templates = '.$dt["Total"].'
			</div>
			<div>
				Total CDU Templates = '.$ct["Total"].'
			</div>
'; ?>
		</div>
		<div id="nav">
			<ul>
				<li>Pending Admin Requests:</li>
				<li><a href="pending_mfg.php">Manufacturers</a></li>
				<li><a href="pending_tmp.php">Templates</a></li>
			</ul>
		</div>
	</div>


<div class="leftArea">
<ul id="mfgList"></ul>
</div>

<form id="mfgForm">

<div class="mainArea">
<label>Request ID:</label>
<input id="requestid" name="requestid" type="text" disabled />

<label>Name:</label>
<input type="text" id="name" name="name" required>

<label>Submitted By:</label>
<input type="text" id="submittedby" name="submittedby" disabled />
<div>
<button id="btnSave">Approve</button>
<button id="btnDelete">Delete</button>

<select name="reason">
<option value=1>Duplicate of Existing Record</option>
<option value=2>This is a product line, not a Manufacturer</option>
</select>
</div>
</div>

</form>

<div class="rightArea">
<h3>Current Manufacturer List</h3>
<ul id="currMfgList"></ul>
</div>

<script type="text/javascript">

$('#btnDelete').hide();

$('#btnSave').click(function() {
	approveRequest();
	return false;
});

$('#btnDelete').click(function() {
	deleteRequest();
	return false;
});

$(document).ready( refreshList() );

function refreshList() {
	$.ajax({
		type: 'GET',
		url: 'https://repository.opendcim.org/api/manufacturer/pending/',
		headers: {
			'APIKey':'<?= $u->APIKey; ?>',
			'UserID':'<?= $u->UserID; ?>'
		},
		dataType: "json",
		success: function(data) {
			// If there wasn't a server error continue
			if(data.errorcode==200 && !data.error) {
				// Update local record with data from queue
				$('#mfgList').html('');
				for( var i in data.manufacturersqueue ) {
					var row = data.manufacturersqueue[i];
					var a=$('<a>').data('identity',row.RequestID).attr('href','#').text(row.RequestID + ' - ' + row.Name);
					var li=$('<li>').append(a);
					$('#mfgList').append(li);

					a.on('click',function(e){
						findById($(this).data('identity'));
					});
				} 
			}
		}
	});

	$.ajax({
		type: 'GET',
		url: 'https://repository.opendcim.org/api/manufacturer',
		dataType: "json",
		success: function(data) {
			if ( data.errorcord=200 && !data.error) {
				$('#currMfgList').html('');
				for( var i in data.manufacturers ) {
					var row = data.manufacturers[i];
					$('#currMfgList').append('<li>'+row.Name+'</li');
				}
			}
		}
	});
};

function findById(id) {
	$.ajax({
		type: 'GET',
		url: 'https://repository.opendcim.org/api/manufacturer/pending/byid/' + id,
		headers: {
			'APIKey':'<?= $u->APIKey; ?>',
			'UserID':'<?= $u->UserID; ?>'
		},
		dataType: "json",
		success: function(data) {
			$('#btnDelete').show();
			$('#requestid').val(data.manufacturersqueue[0].RequestID);
			$('#name').val(data.manufacturersqueue[0].Name);
			$('#submittedby').val(data.manufacturersqueue[0].SubmittedBy);
		}
	});
}

function approveRequest() {
	$.ajax({
		type: 'POST',
		contentType: 'application/json',
		url: 'https://repository.opendcim.org/api/manufacturer/approve',
		dataType: "json",
		headers: {
			'APIKey':'<?= $u->APIKey; ?>',
			'UserID':'<?= $u->UserID; ?>'
		},
		data: JSON.stringify({
			"RequestID":$('#requestid').val(),
			"Name":$('#name').val()
			}),
		success: function ( data, textStatus, jqXHR ) {
			// alert( 'Approval successful.' );
			refreshList();
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			alert( 'Error processing approval.' );
		}
	});
}

function deleteRequest() {
        $.ajax({
                type: 'POST',
                contentType: 'application/json',
                url: 'https://repository.opendcim.org/api/manufacturer/pending/delete/'+$('#requestid').val(),
                dataType: 'json',
                headers: {
                        'APIKey':'<?= $u->APIKey; ?>',
                        'UserID':'<?= $u->UserID; ?>'
                },
		data: JSON.stringify({
			"Reason":$('#reason').val()
			}),
                success: function( data, textStatus, jqXHR ) {
                        refreshList();
                },
                error: function (jqXHR, textStatus, errorThrown ) {
                        alert( 'Error processing request.' );
                }
        });
}
</script>
</body>
</html>
