<?php
/**
 * Telegram WebApp Guard
 * Vérifie que l'utilisateur accède via Telegram WebApp (initData)
 * Fallback: autorise les appareils mobiles uniquement
 */

// Charger la configuration si elle n'est pas déjà chargée
if (!defined('TELEGRAM_BYPASS')) {
    $configPath = __DIR__ . '/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        // Valeur par défaut si config.php n'existe pas
        define('TELEGRAM_BYPASS', false);
    }
}

function isTelegramWebApp() {
    // Vérifier si window.Telegram.WebApp.initData existe (via JavaScript)
    // Cette fonction sera appelée côté serveur, mais on peut vérifier les headers Telegram
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Telegram WebApp envoie des headers spécifiques
    // Vérifier si initData est présent dans les paramètres ou headers
    if (isset($_GET['tgWebAppData']) || isset($_SERVER['HTTP_X_TELEGRAM_INIT_DATA'])) {
        return true;
    }
    
    // Vérifier User-Agent Telegram
    if (stripos($userAgent, 'Telegram') !== false) {
        return true;
    }
    
    return false;
}

function isMobileDevice() {
    // Vérifier si la session a déjà été validée
    if (isset($_SESSION['mobile_verified']) && $_SESSION['mobile_verified'] === true) {
        return true;
    }
    
    // Vérifier User-Agent pour détecter les appareils mobiles
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userAgent = strtolower($userAgent);
    
    // Mots-clés pour détecter les appareils mobiles
    $mobileKeywords = [
        'mobile', 'android', 'iphone', 'ipad', 'ipod', 
        'blackberry', 'windows phone', 'opera mini', 
        'iemobile', 'mobile safari', 'tablet'
    ];
    
    // Mots-clés pour détecter les PC/Desktop (à bloquer)
    $desktopKeywords = [
        'windows nt', 'macintosh', 'linux', 'x11',
        'win64', 'wow64', 'mac os x'
    ];
    
    // Vérifier si c'est un desktop
    foreach ($desktopKeywords as $keyword) {
        if (strpos($userAgent, $keyword) !== false) {
            // Mais exclure les tablettes qui peuvent avoir "windows" ou "mac"
            if (strpos($userAgent, 'tablet') === false && 
                strpos($userAgent, 'ipad') === false) {
                return false; // C'est un PC, bloquer
            }
        }
    }
    
    // Vérifier si c'est un mobile
    foreach ($mobileKeywords as $keyword) {
        if (strpos($userAgent, $keyword) !== false) {
            return true; // C'est un mobile, autoriser
        }
    }
    
    // Si on ne peut pas déterminer, vérifier la largeur d'écran via JavaScript
    // On retourne false par défaut (bloquer) et laisser JavaScript vérifier
    return false;
}

