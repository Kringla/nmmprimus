-- Migration: Legg til tracking av siste brukte SerNr per serie per bruker
-- Dette gjør at systemet husker hvor du var i hver serie og foreslår neste ledige nummer

-- Alternativ 1: Utvid user_preferences (enkel, men kun én serie om gangen)
-- Ikke optimal for vårt bruk siden brukeren kan bytte mellom serier

-- Alternativ 2: Ny tabell for å spore siste SerNr per bruker per serie
CREATE TABLE IF NOT EXISTS `user_serie_sernr` (
  `user_id` int(11) NOT NULL,
  `serie` varchar(8) NOT NULL COMMENT '8 tegn serie-ID (f.eks NSM.9999)',
  `last_sernr` smallint(6) NOT NULL COMMENT 'Siste SerNr brukeren la inn i denne serien',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `serie`),
  CONSTRAINT `fk_user_serie_sernr_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Tracker siste SerNr per bruker per serie for smart nummerforslag';
