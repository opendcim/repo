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
  PasswordHash VARCHAR(255) NOT NULL,
  APIKey VARCHAR(255) NOT NULL,
  LastLoginAddress VARCHAR(80) DEFAULT NULL,
  LastLogin DATETIME DEFAULT NULL,
  LastAPIAddress VARCHAR(80) DEFAULT NULL,
  LastAPILogin DATETIME DEFAULT NULL,
  Disabled TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS Manufacturers;
CREATE TABLE Manufacturers (
  ManufacturerID INT(11) NOT NULL AUTO_INCREMENT,
  Name VARCHAR(80) NOT NULL,
  SubmittedBy VARCHAR(255) NOT NULL,
  SubmissionDate DATETIME DEFAULT NULL,
  ApprovedBy VARCHAR(255) NOT NULL,
  ApprovedDate DATETIME DEFAULT NULL,
  PRIMARY KEY (ManufacturerID),
  UNIQUE KEY (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS DeviceTemplates;

DROP TABLE IF EXISTS CDUTemplates;

DROP TABLE IF EXISTS SensorTemplates;

