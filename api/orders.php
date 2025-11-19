<?php
/**
 * API Orders
 * POST /api/orders.php - Créer une nouvelle commande
 */

require_once __DIR__ . '/security.php';

global $supabase;
session_start();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation stricte des données
    validateInput($data, [
        'items' => ['required' => true, 'type' => 'array'],
        'client' => ['required' => true, 'type' => 'array']
    ]);
    
    if (empty($data['items']) || !is_array($data['items'])) {
        jsonError('Le panier ne peut pas être vide', 400);
    }
    
    if (empty($data['client'])) {
        jsonError('Informations client requises', 400);
    }
    
    $client = $data['client'];
    validateInput($client, [
        'name' => ['required' => true, 'type' => 'string', 'min' => 2, 'max' => 100],
        'phone' => ['required' => true, 'type' => 'string', 'min' => 8, 'max' => 20],
        'email' => ['required' => true, 'type' => 'email']
    ]);
    
    try {
        // Calculer le total
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $price = is_numeric($item['price']) ? floatval($item['price']) : 0;
            $quantity = intval($item['quantity'] ?? 1);
            $subtotal += $price * $quantity;
        }
        
        // Appliquer le code promo si fourni
        $discount = 0;
        if (!empty($data['promo_code'])) {
            try {
                $promoResult = $supabase->request('GET', 'coupons?code=eq.' . strtoupper($data['promo_code']) . '&enabled=eq.true', null, false);
                if (!empty($promoResult) && isset($promoResult[0])) {
                    $coupon = $promoResult[0];
                    $minAmount = floatval($coupon['min_amount'] ?? 0);
                    if ($subtotal >= $minAmount) {
                        if (($coupon['discount_type'] ?? 'fixed') === 'percentage') {
                            $discount = $subtotal * (floatval($coupon['discount'] ?? 0) / 100);
                        } else {
                            $discount = floatval($coupon['discount'] ?? 0);
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignorer l'erreur de promo code
            }
        }
        
        $total = $subtotal - $discount;
        
        // Sanitizer les données avant création
        $orderData = [
            'client_name' => sanitizeInput($client['name']),
            'client_phone' => sanitizeInput($client['phone']),
            'client_email' => sanitizeInput($client['email']),
            'client_address' => !empty($client['address']) ? sanitizeInput($client['address']) : null,
            'service' => !empty($data['service']) ? sanitizeInput($data['service']) : null,
            'time_slot' => !empty($data['time_slot']) ? sanitizeInput($data['time_slot']) : null,
            'payment_method' => !empty($data['payment_method']) ? sanitizeInput($data['payment_method']) : null,
            'items' => json_encode($data['items']),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'promo_code' => !empty($data['promo_code']) ? sanitizeInput(strtoupper($data['promo_code'])) : null,
            'status' => 'pending',
            'created_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        $result = $supabase->createOrder($orderData);
        
        // Vider le panier après création de la commande
        $_SESSION['cart'] = [];
        
        jsonResponse([
            'success' => true,
            'order' => $result,
            'order_id' => $result['id'] ?? null
        ], 201);
    } catch (Exception $e) {
        jsonError('Erreur lors de la création de la commande: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

