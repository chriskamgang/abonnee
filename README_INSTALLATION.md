# Estuaire Abonnées — Guide d'installation

## 1. Pré-requis serveur LWS
- PHP 8.0+
- MySQL 5.7+
- Extension cURL activée

## 2. Configuration

Ouvre `config.php` et remplis :

```php
define('PEAKSMM_API_KEY',    'ta_cle_peaksmm');
define('FREEMOPAY_APP_KEY',    'ton_app_key_freemopay');
define('FREEMOPAY_SECRET_KEY', 'ton_secret_key_freemopay');
define('SITE_URL',     'https://ton-domaine.com');
define('SUPPORT_WHATSAPP', '237XXXXXXXXX');
```

## 3. Base de données

Dans phpMyAdmin (LWS) :
- Crée une base `estuaire_abonnees`
- Importe le fichier `setup.sql`

Puis mets à jour `config.php` avec tes infos MySQL.

## 4. Upload des fichiers

Via FTP (FileZilla) ou le gestionnaire LWS :
- Upload tous les fichiers à la racine de ton domaine (public_html)

## 5. Importer les services PeakSMM

Ouvre dans ton navigateur :
```
https://ton-domaine.com/admin/sync_services.php?secret=CHANGEZ_CE_SECRET
```

Pense à changer `CHANGEZ_CE_SECRET` dans le fichier.

## 6. Accès admin

```
https://ton-domaine.com/admin/dashboard.php?secret=CHANGEZ_CE_SECRET
```

## 7. Taux de marge

Dans `config.php` :
```php
define('MARGE_POURCENT', 60);  // 60% de marge = tu gagnes 60% sur chaque commande
define('USD_TO_XAF', 620);     // Taux de change USD/XAF
```
