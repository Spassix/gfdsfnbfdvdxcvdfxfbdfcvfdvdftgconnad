<?php
/**
 * Client Supabase Storage pour l'upload de fichiers
 */

require_once __DIR__ . '/config.php';

class SupabaseStorage {
    private $url;
    private $key;
    private $serviceKey;
    
    public function __construct() {
        $this->url = rtrim(SUPABASE_URL, '/');
        $this->key = SUPABASE_KEY;
        $this->serviceKey = defined('SUPABASE_SERVICE_KEY') ? SUPABASE_SERVICE_KEY : SUPABASE_KEY;
    }
    
    /**
     * Upload un fichier vers Supabase Storage
     * @param string $bucket Nom du bucket (photos ou videos)
     * @param string $filePath Chemin temporaire du fichier
     * @param string $fileName Nom du fichier
     * @param string $mimeType Type MIME du fichier
     * @return string URL du fichier uploadé
     */
    public function uploadFile($bucket, $filePath, $fileName, $mimeType = null) {
        // Générer un nom de fichier unique
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueName = uniqid() . '_' . time() . '.' . $extension;
        $filePathInBucket = $uniqueName;
        
        // Lire le contenu du fichier
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new Exception('Impossible de lire le fichier');
        }
        
        // URL de l'API Storage
        $url = $this->url . '/storage/v1/object/' . $bucket . '/' . $filePathInBucket;
        
        // Headers
        $headers = [
            'Authorization: Bearer ' . $this->serviceKey,
            'apikey: ' . $this->serviceKey,
            'Content-Type: ' . ($mimeType ?: mime_content_type($filePath) ?: 'application/octet-stream'),
            'x-upsert: true' // Permet d'écraser si le fichier existe déjà
        ];
        
        // Requête cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $fileContent,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erreur cURL: " . $error);
        }
        
        if ($httpCode >= 400) {
            $decoded = json_decode($response, true);
            $errorMsg = is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? 'Erreur HTTP ' . $httpCode) : $response;
            throw new Exception($errorMsg, $httpCode);
        }
        
        // Retourner l'URL publique du fichier
        return $this->url . '/storage/v1/object/public/' . $bucket . '/' . $filePathInBucket;
    }
    
    /**
     * Supprimer un fichier de Supabase Storage
     * @param string $bucket Nom du bucket
     * @param string $filePath Chemin du fichier dans le bucket
     */
    public function deleteFile($bucket, $filePath) {
        $url = $this->url . '/storage/v1/object/' . $bucket . '/' . $filePath;
        
        $headers = [
            'Authorization: Bearer ' . $this->serviceKey,
            'apikey: ' . $this->serviceKey,
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erreur cURL: " . $error);
        }
        
        if ($httpCode >= 400 && $httpCode !== 404) {
            $decoded = json_decode($response, true);
            $errorMsg = is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? 'Erreur HTTP ' . $httpCode) : $response;
            throw new Exception($errorMsg, $httpCode);
        }
        
        return true;
    }
    
    /**
     * Extraire le chemin du fichier depuis une URL Supabase Storage
     * @param string $url URL complète du fichier
     * @return array ['bucket' => string, 'path' => string] ou null
     */
    public function parseStorageUrl($url) {
        if (empty($url)) return null;
        
        // Pattern: https://xxx.supabase.co/storage/v1/object/public/bucket/path
        $pattern = '/\/storage\/v1\/object\/public\/([^\/]+)\/(.+)$/';
        if (preg_match($pattern, $url, $matches)) {
            return [
                'bucket' => $matches[1],
                'path' => $matches[2]
            ];
        }
        
        return null;
    }
}

$supabaseStorage = new SupabaseStorage();

