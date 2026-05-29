-- Estuaire Abonnées - Base de données
CREATE DATABASE IF NOT EXISTS estuaire_abonnees CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE estuaire_abonnees;

CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_ref       VARCHAR(50) UNIQUE NOT NULL,     -- référence unique affichée au client
    email           VARCHAR(150),
    phone           VARCHAR(20) NOT NULL,            -- numéro MTN/Orange pour le paiement
    platform        VARCHAR(50) NOT NULL,            -- tiktok, instagram, facebook...
    service_type    VARCHAR(50) NOT NULL,            -- followers, likes, views, comments
    service_name    VARCHAR(200) NOT NULL,
    peaksmm_service INT NOT NULL,                    -- ID service PeakSMM
    social_url      TEXT NOT NULL,                   -- lien du compte/post
    quantity        INT NOT NULL,
    price_xaf       INT NOT NULL,                    -- prix payé en XAF
    price_usd       DECIMAL(10,4) NOT NULL,          -- coût fournisseur en USD
    freemopay_ref   VARCHAR(100),                    -- référence transaction Freemopay
    peaksmm_order   INT,                             -- ID commande PeakSMM
    status          ENUM('pending','paid','processing','completed','partial','failed','cancelled') DEFAULT 'pending',
    status_message  TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services_cache (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    peaksmm_id      INT UNIQUE NOT NULL,
    name            VARCHAR(200),
    category        VARCHAR(100),
    platform        VARCHAR(50),
    service_type    VARCHAR(50),
    rate_usd        DECIMAL(10,4),     -- prix pour 1000 en USD
    min_qty         INT,
    max_qty         INT,
    active          TINYINT DEFAULT 1,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
