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
<script src="/scripts/jquery-ui.min.js"></script>
<script src="/scripts/jquery.serialize-object.min.js"></script>
<script src="/scripts/jquery.imgareaselect.pack.js"></script>
<link rel="stylesheet" href="/css/repo.css" type="text/css">
<link rel="stylesheet" href="/css/jquery-ui.min.css" type="text/css">
<link rel="stylesheet" href="/css/imgareaselect-default.css" type="text/css">
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
<div class="imgcontainer"><img id="frontpic" width="300"/></div>

<label>Rear Picture File</label>
<input type="text" name="RearPictureFile" id="RearPictureFile" size=40>
<div class="imgcontainer"><img id="rearpic" width="300"/></div>

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
<!-- hiding the dialog contents here -->
<div class="hide" id="hidden">
	<div id="poopupLeft"></div>
	<div id="poopupRight"></div>
</div>

<script type="text/javascript">
// keep enter from approving things by accident
$(':input').keypress(function(e){
	if(e.keyCode==10 || e.keyCode==13){
		e.preventDefault();
	}
});
var wto;

// add a listening to the slots blank
$('#ChassisSlots,#RearChassisSlots').on('focus',function(e){
	$(this).data('pv',this.value);
	console.log('previous value: '+$(this).data('pv'));
}).on('change keyup',function(e){
	var input=$(this);
	var fb=(this.id=='ChassisSlots')?0:1;
	var face=(this.id=='ChassisSlots')?'Front':'Rear';
	clearTimeout(wto);
	wto = setTimeout(function() {
		var rows=$('#Slots > .table > div ~ div');
		var frows=$("#Slots > .table > div ~ div."+face);
		if(frows.length == input.val()){
			// new value is equal to the number of rows we have showing, do nothing
		}else if(frows.length < input.val()){
			// new value is higher than the number of rows we have showing, add some
			for(i=0;i < input.val() - frows.length; i++){
				var slot=parseInt(rows.length) + i;
				$('#Slots > .table').append(addRow(slot,{'BackSide':fb}));
			}
		}else{
			// new value is lower than the number of rows we have showing, drop some
			for(i=frows.length;i > input.val(); i--){
				$("#Slots > .table > div ~ div."+face).last().remove();
			}
		}
		drawSlots();
	}, 750);
});

// adds .naturalWidth() and .naturalHeight() methods to jQuery
// for retreaving a normalized naturalWidth and naturalHeight.
var props=['Width', 'Height'], prop;

while (prop = props.pop()) {
	(function (natural, prop) {
		$.fn[natural] = (natural in new Image()) ?
		function () {
		return this[0][natural];
		} :
		function () {
		var
		node = this[0],
		img,
		value;

		if (node.tagName.toLowerCase() === 'img') {
			img = new Image();
			img.src = node.src,
			value = img[prop];
		}
		return value;
		};
	}('natural' + prop, prop.toLowerCase()));
}

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
			resetPage();
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			alert( "No pending templates at this time." );
		}
	});
};

function resetPage(){
	$('form :input').val('');
	$('#TemplatePorts').html('');
	$('#PowerPorts').html('');
	$('#Slots').html('');
	$('#CDUDetails').html('');
	$('#SensorDetails').html('');
}

function editBtn(e){
	if(this.form==null){
		editSlot(e);
	}else{
		slotEditor(e);
	}
}

function editSlot(e){
	var container=$('#poopupLeft');
	var pic=$('#poopupLeft > img');

	// make easily addressable variables out of the form inputs
	var row=e.currentTarget.parentNode.parentNode;
	var x=$(row.children[2].children[0]);
	var y=$(row.children[3].children[0]);
	var w=$(row.children[4].children[0]);
	var h=$(row.children[5].children[0]);

	// This will take into account the size of the image on the screen
	// vs the actual size of the image
	var zoom=pic.width()/pic.naturalWidth();

	pic.imgAreaSelect({
		x1: parseInt((x.val())*zoom),
		x2: (parseInt(x.val()) + parseInt(w.val()))*zoom,
		y1: parseInt((y.val())*zoom),
		y2: (parseInt(y.val()) + parseInt(h.val()))*zoom,
		parent: container,
		handles: true,
		show: true,
		onSelectEnd: function (img, selection) {
			x.val(parseInt(selection.x1/zoom));
			y.val(parseInt(selection.y1/zoom));
			w.val(parseInt(selection.width/zoom));
			h.val(parseInt(selection.height/zoom));
		}
	});
}

