<?php
// includes/api.php

class ExoboosterAPI {
    private $api_key;
    private $api_url;
    
    public function __construct() {
        $this->api_key = EXOBOOSTER_API_KEY;
        $this->api_url = EXOBOOSTER_API_URL;
    }
    
    public function getServices() {
        $url = $this->api_url . "?key=" . $this->api_key . "&action=services";
        return $this->makeRequest($url);
    }
    
    public function placeOrder($service_id, $link, $quantity) {
        $url = $this->api_url . "?key=" . $this->api_key . "&action=add&service=" . $service_id . "&link=" . urlencode($link) . "&quantity=" . $quantity;
        return $this->makeRequest($url);
    }
    
    public function getOrderStatus($order_id) {
        $url = $this->api_url . "?key=" . $this->api_key . "&action=status&order=" . $order_id;
        return $this->makeRequest($url);
    }
    
    public function getBalance() {
        $url = $this->api_url . "?key=" . $this->api_key . "&action=balance";
        $response = $this->makeRequest($url);
        return isset($response['balance']) ? floatval($response['balance']) : 0;
    }
    
    private function makeRequest($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'LKSBoost v1.0'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
?>