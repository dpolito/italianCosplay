-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Lug 03, 2025 alle 15:52
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `italiancosplay_db`
--
CREATE DATABASE IF NOT EXISTS `italiancosplay_gemini` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `events`
--

CREATE TABLE `events` (
                          `id` int(11) NOT NULL,
                          `titolo` varchar(255) NOT NULL,
                          `descrizione` text DEFAULT NULL,
                          `data_inizio` date NOT NULL,
                          `data_fine` date DEFAULT NULL, -- Aggiunto per eventi su più giorni
                          `luogo` varchar(255) NOT NULL,
                          `regione_id` int(11) DEFAULT NULL,
                          `provincia_id` int(11) DEFAULT NULL,
                          `comune_id` int(11) DEFAULT NULL,
                          `latitudine` decimal(10,7) DEFAULT NULL,
                          `longitudine` decimal(10,7) DEFAULT NULL,
                          `sito_web` varchar(255) DEFAULT NULL,
                          `social_facebook` varchar(255) DEFAULT NULL,
                          `social_twitter` varchar(255) DEFAULT NULL,
                          `social_instagram` varchar(255) DEFAULT NULL,
                          `social_tiktok` varchar(255) DEFAULT NULL,
                          `social_youtube` varchar(255) DEFAULT NULL,
                          `tipo_evento_id` int(11) DEFAULT NULL,
                          `immagine` varchar(255) DEFAULT NULL,
                          `approvato` tinyint(1) NOT NULL DEFAULT 0, -- 0 = da approvare, 1 = approvato
                          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `events`
--

INSERT INTO `events` (`id`, `titolo`, `descrizione`, `data_inizio`, `data_fine`, `luogo`, `regione_id`, `provincia_id`, `comune_id`, `latitudine`, `longitudine`, `sito_web`, `social_facebook`, `social_twitter`, `social_instagram`, `social_tiktok`, `social_youtube`, `tipo_evento_id`, `immagine`, `approvato`, `created_at`, `updated_at`) VALUES
                                                                                                                                                                                                                                                                                                                                                     (1, 'Romics 2025 - Edizione Primavera', 'La fiera del fumetto, animazione, cinema e videogames. Tante aree dedicate al cosplay!', '2025-10-02', '2025-10-05', 'Fiera di Roma', 7, 68, 120, 41.8329430, 12.3615960, 'https://www.romics.it', 'https://facebook.com/romics', NULL, 'https://instagram.com/romicsofficial', NULL, NULL, 1, 'romics_primavera_2025.jpg', 1, '2025-07-03 13:52:27', '2025-07-03 13:52:27'),
                                                                                                                                                                                                                                                                                                                                                     (2, 'Lucca Comics & Games 2025', 'Il festival internazionale del fumetto, del gioco e dell\'illustrazione. L\'evento cosplay più grande d\'Italia!', '2025-10-30', '2025-11-03', 'Centro storico di Lucca', 9, 46, 73, 43.8427950, 10.5029860, 'https://www.luccacomicsandgames.com', 'https://facebook.com/luccacomicsandgames', 'https://twitter.com/LuccaC_G', 'https://instagram.com/luccacomicsandgames', NULL, NULL, 1, 'lucca_comics_2025.jpg', 1, '2025-07-03 13:52:27', '2025-07-03 13:52:27'),
(3, 'Milano Comic Con 2025', 'Un nuovo evento dedicato alla cultura pop e al cosplay a Milano.', '2025-09-15', '2025-09-15', 'Superstudio Più, Milano', 3, 15, 10, 45.4678000, 9.1670000, 'https://www.milanocomiccon.it', NULL, NULL, NULL, NULL, NULL, 1, NULL, 0, '2025-07-03 13:52:27', '2025-07-03 13:52:27'),
(4, 'Fiera del Fumetto di Napoli', 'Evento locale con gara cosplay e workshop.', '2025-08-20', '2025-08-22', 'Mostra d\'Oltremare, Napoli', 4, 63, 48, 40.8242000, 14.1843000, NULL, 'https://facebook.com/fierafumetto.napoli', NULL, NULL, NULL, NULL, 1, NULL, 1, '2025-07-03 13:52:27', '2025-07-03 13:52:27'),
                                                                                                                                                                                                                                                                                                                                                     (5, 'Evento Cosplay Estivo - Roma', 'Un raduno cosplay informale in un parco di Roma.', '2025-07-20', '2025-07-20', 'Villa Borghese, Roma', 7, 68, 120, 41.9130000, 12.4840000, NULL, NULL, NULL, 'https://instagram.com/cosplayroma', NULL, NULL, 2, NULL, 0, '2025-07-03 13:52:27', '2025-07-03 13:52:27');

-- --------------------------------------------------------

--
-- Struttura della tabella `regioni`
--

CREATE TABLE `regioni` (
                           `id` int(11) NOT NULL,
                           `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `regioni`
--

INSERT INTO `regioni` (`id`, `nome`) VALUES
                                         (1, 'Piemonte'),
                                         (2, 'Valle d\'Aosta'),
(3, 'Lombardia'),
(4, 'Trentino-Alto Adige'),
(5, 'Veneto'),
(6, 'Friuli-Venezia Giulia'),
(7, 'Liguria'),
(8, 'Emilia-Romagna'),
(9, 'Toscana'),
(10, 'Umbria'),
(11, 'Marche'),
(12, 'Lazio'),
(13, 'Abruzzo'),
(14, 'Molise'),
(15, 'Campania'),
(16, 'Puglia'),
(17, 'Basilicata'),
(18, 'Calabria'),
(19, 'Sicilia'),
(20, 'Sardegna');

-- --------------------------------------------------------

--
-- Struttura della tabella `province`
--

CREATE TABLE `province` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sigla` varchar(2) NOT NULL,
  `regione_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `province`
--

INSERT INTO `province` (`id`, `nome`, `sigla`, `regione_id`) VALUES
(1, 'Torino', 'TO', 1),
(2, 'Aosta', 'AO', 2),
(3, 'Milano', 'MI', 3),
(4, 'Roma', 'RM', 12),
(5, 'Napoli', 'NA', 15),
(6, 'Lucca', 'LU', 9),
(10, 'Milano', 'MI', 3), -- Esempio per Milano
(15, 'Monza e Brianza', 'MB', 3), -- Esempio per Monza
(46, 'Lucca', 'LU', 9), -- Esempio per Lucca
(63, 'Napoli', 'NA', 15), -- Esempio per Napoli
(68, 'Roma', 'RM', 12); -- Esempio per Roma

-- --------------------------------------------------------

--
-- Struttura della tabella `comuni`
--

CREATE TABLE `comuni` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `provincia_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `comuni`
--

INSERT INTO `comuni` (`id`, `nome`, `provincia_id`) VALUES
(1, 'Torino', 1),
(2, 'Aosta', 2),
(3, 'Milano', 3),
(4, 'Roma', 4),
(5, 'Napoli', 5),
(6, 'Lucca', 6),
(10, 'Milano', 10),
(73, 'Lucca', 46),
(48, 'Napoli', 63),
(120, 'Roma', 68);

-- --------------------------------------------------------

--
-- Struttura della tabella `tipo_evento`
--

CREATE TABLE `tipo_evento` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `tipo_evento`
--

INSERT INTO `tipo_evento` (`id`, `nome`) VALUES
(1, 'Fiera/Convention'),
(2, 'Raduno Cosplay'),
(3, 'Gara Cosplay'),
(4, 'Workshop');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per la tabella `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `regione_id` (`regione_id`),
  ADD KEY `provincia_id` (`provincia_id`),
  ADD KEY `comune_id` (`comune_id`),
  ADD KEY `tipo_evento_id` (`tipo_evento_id`);

--
-- Indici per la tabella `regioni`
--
ALTER TABLE `regioni`
  ADD PRIMARY KEY (`id`);

--
-- Indici per la tabella `province`
--
ALTER TABLE `province`
  ADD PRIMARY KEY (`id`),
  ADD KEY `regione_id` (`regione_id`);

--
-- Indici per la tabella `comuni`
--
ALTER TABLE `comuni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provincia_id` (`provincia_id`);

--
-- Indici per la tabella `tipo_evento`
--
ALTER TABLE `tipo_evento`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `regioni`
--
ALTER TABLE `regioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT per la tabella `province`
--
ALTER TABLE `province`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT per la tabella `comuni`
--
ALTER TABLE `comuni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT per la tabella `tipo_evento`
--
ALTER TABLE `tipo_evento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`regione_id`) REFERENCES `regioni` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`provincia_id`) REFERENCES `province` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`comune_id`) REFERENCES `comuni` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `events_ibfk_4` FOREIGN KEY (`tipo_evento_id`) REFERENCES `tipo_evento` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


                                         -- Tabella per gli utenti
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
);