function slotEditor(e){
	// front =0, back =1
	var fb=e.currentTarget.parentNode.parentNode.children[0].children[0].value;
	var pic=(fb==0)?$('#frontpic').clone():$('#rearpic').clone();

	
	// make our image bigger
	pic.width(400);

	// Hide the lines of the table that aren't for the face that they just
	// clicked edit on.
	$('#Slots > .table > div ~ div').each(function(){
		if(this.children[0].children[0].value!=fb){
			$(this).hide();
		}
	});

	$('#poopupLeft').html(pic);
	$('#poopupRight').append($('#Slots > .table'));

	var poopup=$('<div>');
	poopup.append($('#poopupLeft')).append($('#poopupRight'));
	poopup.dialog({
		minWidth: 1000,
		beforeClose: function( event, ui ) {
			// put the coords table back into the form
			$('#Slots').append($('#poopupRight > .table'));
			// unhide any rows we may have hidden by clicking edit
			$('#Slots > .table > div ~ div').show();
			// update the slots overlay
			drawSlots();
		}
	});
}

var sto;
function addRow(slot,obj){
	var row=$('<div>').data('change',false);
	var fb=(obj.BackSide=='0')?'Front':'Rear';
	row.addClass(fb);
	var input=$('<input>').attr({'size':'4','type':'number','min':'0'}).on('change',function(e){
		clearTimeout(sto);
		sto = setTimeout(function() {
			drawSlots();
		}, 750);
	});
	var b=input.clone().attr({'name':'slots.'+slot+'.BackSide','min':parseInt(obj.BackSide),'max':parseInt(obj.BackSide),'value':obj.BackSide}).val(obj.BackSide);
	var p=input.clone().attr({'name':'slots.'+slot+'.Position','min':parseInt(slot)+1,'max':parseInt(slot)+1}).val(slot+1);
	var x=input.clone(true).attr('name','slots.'+slot+'.X');
	var y=input.clone(true).attr('name','slots.'+slot+'.Y');
	var w=input.clone(true).attr('name','slots.'+slot+'.W');
	var h=input.clone(true).attr('name','slots.'+slot+'.H');
	var edit=$('<button>').attr('type','button').append('Edit');
	edit.click(editBtn);
	row.append($('<div>').append(b)).
		append($('<div>').append(p)).
		append($('<div>').append(x)).
		append($('<div>').append(y)).
		append($('<div>').append(w)).
		append($('<div>').append(h)).
		append($('<div>').append(edit));

	// If a slot has been defined already set the values
	// This is will value changes to the table over the values from the json if the button is hit a second time
	var rrow=$('input[name="slots.'+slot+'.X"]').parent('div').parent('div');
	if((typeof s[slot]!='undefined') || ($('input[name="slots.'+slot+'.X"]').val()!='undefined' && rrow.data('change'))){
		var bval=$('input[name="slots.'+slot+'.BackSide"]').val();
		var pval=$('input[name="slots.'+slot+'.Position"]').val();
		var xval=$('input[name="slots.'+slot+'.X"]').val();
		var yval=$('input[name="slots.'+slot+'.Y"]').val();
		var wval=$('input[name="slots.'+slot+'.W"]').val();
		var hval=$('input[name="slots.'+slot+'.H"]').val();
		b.val(((xval!='undefined' && rrow.data('change'))?xval:s[slot].BackSide));
		p.val(((xval!='undefined' && rrow.data('change'))?xval:s[slot].Position));
		x.val(((xval!='undefined' && rrow.data('change'))?xval:s[slot].X));
		y.val(((yval!='undefined' && rrow.data('change'))?yval:s[slot].Y));
		w.val(((wval!='undefined' && rrow.data('change'))?wval:s[slot].W));
		h.val(((hval!='undefined' && rrow.data('change'))?hval:s[slot].H));
	}

	// Update change status on the row assholes clicking buttons multiple times
	row.data('change',rrow.data('change'));

	return row;
}
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
			resetPage();
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
				$('#frontpic').attr( "src", '/images/submitted/'+row.RequestID+'.'+row.FrontPictureFile ).on('load',drawSlots);
			} else {
				$('#frontpic').attr( "src", "" );
			}
			if ( row.RearPictureFile != "" ) {
				$('#rearpic').attr( "src", '/images/submitted/'+row.RequestID+'.'+row.RearPictureFile ).on('load',drawSlots);
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
				// this is s global var because the function needs access to the data
				s = row.slots;

				var heading = $('<h3>').text("Chassis Slots");
				var table=$('<div>').addClass('table');
				table.append($('<div>').
					append($('<div>').text('Back')).
					append($('<div>').text('Slot')).
					append($('<div>').text('X')).
					append($('<div>').text('Y')).
					append($('<div>').text('W')).
					append($('<div>').text('H')));


				// Use the stupidly long complicated function to add rows to the table
				for ( var i in s ) {
					table.append(addRow(i,s[i]));
				}

				// Render the table out to html and show it to some jackhole
				$('#Slots').html("").append(heading).append(table);
			}
		}
	});
}

