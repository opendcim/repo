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

	function getManufacturer() {
		$st = $this->prepare( "select * from Manufacturers order by Name ASC" );
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
		$this->Name = sanitize( $this->Name );
		$st = $this->prepare( "select * from Manufacturers where UCASE(Name)=UCASE(:Name)" );
		$st->execute( array( ":Name" => $this->Name ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "ManufacturersQueue" );
		$row = $st->fetch();
		if ( $row->ManufacturerID > 0 ) {
			error_log( "Table Manufacturers collision:  Name=>" . $this->Name );
			return false;
		}

		$st = $this->prepare( "insert into ManufacturersQueue set Name=:Name, SubmittedBy='scott@themillikens.com', SubmissionDate=now()" );
		if ( ! $st->execute( array( ":Name" => $this->Name ) ) ) {
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
			$st = $this->prepare( "select * from ManufacturersQueue order by Name ASC, RequestID ASC" );
			$st->execute();
		}

		$mfgList = array();
		$st->setFetchMode( PDO::FETCH_CLASS, "ManufacturersQueue" );
		while ( $mfgRow = $st->fetch() ) {
			$mfgList[] = ManufacturersQueue::RowToObject( $mfgRow );
		}

		return $mfgList;
	}
	
	function approveRequest( $RequestID ) {
		global $currUser;

		$st = $this->prepare( "select * from ManufacturersQueue where RequestID=:RequestID" );
		$st->execute( array( ":RequestID"=>$RequestID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "ManufacturersQueue" );
		if ( $req = $st->fetch() ) {
			// If the ManufacturerID is set in the request, this is an update
			if ( $this->ManufacturerID > 0  )
				$st = prepare( "update Manufacturers set Name=:Name, LastModified=now() where
					ManufacturerID=:ManufacturerID" );
				$st->execute( array( ":Name"=>$this->Name, ":ManufacturerID"=>$this->ManufacturerID ) );
			} else {
				$st->prepare( "insert into Manufacturers set Name=:Name, LastModified=now()" );
				$st->execute( array( ":Name"=>$this->Name ) );
				$this->ManufacturerID=$this->lastInsertId();
			}

			$this->ApprovedBy = $currUser->UserID;
			
			$st = $this->prepare( "update ManufacturersQueue set ApprovedBy=:UserID,
				ManufacturerID=:ManufacturerID, ApprovedTime=now() where RequestID=:RequestID" );
			$st->execute( array( ":UserID"=>$currUser->UserID, 
				":ManufacturerID"=>$this->ManufacturerID, ":RequestID"=>$this->RequestID ) );
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
	var $Notes;
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
		$st = $this->prepare( "select * from DeviceTemplates where ManufacturerID=:ManufacturerID" );
		$st->execute( array( ":ManufacturerID"=>$manufacturerid ) );

		$templateList = array();
		$st->setFetchMode( PDO::FETCH_CLASS, "DeviceTemplates" );
		while ( $t = $st->fetch() ) {
				$templateList[] = $t;
		}

		return $templateList;

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
			$st->execute( ":UserID"=>$UserID, ":ManufacturerID"=>$m );
		}
	}

	function revokeModeration( $UserID, $Manufacturers ) {
		$st = $this->prepare( "delete from Moderators where UserID=:UserID and ManufacturerID=:ManufacturerID" );

		foreach ( $Manufacturers as $m ) {
			$st->execute( ":UserID"=>$UserID, ":ManufacturerID"=>$m );
		}
	}
}

class Users {
	var $UserID;
	var $PrettyName;
	var $APIKey;
	var $LastLoginAddress;
	var $LastLogin;
	var $LastAPIAddress;
	var $LastAPILogin;
	var $Administrator;
	var $Moderator;
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
		
		return true;
	}

	function verifyLogin( $IPAddress ) {
		$st = $this->prepare( "select * from Users where UserID=:UserID and Disabled=false" );
		$st->execute( array( ":UserID"=>$this->UserID ) );
		$st->FetchMode( PDO::FETCH_CLASS, "Users" );
		$row = $st->fetch();

		if ( $row->UserID == null ) {
			return false;
		}

		// This counts as a login, so update the LastLogin time and IP Address
		$st = $this->prepare( "update Users set LastLoginAddress=:IPAddress, LastLogin=now() where UserID=:UserID" );
		$st->execute( array( ":IPAddress"=>$IPAddress, ":UserID"=>$this->UserID ) );

		return $row;
	}
}	

?>
