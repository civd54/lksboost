<?php
// process_payment.php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/api.php';
require_once 'includes/functions.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode non autorisée');
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Token de sécurité invalide');
}

// Récupérer les données
$order_id = intval($_POST['order_id']);
$payment_method = $_POST['payment_method'] ?? 'stripe';

// Récupérer la commande
$db = getDB();
$order = $db->get_row(
    "SELECT o.*, s.service_id as exobooster_service_id 
     FROM orders o 
     JOIN services s ON o.service_id = s.id 
     WHERE o.id = ?",
    [$order_id]
);

if (!$order || $order['status'] !== 'pending') {
    die('Commande invalide');
}

try {
    if ($payment_method === 'stripe') {
        // Configuration Stripe
        require_once 'vendor/autoload.php';
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        // Créer l'intention de paiement
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $order['price'] * 100, // En centimes
            'currency' => 'eur',
            'payment_method' => $_POST['stripe_payment_method_id'],
            'confirm' => true,
            'return_url' => SITE_URL . '/status.php?order=' . $order['order_number'],
            'metadata' => [
                'order_id' => $order['id'],
                'order_number' => $order['order_number']
            ]
        ]);
        
        if ($paymentIntent->status === 'succeeded') {
            // Paiement réussi - envoyer à Exobooster
            sendToExobooster($order);
            
            // Rediriger vers la page de confirmation
            header('Location: status.php?order=' . $order['order_number']);
            exit();
        } else {
            // Paiement en attente ou échoué
            throw new Exception('Paiement non abouti: ' . $paymentIntent->status);
        }
    } else {
        // Autres méthodes de paiement (à implémenter)
        throw new Exception('Méthode de paiement non implémentée');
    }
} catch (Exception $e) {
    // En cas d'erreur
    error_log('Erreur paiement: ' . $e->getMessage());
    die('Erreur lors du traitement du paiement: ' . $e->getMessage());
}

/**
 * Envoyer la commande à Exobooster
 */
function sendToExobooster($order) {
    $db = getDB();
    $api = new ExoboosterAPI();
    
    try {
        // Envoyer la commande via l'API
        $response = $api->placeOrder(
            $order['exobooster_service_id'],
            $order['link'],
            $order['quantity']
        );
        
        if (isset($response['order'])) {
            // Mettre à jour la commande
            $db->update('orders', [
                'exobooster_order_id' => $response['order'],
                'status' => 'in progress',
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$order['id']]);
            
            // Enregistrer la transaction
            $db->insert('transactions', [
                'order_id' => $order['id'],
                'transaction_id' => 'STRIPE_' . time(),
                'payment_gateway' => 'stripe',
                'amount' => $order['price'],
                'currency' => $order['currency'],
                'status' => 'completed',
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            
            return true;
        } else {
            throw new Exception('Réponse API invalide');
        }
    } catch (Exception $e) {
        // En cas d'erreur API, marquer la commande comme échouée
        $db->update('orders', [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$order['id']]);
        
        throw $e;
    }
}
?>