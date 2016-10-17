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
<script src="/scripts/jquery.serialize-object.min.js"></script>
<link rel="stylesheet" href="/css/repo.css" type="text/css">
<title>Pending Administrative Requests</title>
</head>
<body>
<a href="/index.php">Back to Main</a>
<div class="leftArea">
<h3>Pending Device Templates</h3>
<ul id="tmpList"></ul>
</div>

<form id="tmpForm">
<input id="TemplateID" name="TemplateID" type=hidden>

<div class="mainArea">
<label>Request ID:</label>
<input id="RequestID" name="RequestID" type="text"/>

<label>Front Picture File:</label>
<input type="text" name="FrontPictureFile" id="FrontPictureFile" size=40>
<img id="frontpic" width="300"/>

<label>Rear Picture File</label>
<input type="text" name="RearPictureFile" id="RearPictureFile" size=40>

<img id="rearpic" width="300"/>
<label>Manufacturer:</label>
<select name="ManufacturerID" id="ManufacturerID">
</select>

<label>Model:</label>
<input type="text" id="Model" name="Model" required>

<label>Height:</label>
<input type="text" id="Height" name="Height" required>

<label>Weight:</label>
<input type="text" id="Weight" name="Weight" required>

<label>Wattage:</label>
<input type="text" id="Wattage" name="Wattage" required>

<label>Device Type:</label>
<select name="DeviceType" id="DeviceType">
<option value="Server">Server</option>
<option value="Chassis">Chassis</option>
<option value="Appliance">Appliance</option>
<option value="Switch">Switch</option>
<option value="Storage Array">Storage Array</option>
<option value="CDU">CDU</option>
<option value="Sensor">Sensor</option>
<option value="Physical Infrastructure">Physical Infrastructure</option>
</select>

<label>No. of Power Supplies:</label>
<input type="text" name="PSCount" id="PSCount" required>

<label>No. of Ports</label>
<input type="text" name="NumPorts" id="NumPorts">

<label>Chassis Slots (Front)</label>
<input type="text" name="ChassisSlots" id="ChassisSlots">

<label>Chassis Slots (Rear)</label>
<input type="text" name="RearChassisSlots" id="RearChassisSlots">

<label>SNMP Version</label>
<input type="text" name="SNMPVersion" id="SNMPVersion">

<label>Submitted By:</label>
<input type="text" id="SubmittedBy" name="submittedby" disabled />

<button id="btnSave">Approve</button>
<button id="btnDelete">Delete</button>
</div>

<div class="rightArea">
<div id="CDUDetails">
</div>
<div id="SensorDetails">
</div>
<div id="PowerPorts">
</div>
<div id="TemplatePorts">
</div>
<div id="Slots">
</div>

</div>
</form>

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

$.extend(FormSerializer.patterns, {
  validate: /^[a-z][a-z0-9_]*(?:\.[a-z0-9_]+)*(?:\[\])?$/i
});

$(document).ready( refreshList() );
$(document).ready( function() {
	$.ajax({
		type: 'GET',
		url: 'https://repository.opendcim.org/api/manufacturer',
		dataType: 'json',
		success: function(data) {
			if(data.errorcode==200 && !data.error) {
				for( var i in data.manufacturers ) {
					var row = data.manufacturers[i];
					$('#ManufacturerID').append('<option value="'+row.ManufacturerID+'">'+row.Name+'</option>');
				}
			}
		}
	});

});

function refreshList() {
	$.ajax({
		type: 'GET',
		url: 'https://repository.opendcim.org/api/template/pending/',
		headers: {
			'APIKey':'<?= $u->APIKey; ?>',
			'UserID':'<?= $u->UserID; ?>'
		},
		dataType: "json",
		success: function(data) {
			// If there wasn't a server error continue
			if(data.errorcode==200 && !data.error) {
				// Update local record with data from queue
				$('#tmpList').html('');
				for( var i in data.templatequeue ) {
					var row = data.templatequeue[i];
					var a=$('<a>').data('identity',row.RequestID).attr('href','#').text(row.RequestID + ' - ' + row.Model);
					var li=$('<li>').append(a);
					$('#tmpList').append(li);

					a.on('click',function(e){
						findById($(this).data('identity'));
					});
				} 
			}
			$('form :input').val('');
			$('#TemplatePorts').html('');
			$('#PowerPorts').html('');
			$('#Slots').html('');
			$('#CDUDetails').html('');
			$('#SensorDetails').html('');
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			alert( "No pending templates at this time." );
		}
	});
};

