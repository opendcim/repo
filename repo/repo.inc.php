<?php
class Manufacturers {
	var $ManufacturerID;
	var $Name;
	var $SubmittedBy;
	var $SubmissionDate;
	var $ApprovedBy;
	var $ApprovedDate;

static function RowToObject( $dbRow ) {
	$Man = new Manufacturers();
	$Man->ManufacturerID = $dbRow["ManufacturerID"];
	$Man->Name = $dbRow["Name"];
	$Man->SubmittedBy = $dbRow["SubmittedBy"];
	$Man->SubmissionDate = $dbRow["SubmissionDate"];
	$Man->ApprovedBy = $dbRow["ApprovedBy"];
	$Man->ApprovedDate = $dbRow["ApprovedDate"];

	return $Man;
}

function query( $sql ) {
	global $dbh;
	return $dbh->query( $sql );
}

function exec( $sql ) {
	global $dbh;
	return $dbh->exec( $sql );
}

function getManufacturer( $ManufacturerID = null ) {
	if ( isset( $ManufacturerID ) ) {
		$Clause = "AND ManufacturerID=" . intval($ManufacturerID);
	} else {
		$Clause = "";
	}

	$sql = "SELECT * from Manufacturers where ApprovedBy is not null $Clause ORDER BY Name";

	$mfgList = array();
	foreach ( $this->query( $sql ) as $mfgRow ) {
		$mfgList[] = Manufacturers::RowToObject( $mfgRow );
	}

	return $mfgList;
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


?>
