/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE TABLE IF NOT EXISTS `bildeserie` (
  `SerID` int(11) DEFAULT NULL,
  `Serie` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `country` (
  `NID` int(11) NOT NULL AUTO_INCREMENT,
  `Nasjon` varchar(255) DEFAULT NULL,
  `Maritim` bit(1) DEFAULT NULL,
  `PriTy` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`NID`)
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `farttype` (
  `FTY` int(11) NOT NULL AUTO_INCREMENT,
  `TypeFork` varchar(3) DEFAULT NULL,
  `FartType` varchar(50) DEFAULT NULL,
  `OU_ID` varchar(255) DEFAULT NULL,
  `Type_Id` varchar(255) DEFAULT NULL,
  `Emne_Id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`FTY`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `nmmfoto` (
  `Foto_ID` int(11) DEFAULT NULL,
  `NMM_ID` int(11) DEFAULT NULL,
  `URL_Bane` varchar(255) DEFAULT NULL,
  `SerNr` smallint(6) DEFAULT NULL,
  `Bilde_Fil` varchar(255) DEFAULT NULL,
  `MotivBeskr` varchar(255) DEFAULT NULL,
  `MotivBeskrTillegg` varchar(255) DEFAULT NULL,
  `MotivType` mediumtext DEFAULT NULL,
  `MotivEmne` mediumtext DEFAULT NULL,
  `MotivKriteria` mediumtext DEFAULT NULL,
  `Avbildet` mediumtext DEFAULT NULL,
  `Hendelse` mediumtext DEFAULT NULL,
  `Aksesjon` bit(1) DEFAULT NULL,
  `Samling` varchar(255) DEFAULT NULL,
  `Fotografi` bit(1) DEFAULT NULL,
  `Fotograf` varchar(255) DEFAULT NULL,
  `FotoFirma` varchar(255) DEFAULT NULL,
  `FotoTidFra` varchar(255) DEFAULT NULL,
  `FotoTidTil` varchar(255) DEFAULT NULL,
  `FotoSted` varchar(255) DEFAULT NULL,
  `Prosess` varchar(255) DEFAULT NULL,
  `ReferNeg` varchar(255) DEFAULT NULL,
  `ReferFArk` varchar(255) DEFAULT NULL,
  `Plassering` varchar(255) DEFAULT NULL,
  `Svarthvitt` varchar(255) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Tilstand` varchar(255) DEFAULT NULL,
  `FriKopi` bit(1) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  `Transferred` bit(1) DEFAULT NULL,
  `Merknad` VARCHAR(255) DEFAULT NULL;
  `Flag` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxemne` (
  `Ser_ID` int(11) DEFAULT NULL,
  `NMM_ID` int(11) DEFAULT NULL,
  `Id_nr` varchar(255) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  `Motivord` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxhendelse` (
  `Ser_ID` int(11) DEFAULT NULL,
  `Foto_ID` int(11) DEFAULT NULL,
  `Hendelsestype` varchar(255) DEFAULT NULL,
  `ROWID` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxou` (
  `Ser_ID` int(11) DEFAULT NULL,
  `NMM_ID` int(11) DEFAULT NULL,
  `Id` varchar(255) DEFAULT NULL,
  `System` varchar(255) DEFAULT NULL,
  `Kode` varchar(255) DEFAULT NULL,
  `Klassifikasjon` varchar(255) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxtype` (
  `Ser_ID` int(11) DEFAULT NULL,
  `NMM_ID` int(11) DEFAULT NULL,
  `Id` varchar(255) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  `Motivtype` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxudk` (
  `Ser_ID` int(11) DEFAULT NULL,
  `NMM_ID` int(11) DEFAULT NULL,
  `Id` varchar(255) DEFAULT NULL,
  `System` varchar(255) DEFAULT NULL,
  `Kode` varchar(255) DEFAULT NULL,
  `Klassifikasjon` varchar(255) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  `NID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmm_skip` (
  `NMM_ID` int(11) DEFAULT NULL,
  `FTY` varchar(255) DEFAULT NULL,
  `FNA` varchar(255) DEFAULT NULL,
  `XNA` varchar(255) DEFAULT NULL,
  `VID` int(11) DEFAULT NULL,
  `VER` varchar(255) DEFAULT NULL,
  `BNR` varchar(255) DEFAULT NULL,
  `MAT` varchar(255) DEFAULT NULL,
  `KAL` varchar(255) DEFAULT NULL,
  `TON` varchar(255) DEFAULT NULL,
  `UNT` varchar(255) DEFAULT NULL,
  `EIR` varchar(255) DEFAULT NULL,
  `FTO` mediumtext DEFAULT NULL,
  `BYG` varchar(255) DEFAULT NULL,
  `RGH` varchar(255) DEFAULT NULL,
  `NAT` varchar(255) DEFAULT NULL,
  `NID` int(11) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  `USR` varchar(255) DEFAULT NULL,
  `DIV` int(11) DEFAULT NULL,
  `Flag` bit(1) DEFAULT NULL,
  `Flag2` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `LastUsed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `_zhendelsestyper` (
  `Kode` int(11) NOT NULL,
  `Hendelsestype` varchar(255) DEFAULT NULL,
  `ROWID` varchar(255) DEFAULT NULL,
  `Selected` bit(1) DEFAULT NULL,
  PRIMARY KEY (`Kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