function findById(id) {
	$.ajax({
		type: 'GET',
		url: 'https://repository.opendcim.org/api/template/pending/' + id,
		headers: {
			'APIKey':'<?= $u->APIKey; ?>',
			'UserID':'<?= $u->UserID; ?>'
		},
		dataType: "json",
		success: function(data) {
			var row = data.templatequeue[0];
			$('#btnDelete').show();
			$('#RequestID').val(row.RequestID);
			$('#TemplateID').val(row.TemplateID);
			$('#ManufacturerID').val(row.ManufacturerID);
			$('#Model').val(row.Model);
			$('#Height').val(row.Height);
			$('#Weight').val(row.Weight);
			$('#Wattage').val(row.Wattage);
			$('#DeviceType').val(row.DeviceType);
			$('#PSCount').val(row.PSCount);
			$('#NumPorts').val(row.NumPorts);
			$('#ChassisSlots').val(row.ChassisSlots);
			$('#RearChassisSlots').val(row.RearChassisSlots);
			$('#SNMPVersion').val(row.SNMPVersion);
			$('#SubmittedBy').val(row.SubmittedBy);
			$('#FrontPictureFile').val(row.FrontPictureFile);
			$('#RearPictureFile').val(row.RearPictureFile);
			if ( row.FrontPictureFile != "" ) {
				$('#frontpic').attr( "src", '/images/submitted/'+row.RequestID+'.'+row.FrontPictureFile );
			} else {
				$('#frontpic').attr( "src", "" );
			}
			if ( row.RearPictureFile != "" ) {
				$('#rearpic').attr( "src", '/images/submitted/'+row.RequestID+'.'+row.RearPictureFile );
			} else {
				$('#rearpic').attr( "src", "" );
			}

			if ( row.DeviceType == "CDU" ) {
				var ct = row.cdutemplate;
				var tmphtml;
				tmphtml = "<h3>CDU Template Details</h3>\n";

				tmphtml += "<input type='hidden' value='"+ct.TemplateID+"' name='cdutemplate.TemplateID'>\n";
				tmphtml += "<input type='hidden' value='"+ct.RequestID+"' name='cdutemplate.RequestID'>\n";
				tmphtml += "Managed <input type='text' value='"+ct.Managed+"' name='cdutemplate.Managed'>\n";
				tmphtml += "ATS <input type='text' value='"+ct.ATS+"' name='cdutemplate.ATS'>\n";
				tmphtml += "VersionOID <input type='text' value='"+ct.VersionOID+"' name='cdutemplate.VersionOID'>\n";
				tmphtml += "Multiplier <input type='text' value='"+ct.Multiplier+"' name='cdutemplate.Multiplier'>\n";
				tmphtml += "OID1 <input type='text' value='"+ct.OID1+"' name='cdutemplate.OID1'>\n";
				tmphtml += "OID2 <input type='text' value='"+ct.OID2+"' name='cdutemplate.OID2'>\n";
				tmphtml += "OID3 <input type='text' value='"+ct.OID3+"' name='cdutemplate.OID3'>\n";
				tmphtml += "ATSStatusOID <input type='text' value='"+ct.ATSStatusOID+"' name='cdutemplate.ATSStatusOID'>\n";
				tmphtml += "ATSDesiredResult <input type='text' value='"+ct.ATSDesiredResult+"' name='cdutemplate.ATSDesiredResult'>\n";
				tmphtml += "ProcessingProfile <input type='text' value='"+ct.ProcessingProfile+"' name='cdutemplate.ProcessingProfile'>\n";
				tmphtml += "Voltage <input type='text' value='"+ct.Voltage+"' name='cdutemplate.Voltage'>\n";
				tmphtml += "Amperage <input type='text' value='"+ct.Amperage+"' name='cdutemplate.Amperage'>\n";

				$('#CDUDetails').html( tmphtml );
			}

			if ( row.DeviceType == "Sensor" ) {
				var st = row.sensortemplate;
				var tmphtml;

				tmphtml = "<h3>Sensor Template Details</h3>\n";
				tmphtml += "<input type='hidden' value='"+st.TemplateID+"' name='sensortemplate.TemplateID'>\n";
				tmphtml += "<input type='hidden' value='"+st.RequestID+"' name='sensortemplate.RequestID'>\n";
				tmphtml += "Temp OID <input type='text' value='"+st.TemperatureOID+"' name='sensortemplate.TemperatureOID'>\n";
				tmphtml += "Humidity OID <input type='text' value='"+st.HumidityOID+"' name='sensortemplate.HumidityOID'>\n";
				tmphtml += "Temp Multiplier <input type='text' value='"+st.TempMultiplier+"' name='sensortemplate.TempMultiplier'>\n";
				tmphtml += "Humidity Multiplier <input type='text' value='"+st.HumidityMultiplier+"' name='sensortemplate.HumidityMultiplier'>\n";
				tmphtml += "mUnits <input type='text' value='"+st.mUnits+"' name='sensortemplate.mUnits'>\n";

				$('#SensorDetails').html( tmphtml );
			}

			if ( Array.isArray( row.powerports ) ) {
				var pp = row.powerports;
				var tmphtml;
				tmphtml = "<h3>Power Ports</h3>\n";

				for ( var i in pp ) {
					tmphtml += "<input type='hidden' value='"+pp[i].RequestID+"' name='powerports."+i+".RequestID'>\n";
					tmphtml += "<input type='hidden' value='"+pp[i].TemplateID+"' name='powerports."+i+".TemplateID'>\n";
					tmphtml += "<input type='hidden' value='"+pp[i].PortNumber+"' name='powerports."+i+".PortNumber'>\n";
					tmphtml += pp[i].PortNumber + " <input type='text' value='"+pp[i].Label+"' name='powerports."+i+".Label'><br>\n";
				}

				$('#PowerPorts').html( tmphtml );
			}

			if ( Array.isArray( row.ports ) ) {
				var p = row.ports;
				var tmphtml;
				tmphtml = "<h3>Ports</h3>\n";

				for ( var i in p ) {
					tmphtml += "<input type='hidden' value='"+p[i].RequestID+"' name='ports."+i+".RequestID'>\n";
					tmphtml += "<input type='hidden' value='"+p[i].TemplateID+"' name='ports."+i+".TemplateID'>\n";
					tmphtml += "<input type='hidden' value='"+p[i].PortNumber+"' name='ports."+i+".PortNumber'>\n";
					tmphtml += p[i].PortNumber + " <input type='text' value='"+p[i].Label+"' name='ports."+i+".Label'><br>\n";
				}

				$('#TemplatePorts').html( tmphtml );
			}

			if ( Array.isArray( row.slots ) ) {
				var s = row.slots;
				var tmphtml;
				tmphtml = "<h3>Chassis Slots</h3>\n";

				for ( var i in s ) {
					tmphtml += "<div>\n";
					tmphtml += "<input type='hidden' value='"+s[i].RequestID+"' name='slots."+i+".RequestID'>\n";
					tmphtml += "<input type='hidden' value='"+s[i].TemplateID+"' name='slots."+i+".TemplateID'>\n";
					tmphtml += "Back <input type='text' value='"+s[i].BackSide+"' size='3' name='slots."+i+".BackSide'>\n";
					tmphtml += "Slot <input type='text' value='"+s[i].Position+"' size='3' name='slots."+i+".Position'>\n";
					tmphtml += "X <input type='text' value='"+s[i].X+"' size='5' name='slots."+i+".X'>\n";
					tmphtml += "Y <input type='text' value='"+s[i].Y+"' size='5' name='slots."+i+".Y'>\n";
					tmphtml += "W <input type='text' value='"+s[i].W+"' size='4' name='slots."+i+".W'>\n";
					tmphtml += "H <input type='text' value='"+s[i].H+"' size='4' name='slots."+i+".H'>\n";
					tmphtml += "</div>\n";
				}

				$('#Slots').html( tmphtml );
			}
		}
	});
}

function approveRequest() {
	$.ajax({
		type: 'POST',
		contentType: 'application/json',
		url: 'https://repository.opendcim.org/api/template/approve',
		dataType: "json",
		headers: {
			'APIKey':'<?= $u->APIKey; ?>',
			'UserID':'<?= $u->UserID; ?>'
		},
		data: $('#tmpForm').serializeJSON(),
		success: function ( data, textStatus, jqXHR ) {
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
		url: 'https://repository.opendcim.org/api/template/pending/delete/'+$('#RequestID').val(),
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
