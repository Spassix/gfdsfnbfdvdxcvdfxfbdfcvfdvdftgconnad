<?php
/**
 * API Loading Page
 * GET /api/loading_page.php - Récupère la configuration de la page de chargement
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
    try {
        $result = $supabase->request('GET', 'loading_page?order=created_at.desc&limit=1', null, false);
        if (!empty($result) && isset($result[0])) {
            jsonResponse($result[0]);
        } else {
            jsonResponse(['enabled' => false]);
        }
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

