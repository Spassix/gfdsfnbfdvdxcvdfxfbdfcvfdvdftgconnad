<?php
// Configuration de sécurité des sessions (AVANT session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../supabase_client.php';
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        // Limiter les tentatives de connexion (protection brute force basique)
        $attemptsKey = 'login_attempts_' . md5($username . ($_SERVER['REMOTE_ADDR'] ?? ''));
        $attempts = $_SESSION[$attemptsKey] ?? 0;
        $lastAttempt = $_SESSION[$attemptsKey . '_time'] ?? 0;
        
        // Réinitialiser après 15 minutes
        if (time() - $lastAttempt > 900) {
            $attempts = 0;
        }
        
        // Bloquer après 5 tentatives
        if ($attempts >= 5) {
            $error = 'Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.';
        } else {
            try {
                $admin = $supabase->verifyAdmin($username, $password);
                
                if ($admin) {
                    // Connexion réussie : réinitialiser les tentatives
                    unset($_SESSION[$attemptsKey]);
                    unset($_SESSION[$attemptsKey . '_time']);
                    
                    // Régénérer l'ID de session pour prévenir la fixation
                    session_regenerate_id(true);
                    
                    // Stocker les informations de session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                    $_SESSION['created'] = time();
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Incrémenter les tentatives
                    $_SESSION[$attemptsKey] = $attempts + 1;
                    $_SESSION[$attemptsKey . '_time'] = time();
                    
                    // Message générique pour ne pas révéler si l'utilisateur existe
                    $error = 'Identifiants incorrects';
                }
            } catch (Exception $e) {
                // Ne pas révéler les détails de l'erreur en production
                $error = 'Erreur de connexion. Veuillez réessayer.';
                
                // Debug: afficher plus de détails uniquement si demandé
                if (isset($_GET['debug'])) {
                    $error .= '<br><small>' . htmlspecialchars($e->getMessage()) . '</small>';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0d0f17 0%, #1a1d29 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: rgba(26, 29, 41, 0.9);
            border: 1px solid #333;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        h1 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #ccc;
            margin-bottom: 8px;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px;
            background: #1a1d29;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔐 Connexion Admin</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>

