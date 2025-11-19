<?php
/**
 * Endpoint public pour l'upload de photos d'avis vers Supabase Storage
 */

require_once __DIR__ . '/../telegram_guard.php';
require_once __DIR__ . '/../supabase_storage.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Aucun fichier uploadé ou erreur lors de l\'upload');
    }
    
    $file = $_FILES['file'];
    
    // Vérifier le type de fichier (uniquement images)
    $mimeType = $file['type'];
    $isImage = strpos($mimeType, 'image/') === 0;
    
    if (!$isImage) {
        throw new Exception('Le fichier doit être une image');
    }
    
    // Vérifier la taille (max 10MB pour les photos)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Fichier trop volumineux. Maximum: 10MB');
    }
    
    // Upload vers Supabase Storage (bucket photos)
    $url = $supabaseStorage->uploadFile(
        'photos',
        $file['tmp_name'],
        'review_' . time() . '_' . $file['name'],
        $mimeType
    );
    
    echo json_encode([
        'success' => true,
        'url' => $url,
        'filename' => $file['name'],
        'size' => $file['size']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

