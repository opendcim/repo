--
-- Database structure for openDCIM Template Repository
--

--
-- UserID is email address, so no need to keep it as a separate field
-- 

DROP TABLE IF EXISTS Users;
CREATE TABLE Users (
  UserID VARCHAR(255) NOT NULL,
  PrettyName VARCHAR(255) NOT NULL,
  APIKey VARCHAR(255) NOT NULL,
  Administrator TINYINT(1) NOT NULL,
  Moderator TINYINT(1) NOT NULL,
  LastLoginAddress VARCHAR(80) DEFAULT NULL,
  LastLogin DATETIME DEFAULT NULL,
  LastAPIAddress VARCHAR(80) DEFAULT NULL,
  LastAPILogin DATETIME DEFAULT NULL,
  Disabled TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS ManufacturersQueue;
CREATE TABLE ManufacturersQueue (
  RequestID INT(11) NOT NULL AUTO_INCREMENT,
  ManufacturerID INT(11) NOT NULL,
  Name VARCHAR(80) NOT NULL,
  SubmittedBy VARCHAR(255) NOT NULL,
  SubmissionDate DATETIME DEFAULT NULL,
  ApprovedBy VARCHAR(255) NOT NULL,
  ApprovedDate DATETIME DEFAULT NULL,
  PRIMARY KEY (RequestID),
  UNIQUE KEY (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS Manufacturers;
CREATE TABLE Manufacturers (
  ManufacturerID INT(11) NOT NULL AUTO_INCREMENT,
  Name VARCHAR(80) NOT NULL,
  LastModified DATETIME NOT NULL,
  PRIMARY KEY(ManufacturerID),
  UNIQUE KEY (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS DeviceTemplatesQueue;
CREATE TABLE DeviceTemplatesQueue (
  RequestID INT(11) NOT NULL AUTO_INCREMENT,
  TemplateID INT(11) NOT NULL,
  ManufacturerID int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Height int(11) NOT NULL,
  Weight int(11) NOT NULL,
  Wattage int(11) NOT NULL,
  DeviceType varchar(23) NOT NULL default 'Server',
  PSCount int(11) NOT NULL,
  NumPorts int(11) NOT NULL,
  Notes text NOT NULL,
  FrontPictureFile VARCHAR(45) NOT NULL,
  RearPictureFile VARCHAR(45) NOT NULL,
  ChassisSlots SMALLINT(6) NOT NULL,
  RearChassisSlots SMALLINT(6) NOT NULL,
  SubmittedBy VARCHAR(255) NOT NULL,
  SubmissionDate DATETIME DEFAULT NULL,
  ApprovedBy VARCHAR(255) NOT NULL,
  ApprovedDate DATETIME DEFAULT NULL,
  PRIMARY KEY (RequestID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS DeviceTemplates;
CREATE TABLE DeviceTemplates (
  TemplateID INT(11) NOT NULL AUTO_INCREMENT,
  ManufacturerID int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Height int(11) NOT NULL,
  Weight int(11) NOT NULL,
  Wattage int(11) NOT NULL,
  DeviceType varchar(23) NOT NULL default 'Server',
  PSCount int(11) NOT NULL,
  NumPorts int(11) NOT NULL,
  Notes text NOT NULL,
  FrontPictureFile VARCHAR(45) NOT NULL,
  RearPictureFile VARCHAR(45) NOT NULL,
  ChassisSlots SMALLINT(6) NOT NULL,
  RearChassisSlots SMALLINT(6) NOT NULL,
  LastModified DATETIME NOT NULL,
  PRIMARY KEY (TemplateID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS SlotsQueue;
CREATE TABLE SlotsQueue (
  RequestID INT(11) NOT NULL AUTO_INCREMENT,
  TemplateID INT(11) NOT NULL,
  Position INT(11) NOT NULL,
  BackSide TINYINT(1) NOT NULL,
  X INT(11) NULL,
  Y INT(11) NULL,
  W INT(11) NULL,
  H INT(11) NULL,
  SubmittedBy VARCHAR(255) NOT NULL,
  SubmissionDate DATETIME DEFAULT NULL,
  ApprovedBy VARCHAR(255) NOT NULL,
  ApprovedDate DATETIME DEFAULT NULL,
  PRIMARY KEY (RequestID)
) ENGINE = InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS TemplatePortsQueue;
CREATE TABLE TemplatePortQueues (
  RequestID INT(11) NOT NULL AUTO_INCREMENT,
  TemplateID INT(11) NOT NULL,
  PortNumber INT(11) NOT NULL,
  Label VARCHAR(40) NOT NULL,
  MediaID INT(11) NOT NULL DEFAULT 0,
  ColorID INT(11) NOT NULL DEFAULT 0,
  PortNotes VARCHAR(80) NOT NULL,
  SubmittedBy VARCHAR(255) NOT NULL,
  SubmissionDate DATETIME DEFAULT NULL,
  ApprovedBy VARCHAR(255) NOT NULL,
  ApprovedDate DATETIME DEFAULT NULL,
  PRIMARY KEY(RequestID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS CDUTemplates;
CREATE TABLE fac_CDUTemplate (
  TemplateID int(11) NOT NULL AUTO_INCREMENT,
  ManufacturerID int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Managed int(1) NOT NULL,
  ATS int(1) NOT NULL,
  SNMPVersion varchar(2) NOT NULL DEFAULT '2c',
  VersionOID varchar(80) NOT NULL,
  Multiplier varchar(6),
  OID1 varchar(80) NOT NULL,
  OID2 varchar(80) NOT NULL,
  OID3 varchar(80) NOT NULL,
  ATSStatusOID varchar(80) NOT NULL,
  ATSDesiredResult varchar(80) NOT NULL,
  ProcessingProfile enum('SingleOIDWatts','SingleOIDAmperes','Combine3OIDWatts','Combine3OIDAmperes','Convert3PhAmperes'),
  Voltage int(11) NOT NULL,
  Amperage int(11) NOT NULL,
  NumOutlets int(11) NOT NULL,
  GlobalID int(11) NOT NULL,
  ShareToRepo tinyint(1) NOT NULL DEFAULT 0,
  KeepLocal tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (TemplateID),
  KEY ManufacturerID (ManufacturerID),
  UNIQUE KEY (ManufacturerID, Model)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS SensorTemplates;

