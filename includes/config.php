<?php
// Configuration de base
define('SITE_NAME', 'Exobooster Revendeur');
define('SITE_URL', 'https://votresite.com');
define('CURRENCY', '€');

// API Exobooster
define('EXOBOOSTER_API_KEY', 'votre_api_key');
define('EXOBOOSTER_API_URL', 'https://exobooster.com/api/v2');

// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'exobooster_site');
define('DB_USER', 'username');
define('DB_PASS', 'password');

// Paiement
define('STRIPE_PUBLIC_KEY', 'pk_test_...');
define('STRIPE_SECRET_KEY', 'sk_test_...');
define('PAYPAL_CLIENT_ID', '...');
define('PAYPAL_SECRET', '...');

// Autres paramètres
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>