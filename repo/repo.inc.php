<?php

// Generic text sanitization function
if(!function_exists("sanitize")){
	function sanitize($string,$stripall=true){
		// Trim any leading or trailing whitespace
		$clean=trim($string);

		// Convert any special characters to their normal parts
		$clean=html_entity_decode($clean,ENT_COMPAT,"UTF-8");

		// By default strip all html
		$allowedtags=($stripall)?'':'<a><b><i><img><u><br>';

		// Strip out the shit we don't allow
		$clean=strip_tags($clean, $allowedtags);
		// If we decide to strip double quotes instead of encoding them uncomment the 
		//	next line
	//	$clean=($stripall)?str_replace('"','',$clean):$clean;
		// What is this gonna do ?
		$clean=filter_var($clean, FILTER_SANITIZE_SPECIAL_CHARS);

		// There shoudln't be anything left to escape but wtf do it anyway
		$clean=addslashes($clean);

		return $clean;
	}
}

class Manufacturers {
	var $ManufacturerID;
	var $Name;
	var $LastModified;

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}

	function getManufacturer($ManufacturerID=null) {
		$match=(!is_null($ManufacturerID))?" WHERE ManufacturerID=".intval($ManufacturerID):"";
		$st = $this->prepare( "select * from Manufacturers $match order by Name ASC" );
		$st->execute();

		$mfgList = array();
		$st->setFetchMode( PDO::FETCH_CLASS, "Manufacturers" );
		while ( $m = $st->fetch() ) {
			$mfgList[] = $m;
		}

		return $mfgList;
	}

	function updateManufacturer() {
		$st = $this->prepare( "update Manufacturers set Name=:Name where ManufacturerID=:ManufacturerID" );
		return $st->execute( array( ":Name"=>$this->Name, ":ManufacturerID"=>$this->ManufacturerID ) );
	}
}

