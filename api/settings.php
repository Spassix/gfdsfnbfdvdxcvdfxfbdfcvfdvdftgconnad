<?php
/**
 * API Settings
 * GET /api/settings.php?key=X - Récupère un setting
 */

global $supabase;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $key = $_GET['key'] ?? null;
    
    if (!$key) {
        jsonError('Paramètre "key" requis', 400);
    }
    
    try {
        $setting = $supabase->getSettings($key, false);
        if ($setting !== null) {
            jsonResponse($setting);
        } else {
            jsonResponse(null);
        }
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

