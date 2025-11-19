<?php
/**
 * API Farms
 * GET /api/farms.php - Liste toutes les farms
 */

global $supabase;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $farms = $supabase->getFarms(false);
        jsonResponse($farms);
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