class ManufacturersQueue {
	var $RequestID;
	var $ManufacturerID;
	var $Name;
	var $SubmittedBy;
	var $SubmissionDate;
	var $ApprovedBy;
	var $ApprovedDate;

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}

	function lastInsertId() {
		global $dbh;
		return $dbh->lastInsertId();
	}

	function queueManufacturer() {
		global $currUser;

		$this->Name = sanitize( $this->Name );
		$st = $this->prepare( "select * from Manufacturers where UCASE(Name)=UCASE(:Name)" );
		$st->execute( array( ":Name" => $this->Name ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "Manufacturers" );
		$row = $st->fetch();
		if ( @$row->ManufacturerID > 0 ) {
			error_log( "Table Manufacturers collision:  Name=>" . $this->Name );
			return false;
		}

		$st = $this->prepare( "insert into ManufacturersQueue set Name=:Name, SubmittedBy=:UserID, SubmissionDate=now()" );
		if ( ! $st->execute( array( ":Name" => $this->Name, ":UserID"=>$currUser->UserID ) ) ) {
			return null;
		}

		$this->RequestID = $this->lastInsertId();

		return $this->RequestID;
	}

	function viewStatus( $RequestID = null, $UserID = null ) {
		if ( isset( $RequestID ) ){
			$st = $this->prepare( "select * from ManufacturersQueue where RequestID=:RequestID" );
			$st->execute( array( ":RequestID"=>$RequestID ) );
		} else {
			$st = $this->prepare( "select * from ManufacturersQueue where ApprovedBy='' order by Name ASC, RequestID ASC" );
			$st->execute();
		}

		$mfgList = array();
		$st->setFetchMode( PDO::FETCH_CLASS, "ManufacturersQueue" );
		while ( $mfgRow = $st->fetch() ) {
			$mfgList[] = $mfgRow;
		}

		return $mfgList;
	}
	
	function approveRequest( $currUser ) {
		$st = $this->prepare( "select * from ManufacturersQueue where RequestID=:RequestID" );
		$st->execute( array( ":RequestID" => $this->RequestID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "ManufacturersQueue" );
		if ( $req = $st->fetch() ) {
			// If the ManufacturerID is set in the request, this is an update
			if ( $this->ManufacturerID > 0  ) {
				$st = $this->prepare( "update Manufacturers set Name=:Name, LastModified=now() where
					ManufacturerID=:ManufacturerID" );
				$st->execute( array( ":Name"=>$this->Name, ":ManufacturerID"=>$this->ManufacturerID ) );
			} else {
				$st = $this->prepare( "insert into Manufacturers set Name=:Name, LastModified=now()" );
				$st->execute( array( ":Name"=>$this->Name ) );
				$this->ManufacturerID=$this->lastInsertId();
			}

			$this->ApprovedBy = $currUser->UserID;
			
			$st = $this->prepare( "update ManufacturersQueue set ApprovedBy=:UserID,
				ManufacturerID=:ManufacturerID, ApprovedDate=now() where RequestID=:RequestID" );
			$st->execute( array( ":UserID"=>$currUser->UserID, 
				":ManufacturerID"=>$this->ManufacturerID, ":RequestID"=>$this->RequestID ) );
		} else {
			error_log( "Fetch failed for request=" . $this->RequestID );
		}
		
		return true;
	}
}

class DeviceTemplates {
	var $TemplateID;
	var $ManufacturerID;
	var $Model;
	var $Height;
	var $Weight;
	var $Wattage;
	var $DeviceType;
	var $PSCount;
	var $NumPorts;
	var $FrontPictureFile;
	var $RearPictureFile;
	var $ChassisSlots;
	var $RearChassisSlots;
	var $LastModified;

	function prepare( $sql ) {
			global $dbh;
			return $dbh->prepare( $sql );
	}

	function getDeviceTemplate( $TemplateID = null ) {
		if ( isset( $TemplateID ) ) {
			$st = $this->prepare( "select * from DeviceTemplates where TemplateID=:TemplateID order by ManufacturerID ASC, Model ASC" );
			$st->execute( array( ":TemplateID"=>$TemplateID ) );
		} else {
			$st = $this->prepare( "select * from DeviceTemplates order by ManufacturerID ASC, Model ASC" );
			$st->execute();
		}

		$templateList = array();
		$st->setFetchMode( PDO::FETCH_CLASS, "DeviceTemplates" );
		while ( $t = $st->fetch() ) {
			$templateList[] = $t;
		}

		return $templateList;
	}

	function getDeviceTemplateByMFG( $manufacturerid ) {
		$st = $this->prepare( "select * from DeviceTemplates where ManufacturerID=:ManufacturerID order by Model ASC" );
		$st->execute( array( ":ManufacturerID"=>$manufacturerid ) );

		$templateList = array();
		$st->setFetchMode( PDO::FETCH_CLASS, "DeviceTemplates" );
		while ( $t = $st->fetch() ) {
				$templateList[] = $t;
		}

		return $templateList;

	}
}

class DeviceTemplatesQueue {
	var $RequestID;
        var $TemplateID;
        var $ManufacturerID;
        var $Model;
        var $Height;
        var $Weight;
        var $Wattage;
        var $DeviceType;
        var $PSCount;
        var $NumPorts;
        var $Notes;
        var $FrontPictureFile;
        var $RearPictureFile;
        var $ChassisSlots;
        var $RearChassisSlots;
        var $SubmittedBy;
	var $SubmissionDate;
	var $ApprovedBy;
	var $ApprovedDate;

        function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
        }

	function lastInsertId() {
		global $dbh;
		return $dbh->lastInsertId();
	}

	function makeSafe() {
		$this->TemplateID = intval( $this->TemplateID );
		$this->ManufacturerID = intval( $this->ManufacturerID );
		$this->Model = sanitize( $this->Model );
		$this->Height = intval( $this->Height );
		$this->Weight = intval( $this->Weight );
		$this->Wattage = intval( $this->Wattage );
		$this->DeviceType = sanitize( $this->DeviceType );
		$this->PSCount = intval( $this->PSCount );
		$this->NumPorts = intval( $this->NumPorts );
		$this->Notes = sanitize( $this->Notes );
		$this->FrontPictureFile = sanitize( $this->FrontPictureFile );
		$this->RearPictureFile = sanitize( $this->RearPictureFile );
		$this->ChassisSlots = intval( $this->ChassisSlots );
		$this->RearChassisSlots = intval( $this->RearChassisSlots );
	}

	function viewStatus( $RequestID = null ) {
		global $currUser;

		// Behavior
		//	If the RequestID is set, pull a specific RequestID
		//	If not, see if the ManufacturerID is set - if so, pull all for that ManufacturerID, if authorized
		//	If that isn't set, pull everything authorized to view
                if ( isset( $RequestID ) ){
                        $st = $this->prepare( "select * from DeviceTemplatesQueue where RequestID=:RequestID" );
                        $st->execute( array( ":RequestID"=>$RequestID ) );
                } else {
			if ( ! $currUser->Administrator ) {
				$st = $this->prepare( "select * from DeviceTemplatesQueue where ApprovedBy='' and ManufacturerID in (select ManufacturerID from Moderators where UserID=:UserID) order by Model ASC, RequestID ASC" );
				$st->execute( array( ":UserID"=>$currUser->UserID ) );
			} else {
				$st = $this->prepare( "select * from DeviceTemplatesQueue where ApprovedBy='' order by Model ASC, RequestID ASC" );
				$st->execute();
			}
		}

                $tmpList = array();
                $st->setFetchMode( PDO::FETCH_CLASS, "DeviceTemplatesQueue" );
                while ( $tmpRow = $st->fetch() ) {
                        $tmpList[] = $tmpRow;
                }

                return $tmpList;
        }

	function deleteRequest( $RequestID ) {
		$sql = array( "delete from CDUTemplatesQueue where RequestID=:RequestID",
				"delete from ChassisSlotsQueue where RequestID=:RequestID",
				"delete from TemplatePortsQueue where RequestID=:RequestID",
				"delete from TemplatePowerPortsQueue where RequestID=:RequestID",
				"delete from DeviceTemplatesQueue where RequestID=:RequestID" );

		foreach ( $sql as $s ) {
			$st = $this->prepare( $s );
			$st->execute( array( ":RequestID"=>$RequestID ) );
		}

		array_map( "unlink", glob( "/home/dcim/repo/repo/images/submitted/$RequestID.*" ) );

		return true;
	}

	function approveRequest( $currUser ) {
		// Check for an existing Make & Model combination, and if so, turn this into an update
		$st = $this->prepare( "select TemplateID,count(*) as Total from DeviceTemplates where ManufacturerID=:ManufacturerID and ucase(Model)=ucase(:Model)" );
		$st->execute( array( ":ManufacturerID"=>$this->ManufacturerID, ":Model"=>$this->Model ) );
		$res = $st->fetch();

		if ( $res['Total'] > 0 ) {
			$this->TemplateID = $res['TemplateID'];
		}

		// Grab the original file names before we get started
                $st = $this->prepare( "select * from DeviceTemplatesQueue where RequestID=:RequestID" );
                $st->execute( array( ":RequestID" => $this->RequestID ) );
                $st->setFetchMode( PDO::FETCH_CLASS, "DeviceTemplatesQueue" );
                if ( $req = $st->fetch() ) {
			$srcFront = sprintf( "/home/dcim/repo/repo/images/submitted/%d.%s", $this->RequestID, $req->FrontPictureFile );
			$srcRear = sprintf( "/home/dcim/repo/repo/images/submitted/%d.%s", $this->RequestID, $req->RearPictureFile );
                        // If the TemplateID is set in the request, this is an update
                        if ( $this->TemplateID > 0  ) {
                                $st = $this->prepare( "update DeviceTemplates set ManufacturerID=:ManufacturerID,
					Model=:Model, Height=:Height, Weight=:Weight, Wattage=:Wattage, DeviceType=:DeviceType,
					PSCount=:PSCount, NumPorts=:NumPorts, FrontPictureFile=:FrontPictureFile,
					RearPictureFile=:RearPictureFile, ChassisSlots=:ChassisSlots, RearChassisSlots=:RearChassisSlots,
					LastModified=now() where TemplateID=:TemplateID" );
                                $st->execute( array( ":ManufacturerID"=>$this->ManufacturerID,
					":Model"=>$this->Model,
					":Height"=>$this->Height,
					":Weight"=>$this->Weight,
					":Wattage"=>$this->Wattage,
					":DeviceType"=>$this->DeviceType,
					":PSCount"=>$this->PSCount,
					":NumPorts"=>$this->NumPorts,
					":FrontPictureFile"=>$this->FrontPictureFile,
					":RearPictureFile"=>$this->RearPictureFile,
					":ChassisSlots"=>$this->ChassisSlots,
					":RearChassisSlots"=>$this->RearChassisSlots,
					":TemplateID"=>$this->TemplateID ) );
                        } else {
                                $st = $this->prepare( "insert into DeviceTemplates set ManufacturerID=:ManufacturerID,
					Model=:Model, Height=:Height, Weight=:Weight, Wattage=:Wattage, DeviceType=:DeviceType,
					PSCount=:PSCount, NumPorts=:NumPorts, FrontPictureFile=:FrontPictureFile,
					RearPictureFile=:RearPictureFile, ChassisSlots=:ChassisSlots, RearChassisSlots=:RearChassisSlots,
					LastModified=now()" );
                                $st->execute( array( ":ManufacturerID"=>$this->ManufacturerID,
					":Model"=>$this->Model,
					":Height"=>$this->Height,
					":Weight"=>$this->Weight,
					":Wattage"=>$this->Wattage,
					":DeviceType"=>$this->DeviceType,
					":PSCount"=>$this->PSCount,
					":NumPorts"=>$this->NumPorts,
					":FrontPictureFile"=>$this->FrontPictureFile,
					":RearPictureFile"=>$this->RearPictureFile,
					":ChassisSlots"=>$this->ChassisSlots,
					":RearChassisSlots"=>$this->RearChassisSlots ) );
                                $this->TemplateID=$this->lastInsertId();

				// We just assigned a TemplateID, so propagate that to all the other tables depending on it
				$tables = array( "TemplatePowerPortsQueue", "TemplatePortsQueue", "SlotsQueue", "CDUTemplatesQueue", "SensorTemlatesQueue" );
				foreach ( $tables as $table ) {
					$st = $this->prepare( "update $table set TemplateID=:TemplateID where RequestID=:RequestID" );
					$st->execute( array( ":TemplateID"=>$this->TemplateID, ":RequestID"=>$this->RequestID ) );
				}
             		}

			// Remove any previous images for this TemplateID
			$targetPattern = "images/approved/" . $this->TemplateID . "*";
			array_map( "unlink", glob( $targetPattern ));


			$tgtFront = sprintf( "/home/dcim/repo/repo/images/approved/%d.%s", $this->TemplateID, $this->FrontPictureFile );
			$tgtRear = sprintf( "/home/dcim/repo/repo/images/approved/%d.%s", $this->TemplateID, $this->RearPictureFile );

			@rename( $srcFront, $tgtFront );
			@rename( $srcRear, $tgtRear );

	                $this->ApprovedBy = $currUser->UserID;

       	                $st = $this->prepare( "update DeviceTemplatesQueue set ApprovedBy=:UserID,
       	                        TemplateID=:TemplateID, ApprovedDate=now() where RequestID=:RequestID" );
       	                $st->execute( array( ":UserID"=>$currUser->UserID,
       	                        ":TemplateID"=>$this->TemplateID, ":RequestID"=>$this->RequestID ) );
       	        } else {
       	                error_log( "Fetch failed for request=" . $this->RequestID );
       	        }

		return true;
	}

	function queueDeviceTemplate() {
		$this->makeSafe();

		// Make sure that we don't violate unique keys
		// If the TemplateID > 0, this is an update to a record
		if ( $this->TemplateID == 0 ) {
			$st = $this->prepare( "select TemplateID, count(*) as Total from DeviceTemplates where ManufacturerID=:ManufacturerID and ucase(Model)=ucase(:Model)" );
			$st->execute( array( ":ManufacturerID"=>$this->ManufacturerID, ":Model"=>$this->Model ) );
			$row = $st->fetch();

			if ( $row["Total"] > 0 ) {
				$this->TemplateID = $row["TemplateID"];
			}
		}

		// At this stage, there's no difference in the queueing other than the
		// fact that we add in the TemplateID for existing records.
		$st = $this->prepare( "insert into DeviceTemplatesQueue set TemplateID=:TemplateID,
			ManufacturerID=:ManufacturerID, Model=:Model, Height=:Height,
			Weight=:Weight, Wattage=:Wattage, DeviceType=:DeviceType,
			PSCount=:PSCount, NumPorts=:NumPorts,
			FrontPictureFile=:FrontPictureFile, RearPictureFile=:RearPictureFile,
			ChassisSlots=:ChassisSlots, RearChassisSlots=:RearChassisSlots,
			SubmittedBy=:SubmittedBy, SubmissionDate=now()");
		$st->execute( array( ":TemplateID"=>$this->TemplateID,
			":ManufacturerID"=>$this->ManufacturerID,
			":Model"=>$this->Model,
			":Height"=>$this->Height,
			":Weight"=>$this->Weight,
			":Wattage"=>$this->Wattage,
			":DeviceType"=>$this->DeviceType,
			":PSCount"=>$this->PSCount,
			":NumPorts"=>$this->NumPorts,
			":FrontPictureFile"=>$this->FrontPictureFile,
			":RearPictureFile"=>$this->RearPictureFile,
			":ChassisSlots"=>$this->ChassisSlots,
			":RearChassisSlots"=>$this->RearChassisSlots,
			":SubmittedBy"=>$this->SubmittedBy ) );

		$this->RequestID = $this->lastInsertId();
		return $this->RequestID;
	}
}

class CDUTemplatesQueue {
	var $RequestID;
	var $TemplateID;
	var $Managed;
	var $ATS;
	var $SNMPVersion;
	var $VersionOID;
	var $Multiplier;
	var $OID1;
	var $OID2;
	var $OID3;
	var $ATSStatusOID;
	var $ATSDesiredResult;
	var $ProcessingProfile;
	var $Voltage;
	var $Amperage;

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}

	function getTemplate( $RequestID ) {
		$st = $this->prepare( "select * from CDUTemplatesQueue where RequestID=:RequestID" );

		if ( ! $st->execute( array( ":RequestID"=>$RequestID ) ) ) {
			return false;
		}

		$st->setFetchMode( PDO::FETCH_CLASS, "CDUTemplatesQueue" );
		if ( $row = $st->fetch() ) {
			foreach ( $row as $prop=>$val ) {
				$this->$prop = $val;
			}
		}
	}

	function queueTemplate() {
		$st = $this->prepare( "insert into CDUTemplatesQueue set RequestID=:RequestID, TemplateID=:TemplateID,
			Managed=:Managed, ATS=:ATS, SNMPVersion=:SNMPVersion,
			VersionOID=:VersionOID, Multiplier=:Multiplier, OID1=:OID1, OID2=:OID2, OID3=:OID3, ATSStatusOID=:ATSStatusOID,
			ATSDesiredResult=:ATSDesiredResult, ProcessingProfile=:ProcessingProfile, Voltage=:Voltage, Amperage=:Amperage" );
		return $st->execute( array( ":RequestID"=>$this->RequestID,
			":TemplateID"=>$this->TemplateID,
			":Managed"=>$this->Managed,
			":ATS"=>$this->ATS,
			":SNMPVersion"=>$this->SNMPVersion,
			":VersionOID"=>$this->VersionOID,
			":Multiplier"=>$this->Multiplier,
			":OID1"=>$this->OID1,
			":OID2"=>$this->OID2,
			":OID3"=>$this->OID3,
			":ATSStatusOID"=>$this->ATSStatusOID,
			":ATSDesiredResult"=>$this->ATSDesiredResult,
			":ProcessingProfile"=>$this->ProcessingProfile,
			":Voltage"=>$this->Voltage,
			":Amperage"=>$this->Amperage ) );
	}

	function approveRequest() {
                $st = $this->prepare( "select count(*) as Total from CDUTemplates where TemplateID=:TemplateID" );
                $st->execute( array( ":TemplateID" => $this->TemplateID ) );
		$r = $st->fetch();
                if ( $r['Total'] > 0 ) {
                	$st = $this->prepare( "update CDUTemplates set 
				Managed=:Managed, ATS=:ATS, SNMPVersion=:SNMPVersion, VersionOID=:VersionOID,
				Multiplier=:Multiplier, OID1=:OID1, OID2=:OID2, OID3=:OID3, ATSStatusOID=:ATSStatusOID,
				ATSDesiredResult=:ATSDesiredResult, ProcessingProfile=:ProcessingProfile, Voltage=:Voltage,
				Amperage=:Amperage where TemplateID=:TemplateID" );
                        $st->execute( array( ":Managed"=>$this->Managed,
				":ATS"=>$this->ATS,
				":SNMPVersion"=>$this->SNMPVersion,
				":VersionOID"=>$this->VersionOID,
				":Multiplier"=>$this->Multiplier,
				":OID1"=>$this->OID1,
				":OID2"=>$this->OID2,
				":OID3"=>$this->OID3,
				":ATSStatusOID"=>$this->ATSStatusOID,
				":ATSDesiredResult"=>$this->ATSDesiredResult,
				":ProcessingProfile"=>$this->ProcessingProfile,
				":Voltage"=>$this->Voltage,
				":Amperage"=>$this->Amperage,
				":TemplateID"=>$this->TemplateID ) );
		} else {
                        $st = $this->prepare( "insert into CDUTemplates set TemplateID=:TemplateID,
                                Managed=:Managed, ATS=:ATS, SNMPVersion=:SNMPVersion, VersionOID=:VersionOID,
                                Multiplier=:Multiplier, OID1=:OID1, OID2=:OID2, OID3=:OID3, ATSStatusOID=:ATSStatusOID,
                                ATSDesiredResult=:ATSDesiredResult, ProcessingProfile=:ProcessingProfile, Voltage=:Voltage,
                                Amperage=:Amperage" );
                	$st->execute( array( ":TemplateID"=>$this->TemplateID,
				":Managed"=>$this->Managed,
                                ":ATS"=>$this->ATS,
                                ":SNMPVersion"=>$this->SNMPVersion,
                                ":VersionOID"=>$this->VersionOID,
                                ":Multiplier"=>$this->Multiplier,
                                ":OID1"=>$this->OID1,
                                ":OID2"=>$this->OID2,
                                ":OID3"=>$this->OID3,
                                ":ATSStatusOID"=>$this->ATSStatusOID,
                                ":ATSDesiredResult"=>$this->ATSDesiredResult,
                                ":ProcessingProfile"=>$this->ProcessingProfile,
                                ":Voltage"=>$this->Voltage,
                        	":Amperage"=>$this->Amperage ) );
                }

                return true;
	}
}

class CDUTemplates {
	var $TemplateID;
	var $Managed;
	var $ATS;
	var $SNMPVersion;
	var $VersionOID;
	var $Multiplier;
	var $OID1;
	var $OID2;
	var $OID3;
	var $ATSStatusOID;
	var $ATSDesiredResult;
	var $ProcessingProfile;
	var $Voltage;
	var $Amperage;


        function prepare( $sql ) {
                global $dbh;
                return $dbh->prepare( $sql );
        }

	function getTemplate( $TemplateID ) {
		$st = $this->prepare( "select * from CDUTemplates where TemplateID=:TemplateID" );
		$st->execute( array( ":TemplateID"=>$TemplateID ) );

		$st->setFetchMode( PDO::FETCH_CLASS, "CDUTemplates" );
		if ( $row = $st->fetch() ) {
			foreach ( $row as $prop=>$val ) {
				$this->$prop = $val;
			}
		}

		return;
	}
}


class ChassisSlotsQueue {
	var $RequestID;
	var $TemplateID;
	var $Position;
	var $BackSide;
	var $X;
	var $Y;
	var $W;
	var $H;


        function prepare( $sql ) {
                global $dbh;
                return $dbh->prepare( $sql );
        }

	function getSlots( $RequestID ) {
		$st = $this->prepare( "select * from ChassisSlotsQueue where RequestID=:RequestID order by Position ASC" );
		$st->execute( array( ":RequestID"=>$RequestID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "ChassisSlotsQueue" );
		$sList = array();
		while ( $row = $st->fetch() ) {
			$sList[] = $row;
		}

		return $sList;
	}

	function queueSlots( $sList ) {
		$st = $this->prepare( "insert into ChassisSlotsQueue set RequestID=:RequestID, TemplateID=:TemplateID, Position=:Position, BackSide=:BackSide, X=:X, Y=:Y, W=:W, H=:H" );

		foreach ( $sList as $slot ) {
			$st->execute( array( ":RequestID"=>$this->RequestID, ":TemplateID"=>$this->TemplateID, ":Position"=>$slot->Position, 
			 	":BackSide"=>$slot->BackSide, ":X"=>$slot->X, ":Y"=>$slot->Y, ":W"=>$slot->W, ":H"=>$slot->H ) );
		}
	}

	function approveRequest() {
		$st = $this->prepare( "insert into ChassisSlots set TemplateID=:TemplateID, Position=:Position, BackSide=:BackSide, X=:X, Y=:Y, W=:W, H=:H" );

		$st->execute( array( ":TemplateID"=>$this->TemplateID, ":Position"=>$this->Position, ":BackSide"=>$this->BackSide, ":X"=>$this->X, ":Y"=>$this->Y, ":W"=>$this->W, ":H"=>$this->H ) );
	}
}

class ChassisSlots {
	var $TemplateID;
	var $Position;
	var $BackSide;
	var $X;
	var $Y;
	var $W;
	var $H;

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}

	function flushSlots( $TemplateID ) {
		$st = $this->prepare( "delete from ChassisSlots where TemplateID=:TemplateID" );
		return $st->execute( array( ":TemplateID"=>$TemplateID ) );
	}

	function getSlots( $TemplateID ) {
		// Searching for slots without a valid TemplateID will simply return an empty set
		$st = $this->prepare( "select * from ChassisSlots where TemplateID=:TemplateID order by BackSide ASC, Position ASC" );
		$st->execute( array( ":TemplateID"=>$TemplateID ) );

		$sList = array();
		$st->setFetchMode( PDO::FETCH_CLASS, "ChassisSlots" );
		while ( $row = $st->fetch() ) {
			$sList[] = $row;
		}

		return $sList;
	}
}

class TemplatePortsQueue {
	var $RequestID;
	var $TemplateID;
	var $PortNumber;
	var $Label;

	function prepare( $sql ) {
		global $dbh;

		return $dbh->prepare( $sql );
	}

	function getPorts( $RequestID ) {
		$st = $this->prepare( "select * from TemplatePortsQueue where RequestID=:RequestID order by PortNumber ASC" );
		$st->execute( array( ":RequestID"=>$RequestID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "TemplatePortsQueue" );
		$tpList = array();
		while ( $row = $st->fetch() ) {
			$tpList[] = $row;
		}

		return $tpList;
	}

	function approveRequest() {
		// This is a child of a main template, assume that TemplatePorts::FlushPorts was called first
		$st = $this->prepare( "insert into TemplatePorts set TemplateID=:TemplateID, PortNumber=:PortNumber, Label=:Label" );
		$st->execute( array( ":TemplateID"=>$this->TemplateID, ":PortNumber"=>$this->PortNumber, ":Label"=>$this->Label ) );

		return true;
	}

	function queuePorts( $tpList ) {
		$st = $this->prepare( "insert into TemplatePortsQueue set RequestID=:RequestID, TemplateID=:TemplateID, PortNumber=:PortNumber, Label=:Label" );

		foreach( $tpList as $tp ) {
			$st->execute( array( ":RequestID"=>$this->RequestID, ":TemplateID"=>$this->TemplateID, ":PortNumber"=>$tp->PortNumber, ":Label"=>$tp->Label ) );
		}
	}
}

class TemplatePorts {
	var $TemplateID;
	var $PortNumber;
	var $Label;


        function prepare( $sql ) {
                global $dbh;
                return $dbh->prepare( $sql );
        }

	function flushPorts( $TemplateID ) {
		$st = $this->prepare( "delete from TemplatePorts where TemplateID=:TemplateID" );
		$st->execute( array( ":TemplateID"=>$TemplateID ) );

		return;
	}

	function getPorts( $TemplateID ) {
		// Passing an invalid port will simply return an empty set
		$st = $this->prepare( "select * from TemplatePorts where TemplateID=:TemplateID order by PortNumber ASC" );
		$st->execute( array( ":TemplateID"=>$TemplateID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "TemplatePorts" );

		$pList = array();
		while ( $row = $st->fetch() ) {
			$pList[] = $row;
		}

		return $pList;
	}
}

class TemplatePowerPortsQueue {
	var $RequestID;
	var $TemplateID;
	var $PortNumber;
	var $Label;


        function prepare( $sql ) {
                global $dbh;
                return $dbh->prepare( $sql );
        }

	function getPorts( $RequestID ) {
		$st = $this->prepare( "select * from TemplatePowerPortsQueue where RequestID=:RequestID order by PortNumber ASC" );
		$st->execute( array( ":RequestID"=>$RequestID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "TemplatePowerPortsQueue" );
		$ppList = array();
		while ( $row = $st->fetch() ) {
			$ppList[] = $row;
		}

		return $ppList;
	}

        function approveRequest() {
                $st = $this->prepare( "insert into TemplatePowerPorts set TemplateID=:TemplateID, PortNumber=:PortNumber, Label=:Label" );
                $st->execute( array( ":TemplateID"=>$this->TemplateID, ":PortNumber"=>$this->PortNumber, ":Label"=>$this->Label ) );

                return true;
        }


	function queuePorts( $ppList ) {
		$st = $this->prepare( "insert into TemplatePowerPortsQueue set RequestID=:RequestID, TemplateID=:TemplateID, PortNumber=:PortNumber, Label=:Label" );

		foreach ( $ppList as $p ) {
			$st->execute( array( ":RequestID"=>$this->RequestID, ":TemplateID"=>$this->TemplateID, ":PortNumber"=>$p->PortNumber, ":Label"=>$p->Label ) );
		}
	}
}

class TemplatePowerPorts {
	var $TemplateID;
	var $PortNumber;
	var $Label;


        function prepare( $sql ) {
                global $dbh;
                return $dbh->prepare( $sql );
        }

	function flushPorts( $TemplateID ) {
		$st = $this->prepare( "delete from TemplatePowerPorts where TemplateID=:TemplateID" );
		return $st->execute( array( ":TemplateID"=>$TemplateID ) );
	}

	function getPorts( $TemplateID ) {
		$st = $this->prepare( "select * from TemplatePowerPorts where TemplateID=:TemplateID order by PortNumber ASC" );
		$st->execute( array( ":TemplateID"=>$TemplateID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "TemplatePowerPorts" );

		$ppList = array();
		while ( $row = $st->fetch() ) {
			$ppList[] = $row;
		}

		return $ppList;
	}
}

class SensorTemplatesQueue {
	var $RequestID;
	var $TemplateID;
	var $SNMPVersion;
	var $TemperatureOID;
	var $HumidityOID;
	var $TempMultiplier;
	var $HumidityMultiplier;
	var $mUnits;

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}

	function getTemplate( $requestid ) {
		$st = $this->prepare( "select * from SensorTemplatesQueue where RequestID=:RequestID" );
		$st->execute( array( ":RequestID"=>$requestid ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "SensorTemplatesQueue" );

		if ( $row = $st->fetch() ) {
			foreach ( $row as $prop=>$val ) {
				$this->$prop = $val;
			}
			return true;
		} else {
			return false;
		}
	}

	function queueTemplate() {
		$st = $this->prepare( "insert into SensorTemplatesQueue set RequestID=:RequestID, TemplateID=:TemplateID,
			SNMPVersion=:SNMPVersion, TemperatureOID=:TemperatureOID, HumidityOID=:HumidityOID,
			TempMultiplier=:TempMultiplier, HumidityMultiplier=:HumidityMultiplier, mUnits=:mUnits" );
		return $st->execute( array( ":RequestID"=>$this->RequestID,
			":TemplateID"=>$this->TemplateID,
			":SNMPVersion"=>$this->SNMPVersion,
			":TemperatureOID"=>$this->TemperatureOID,
			":HumidityOID"=>$this->HumidityOID,
			":TempMultiplier"=>$this->TempMultiplier,
			":HumidityMultiplier"=>$this->HumidityMultiplier,
			":mUnits"=>$this->mUnits ) );
	}

	function approveRequest() {
		$st = $this->prepare( "select TemplateID, count(*) as Total from SensorTemplates where TemplateID=:TemplateID" );
		$st->execute( array( ":TemplateID"=>$this->TemplateID ) );
		$row = $st->fetch();

		if ( $row['Total'] > 0 ) {
			$st = $this->prepare( "update SensorTemplates set SNMPVersion=:SNMPVersion, TemperatureOID=:TemperatureOID,
				HumidityOID=:HumidityOID, TempMultiplier=:TempMultiplier, HumidityMultiplier=:HumidityMultiplier,
				mUnits=:mUnits where TemplateID=:TemplateID" );
		} else {
			$st = $this->prepare( "insert into SensorTemplates set SNMPVersion=:SNMPVersion, TemperatureOID=:TemperatureOID,
				HumidityOID=:HumidityOID, TempMultiplier=:TempMultiplier, HumidityMultiplier=:HumidityMultiplier,
				mUnits=:mUnits, TemplateID=:TemplateID" );
		}

		return $st->execute( array( ":TemplateID"=>$this->TemplateID,
                        ":SNMPVersion"=>$this->SNMPVersion,
                        ":TemperatureOID"=>$this->TemperatureOID,
                        ":HumidityOID"=>$this->HumidityOID,
                        ":TempMultiplier"=>$this->TempMultiplier,
                        ":HumidityMultiplier"=>$this->HumidityMultiplier,
                        ":mUnits"=>$this->mUnits ) );
	}
}

class SensorTemplates {
        var $TemplateID;
        var $SNMPVersion;
        var $TemperatureOID;
        var $HumidityOID;
        var $TempMultiplier;
        var $HumidityMultiplier;
        var $mUnits;

        function prepare( $sql ) {
                global $dbh;
                return $dbh->prepare( $sql );
        }

        function getTemplate( $templateid ) {
                $st = $this->prepare( "select * from SensorTemplates where TemplateID=:TemplateID" );
                $st->execute( array( ":TemplateID"=>$templateid ) );
                $st->setFetchMode( PDO::FETCH_CLASS, "SensorTemplates" );

                if ( $row = $st->fetch() ) {
                        foreach ( $row as $prop=>$val ) {
                                $this->$prop = $val;
                        }
                        return true;
                } else {
                        return false;
                }
        }
}

class Moderators {
	/* Simple authorization schema:
		If you are an Administrator, you can do anything
		If you are a Moderator, it is for 1 or more Manufacturer lines
		Goal is to have a Moderator for each Manufacturer that will keep that
		information up to date, and in the long out future we could have some
		manufacturers assign someone from their organization to keep the data
		accurate.
	*/
	var $UserID;
	var $ManufacturerID;

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}

	function checkRights() {
		// Set the UserID and ManufacturerID before calling.  This returns true if they are authorizes, or false if not
		$st = $this->prepare( "select count(*) as Total from Moderators where UserID=:UserID and ManufacturerID=:ManufactrerID" );
		$st->execute( array( ":UserID"=>$this->UserID, ":ManufacturerID"=>$this->ManufacturerID ) );
		$r = $st->fetch();

		if ( $r["Total"] == 1 ) {
			return true;
		} else {
			return false;
		}
	}

	function getRights() {
		$st = $this->prepare( "select * from Moderators where UserID=:UserID order by ManufacturerID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "Moderators" );
		$st->execute( ":UserID", $this->UserID );

		$rightsList = array();
		while ( $r = $st->fetch() ) {
			$rightsList[] = $r;
		}

		return $rightsList;
	}

	function grantModeration( $UserID, $Manufacturers ) {
		$st = $this->prepare( "insert into Moderators set UserID=:UserID, ManufacturerID=:ManufacturerID" );
		foreach ( $Manufacturers as $m ) {
			$st->execute( array( ":UserID"=>$UserID, ":ManufacturerID"=>$m ) );
		}
	}

	function revokeModeration( $UserID, $Manufacturers ) {
		$st = $this->prepare( "delete from Moderators where UserID=:UserID and ManufacturerID=:ManufacturerID" );

		foreach ( $Manufacturers as $m ) {
			$st->execute( array( ":UserID"=>$UserID, ":ManufacturerID"=>$m ) );
		}
	}
}

class Users {
	var $UserID;
	var $PrettyName;
	var $APIKey;
	var $Administrator;
	var $Moderator;
	var $LastLoginAddress;
	var $LastLogin;
	var $LastAPIAddress;
	var $LastAPILogin;
	var $Disabled;
	
	function prepare( $sql ) {
		global $dbh;
		
		return $dbh->prepare( $sql );
	}
	
	function makeSafe() {
		$this->UserID = filter_var( FILTER_SANITIZE_EMAIL );
		$this->PrettyName = sanitize( $this->PrettyName );
		$this->APIKey = sanitize( $this->APIKey );
		$this->Administrator = intval( $this->Administrator );
		$this->Moderator = intval( $this->Moderator );
		$this->Disabled = intval( $this->Disabled );
	}

	function addUser() {
		$this->makeSafe();
		$st = $this->prepare( "insert into Users set UserID=:UserID, PrettyName=:PrettyName, APIKey=:APIKey" );
		if( ! $st->execute( array( ":UserID"=>$this->UserID, ":PrettyName"=>$this->PrettyName, ":APIKey"=>$this->APIKey ) ) ) {
			error_log( "Unable to create user account for " . $this->UserID );
			return false;
		}

		return true;
	}

	function updateUser() {
		$this->makeSafe();

		$st = $this->prepare( "update Users set PrettyName=:PrettyName, APIKey=:APIKey,
			Administrator=:Administrator, Moderator=:Moderator, Disabled=:Disabled
			where UserID=:UserID" );

		if ( ! $st->execute( array( ":PrettyName"=>$this->PrettyName,
			":APIKey"=>$this->APIKey, ":Administrator"=>$this->Administrator,
			":Moderator"=>$this->Moderator, ":UserID"=>$this->UserID ) ) ) {
			error_log( "Unable to update user account for " . $this->UserID );
			return false;
		}

		return true;
	}

	function verifyAPIKey( $APIKey, $IPAddress ) {
		$st = $this->prepare( "select * from Users where APIKey=:APIKey and Disabled=false" );
		$st->execute( array( ":APIKey"=>$APIKey ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "Users" );
		$row = $st->fetch();

		if ( $row->UserID == null ) {
			return false;
		}
		
		// Obviously this counts as a login, so update the LastAPILogin time and IP Address
		$st = $this->prepare( "update Users set LastAPIAddress=:IPAddress, LastAPILogin=now() where APIKey=:APIKey" );
		$st->execute( array( ":IPAddress"=>$IPAddress, ":APIKey"=>$APIKey ) );
		
		foreach( $row as $key=>$value ) {
			$this->$key = $value;
		}
		
		return $row->UserID;
	}

	function verifyLogin( $IPAddress ) {
		$st = $this->prepare( "select * from Users where UserID=:UserID and Disabled=false" );
		$st->execute( array( ":UserID"=>$this->UserID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "Users" );
		$row = $st->fetch();

		if ( $row->UserID == null ) {
			return false;
		}

		foreach( $row as $key=>$value ) {
			$this->$key = $value;
		}

		// This counts as a login, so update the LastLogin time and IP Address
		$st = $this->prepare( "update Users set LastLoginAddress=:IPAddress, LastLogin=now() where UserID=:UserID" );
		$st->execute( array( ":IPAddress"=>$IPAddress, ":UserID"=>$this->UserID ) );

		return true;
	}
}	

?>
