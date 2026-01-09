-- Migration: Endre BIT(1) til TINYINT(1) i nmmfoto-tabellen
-- Dato: 2026-01-08
-- Årsak: BIT(1) felter forårsaker problemer med PDO i PHP
--         TINYINT(1) er standard tilnærming for boolean-verdier i moderne MySQL/PHP

-- ============================================================
-- BACKUP FØRST! Kjør denne kommandoen i terminal:
-- mysqldump -u [user] -p nmmprimus nmmfoto > nmmfoto_backup_20260108.sql
-- ============================================================

-- Endre datatyper fra BIT(1) til TINYINT(1)
ALTER TABLE `nmmfoto`
  MODIFY COLUMN `Aksesjon` TINYINT(1) DEFAULT NULL,
  MODIFY COLUMN `Fotografi` TINYINT(1) DEFAULT NULL,
  MODIFY COLUMN `FriKopi` TINYINT(1) DEFAULT NULL,
  MODIFY COLUMN `Transferred` TINYINT(1) DEFAULT NULL,
  MODIFY COLUMN `Flag` TINYINT(1) DEFAULT NULL;

-- Oppdater eksisterende verdier for å sikre konsistens
-- BIT(1) verdier (b'0', b'1', eller NULL) → TINYINT(1) (0, 1, eller NULL)
UPDATE `nmmfoto` SET `Aksesjon` = IF(`Aksesjon` IS NULL, NULL, IF(`Aksesjon` = 0, 0, 1));
UPDATE `nmmfoto` SET `Fotografi` = IF(`Fotografi` IS NULL, NULL, IF(`Fotografi` = 0, 0, 1));
UPDATE `nmmfoto` SET `FriKopi` = IF(`FriKopi` IS NULL, NULL, IF(`FriKopi` = 0, 0, 1));
UPDATE `nmmfoto` SET `Transferred` = IF(`Transferred` IS NULL, NULL, IF(`Transferred` = 0, 0, 1));
UPDATE `nmmfoto` SET `Flag` = IF(`Flag` IS NULL, NULL, IF(`Flag` = 0, 0, 1));

-- Verifiser endringene
SELECT
  COLUMN_NAME,
  DATA_TYPE,
  COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'nmmprimus'
  AND TABLE_NAME = 'nmmfoto'
  AND COLUMN_NAME IN ('Aksesjon', 'Fotografi', 'FriKopi', 'Transferred', 'Flag');

-- Sjekk noen eksempelverdier
SELECT
  Foto_ID,
  Aksesjon,
  Fotografi,
  FriKopi,
  Transferred,
  Flag
FROM nmmfoto
LIMIT 10;