function checkTelegramAccess() {
    // Si on est dans l'admin, on autorise toujours (pour la configuration)
    $isAdmin = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false;
    if ($isAdmin) {
        return true;
    }
    
    // Vérifier si l'accès est autorisé (bypass activé)
    $bypassEnabled = defined('TELEGRAM_BYPASS') && TELEGRAM_BYPASS === true;
    
    if ($bypassEnabled) {
        return true;
    }
    
    // Si on a déjà vérifié dans cette session
    if (isset($_SESSION['telegram_verified']) && $_SESSION['telegram_verified'] === true) {
        return true;
    }
    
    // Vérifier si c'est une requête AJAX pour valider Telegram (depuis JavaScript)
    if (isset($_GET['verify_telegram']) && $_GET['verify_telegram'] === '1') {
        // Cette requête vient du JavaScript qui vérifie Telegram WebApp
        $_SESSION['telegram_verified'] = true;
        $_SESSION['mobile_verified'] = true; // Compatibilité
        setcookie('telegram_verified', '1', time() + 3600 * 24 * 7, '/', '', false, true);
        setcookie('mobile_verified', '1', time() + 3600 * 24 * 7, '/', '', false, true);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        echo json_encode(['verified' => true]);
        exit;
    }
    
    // Vérifier si c'est une requête AJAX pour valider mobile (fallback)
    if (isset($_GET['verify_mobile']) && $_GET['verify_mobile'] === '1') {
        // Fallback: vérifier si c'est un mobile
        if (isMobileDevice()) {
            $_SESSION['mobile_verified'] = true;
            setcookie('mobile_verified', '1', time() + 3600 * 24 * 7, '/', '', false, true);
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Credentials: true');
            echo json_encode(['verified' => true]);
            exit;
        }
    }
    
    // Vérifier Telegram WebApp côté serveur (headers)
    if (isTelegramWebApp()) {
        $_SESSION['telegram_verified'] = true;
        $_SESSION['mobile_verified'] = true;
        setcookie('telegram_verified', '1', time() + 3600 * 24 * 7, '/', '', false, true);
        setcookie('mobile_verified', '1', time() + 3600 * 24 * 7, '/', '', false, true);
        return true;
    }
    
    // Fallback: vérifier si c'est un mobile
    if (isMobileDevice()) {
        $_SESSION['mobile_verified'] = true;
        setcookie('mobile_verified', '1', time() + 3600 * 24 * 7, '/', '', false, true);
        return true;
    }
    
    // Bloquer l'accès depuis PC, permettre à JavaScript de vérifier si c'est un mobile
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Accès restreint</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }
            .container {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 1rem;
                padding: 2rem 1.5rem;
                text-align: center;
                max-width: 500px;
                width: 90%;
                margin: 0 auto;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            @media (max-width: 768px) {
                .container {
                    padding: 1.5rem 1rem;
                    width: 95%;
                }
                h1 {
                    font-size: 1.5rem;
                }
                p {
                    font-size: 1rem;
                }
            }
            .icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }
            h1 {
                color: #1a202c;
                font-size: 1.875rem;
                margin-bottom: 1rem;
            }
            p {
                color: #4a5568;
                font-size: 1.125rem;
                line-height: 1.6;
                margin-bottom: 2rem;
            }
            .telegram-link {
                display: inline-block;
                background: #0088cc;
                color: #fff;
                padding: 1rem 2rem;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                font-size: 1.125rem;
                transition: background 0.3s;
            }
            .telegram-link:hover {
                background: #006ba3;
            }
            .loading {
                display: none;
                margin-top: 1rem;
                color: #667eea;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">🔒</div>
            <h1>Accès restreint</h1>
            <p>
                Cette application est accessible uniquement via Telegram Mini App.<br>
                Veuillez ouvrir cette page depuis l'application Telegram.
            </p>
            <div class="loading" id="loading">Vérification en cours...</div>
            <a href="#" class="telegram-link" id="telegram-link" onclick="return false;">
                Accès Telegram uniquement
            </a>
        </div>
        <script src="https://telegram.org/js/telegram-web-app.js"></script>
        <script>
            let verified = false;
            let checking = false;
            
            // Fonction pour vérifier Telegram WebApp
            function isTelegramWebApp() {
                try {
                    // Vérifier si window.Telegram.WebApp existe
                    if (typeof window.Telegram !== 'undefined' && 
                        typeof window.Telegram.WebApp !== 'undefined') {
                        const tg = window.Telegram.WebApp;
                        // Vérifier si initData existe et n'est pas vide
                        if (tg.initData && tg.initData !== '') {
                            return true;
                        }
                    }
                } catch (e) {
                    // Erreur silencieuse
                }
                return false;
            }
            
            // Fonction pour détecter si c'est un mobile (fallback)
            function isMobile() {
                // Vérifier la largeur d'écran
                const width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
                
                // Vérifier User-Agent
                const userAgent = navigator.userAgent || navigator.vendor || window.opera;
                const userAgentLower = userAgent.toLowerCase();
                
                // Mots-clés mobiles
                const mobileKeywords = [
                    'mobile', 'android', 'iphone', 'ipad', 'ipod',
                    'blackberry', 'windows phone', 'opera mini',
                    'iemobile', 'mobile safari', 'tablet'
                ];
                
                // Vérifier User-Agent
                for (let keyword of mobileKeywords) {
                    if (userAgentLower.includes(keyword)) {
                        if (keyword === 'ipad' || keyword === 'tablet') {
                            return true;
                        }
                        if (keyword !== 'mobile' || !userAgentLower.includes('windows')) {
                            return true;
                        }
                    }
                }
                
                // Vérifier la largeur d'écran (moins de 768px = probablement mobile)
                if (width <= 768) {
                    return true;
                }
                
                // Vérifier touch support
                if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
                    if (!userAgentLower.includes('windows')) {
                        return true;
                    }
                }
                
                return false;
            }
            
            // Fonction pour valider l'accès Telegram (une seule fois)
            function verifyTelegramAccess() {
                // Empêcher les vérifications multiples
                if (verified || checking) return;
                checking = true;
                
                // Priorité: Vérifier Telegram WebApp
                if (isTelegramWebApp()) {
                    verified = true;
                    checking = false;
                    const loadingEl = document.getElementById('loading');
                    const linkEl = document.getElementById('telegram-link');
                    if (loadingEl) {
                        loadingEl.style.display = 'block';
                        loadingEl.classList.add('active');
                    }
                    if (linkEl) linkEl.style.display = 'none';
                    
                    // Valider côté serveur
                    fetch(window.location.pathname + '?verify_telegram=1', {
                        method: 'GET',
                        credentials: 'include'
                    })
                        .then(response => {
                            if (response.ok) {
                                return response.json();
                            }
                            throw new Error('Network response was not ok');
                        })
                        .then(data => {
                            if (data.verified) {
                                // Recharger UNE SEULE FOIS
                                window.location.reload();
                            } else {
                                checking = false;
                            }
                        })
                        .catch(() => {
                            checking = false;
                        });
                    return true;
                }
                
                // Fallback: Vérifier si c'est un mobile
                if (isMobile()) {
                    verified = true;
                    checking = false;
                    const loadingEl = document.getElementById('loading');
                    const linkEl = document.getElementById('telegram-link');
                    if (loadingEl) {
                        loadingEl.style.display = 'block';
                        loadingEl.classList.add('active');
                    }
                    if (linkEl) linkEl.style.display = 'none';
                    
                    // Valider côté serveur (mobile)
                    fetch(window.location.pathname + '?verify_mobile=1', {
                        method: 'GET',
                        credentials: 'include'
                    })
                        .then(response => {
                            if (response.ok) {
                                return response.json();
                            }
                            throw new Error('Network response was not ok');
                        })
                        .then(data => {
                            if (data.verified) {
                                // Recharger UNE SEULE FOIS
                                window.location.reload();
                            } else {
                                checking = false;
                            }
                        })
                        .catch(() => {
                            checking = false;
                        });
                    return true;
                }
                
                checking = false;
                return false;
            }
            
            // Vérifier une seule fois après le chargement complet
            window.addEventListener('load', () => {
                setTimeout(() => verifyTelegramAccess(), 500);
            });
            
            // Vérifier aussi si Telegram est déjà chargé
            if (typeof window.Telegram !== 'undefined') {
                setTimeout(() => verifyTelegramAccess(), 500);
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier l'accès (sauf pour les fichiers statiques et l'admin)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isStaticFile = preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|ico|woff|woff2|ttf|eot)$/i', $requestUri);
$isAdmin = strpos($requestUri, '/admin/') !== false;
$isApi = strpos($requestUri, '/api/') !== false;

if (!$isStaticFile && !$isAdmin && !$isApi) {
    checkTelegramAccess();
}

