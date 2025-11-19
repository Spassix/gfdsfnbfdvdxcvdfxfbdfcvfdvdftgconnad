<?php
/**
 * API Catégories
 * GET /api/categories.php - Liste toutes les catégories
 */

global $supabase;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $categories = $supabase->getCategories(false);
        jsonResponse($categories);
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

