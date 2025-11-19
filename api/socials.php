<?php
/**
 * API Socials
 * GET /api/socials.php - Liste tous les liens sociaux
 */

global $supabase;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $socials = $supabase->getSocials(false);
        jsonResponse($socials ?? []);
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

