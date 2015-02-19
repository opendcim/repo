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

	static function RowToObject( $row ) {
		$m = new Manufacturers();

		foreach( $row as $prop=>$value ) {
			$m->$prop = $value;
		}

		return $m;
	}

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}

	function getManufacturer() {
		$st = $this->prepare( "select * from Manufacturers order by Name ASC" );
		$st->execute();

		$mfgList = array();
		while ( $row = $st->fetch( PDO::FETCH_ASSOC ) ) {
			$mfgList[] = Manufacturers::RowToObject( $row );
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

	static function RowToObject( $dbRow ) {
		$Man = new ManufacturersQueue();
		$Man->RequestID = $dbRow["RequestID"];
		$Man->ManufacturerID = $dbRow["ManufacturerID"];
		$Man->Name = $dbRow["Name"];
		$Man->SubmittedBy = $dbRow["SubmittedBy"];
		$Man->SubmissionDate = $dbRow["SubmissionDate"];
		$Man->ApprovedBy = $dbRow["ApprovedBy"];
		$Man->ApprovedDate = $dbRow["ApprovedDate"];

		return $Man;
	}
	
	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}

	function queueManufacturer() {
		global $dbh;
		
		$this->Name = sanitize( $this->Name );
		$st = $this->prepare( "select * from Manufacturers where UCASE(Name)=UCASE(:Name)" );
		$st->execute( array( ":Name" => $this->Name ) );
		$row = $st->fetch();
		if ( $row["ManufacturerID"] > 0 ) {
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

	function query( $sql ) {
			global $dbh;
			return $dbh->query( $sql );
	}

	function exec( $sql ) {
			global $dbh;
			return $dbh->exec( $sql );
	}

	static function RowToObject( $dbRow ) {
		$t = new DeviceTemplates();

		$t->TemplateID = $dbRow["TemplateID"];
		$t->ManufacturerID = $dbRow["ManufacturerID"];
		$t->Model = $dbRow["Model"];
		$t->Height = $dbRow["Height"];
		$t->Weight = $dbRow["Weight"];
		$t->Wattage = $dbRow["Wattage"];
		$t->DeviceType = $dbRow["DeviceType"];
		$t->PSCount = $dbRow["PSCount"];
		$t->NumPorts = $dbRow["NumPorts"];
		$t->Notes = $dbRow["Notes"];
		$t->FrontPictureFile = $dbRow["FrontPictureFile"];
		$t->RearPictureFile = $dbRow["RearPictureFile"];
		$t->ChassisSlots = $dbRow["ChassisSlots"];
		$t->RearChassisSlots = $dbRow["RearChassisSlots"];

		return $t;
	}

	function getDeviceTemplate( $TemplateID = null ) {
		if ( isset( $TemplateID ) ) {
			$Clause = "AND TemplateID=" . intval( $TemplateID );
		} else {
			$Clause = "";
		}

		$sql = "SELECT * from DeviceTemplates WHERE ApprovedBy IS NOT NULL $Clause ORDER BY ManufacturerID ASC, Model ASC";

		$templateList = array();
		foreach ( $this->query( $sql ) as $tmpRow ) {
			$templateList[] = DeviceTemplates::RowToObject( $tmpRow );
		}

		return $templateList;
	}

	function getDeviceTemplateByMFG( $manufacturerid ) {
			$sql = "SELECT * from DeviceTemplates WHERE ApprovedBy IS NOT NULL AND ManufacturerID=".intval($manufacturerid)." ORDER BY ManufacturerID ASC, Model ASC";

			$templateList = array();
			foreach ( $this->query( $sql ) as $tmpRow ) {
					$templateList[] = DeviceTemplates::RowToObject( $tmpRow );
			}

			return $templateList;

	}

}

class Users {
	var $UserID;
	var $PrettyName;
	var $PasswordHash;
	var $APIKey;
	var $LastLoginAddress;
	var $LastLogin;
	var $LastAPIAddress;
	var $LastAPILogin;
	var $Disabled;
	
	function prepare( $sql ) {
		global $dbh;
		
		return $dbh->prepare( $sql );
	}
	
	public function RowToObject( $row ) {
		$u = new Users;
		
		$u->UserID = $row["UserID"];
		$u->PrettyName = $row["PrettyName"];
		$u->PasswordHash = $row["PasswordHash"];
		$u->APIKey = $row["APIKey"];
		$u->LastLoginAddress = $row["LastLoginAddress"];
		$u->LastLogin = $row["LastLogin"];
		$u->LastAPIAddress = $row["LastAPIAddress"];
		$u->LastAPILogin = $row["LastAPILogin"];
	}
	
	function verifyAPIKey( $APIKey, $IPAddress ) {
		$st = $this->prepare( "select * from Users where APIKey=:APIKey and Disabled=false" );
		$st->execute( array( ":APIKey"=>$APIKey ) );
		$row = $st->fetch();

		if ( $row["UserID"] == null ) {
			return false;
		}
		
		// Obviously this counts as a login, so update the LastLogin time and IP Address
		$st = $this->prepare( "update Users set LastAPIAddress=:ipaddress, LastAPILogin=now() where APIKey=:APIKey" );
		$st->execute( array( ":ipaddress"=>$IPAddress, ":APIKey"=>$APIKey ) );
		
		foreach( $row as $prop=>$value ) {
			$this->$prop = $value;
		}
		
		return true;
	}
	
}	

?>
