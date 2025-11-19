<?php
/**
 * API REST pour la boutique
 * Tous les endpoints retournent du JSON
 * Sécurisé avec rate limiting, CORS, validation
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../supabase_client.php';

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    initSecurity(['rate_limit' => false, 'cors' => true, 'auth' => false, 'csrf' => false]);
    http_response_code(200);
    exit;
}

// Initialiser la sécurité pour toutes les requêtes
// Rate limiting activé, CORS activé, pas d'auth pour les endpoints publics
initSecurity(['rate_limit' => true, 'cors' => true, 'auth' => false, 'csrf' => false]);

header('Content-Type: application/json; charset=utf-8');

// Fonction pour retourner une réponse JSON
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Fonction pour retourner une erreur
function jsonError($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}

// Récupérer le chemin de la requête
$path = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($path, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$path = trim($path, '/');
$parts = explode('/', $path);

// Router basique
$endpoint = $parts[0] ?? '';

try {
    switch ($endpoint) {
        case 'products':
            require __DIR__ . '/products.php';
            break;
        case 'categories':
            require __DIR__ . '/categories.php';
            break;
        case 'farms':
            require __DIR__ . '/farms.php';
            break;
        case 'settings':
            require __DIR__ . '/settings.php';
            break;
        case 'reviews':
            require __DIR__ . '/reviews.php';
            break;
        case 'promos':
            require __DIR__ . '/promos.php';
            break;
        case 'cart':
            require __DIR__ . '/cart.php';
            break;
        case 'orders':
            require __DIR__ . '/orders.php';
            break;
        case 'socials':
            require __DIR__ . '/socials.php';
            break;
        case 'loading_page':
            require __DIR__ . '/loading_page.php';
            break;
        default:
            jsonError('Endpoint non trouvé', 404);
    }
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}

