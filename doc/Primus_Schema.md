/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE TABLE IF NOT EXISTS `bildeserie` (
  `SerID` int(11) NOT NULL AUTO_INCREMENT,
  `Serie` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`SerID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `country` (
  `Nasjon_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Nasjon` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Nasjon_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `Foto_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NMM_ID` int(11) DEFAULT NULL,
  `URL_Bane` varchar(255) DEFAULT NULL,
  `SerNr` smallint(6) NOT NULL,
  `Bilde_Fil` varchar(255) NOT NULL,
  `MotivBeskr` varchar(255) NOT NULL,
  `MotivBeskrTillegg` varchar(255) DEFAULT NULL,
  `MotivType` mediumtext DEFAULT NULL,
  `MotivEmne` mediumtext DEFAULT NULL,
  `MotivKriteria` mediumtext DEFAULT NULL,
  `Avbildet` mediumtext DEFAULT NULL,
  `Hendelse` mediumtext DEFAULT NULL,
  `Aksesjon` tinyint(1) DEFAULT 0,
  `Samling` varchar(255) DEFAULT NULL,
  `Fotografi` tinyint(1) DEFAULT 0,
  `Fotograf` varchar(255) DEFAULT NULL,
  `FotoFirma` varchar(255) DEFAULT NULL,
  `FotoTidFra` varchar(255) DEFAULT NULL,
  `FotoTidTil` varchar(255) DEFAULT NULL,
  `FotoSted` varchar(255) DEFAULT NULL,
  `Prosess` varchar(255) DEFAULT 'Positivkopi;300',
  `ReferNeg` varchar(255) DEFAULT NULL,
  `ReferFArk` varchar(255) DEFAULT NULL,
  `Plassering` varchar(255) DEFAULT '0286/Mus/Bib',
  `PlassFriTekst` varchar(255) DEFAULT 'Fotoarkiv: Damp- og Motorskip',
  `Svarthvitt` varchar(255) DEFAULT 'Svart-hvit',
  `Status` varchar(255) DEFAULT 'Original',
  `Tilstand` varchar(255) DEFAULT 'God',
  `FriKopi` tinyint(1) DEFAULT NULL,
  `Antall` smallint(2) DEFAULT NULL,
  `UUID` varchar(255) NOT NULL,
  `Transferred` tinyint(1) DEFAULT NULL,
  `Merknad` varchar(255) DEFAULT NULL,
  `Flag` tinyint(1) DEFAULT NULL,
  `Opprettet_Tid` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Tidspunkt for opprettelse av rad',
  `Oppdatert_Tid` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Tidspunkt for siste oppdatering',
  PRIMARY KEY (`Foto_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=239 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxemne` (
  `Ser_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NMM_ID` int(11) DEFAULT NULL,
  `Id_nr` varchar(255) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  `Motivord` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Ser_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=35608 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxhendelse` (
  `Ser_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Foto_ID` int(11) DEFAULT NULL,
  `Hendelsestype` varchar(255) DEFAULT NULL,
  `ROWID` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Ser_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxou` (
  `Ser_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NMM_ID` int(11) DEFAULT NULL,
  `Id` varchar(255) DEFAULT NULL,
  `System` varchar(255) DEFAULT NULL,
  `Kode` varchar(255) DEFAULT NULL,
  `Klassifikasjon` varchar(255) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Ser_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=19564 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxtype` (
  `Ser_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NMM_ID` int(11) DEFAULT NULL,
  `Id` varchar(255) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  `Motivtype` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Ser_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=19273 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmmxudk` (
  `Ser_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NMM_ID` int(11) DEFAULT NULL,
  `Id` varchar(255) DEFAULT NULL,
  `System` varchar(255) DEFAULT NULL,
  `Kode` varchar(255) DEFAULT NULL,
  `Klassifikasjon` varchar(255) DEFAULT NULL,
  `UUID` varchar(255) DEFAULT NULL,
  `NID` int(11) DEFAULT NULL,
  PRIMARY KEY (`Ser_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=20778 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nmm_skip` (
  `NMM_ID` int(11) NOT NULL,
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
  `Flag2` bit(1) DEFAULT NULL,
  PRIMARY KEY (`NMM_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `LastUsed` timestamp NULL DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `user_id` int(11) NOT NULL,
  `last_serie` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_serie_sernr` (
  `user_id` int(11) NOT NULL,
  `serie` varchar(8) NOT NULL COMMENT '8 tegn serie-ID (f.eks NSM.9999)',
  `last_sernr` smallint(6) NOT NULL COMMENT 'Siste SerNr brukeren la inn i denne serien',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `serie`),
  CONSTRAINT `fk_user_serie_sernr_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Tracker siste SerNr per bruker per serie for smart nummerforslag';

CREATE TABLE IF NOT EXISTS `user_remember_tokens` (
  `token_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` char(24) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `last_used_at` datetime NOT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `uq_selector` (`selector`),
  KEY `ix_user_id` (`user_id`),
  KEY `ix_expires` (`expires_at`),
  CONSTRAINT `fk_user_remember_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
