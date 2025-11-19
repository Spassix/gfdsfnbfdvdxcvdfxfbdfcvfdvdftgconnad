<?php
/**
 * Script pour créer un administrateur avec un mot de passe hashé
 * ⚠️ À utiliser UNIQUEMENT en local ou via ligne de commande sécurisée
 * ⚠️ SUPPRIMEZ ce fichier après utilisation en production !
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../supabase_client.php';

// Sécurité : Vérifier que c'est bien une requête locale ou avec authentification
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
$hasSecret = isset($_GET['secret']) && $_GET['secret'] === 'CHANGEZ_MOI_EN_PRODUCTION';

if (!$isLocal && !$hasSecret) {
    die('❌ Accès refusé. Ce script ne peut être exécuté qu\'en local ou avec un secret.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Tous les champs sont requis';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide';
    } else {
        try {
            // Hash du mot de passe avec bcrypt (algorithme par défaut de password_hash)
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Vérifier si l'utilisateur existe déjà
            $existing = $supabase->request('GET', 'admins?username=eq.' . urlencode($username) . '&select=id', null, true);
            
            if (!empty($existing)) {
                $error = 'Ce nom d\'utilisateur existe déjà';
            } else {
                // Créer l'admin
                $adminData = [
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'role' => $role,
                    'active' => true,
                    'created_at' => date('Y-m-d\TH:i:s.u\Z'),
                    'updated_at' => date('Y-m-d\TH:i:s.u\Z')
                ];
                
                $result = $supabase->request('POST', 'admins', $adminData, true);
                
                if ($result) {
                    $success = '✅ Administrateur créé avec succès !';
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Administrateur</title>
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
        .container {
            background: rgba(26, 29, 41, 0.9);
            border: 1px solid #333;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        h1 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .warning {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fca5a5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
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
        input, select {
            width: 100%;
            padding: 12px;
            background: #1a1d29;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
        }
        input:focus, select:focus {
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
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.5);
        }
        .success {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(34, 197, 94, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Créer un Administrateur</h1>
        
        <div class="warning">
            ⚠️ <strong>ATTENTION :</strong> Ce script doit être supprimé après utilisation en production !
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nom d'utilisateur *</label>
                <input type="text" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Mot de passe * (min. 8 caractères)</label>
                <input type="password" name="password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label>Rôle</label>
                <select name="role">
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            
            <button type="submit">Créer l'administrateur</button>
        </form>
    </div>
</body>
</html>

