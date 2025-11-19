<?php
/**
 * Client Supabase pour PHP
 */

require_once __DIR__ . '/config.php';

class SupabaseClient {
    private $url;
    private $key;
    private $serviceKey;
    
    public function __construct() {
        $this->url = rtrim(SUPABASE_URL, '/');
        $this->key = SUPABASE_KEY;
        $this->serviceKey = defined('SUPABASE_SERVICE_KEY') ? SUPABASE_SERVICE_KEY : SUPABASE_KEY;
    }
    
    public function request($method, $endpoint, $data = null, $useServiceKey = true) {
        $url = $this->url . '/rest/v1/' . ltrim($endpoint, '/');
        // Utiliser la service key pour les opérations admin, sinon l'anon key
        $key = $useServiceKey ? $this->serviceKey : (SUPABASE_ANON_KEY ?? $this->key);
        
        $ch = curl_init($url);
        
        $headers = [
            'apikey: ' . $key,
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        if ($data !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erreur cURL: " . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? 'Erreur HTTP ' . $httpCode) : $response;
            throw new Exception($errorMsg, $httpCode);
        }
        
        return $decoded;
    }
    
    public function createOrder($orderData) {
        return $this->request('POST', 'orders', $orderData);
    }
    
    public function getOrders($filters = []) {
        $query = 'orders?order=created_at.desc';
        if (!empty($filters['status'])) {
            $query .= '&status=eq.' . urlencode($filters['status']);
        }
        if (!empty($filters['limit'])) {
            $query .= '&limit=' . (int)$filters['limit'];
        }
        return $this->request('GET', $query);
    }
    
    public function getOrder($id) {
        $result = $this->request('GET', 'orders?id=eq.' . $id);
        return !empty($result) ? $result[0] : null;
    }
    
    public function updateOrderStatus($id, $status) {
        return $this->request('PATCH', 'orders?id=eq.' . $id, ['status' => $status]);
    }
    
    public function getProducts($filters = [], $useServiceKey = true) {
        $query = 'products?select=*,categories(*),farms(*)&order=created_at.desc';
        
        if (!empty($filters['active'])) {
            $query .= '&active=eq.' . ($filters['active'] ? 'true' : 'false');
        }
        
        return $this->request('GET', $query, null, $useServiceKey);
    }
    
    public function getProduct($id, $useServiceKey = true) {
        $result = $this->request('GET', 'products?id=eq.' . $id . '&select=*,categories(*),farms(*)', null, $useServiceKey);
        return !empty($result) ? $result[0] : null;
    }
    
    public function getCategories($useServiceKey = true) {
        return $this->request('GET', 'categories?enabled=eq.true&order=sort_order.asc', null, $useServiceKey);
    }
    
    public function getFarms($useServiceKey = true) {
        return $this->request('GET', 'farms?enabled=eq.true&order=name.asc', null, $useServiceKey);
    }
    
    /**
     * Vérifie les identifiants d'un administrateur
     * Utilise password_verify() pour comparer le mot de passe avec le hash bcrypt
     * 
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe en clair
     * @return array|false Retourne les données de l'admin (sans password_hash) ou false
     */
    public function verifyAdmin($username, $password) {
        // Utiliser le service key pour accéder à la table admins (protégée par RLS)
        $result = $this->request('GET', 'admins?username=eq.' . urlencode($username) . '&active=eq.true', null, true);
        
        if (empty($result)) {
            // Ne pas révéler si l'utilisateur existe ou non (timing attack protection)
            // On fait quand même un hash pour avoir un temps de traitement similaire
            password_verify($password, '$2y$12$dummy.hash.to.prevent.timing.attacks');
            return false;
        }
        
        $admin = $result[0];
        
        // Vérifier le mot de passe avec password_verify (sécurisé contre timing attacks)
        if (password_verify($password, $admin['password_hash'])) {
            // Mettre à jour la date de dernière connexion
            try {
                $this->request('PATCH', 'admins?id=eq.' . $admin['id'], [
                    'last_login' => date('Y-m-d\TH:i:s.u\Z'),
                    'updated_at' => date('Y-m-d\TH:i:s.u\Z')
                ], true);
            } catch (Exception $e) {
                // Ignorer l'erreur de mise à jour, ce n'est pas critique
            }
            
            // Retourner les données admin SANS le password_hash
            unset($admin['password_hash']);
            return $admin;
        }
        
        return false;
    }
    
    public function getSettings($key, $useServiceKey = true) {
        $result = $this->request('GET', 'settings?key=eq.' . urlencode($key), null, $useServiceKey);
        return !empty($result) && isset($result[0]['value']) ? $result[0]['value'] : null;
    }
    
    public function getReviews($filters = [], $useServiceKey = true) {
        $query = 'reviews?order=created_at.desc';
        if (isset($filters['approved'])) {
            $query .= '&approved=eq.' . ($filters['approved'] ? 'true' : 'false');
        }
        return $this->request('GET', $query, null, $useServiceKey);
    }
    
    public function createProduct($data) {
        return $this->request('POST', 'products', $data);
    }
    
    public function updateProduct($id, $data) {
        return $this->request('PATCH', 'products?id=eq.' . $id, $data);
    }
    
    public function deleteProduct($id) {
        return $this->request('DELETE', 'products?id=eq.' . $id);
    }
    
    public function createCategory($data) {
        return $this->request('POST', 'categories', $data);
    }
    
    public function updateCategory($id, $data) {
        return $this->request('PATCH', 'categories?id=eq.' . $id, $data);
    }
    
    public function deleteCategory($id) {
        return $this->request('DELETE', 'categories?id=eq.' . $id);
    }
    
    public function createFarm($data) {
        return $this->request('POST', 'farms', $data);
    }
    
    public function updateFarm($id, $data) {
        return $this->request('PATCH', 'farms?id=eq.' . $id, $data);
    }
    
    public function deleteFarm($id) {
        return $this->request('DELETE', 'farms?id=eq.' . $id);
    }
    
    public function updateReview($id, $data) {
        return $this->request('PATCH', 'reviews?id=eq.' . $id, $data);
    }
    
    public function deleteReview($id) {
        return $this->request('DELETE', 'reviews?id=eq.' . $id);
    }
    
    public function createPromo($data) {
        return $this->request('POST', 'coupons', $data);
    }
    
    public function updatePromo($id, $data) {
        return $this->request('PATCH', 'coupons?id=eq.' . $id, $data);
    }
    
    public function deletePromo($id) {
        return $this->request('DELETE', 'coupons?id=eq.' . $id);
    }
    
    public function getPromos($useServiceKey = true) {
        return $this->request('GET', 'coupons?order=created_at.desc', null, $useServiceKey);
    }
    
    public function saveSetting($key, $value, $useServiceKey = true) {
        // Vérifier si le setting existe
        $existing = $this->request('GET', 'settings?key=eq.' . urlencode($key), null, $useServiceKey);
        
        if (!empty($existing)) {
            // Mettre à jour
            return $this->request('PATCH', 'settings?key=eq.' . urlencode($key), [
                'value' => $value,
                'updated_at' => date('Y-m-d\TH:i:s.u\Z')
            ], $useServiceKey);
        } else {
            // Créer
            return $this->request('POST', 'settings', [
                'key' => $key,
                'value' => $value,
                'created_at' => date('Y-m-d\TH:i:s.u\Z'),
                'updated_at' => date('Y-m-d\TH:i:s.u\Z')
            ], $useServiceKey);
        }
    }
    
    public function getSocials($useServiceKey = true) {
        return $this->request('GET', 'socials?enabled=eq.true&order=sort_order.asc', null, $useServiceKey);
    }
}

$supabase = new SupabaseClient();

