<?php
	require_once( "../db.inc.php" );
	require_once( "../repo.inc.php" );

	if ( ! isset( $_SESSION['userid'] ) ) {
		header( "Location: /login.php" );
		exit;
	}

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
?>
<!DOCTYPE html>
<html>
<head>
<script src="/scripts/jquery-1.10.1.min.js"></script>
<link rel="stylesheet" href="/css/repo.css" type="text/css">
<title>Pending Administrative Requests</title>
</head>
<body>
<a href="/index.php">Back to Main</a>
<div class="leftArea">
<h3>Pending Manufacturer Names</h3>
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

<button id="btnSave">Approve</button>
<button id="btnDelete">Delete</button>
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
