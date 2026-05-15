-- =============================================================================
--  E-coomy — setup_production.sql
--  USE THIS FILE for external MySQL providers (Railway, Aiven, PlanetScale).
--
--  Difference from setup.sql:
--    • No CREATE DATABASE statement (the provider creates the DB for you)
--    • No USE statement (connect directly to your DB via DB_NAME env var)
--
--  How to run:
--    mysql -h <DB_HOST> -u <DB_USER> -p<DB_PASS> <DB_NAME> < setup_production.sql
--  Or paste contents into your provider's SQL console.
-- =============================================================================

-- ── Table : Client ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Client` (
  `id_client`      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `prenom`         VARCHAR(80)   NOT NULL,
  `nom`            VARCHAR(80)   NOT NULL,
  `email`          VARCHAR(180)  NOT NULL,
  `phone`          VARCHAR(20)   DEFAULT NULL,
  `date_naissance` DATE          DEFAULT NULL,
  `adresse`        VARCHAR(255)  DEFAULT NULL,
  `wilaya`         VARCHAR(80)   DEFAULT NULL,
  `code_postal`    VARCHAR(10)   DEFAULT NULL,
  `mot_de_passe`   VARCHAR(255)  NOT NULL COMMENT 'bcrypt hash',
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_client`),
  UNIQUE KEY `uq_client_email` (`email`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── Table : Commande_produit ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `Commande_produit` (
  `id_commande`   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `id_client`     INT UNSIGNED     DEFAULT NULL,
  `product_name`  VARCHAR(120)     NOT NULL,
  `product_ref`   VARCHAR(40)      NOT NULL DEFAULT '',
  `customer_name` VARCHAR(160)     NOT NULL,
  `email`         VARCHAR(180)     NOT NULL,
  `phone`         VARCHAR(20)      NOT NULL,
  `adresse`       VARCHAR(255)     NOT NULL,
  `wilaya`        VARCHAR(80)      NOT NULL,
  `code_postal`   VARCHAR(10)      DEFAULT NULL,
  `livraison`     ENUM('standard','express','pickup') NOT NULL DEFAULT 'standard',
  `quantite`      TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `taille`        ENUM('XS','S','M','L','XL','XXL') NOT NULL,
  `couleur`       VARCHAR(40)      DEFAULT NULL,
  `paiement`      ENUM('cod','ccp','baridimob') NOT NULL DEFAULT 'cod',
  `notes`         TEXT             DEFAULT NULL,
  `statut`        ENUM('en_attente','confirmee','expediee','livree','annulee')
                  NOT NULL DEFAULT 'en_attente',
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

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