function drawSlots(){
	var b, color, el, g, i, number, point, points, r, _i, _ref;

	number = parseInt($('#Slots > .table > div ~ div').length, 10);
	points = new Points(number);
	point = null;
	bordercolors = [];
	for (i = _i = 1; 1 <= number ? _i <= number : _i >= number; i = 1 <= number ? ++_i : --_i) {
		point = points.pick(point);
		_ref = RYB.rgb.apply(RYB, point).map(function(x) {
			return Math.floor(255 * x);
		}), r = _ref[0], g = _ref[1], b = _ref[2];
		color = "rgb(" + r + ", " + g + ", " + b + ")";
		bordercolors[i]=color;
	}
	// the first bordercolor element is null so pop it off
	bordercolors.shift();

	// clear out anything existing
	$(".imgcontainer > div").remove();

	$('#Slots .table > div ~ div > div:nth-child(2)').each(function(){
		var row=this.parentNode;
		// figure out the slot we're dealing with
		var slotnum=parseInt(this.children[0].value)-1;

		// define the image and div we're manipulating
		var img=($('input[name="slots.'+slotnum+'.BackSide"]').val()=='0')?$('#frontpic'):$('#rearpic');
		var imgDiv=img.parent('.imgcontainer');

		// amount to multiply the coordinate by to scale it to our image
		var ratio=parseInt(img.width())/parseInt(img.naturalWidth());

		// draw the slot
		$('<div>').css({
			'left':($('input[name="slots.'+slotnum+'.X"]').val()*ratio)+'px',
			'top':($('input[name="slots.'+slotnum+'.Y"]').val()*ratio)+'px',
			// 6px to account for fat borders on slots
			'width':($('input[name="slots.'+slotnum+'.W"]').val()*ratio)-6+'px',
			'height':($('input[name="slots.'+slotnum+'.H"]').val()*ratio)-6+'px',
			'border-color':bordercolors[slotnum],
			'position':'absolute'
		}).append($('<span>').text(parseInt(slotnum)+1)).appendTo(imgDiv);

		// color the row to match the slot.
		$(row).css({'background-color':bordercolors[slotnum]});
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

// Functions for generating random number of colors.
var Points, RYB, display, generateColors, numberColors,
  __hasProp = {}.hasOwnProperty,
  __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor; child.__super__ = parent.prototype; return child; };

RYB = {
  white: [1, 1, 1],
  red: [1, 0, 0],
  yellow: [1, 1, 0],
  blue: [0.163, 0.373, 0.6],
  violet: [0.5, 0, 0.5],
  green: [0, 0.66, 0.2],
  orange: [1, 0.5, 0],
  black: [0.2, 0.094, 0.0],
  rgb: function(r, y, b) {
    var i, _i, _results;
    _results = [];
    for (i = _i = 0; _i <= 2; i = ++_i) {
      _results.push(RYB.white[i] * (1 - r) * (1 - b) * (1 - y) + RYB.red[i] * r * (1 - b) * (1 - y) + RYB.blue[i] * (1 - r) * b * (1 - y) + RYB.violet[i] * r * b * (1 - y) + RYB.yellow[i] * (1 - r) * (1 - b) * y + RYB.orange[i] * r * (1 - b) * y + RYB.green[i] * (1 - r) * b * y + RYB.black[i] * r * b * y);
    }
    return _results;
  }
};

Points = (function(_super) {

  __extends(Points, _super);

  Points.name = 'Points';

  function Points(number) {
    var base, n, _i, _ref;
    base = Math.ceil(Math.pow(number, 1 / 3));
    for (n = _i = 0, _ref = Math.pow(base, 3); 0 <= _ref ? _i < _ref : _i > _ref; n = 0 <= _ref ? ++_i : --_i) {
      this.push([Math.floor(n / (base * base)) / (base - 1), Math.floor(n / base % base) / (base - 1), Math.floor(n % base) / (base - 1)]);
    }
    this.picked = null;
    this.plength = 0;
  }

  Points.prototype.distance = function(p1) {
    var _this = this;
    return [0, 1, 2].map(function(i) {
      return Math.pow(p1[i] - _this.picked[i], 2);
    }).reduce(function(a, b) {
      return a + b;
    });
  };

  Points.prototype.pick = function() {
    var index, pick, _, _ref,
      _this = this;
    if (this.picked == null) {
      pick = this.picked = this.shift();
      this.plength = 1;
    } else {
      _ref = this.reduce(function(_arg, p2, i2) {
        var d1, d2, i1;
        i1 = _arg[0], d1 = _arg[1];
        d2 = _this.distance(p2);
        if (d1 < d2) {
          return [i2, d2];
        } else {
          return [i1, d1];
        }
      }, [0, this.distance(this[0])]), index = _ref[0], _ = _ref[1];
      pick = this.splice(index, 1)[0];
      this.picked = [0, 1, 2].map(function(i) {
        return (_this.plength * _this.picked[i] + pick[i]) / (_this.plength + 1);
      });
      this.plength++;
    }
    return pick;
  };

  return Points;

})(Array);

$(document).ready( function() {
	refreshList();
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


</script>
</body>
</html>
