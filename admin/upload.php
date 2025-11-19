<?php
/**
 * Endpoint pour l'upload de fichiers vers Supabase Storage
 */

require_once __DIR__ . '/config.php';
checkAuth();

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
    $type = $_POST['type'] ?? 'photo'; // 'photo' ou 'video'
    
    // Vérifier le type de fichier
    $mimeType = $file['type'];
    $isImage = strpos($mimeType, 'image/') === 0;
    $isVideo = strpos($mimeType, 'video/') === 0;
    
    // Déterminer le bucket
    if ($type === 'video' || $isVideo) {
        $bucket = 'videos';
        if (!$isVideo) {
            throw new Exception('Le fichier doit être une vidéo');
        }
    } else {
        $bucket = 'photos';
        if (!$isImage) {
            throw new Exception('Le fichier doit être une image');
        }
    }
    
    // Vérifier la taille (max 50MB pour les vidéos, 10MB pour les photos)
    $maxSize = $bucket === 'videos' ? 50 * 1024 * 1024 : 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Fichier trop volumineux. Maximum: ' . ($maxSize / 1024 / 1024) . 'MB');
    }
    
    // Upload vers Supabase Storage
    $url = $supabaseStorage->uploadFile(
        $bucket,
        $file['tmp_name'],
        $file['name'],
        $mimeType
    );
    
    echo json_encode([
        'success' => true,
        'url' => $url,
        'bucket' => $bucket,
        'filename' => $file['name'],
        'size' => $file['size']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

