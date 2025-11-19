<?php
/**
 * API Promos
 * GET /api/promos.php?code=X - Vérifier un code promo
 */

// S'assurer que les fonctions JSON sont disponibles
if (!function_exists('jsonResponse')) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../supabase_client.php';
    
    function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    function jsonError($message, $statusCode = 400) {
        jsonResponse(['error' => $message], $statusCode);
    }
} else {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../supabase_client.php';
}

global $supabase;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $code = $_GET['code'] ?? null;
    
    if (!$code) {
        jsonError('Paramètre "code" requis', 400);
    }
    
    try {
        $result = $supabase->request('GET', 'coupons?code=eq.' . strtoupper($code) . '&enabled=eq.true', null, false);
        
        if (!empty($result) && isset($result[0])) {
            $coupon = $result[0];
            
            // Vérifier si le coupon est encore valide (date d'expiration)
            $now = time();
            $expiresAt = isset($coupon['expires_at']) ? strtotime($coupon['expires_at']) : null;
            
            if ($expiresAt !== null && $now > $expiresAt) {
                jsonResponse(['valid' => false, 'message' => 'Code promo expiré'], 200);
                return;
            }
            
            // Vérifier le nombre maximum d'utilisations
            if (isset($coupon['max_usage']) && $coupon['max_usage'] > 0) {
                $usageCount = intval($coupon['usage_count'] ?? 0);
                if ($usageCount >= $coupon['max_usage']) {
                    jsonResponse(['valid' => false, 'message' => 'Code promo épuisé'], 200);
                    return;
                }
            }
            
            // Gérer les différents formats de colonnes (type/discount_type, value/discount)
            $discountType = $coupon['type'] ?? $coupon['discount_type'] ?? 'fixed';
            $discountValue = floatval($coupon['value'] ?? $coupon['discount'] ?? 0);
            
            jsonResponse([
                'valid' => true,
                'code' => $coupon['code'],
                'type' => $discountType,
                'value' => $discountValue,
                'min_amount' => floatval($coupon['min_amount'] ?? 0)
            ]);
        } else {
            jsonResponse(['valid' => false, 'message' => 'Code promo invalide'], 200);
        }
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

