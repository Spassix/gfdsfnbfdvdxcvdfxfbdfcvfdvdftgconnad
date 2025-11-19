<?php
/**
 * Script de création d'un compte admin
 * ⚠️ À SUPPRIMER après utilisation pour la sécurité
 */

require_once __DIR__ . '/supabase_client.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Tous les champs sont obligatoires';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } else {
        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            $adminData = [
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => 'admin',
                'active' => true
            ];
            
            // Utiliser la service key pour créer un admin (nécessite les permissions)
            $result = $supabase->request('POST', 'admins', $adminData, true);
            
            if (!empty($result)) {
                $success = '✅ Compte admin créé avec succès !';
                $error = ''; // Réinitialiser l'erreur
            }
        } catch (Exception $e) {
            $error = 'Erreur: ' . $e->getMessage();
            // Afficher plus de détails en mode debug
            if (defined('DEBUG') && DEBUG) {
                $error .= '<br><small>Code: ' . $e->getCode() . '</small>';
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
    <title>Créer un compte Admin</title>
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
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
            font-size: 14px;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Créer un compte Admin</h1>
        
        <div class="warning">
            ⚠️ <strong>Important:</strong> Supprimez ce fichier après avoir créé votre compte admin pour la sécurité !
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <div class="links">
                <a href="admin/login.php">→ Se connecter maintenant</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit">Créer le compte admin</button>
            </form>
        <?php endif; ?>
        
        <div class="links">
            <a href="admin/login.php">← Retour à la connexion</a>
        </div>
    </div>
</body>
</html>

