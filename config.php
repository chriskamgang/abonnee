<?php
// ============================================================
//  ESTUAIRE ABONNEES - Configuration
// ============================================================

// Shaker SMM API
define('PEAKSMM_API_URL', 'https://shaker.co.ke/api/v2');
define('PEAKSMM_API_KEY', 'VOTRE_CLE_SHAKER_ICI');

// Freemopay API
define('FREEMOPAY_BASE_URL', 'https://api-v2.freemopay.com');
define('FREEMOPAY_APP_KEY',    'VOTRE_APP_KEY_FREEMOPAY');
define('FREEMOPAY_SECRET_KEY', 'VOTRE_SECRET_KEY_FREEMOPAY');

// Site
define('SITE_NAME',    'Estuaire Abonnées');
define('SITE_URL',     'https://votre-domaine.com');
define('WEBHOOK_URL',  SITE_URL . '/webhook.php');
define('SUPPORT_WHATSAPP', '237600000000');

// Taux de change USD → XAF + marge bénéficiaire
define('USD_TO_XAF',   620);    // 1 USD = 620 XAF
define('MARGE_POURCENT', 60);   // 60% de marge sur le prix fournisseur

// Base de données MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'estuaire_abonnees');
define('DB_USER', 'root');
define('DB_PASS', '');
