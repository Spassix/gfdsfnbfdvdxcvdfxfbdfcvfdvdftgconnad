<?php
/**
 * Fonctions helper pour le panel admin
 */

// Fonction pour normaliser les données produit (gère category_id/category et farm_id/farm)
function normalizeProduct($product) {
    if (!$product) return null;
    
    // Normaliser category
    if (isset($product['category_id'])) {
        $product['category'] = $product['category_id'];
    } elseif (isset($product['categories']) && is_array($product['categories']) && !empty($product['categories'])) {
        $product['category'] = $product['categories'][0]['id'] ?? null;
    }
    
    // Normaliser farm
    if (isset($product['farm_id'])) {
        $product['farm'] = $product['farm_id'];
    } elseif (isset($product['farms']) && is_array($product['farms']) && !empty($product['farms'])) {
        $product['farm'] = $product['farms'][0]['id'] ?? null;
    }
    
    // Normaliser photo
    if (isset($product['photo_url'])) {
        $product['photo'] = $product['photo_url'];
    }
    if (isset($product['image'])) {
        $product['photo'] = $product['image'];
    }
    
    // Normaliser video
    if (isset($product['video_url'])) {
        $product['video'] = $product['video_url'];
    }
    if (isset($product['media'])) {
        $product['video'] = $product['media'];
    }
    
    return $product;
}

// Normaliser un tableau de produits
function normalizeProducts($products) {
    return array_map('normalizeProduct', $products);
}

