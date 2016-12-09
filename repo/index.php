<?php
	include( "db.inc.php" );

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
<!doctype html>
<html>
<head>
<title>openDCIM Template Repository</title>
<script src="scripts/jquery-1.10.1.min.js"></script>
<script src="scripts/jquery-ui.min.js"></script>
<link rel="stylesheet" href="css/repo.css" type="text/css">
<link rel="stylesheet" href="css/jquery-ui.min.css" type="text/css">
<link rel="stylesheet" href="css/jquery-ui.structure.min.css" type="text/css">
<link rel="stylesheet" href="css/jquery-ui.theme.min.css" type="text/css">

<script type="text/javascript">
$(document).ready(function(){ 
	// Limit the height
	$('div.left').height($(window).outerHeight() - $('#header').outerHeight() - $('#footer').outerHeight());

	// Wait for the logo to load before adusting the size of the title
	$('#logo img').on('load',function(e){
		$('#title').css({'padding-left':$('#logo').outerWidth()+'px','padding-right':$('#stats').outerWidth()+'px'});
	});

	// Load data
	refreshList();
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

function ShowFullDevice(e){
	var template=$(e.currentTarget).data('template');
	var containerdiv=$('<div>');
	var left=$('<div>').addClass('left');
	var right=$('<div>').addClass('right');
	var power=$('<div>').addClass('power');
	var ports=$('<div>').addClass('ports');
	left.append($('<p>').text('Model: '+template['Model']));
	left.append($('<p>').text('Height: '+template['Height']));
	left.append($('<p>').text('Weight: '+template['Weight']));
	left.append($('<p>').text('Wattage: '+template['Wattage']));
	left.append($('<p>').text('DeviceType: '+template['DeviceType']));
	left.append($('<p>').text('PSCount: '+template['PSCount']));
	left.append($('<p>').text('NumPorts: '+template['NumPorts']));
	left.append($('<p>').text('ChassisSlots: '+template['ChassisSlots']));
	left.append($('<p>').text('RearChassisSlots: '+template['RearChassisSlots']));
	left.append($('<p>').text('SNMPVersion: '+template['SNMPVersion']));
	left.append($('<p>').text('LastModified: '+template['LastModified']));

	var frontimage=$('<div>').addClass('image');	
	var rearimage=$('<div>').addClass('image');	
	frontimage.append($('<img>',{'src':template['FrontPictureFile'],'width':'400px','alt':'Front'}).on('load',slots));
	rearimage.append($('<img>',{'src':template['RearPictureFile'],'width':'400px','alt':'Rear'}).on('load',slots));

	// called on load of the image so we can use the image dimensions
	function slots(){
		var ratio=this.width/this.naturalWidth;
		var template=$(e.currentTarget).data('template');

		// yeah this is dumb but +4 lines to reuse the function = fuck yeah
		var slots=(this.alt=='Front')?"ChassisSlots":"RearChassisSlots";
		var filename=(this.alt=='Front')?"FrontPictureFile":"RearPictureFile";
		var imgobj=(this.alt=='Front')?frontimage:rearimage;
		var stupidchk=(this.alt=='Front')?0:1;

		// generate slot display
		if(template[slots]>0 && template[filename]!=''){
			for(var i in template['slots']){
				if(template['slots'][i].BackSide!=(this.alt=='Front')){
					$('<div>').css({
						'left':(template['slots'][i].X*ratio)+'px',
						'top':(template['slots'][i].Y*ratio)+'px',
						'width':(template['slots'][i].W*ratio)+'px',
						'height':(template['slots'][i].H*ratio)+'px',
						'position':'absolute'
					}).append($('<span>').text(template['slots'][i].Position)).appendTo(imgobj);
				}
			}
		}
	}

	// Display data ports config
	var tbl_ports=$('<table>').appendTo(ports);
	for(var p in template['ports']){
		var row=$('<tr>');
		$('<td>').text(template['ports'][p].PortNumber).appendTo(row);
		$('<td>').text(template['ports'][p].Label).appendTo(row);
		row.appendTo(tbl_ports);
	}
	// if we added ports to the table then prepend a header row
	if(tbl_ports.children().length){
		var row=$('<tr>');
		$('<th>').text('Port').appendTo(row);
		$('<th>').text('Label').appendTo(row);
		row.prependTo(tbl_ports);
		$('<caption>').prop('align','top').text('Data Ports').appendTo(tbl_ports);
	}

	// Display power ports config
	var tbl_pports=$('<table>').appendTo(power);
	for(var p in template['powerports']){
		var row=$('<tr>');
		$('<td>').text(template['powerports'][p].PortNumber).appendTo(row);
		$('<td>').text(template['powerports'][p].Label).appendTo(row);
		row.appendTo(tbl_pports);
	}
	// if we added ports to the table then prepend a header row
	if(tbl_pports.children().length){
		var row=$('<tr>');
		$('<th>').text('Port').appendTo(row);
		$('<th>').text('Label').appendTo(row);
		row.prependTo(tbl_pports);
		$('<caption>').prop('align','top').text('Power Ports').appendTo(tbl_pports);
	}

	// put everything in the divs
	right.append(frontimage).append(rearimage);
	containerdiv.append(left).append(right).append(ports).append(power);

	// fire the dialog off
	containerdiv.dialog({width: 850});
}

function refreshList() {
        $.ajax({
                type: 'GET',
                url: 'https://repository.opendcim.org/api/manufacturer',
                dataType: "json",
                success: function(data) {
                        // If there wasn't a server error continue
                        if(data.errorcode==200 && !data.error) {
                                // Update local record with data from queue
                                $('#mfgList').html('');
                                for( var i in data.manufacturers ) {
                                        var row = data.manufacturers[i];
                                        var a=$('<a>').data('identity',row.ManufacturerID).attr('href','#').text(row.ManufacturerID + ' - ' + row.Name);
                                        var li=$('<li>').append(a);
                                        $('#mfgList').append(li);

                                        a.on('click',function(e){
						$('a.selected').removeClass('selected');
                                                findById($(this).data('identity'));
						$(e.currentTarget).addClass('selected');
                                        });
                                }
                        }
                }
        });
}

function findById(mfgID) {
	$.ajax({
		type: 'GET',
		url: 'https://repository.opendcim.org/api/template/bymanufacturer/'+mfgID,
		dataType: "json",
		success: function(data) {
			if(data.errorcode==200 && !data.error) {
				$('#tmpList').html('');
				// check for emply list and tell the user
				if(data.templates==''){
					var vaultboy=$('<img>',{'src':'vault_boy_search.png','title':'Nothing Found','alt':'Nothing Found'});
					var errormsg=$('<p>').text('No devices found for id: '+mfgID);
					$('#tmpList').append($('<li>').append(errormsg,vaultboy));
				}else{
					for ( var i in data.templates ) {
						var row = data.templates[i];
						var li = $('<li>').html('Model: '+row.Model+'<br>').data('template',row).on('click',ShowFullDevice);
						if(row.FrontPictureFile!=''){
							var img = $('<img>',{'src':row.FrontPictureFile,'height':'30px'});
							li.append(img);
						}
						$('#tmpList').append(li);
					}
				}
			}
		}
	});
}
</script>
</head>
<body>
	<div id="header">
		<div id="logo">
			<img src="opendcim.png">
		</div>
		<div id="title">
			<h1>openDCIM Device Template Repository</h1>
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
				<li><a href="admin/pending_mfg.php">Manufacturers</a></li>
				<li><a href="admin/pending_tmp.php">Templates</a></li>
			</ul>
		</div>
	</div>
	<div id="content">
		<div class="left">
		<h3>Manufacturers</h3>
		<ul id="mfgList"></ul>
		</div><!-- end left pane -->
		<div class="right">
		<h3>Templates</h3>
		<ul id="tmpList"></ul>
		</div><!-- end right pane -->
	</div><!-- end content div -->
	<div id="footer">
		<p>This respository is public information, licensed under the GNU Public License v3.  If you would like to pull the data for your own application, please see the <a href="http://wiki.opendcim.org">wiki</a> for details on the API format.</p>
	</div>
</body>
</html>
