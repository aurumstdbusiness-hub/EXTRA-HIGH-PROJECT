-- ============================================================
--  EXTRA HIGH PROJECT — Database Setup
--  MySQL 5.7+ / MariaDB 10.3+
--
--  HOW TO IMPORT:
--    phpMyAdmin → Importer → Choisir le fichier → setup.sql
--    → Exécuter
-- ============================================================

CREATE DATABASE IF NOT EXISTS `Base_Client`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `Base_Client`;

-- ── Table : Client ────────────────────────────────────────────────────────────
-- Stores registered customer accounts.
-- mot_de_passe stores a PHP bcrypt hash — never plain text.

CREATE TABLE IF NOT EXISTS `Client` (
  `id_client`      INT UNSIGNED  NOT NULL AUTO_INCREMENT
                     COMMENT 'Primary key — auto-incremented',
  `prenom`         VARCHAR(80)   NOT NULL,
  `nom`            VARCHAR(80)   NOT NULL,
  `email`          VARCHAR(180)  NOT NULL
                     COMMENT 'Unique login identifier',
  `phone`          VARCHAR(20)   DEFAULT NULL,
  `date_naissance` DATE          DEFAULT NULL,
  `adresse`        VARCHAR(255)  DEFAULT NULL,
  `wilaya`         VARCHAR(80)   DEFAULT NULL,
  `code_postal`    VARCHAR(10)   DEFAULT NULL,
  `mot_de_passe`   VARCHAR(255)  NOT NULL
                     COMMENT 'bcrypt hash — password_hash(PASSWORD_BCRYPT)',
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_client`),
  UNIQUE KEY `uq_client_email` (`email`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registered customer accounts';


-- ── Table : Commande_produit ──────────────────────────────────────────────────
-- Stores every order submitted via commande_produit*.html.
-- id_client is nullable to support guest orders.

CREATE TABLE IF NOT EXISTS `Commande_produit` (
  `id_commande`   INT UNSIGNED     NOT NULL AUTO_INCREMENT
                    COMMENT 'Primary key — order number',
  `id_client`     INT UNSIGNED     DEFAULT NULL
                    COMMENT 'FK to Client — NULL for guest orders',

  `product_name`  VARCHAR(120)     NOT NULL,
  `product_ref`   VARCHAR(40)      NOT NULL DEFAULT ''
                    COMMENT 'Internal reference e.g. EHP-001',

  -- Customer contact
  `customer_name` VARCHAR(160)     NOT NULL,
  `email`         VARCHAR(180)     NOT NULL,
  `phone`         VARCHAR(20)      NOT NULL,

  -- Delivery address
  `adresse`       VARCHAR(255)     NOT NULL,
  `wilaya`        VARCHAR(80)      NOT NULL,
  `code_postal`   VARCHAR(10)      DEFAULT NULL,
  `livraison`     ENUM(
                    'standard',
                    'express',
                    'pickup'
                  ) NOT NULL DEFAULT 'standard',

  -- Product options
  `quantite`      TINYINT UNSIGNED NOT NULL DEFAULT 1
                    COMMENT 'Range 1–99',
  `taille`        ENUM('XS','S','M','L','XL','XXL') NOT NULL,
  `couleur`       VARCHAR(40)      DEFAULT NULL,

  -- Payment & notes
  `paiement`      ENUM('cod','ccp','baridimob') NOT NULL DEFAULT 'cod',
  `notes`         TEXT             DEFAULT NULL,

  -- Status lifecycle
  `statut`        ENUM(
                    'en_attente',
                    'confirmee',
                    'expediee',
                    'livree',
                    'annulee'
                  ) NOT NULL DEFAULT 'en_attente',

  `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_commande`),
  KEY `idx_cp_client`  (`id_client`),
  KEY `idx_cp_statut`  (`statut`),
  KEY `idx_cp_created` (`created_at`),

  CONSTRAINT `fk_cp_client`
    FOREIGN KEY (`id_client`)
    REFERENCES `Client` (`id_client`)
    ON DELETE SET NULL
    ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer orders';


-- ── Verification queries (optional, comment out before import) ────────────────
-- SHOW TABLES;
-- DESCRIBE Client;
-- DESCRIBE Commande_produit;
