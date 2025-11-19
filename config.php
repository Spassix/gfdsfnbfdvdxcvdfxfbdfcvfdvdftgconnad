<?php
/**
 * Configuration Supabase
 */

// Configuration Supabase
define('SUPABASE_URL', 'https://kmnzurtfifwalpufpuei.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imttbnp1cnRmaWZ3YWxwdWZwdWVpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjM0ODk0NjksImV4cCI6MjA3OTA2NTQ2OX0.RwYsnJCoxNS7I6ydSGlNUB7Clx9ABsLPZ-ujfel1YSo'); // Anon key (pour lecture)
define('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imttbnp1cnRmaWZ3YWxwdWZwdWVpIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MzQ4OTQ2OSwiZXhwIjoyMDc5MDY1NDY5fQ.LvpryvPi_lnmKo-77xFfudXoeVZJUzrWn7p8Dc-gKuI'); // Service role key (pour admin)
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imttbnp1cnRmaWZ3YWxwdWZwdWVpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjM0ODk0NjksImV4cCI6MjA3OTA2NTQ2OX0.RwYsnJCoxNS7I6ydSGlNUB7Clx9ABsLPZ-ujfel1YSo');

// Telegram Guard - Définir à true pour désactiver temporairement (développement uniquement)
define('TELEGRAM_BYPASS', false); // Mettre à true pour tester sans Telegram

// Configuration de l'application
define('APP_NAME', 'Panel Admin');
define('SESSION_NAME', 'admin_session');
define('SESSION_LIFETIME', 3600 * 24);

// Timezone
date_default_timezone_set('Europe/Paris');

// Charger les variables d'environnement depuis .env si le fichier existe
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignorer les commentaires
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key) && !getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Clé API secrète (peut être surchargée par .env)
if (!defined('API_SECRET_KEY')) {
    define('API_SECRET_KEY', getenv('API_SECRET_KEY') ?: 'DQjVy7UkeA/RiQdBKYGobB1aDfEiPT/7vHHT63kuq0e9fGPmI1ThQaSzKwxt3kT8OggtNkN6eP2WPiGJVIUZXw==');
}

// Erreurs (activer pour debug local)
error_reporting(E_ALL);
ini_set('display_errors', 1);

