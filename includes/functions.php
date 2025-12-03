<?php
require_once 'database.php';
require_once 'api.php';

// Récupérer les services populaires
function getPopularServices($limit = 6) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY id LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Récupérer tous les services actifs
function getAllActiveServices() {
    $db = getDB();
    $result = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY category, name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Récupérer les catégories de services
function getServiceCategories() {
    $db = getDB();
    $result = $db->query("SELECT DISTINCT category FROM services WHERE is_active = 1 ORDER BY category");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    return $categories;
}

// Récupérer un service par ID
function getServiceById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Créer une commande
function createOrder($service_id, $link, $quantity, $price) {
    $db = getDB();
    $order_number = generateOrderNumber();
    
    $stmt = $db->prepare("INSERT INTO orders (order_number, service_id, link, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisid", $order_number, $service_id, $link, $quantity, $price);
    
    if ($stmt->execute()) {
        return $db->insert_id;
    }
    return false;
}

// Générer un numéro de commande unique
function generateOrderNumber() {
    return 'EXO' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

// Traitement du paiement (simplifié)
function processPayment($order_id, $method, $amount) {
    $db = getDB();
    
    // En production, intégrer avec les APIs de paiement réelles
    $transaction_id = 'TXN_' . uniqid();
    
    $stmt = $db->prepare("INSERT INTO transactions (order_id, transaction_id, payment_gateway, amount, status) VALUES (?, ?, ?, ?, 'completed')");
    $stmt->bind_param("issd", $order_id, $transaction_id, $method, $amount);
    
    return $stmt->execute();
}

// Envoyer la commande à Exobooster
function sendOrderToExobooster($order) {
    $api = new ExoboosterAPI();
    $service = getServiceById($order['service_id']);
    
    return $api->placeOrder($service['service_id'], $order['link'], $order['quantity']);
}

// Mettre à jour la commande avec l'ID Exobooster
function updateOrderWithExoboosterId($order_id, $exobooster_order_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE orders SET exobooster_order_id = ?, status = 'in progress' WHERE id = ?");
    $stmt->bind_param("ii", $exobooster_order_id, $order_id);
    return $stmt->execute();
}

// Fonctions d'administration
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function getTotalOrders() {
    $db = getDB();
    $result = $db->query("SELECT COUNT(*) as total FROM orders");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getPendingOrders() {
    $db = getDB();
    $result = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getTotalRevenue() {
    $db = getDB();
    $result = $db->query("SELECT SUM(price) as total FROM orders WHERE status != 'cancelled'");
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getRecentOrders($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT o.*, s.name as service_name FROM orders o JOIN services s ON o.service_id = s.id ORDER BY o.created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getStatusBadgeColor($status) {
    switch ($status) {
        case 'completed': return 'success';
        case 'in progress': return 'primary';
        case 'pending': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>