<?php
/**
 * API Produits
 * GET /api/products.php - Liste tous les produits
 * GET /api/products.php?id=X - Détail d'un produit
 */

global $supabase;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Détail d'un produit
        try {
            $product = $supabase->getProduct($id, false);
            if ($product) {
                jsonResponse($product);
            } else {
                jsonError('Produit non trouvé', 404);
            }
        } catch (Exception $e) {
            jsonError('Erreur: ' . $e->getMessage(), 500);
        }
    } else {
        // Liste des produits
        try {
            $filters = [];
            if (isset($_GET['active'])) {
                $filters['active'] = $_GET['active'] === 'true' || $_GET['active'] === '1';
            }
            
            $products = $supabase->getProducts($filters, false);
            
            // Filtrer par catégorie si fourni
            if (!empty($_GET['category'])) {
                $category = $_GET['category'];
                $products = array_filter($products, function($p) use ($category) {
                    $catId = is_numeric($p['category']) ? $p['category'] : null;
                    $catName = is_string($p['category']) ? $p['category'] : null;
                    return ($catId && $catId == $category) || ($catName && $catName === $category);
                });
            }
            
            // Filtrer par farm si fourni
            if (!empty($_GET['farm'])) {
                $farm = $_GET['farm'];
                $products = array_filter($products, function($p) use ($farm) {
                    $farmId = is_numeric($p['farm']) ? $p['farm'] : null;
                    $farmName = is_string($p['farm']) ? $p['farm'] : null;
                    return ($farmId && $farmId == $farm) || ($farmName && $farmName === $farm);
                });
            }
            
            // Recherche si fournie
            if (!empty($_GET['search'])) {
                $search = strtolower($_GET['search']);
                $products = array_filter($products, function($p) use ($search) {
                    return stripos($p['name'] ?? '', $search) !== false || 
                           stripos($p['description'] ?? '', $search) !== false;
                });
            }
            
            jsonResponse(array_values($products));
        } catch (Exception $e) {
            jsonError('Erreur: ' . $e->getMessage(), 500);
        }
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

