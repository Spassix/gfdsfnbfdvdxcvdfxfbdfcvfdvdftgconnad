<?php
// Router simple pour le serveur PHP
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Enlever le slash initial
$requestPath = ltrim($requestPath, '/');

// Si c'est la racine, rediriger vers shop/index.php
if ($requestPath === '' || $requestPath === '/') {
    $requestPath = 'shop/index.php';
}

// Si le fichier existe, le servir
$filePath = __DIR__ . '/' . $requestPath;

if (file_exists($filePath) && is_file($filePath)) {
    return false; // Laisser PHP servir le fichier normalement
}

// Si c'est un dossier, chercher index.php
if (is_dir($filePath)) {
    $indexPath = $filePath . '/index.php';
    if (file_exists($indexPath)) {
        require $indexPath;
        return true;
    }
}

// 404
http_response_code(404);
echo "404 - Page non trouvée";
return true;

