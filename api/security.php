<?php
/**
 * Système de sécurité pour les API
 */

// Configuration de sécurité
define('API_RATE_LIMIT', 100); // Nombre de requêtes par minute
define('API_RATE_LIMIT_WINDOW', 60); // Fenêtre en secondes
define('API_ALLOWED_ORIGINS', [
    'http://localhost:8000',
    'http://localhost',
    'https://votre-domaine.com' // Remplacez par votre domaine
]);

// Fonction pour vérifier l'origine (CORS)
function checkOrigin() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    
    // Si pas d'origine, autoriser (requêtes directes)
    if (empty($origin)) {
        return true;
    }
    
    // Extraire le domaine de l'origine
    $parsed = parse_url($origin);
    $domain = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '');
    
    // Vérifier si le domaine est autorisé
    foreach (API_ALLOWED_ORIGINS as $allowed) {
        if ($domain === $allowed || strpos($domain, $allowed) === 0) {
            return true;
        }
    }
    
    return false;
}

// Fonction pour gérer CORS de manière sécurisée
function handleCORS() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Si pas d'origine (requête directe), autoriser
    if (empty($origin)) {
        header('Access-Control-Allow-Origin: *');
    } elseif (checkOrigin()) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Origin non autorisée']);
        exit;
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 24 heures
}

// Rate limiting basique (utilise les sessions)
function checkRateLimit($identifier = null) {
    session_start();
    
    // Utiliser l'IP comme identifiant par défaut
    if (!$identifier) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = 'rate_limit_' . md5($identifier);
    $now = time();
    
    // Récupérer les requêtes précédentes
    $requests = $_SESSION[$key] ?? [];
    
    // Nettoyer les requêtes anciennes (hors de la fenêtre)
    $requests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < API_RATE_LIMIT_WINDOW;
    });
    
    // Vérifier la limite
    if (count($requests) >= API_RATE_LIMIT) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Trop de requêtes. Limite: ' . API_RATE_LIMIT . ' requêtes par minute.',
            'retry_after' => API_RATE_LIMIT_WINDOW
        ]);
        exit;
    }
    
    // Ajouter la requête actuelle
    $requests[] = $now;
    $_SESSION[$key] = $requests;
}

// Validation des données d'entrée
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        $required = $rule['required'] ?? false;
        $type = $rule['type'] ?? 'string';
        $min = $rule['min'] ?? null;
        $max = $rule['max'] ?? null;
        $pattern = $rule['pattern'] ?? null;
        
        // Vérifier si requis
        if ($required && ($value === null || $value === '')) {
            $errors[$field] = "Le champ '$field' est requis";
            continue;
        }
        
        // Si non requis et vide, passer
        if (!$required && ($value === null || $value === '')) {
            continue;
        }
        
        // Vérifier le type
        switch ($type) {
            case 'int':
            case 'integer':
                if (!is_numeric($value)) {
                    $errors[$field] = "Le champ '$field' doit être un nombre";
                } else {
                    $value = (int)$value;
                    if ($min !== null && $value < $min) {
                        $errors[$field] = "Le champ '$field' doit être >= $min";
                    }
                    if ($max !== null && $value > $max) {
                        $errors[$field] = "Le champ '$field' doit être <= $max";
                    }
                }
                break;
                
            case 'float':
            case 'number':
                if (!is_numeric($value)) {
                    $errors[$field] = "Le champ '$field' doit être un nombre";
                } else {
                    $value = (float)$value;
                    if ($min !== null && $value < $min) {
                        $errors[$field] = "Le champ '$field' doit être >= $min";
                    }
                    if ($max !== null && $value > $max) {
                        $errors[$field] = "Le champ '$field' doit être <= $max";
                    }
                }
                break;
                
            case 'string':
                $value = (string)$value;
                if ($min !== null && strlen($value) < $min) {
                    $errors[$field] = "Le champ '$field' doit contenir au moins $min caractères";
                }
                if ($max !== null && strlen($value) > $max) {
                    $errors[$field] = "Le champ '$field' doit contenir au maximum $max caractères";
                }
                if ($pattern && !preg_match($pattern, $value)) {
                    $errors[$field] = "Le champ '$field' a un format invalide";
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Le champ '$field' doit être un email valide";
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$field] = "Le champ '$field' doit être une URL valide";
                }
                break;
                
            case 'array':
                if (!is_array($value)) {
                    $errors[$field] = "Le champ '$field' doit être un tableau";
                }
                break;
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => 'Erreurs de validation', 'details' => $errors]);
        exit;
    }
    
    return true;
}

// Protection contre les injections SQL (déjà géré par Supabase, mais on nettoie quand même)
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    // Nettoyer les caractères dangereux
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = trim($data);
    
    return $data;
}

// Vérifier si une requête nécessite une authentification
function requireAuth() {
    // Vérifier la clé API dans les headers
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
    
    // Clé API secrète (peut être définie via variable d'environnement ou constante)
    $validApiKey = defined('API_SECRET_KEY') ? API_SECRET_KEY : (getenv('API_SECRET_KEY') ?: 'DQjVy7UkeA/RiQdBKYGobB1aDfEiPT/7vHHT63kuq0e9fGPmI1ThQaSzKwxt3kT8OggtNkN6eP2WPiGJVIUZXw==');
    
    if (!$apiKey || $apiKey !== $validApiKey) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentification requise. Fournissez une clé API valide dans le header X-API-Key']);
        exit;
    }
}

// Protection CSRF pour les requêtes POST/PUT/DELETE
function checkCSRF() {
    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        session_start();
        
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        if (!$token || $token !== $sessionToken) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide']);
            exit;
        }
    }
}

// Générer un token CSRF
function generateCSRFToken() {
    session_start();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Log des requêtes suspectes
function logSuspiciousActivity($message, $data = []) {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'message' => $message,
        'data' => $data
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
}

// Initialisation de la sécurité
function initSecurity($options = []) {
    $rateLimit = $options['rate_limit'] ?? true;
    $cors = $options['cors'] ?? true;
    $auth = $options['auth'] ?? false;
    $csrf = $options['csrf'] ?? false;
    
    // Gérer CORS
    if ($cors) {
        handleCORS();
    }
    
    // Rate limiting
    if ($rateLimit) {
        checkRateLimit();
    }
    
    // Authentification
    if ($auth) {
        requireAuth();
    }
    
    // CSRF
    if ($csrf) {
        checkCSRF();
    }
}

