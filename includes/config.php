<?php
// includes/config.php

// =========== CONFIGURATION DE BASE ===========
define('SITE_NAME', 'LKSBoost');
define('SITE_URL', 'https://votredomaine.com');
define('SITE_EMAIL', 'contact@votredomaine.com');
define('CURRENCY', '€');
define('DEFAULT_MARGIN', 30.00); // 30% de marge

// =========== ENVIRONNEMENT ===========
define('ENVIRONMENT', 'development'); // 'development' ou 'production'

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// =========== BASE DE DONNÉES ===========
define('DB_HOST', 'localhost');
define('DB_NAME', 'lksboost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =========== API EXOBOOSTER ===========
define('EXOBOOSTER_API_KEY', 'VOTRE_CLE_API'); // À obtenir sur exobooster.com
define('EXOBOOSTER_API_URL', 'https://exobooster.com/api/v2');
define('EXOBOOSTER_TIMEOUT', 30);

// =========== PAIEMENT STRIPE ===========
define('STRIPE_PUBLIC_KEY', 'pk_test_votre_cle'); // À obtenir sur stripe.com
define('STRIPE_SECRET_KEY', 'sk_test_votre_cle'); // À obtenir sur stripe.com

// =========== PAIEMENT PAYPAL ===========
define('PAYPAL_CLIENT_ID', 'votre_client_id'); // À obtenir sur developer.paypal.com
define('PAYPAL_SECRET', 'votre_secret'); // À obtenir sur developer.paypal.com
define('PAYPAL_ENVIRONMENT', 'sandbox'); // 'sandbox' ou 'live'

// =========== SÉCURITÉ ===========
define('CSRF_TOKEN_LIFETIME', 3600); // 1 heure
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// =========== AUTRES PARAMÈTRES ===========
define('ITEMS_PER_PAGE', 12);
define('ORDER_UPDATE_INTERVAL', 300); // 5 minutes

// Démarrer la session
session_start();

// Générer un token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>