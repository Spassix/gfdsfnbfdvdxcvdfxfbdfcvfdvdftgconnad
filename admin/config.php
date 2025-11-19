<?php
/**
 * Configuration Admin Panel
 */

// Configuration de sécurité des sessions (AVANT session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Démarrer la session après avoir configuré les paramètres
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Régénérer l'ID de session périodiquement pour prévenir les attaques de fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Régénérer toutes les 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Inclure la config principale
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../supabase_client.php';

// Vérifier si l'utilisateur est connecté
function checkAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Détruire la session pour sécurité
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Vérifier que l'ID admin existe toujours
    if (!isset($_SESSION['admin_id'])) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

// Fonction de déconnexion
function logout() {
    // Nettoyer toutes les variables de session
    $_SESSION = array();
    
    // Détruire le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
    header('Location: login.php');
    exit;
}

