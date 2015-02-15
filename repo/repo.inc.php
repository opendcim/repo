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
?>
