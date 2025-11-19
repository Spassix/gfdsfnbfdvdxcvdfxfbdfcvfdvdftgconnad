<?php
/**
 * API Cart
 * GET /api/cart.php - Récupère le panier (session)
 * POST /api/cart.php - Ajoute un produit au panier
 * PUT /api/cart.php - Met à jour le panier
 * DELETE /api/cart.php - Vide le panier ou supprime un item
 */

require_once __DIR__ . '/security.php';

global $supabase;
session_start();

$method = $_SERVER['REQUEST_METHOD'];

// Initialiser le panier si nécessaire
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($method === 'GET') {
    // Retourner le panier
    jsonResponse([
        'items' => $_SESSION['cart'] ?? [],
        'count' => count($_SESSION['cart'] ?? []),
        'total' => calculateCartTotal($_SESSION['cart'] ?? [])
    ]);
} elseif ($method === 'POST') {
    // Ajouter un produit au panier
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation des données
    validateInput($data, [
        'product_id' => ['required' => true, 'type' => 'string', 'min' => 1],
        'variant' => ['required' => true, 'type' => 'string', 'min' => 1],
        'quantity' => ['required' => false, 'type' => 'int', 'min' => 1, 'max' => 100],
        'price' => ['required' => false, 'type' => 'float', 'min' => 0]
    ]);
    
    if (empty($data['product_id']) || empty($data['variant'])) {
        jsonError('Champs requis: product_id, variant', 400);
    }
    
    try {
        $product = $supabase->getProduct($data['product_id']);
        if (!$product) {
            jsonError('Produit non trouvé', 404);
        }
        
        // Sanitizer les données
        $cartItem = [
            'product_id' => sanitizeInput($data['product_id']),
            'product_name' => sanitizeInput($product['name'] ?? ''),
            'variant' => sanitizeInput($data['variant']),
            'quantity' => max(1, min(100, intval($data['quantity'] ?? 1))), // Limiter entre 1 et 100
            'price' => max(0, floatval($data['price'] ?? 0)),
            'image' => sanitizeInput($product['photo'] ?? $product['image'] ?? '')
        ];
        
        $_SESSION['cart'][] = $cartItem;
        
        jsonResponse([
            'success' => true,
            'cart' => [
                'items' => $_SESSION['cart'],
                'count' => count($_SESSION['cart']),
                'total' => calculateCartTotal($_SESSION['cart'])
            ]
        ], 201);
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} elseif ($method === 'PUT') {
    // Mettre à jour le panier
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['items']) && is_array($data['items'])) {
        $_SESSION['cart'] = $data['items'];
        jsonResponse([
            'success' => true,
            'cart' => [
                'items' => $_SESSION['cart'],
                'count' => count($_SESSION['cart']),
                'total' => calculateCartTotal($_SESSION['cart'])
            ]
        ]);
    } else {
        jsonError('Champ "items" requis', 400);
    }
} elseif ($method === 'DELETE') {
    // Vider le panier ou supprimer un item
    $index = $_GET['index'] ?? null;
    
    if ($index !== null) {
        // Supprimer un item spécifique
        $index = intval($index);
        if (isset($_SESSION['cart'][$index])) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Réindexer
        }
    } else {
        // Vider tout le panier
        $_SESSION['cart'] = [];
    }
    
    jsonResponse([
        'success' => true,
        'cart' => [
            'items' => $_SESSION['cart'],
            'count' => count($_SESSION['cart']),
            'total' => calculateCartTotal($_SESSION['cart'])
        ]
    ]);
} else {
    jsonError('Méthode non autorisée', 405);
}

// Fonction pour calculer le total du panier
function calculateCartTotal($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $price = is_numeric($item['price']) ? $item['price'] : 0;
        $quantity = intval($item['quantity'] ?? 1);
        $total += $price * $quantity;
    }
    return $total;
}

