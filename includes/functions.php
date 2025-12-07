<?php
// includes/functions.php

require_once 'database.php';
require_once 'api.php';

function getPopularServices($limit = 6) {
    $db = getDB();
    return $db->get_all(
        "SELECT * FROM services WHERE is_active = 1 AND is_featured = 1 ORDER BY id LIMIT ?",
        [$limit]
    );
}

function getAllActiveServices() {
    $db = getDB();
    return $db->get_all("SELECT * FROM services WHERE is_active = 1 ORDER BY category, name");
}

function getServiceById($id) {
    $db = getDB();
    return $db->get_row("SELECT * FROM services WHERE id = ? AND is_active = 1", [$id]);
}

function getServiceCategories() {
    $db = getDB();
    $result = $db->get_all("SELECT DISTINCT category FROM services WHERE is_active = 1 ORDER BY category");
    $categories = [];
    foreach ($result as $row) {
        $categories[] = $row['category'];
    }
    return $categories;
}

function createOrder($service_id, $link, $quantity, $price) {
    $db = getDB();
    $order_number = 'LKS' . date('Ymd') . strtoupper(substr(uniqid(), -8));
    
    return $db->insert('orders', [
        'order_number' => $order_number,
        'service_id' => $service_id,
        'link' => $link,
        'quantity' => $quantity,
        'price' => $price,
        'currency' => CURRENCY,
        'status' => 'pending',
        'user_ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);
}

function getOrderByNumber($order_number) {
    $db = getDB();
    return $db->get_row(
        "SELECT o.*, s.name as service_name FROM orders o 
         JOIN services s ON o.service_id = s.id 
         WHERE o.order_number = ?",
        [$order_number]
    );
}

function updateOrderStatus($order_id, $status, $exobooster_id = null) {
    $db = getDB();
    $data = ['status' => $status];
    
    if ($exobooster_id) {
        $data['exobooster_order_id'] = $exobooster_id;
    }
    
    return $db->update('orders', $data, 'id = ?', [$order_id]);
}

function getSettings() {
    $db = getDB();
    return $db->get_row("SELECT * FROM settings LIMIT 1");
}

// Fonction pour mettre à jour automatiquement les services depuis l'API
function updateServicesFromAPI() {
    $api = new ExoboosterAPI();
    $db = getDB();
    $settings = getSettings();
    
    try {
        $services = $api->getServices();
        
        if (isset($services['services'])) {
            foreach ($services['services'] as $service) {
                // Calculer notre prix avec marge
                $our_price = $service['rate'] * (1 + ($settings['margin_percentage'] / 100));
                
                // Vérifier si le service existe déjà
                $existing = $db->get_row(
                    "SELECT id FROM services WHERE service_id = ?",
                    [$service['service']]
                );
                
                if ($existing) {
                    // Mettre à jour
                    $db->update('services', [
                        'name' => $service['name'],
                        'category' => $service['category'],
                        'rate_per_1000' => $service['rate'],
                        'min_amount' => $service['min'],
                        'max_amount' => $service['max'],
                        'our_price' => $our_price,
                        'api_data' => json_encode($service),
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'service_id = ?', [$service['service']]);
                } else {
                    // Ajouter
                    $db->insert('services', [
                        'service_id' => $service['service'],
                        'name' => $service['name'],
                        'category' => $service['category'],
                        'rate_per_1000' => $service['rate'],
                        'min_amount' => $service['min'],
                        'max_amount' => $service['max'],
                        'our_price' => $our_price,
                        'api_data' => json_encode($service),
                        'is_active' => 1
                    ]);
                }
            }
            
            return ['success' => true, 'message' => 'Services mis à jour avec succès'];
        }
        
        return ['success' => false, 'message' => 'Aucun service trouvé dans l\'API'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur API: ' . $e->getMessage()];
    }
}
?>