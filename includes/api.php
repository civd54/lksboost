<?php
require_once 'config.php';

class ExoboosterAPI {
    private $api_key;
    private $api_url;
    
    public function __construct() {
        $this->api_key = EXOBOOSTER_API_KEY;
        $this->api_url = EXOBOOSTER_API_URL;
    }
    
    // Récupérer la liste des services
    public function getServices() {
        $url = $this->api_url . "?key=" . $this->api_key . "&action=services";
        $response = $this->makeRequest($url);
        return json_decode($response, true);
    }
    
    // Passer une commande
    public function placeOrder($service_id, $link, $quantity) {
        $url = $this->api_url . "?key=" . $this->api_key . "&action=add&service=" . $service_id . "&link=" . urlencode($link) . "&quantity=" . $quantity;
        $response = $this->makeRequest($url);
        return json_decode($response, true);
    }
    
    // Vérifier le statut d'une commande
    public function getOrderStatus($order_id) {
        $url = $this->api_url . "?key=" . $this->api_key . "&action=status&order=" . $order_id;
        $response = $this->makeRequest($url);
        return json_decode($response, true);
    }
    
    // Requête HTTP
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
?>