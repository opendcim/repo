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

	function queueManufacturer() {
		global $dbh;
		
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

		$this->RequestID = $dbh->lastInsertId();

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
		$st = $this->prepare( "select * from ManufacturersQueue where RequestID=:RequestID" );
		$st->execute( array( ":RequestID"=>$RequestID ) );
		if ( $reqRow = $st->fetch() ) {
		}
		
		
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
		
		// Obviously this counts as a login, so update the LastLogin time and IP Address
		$st = $this->prepare( "update Users set LastAPIAddress=:ipaddress, LastAPILogin=now() where APIKey=:APIKey" );
		$st->execute( array( ":ipaddress"=>$IPAddress, ":APIKey"=>$APIKey ) );
		
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

		$st = $this->prepare( "update Users set LastLoginAddress=:IPAddress, LastLogin=now() where UserID=:UserID" );
		$st->execute( array( ":IPAddress"=>$IPAddress, ":UserID"=>$this->UserID ) );

		return $row;
	}
}	

?>
