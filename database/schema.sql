-- Kalaat Bona FC - database schema
-- Import this file first, then import seed.sql.
-- Tested with MariaDB 10.4+ / MySQL 5.7+ (utf8mb4).

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'subadmin',
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `contact_site` (
  `id_message` int(11) NOT NULL AUTO_INCREMENT,
  `nom_complet` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `parent` (
  `id_parent` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `login_token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id_parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `joueur` (
  `id_joueur` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `prenom` varchar(50) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `date_naissance` date NOT NULL,
  `nationalite` varchar(50) DEFAULT NULL,
  `adresse` mediumtext DEFAULT NULL,
  `id_parent` int(11) DEFAULT NULL,
  `sexe` varchar(10) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `login_token` varchar(64) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `taille` decimal(5,1) DEFAULT NULL COMMENT 'Taille en cm',
  `poids` decimal(5,1) DEFAULT NULL COMMENT 'Poids en kg',
  `maladie_chronique` text DEFAULT NULL,
  PRIMARY KEY (`id_joueur`),
  KEY `id_parent` (`id_parent`),
  CONSTRAINT `joueur_ibfk_1` FOREIGN KEY (`id_parent`) REFERENCES `parent` (`id_parent`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `programme` (
  `id_programme` int(11) NOT NULL AUTO_INCREMENT,
  `id_coach` int(11) DEFAULT NULL,
  `nom_programme` varchar(100) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `icone` varchar(50) DEFAULT '⚽',
  `image` varchar(255) DEFAULT NULL,
  `lieu` varchar(255) DEFAULT '',
  `date_horaire` varchar(255) DEFAULT '',
  `places` varchar(100) DEFAULT '',
  `age` varchar(50) DEFAULT '',
  `destinations` text DEFAULT NULL,
  `inscriptions_info` varchar(255) DEFAULT '',
  `age_min` int(11) DEFAULT NULL,
  `age_max` int(11) DEFAULT NULL,
  `nom_programme_en` varchar(255) DEFAULT '',
  `nom_programme_ar` varchar(255) DEFAULT '',
  `description_en` text DEFAULT NULL,
  `description_ar` text DEFAULT NULL,
  `lieu_en` varchar(255) DEFAULT '',
  `lieu_ar` varchar(255) DEFAULT '',
  `date_horaire_en` varchar(255) DEFAULT '',
  `date_horaire_ar` varchar(255) DEFAULT '',
  `places_en` varchar(255) DEFAULT '',
  `places_ar` varchar(255) DEFAULT '',
  `age_en` varchar(255) DEFAULT '',
  `age_ar` varchar(255) DEFAULT '',
  PRIMARY KEY (`id_programme`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `inscription` (
  `id_inscription` int(11) NOT NULL AUTO_INCREMENT,
  `id_joueur` int(11) DEFAULT NULL,
  `id_programme` int(11) DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `commentaires` mediumtext DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'en_attente',
  `email_contact` varchar(255) DEFAULT NULL,
  `nom_contact` varchar(100) DEFAULT NULL,
  `login_contact` varchar(100) DEFAULT NULL,
  `mot_de_passe_clair` varchar(255) DEFAULT NULL,
  `traite_par` int(11) DEFAULT NULL,
  `traite_le` datetime DEFAULT NULL,
  PRIMARY KEY (`id_inscription`),
  KEY `id_joueur` (`id_joueur`),
  KEY `id_programme` (`id_programme`),
  CONSTRAINT `inscription_ibfk_1` FOREIGN KEY (`id_joueur`) REFERENCES `joueur` (`id_joueur`) ON DELETE CASCADE,
  CONSTRAINT `inscription_ibfk_2` FOREIGN KEY (`id_programme`) REFERENCES `programme` (`id_programme`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `demande_changement` (
  `id_demande` int(11) NOT NULL AUTO_INCREMENT,
  `id_joueur` int(11) NOT NULL,
  `poste_actuel` varchar(50) DEFAULT NULL,
  `poste_souhaite` varchar(100) NOT NULL,
  `motif` text DEFAULT NULL,
  `date_demande` datetime DEFAULT current_timestamp(),
  `statut` varchar(20) DEFAULT 'en_attente',
  PRIMARY KEY (`id_demande`),
  KEY `id_joueur` (`id_joueur`),
  CONSTRAINT `demande_changement_ibfk_1` FOREIGN KEY (`id_joueur`) REFERENCES `joueur` (`id_joueur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `messages` (
  `id_message` int(11) NOT NULL AUTO_INCREMENT,
  `id_parent` int(11) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `sender_type` enum('parent','admin') NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `paiement` (
  `id_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `id_inscription` int(11) DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `statut` varchar(50) DEFAULT 'Complété',
  `date_paiement` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_paiement`),
  KEY `id_inscription` (`id_inscription`),
  CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`id_inscription`) REFERENCES `inscription` (`id_inscription`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `profil_football` (
  `id_profil` int(11) NOT NULL AUTO_INCREMENT,
  `id_joueur` int(11) DEFAULT NULL,
  `poste` varchar(50) DEFAULT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  `experience` varchar(20) DEFAULT NULL,
  `club_precedent` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_profil`),
  KEY `id_joueur` (`id_joueur`),
  CONSTRAINT `profil_football_ibfk_1` FOREIGN KEY (`id_joueur`) REFERENCES `joueur` (`id_joueur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `s_key` varchar(100) NOT NULL,
  `s_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `s_key` (`s_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `suivi` (
  `id_suivi` int(11) NOT NULL AUTO_INCREMENT,
  `id_joueur` int(11) DEFAULT NULL,
  `performance` mediumtext DEFAULT NULL,
  `remarque` mediumtext DEFAULT NULL,
  `presence` varchar(20) DEFAULT 'Présent',
  `date_suivi` date DEFAULT NULL,
  PRIMARY KEY (`id_suivi`),
  KEY `id_joueur` (`id_joueur`),
  CONSTRAINT `suivi_ibfk_1` FOREIGN KEY (`id_joueur`) REFERENCES `joueur` (`id_joueur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
