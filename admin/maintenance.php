<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$error = null;
$success = null;

// Charger les paramètres de maintenance
$maintenance = null;
try {
    $result = $supabase->request('GET', 'maintenance?order=created_at.desc&limit=1', null, true);
    if (!empty($result)) {
        $maintenance = $result[0];
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $enabled = isset($_POST['enabled']);
        $message = trim($_POST['message'] ?? '');
        
        $data = [
            'enabled' => $enabled,
            'message' => $message,
            'image_url' => null,
            'updated_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        if ($maintenance && isset($maintenance['id'])) {
            // Mise à jour
            $supabase->request('PATCH', 'maintenance?id=eq.' . $maintenance['id'], $data, true);
            $success = 'Paramètres de maintenance mis à jour avec succès !';
        } else {
            // Création
            $data['created_at'] = date('Y-m-d\TH:i:s.u\Z');
            $supabase->request('POST', 'maintenance', $data, true);
            $success = 'Paramètres de maintenance créés avec succès !';
        }
        
        // Recharger les données
        $result = $supabase->request('GET', 'maintenance?order=created_at.desc&limit=1', null, true);
        if (!empty($result)) {
            $maintenance = $result[0];
        }
    } catch (Exception $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

$enabled = $maintenance['enabled'] ?? false;
$message = $maintenance['message'] ?? 'Le site est actuellement en maintenance. Nous serons de retour bientôt !';

$pageTitle = 'Maintenance - Panel Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../shop/assets/css/style.css">
    <?php include __DIR__ . '/components/layout.php'; ?>
    <style>
        .settings-section {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            margin-bottom: 1.5rem;
        }
        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            color: #fff;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-input,
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1rem;
        }
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #6b7280;
        }
        .form-help {
            color: #9ca3af;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 0.5rem;
            border: 1px solid rgba(55, 65, 81, 0.3);
        }
        .form-checkbox input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 0.25rem;
            border: 1px solid rgba(75, 85, 99, 1);
            background: rgba(30, 41, 59, 1);
            cursor: pointer;
        }
        .form-checkbox input[type="checkbox"]:checked {
            background: rgba(37, 99, 235, 1);
            border-color: rgba(37, 99, 235, 1);
        }
        .form-checkbox label {
            color: #fff;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .status-badge.active {
            background: rgba(239, 68, 68, 0.2);
            color: rgba(248, 113, 113, 1);
        }
        .status-badge.inactive {
            background: rgba(34, 197, 94, 0.2);
            color: rgba(74, 222, 128, 1);
        }
        .preview-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(30, 41, 59, 0.3);
            border-radius: 0.5rem;
            border: 1px solid rgba(55, 65, 81, 0.5);
        }
        .preview-title {
            color: #fff;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .preview-content {
            color: #fff;
            text-align: center;
            padding: 2rem;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .btn-primary {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(to right, #9333ea, #db2777);
            color: #fff;
            font-weight: 700;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #7e22ce, #be185d);
        }
        .error-message,
        .success-message {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .error-message {
            background: rgba(127, 29, 29, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
        }
        .error-message p {
            color: rgba(248, 113, 113, 1);
        }
        .success-message {
            background: rgba(20, 83, 45, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.5);
        }
        .success-message p {
            color: rgba(74, 222, 128, 1);
        }
        .image-preview {
            margin-top: 0.5rem;
        }
        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 0.5rem;
            border: 1px solid rgba(55, 65, 81, 0.5);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/components/layout.php'; ?>
    
    <div class="admin-wrapper">
        <?php include __DIR__ . '/components/sidebar.php'; ?>
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">🔧 Maintenance</h1>
                <p class="page-subtitle" style="color: #fff;">Mode maintenance et paramètres système</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <p>Erreur: <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="settings-section neon-border">
                    <h2 class="section-title">État de la maintenance</h2>
                    
                    <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" 
                                   name="enabled" 
                                   id="enabled"
                                   <?php echo $enabled ? 'checked' : ''; ?>
                                   onchange="updateStatus()">
                            <label for="enabled">
                                Activer le mode maintenance
                                <span class="status-badge <?php echo $enabled ? 'active' : 'inactive'; ?>" id="status-badge">
                                    <?php echo $enabled ? '🔴 ACTIF' : '🟢 INACTIF'; ?>
                                </span>
                            </label>
                        </div>
                        <p class="form-help">Quand activé, les visiteurs verront la page de maintenance au lieu du site.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message de maintenance</label>
                        <textarea name="message" 
                                  class="form-textarea"
                                  placeholder="Le site est actuellement en maintenance. Nous serons de retour bientôt !"
                                  required><?php echo htmlspecialchars($message); ?></textarea>
                        <p class="form-help">Message affiché aux visiteurs pendant la maintenance.</p>
                    </div>


                    <div class="preview-section">
                        <div class="preview-title">Aperçu de la page de maintenance</div>
                        <div class="preview-content">
                            <p><?php echo nl2br(htmlspecialchars($message)); ?></p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary">💾 Enregistrer les modifications</button>
            </form>
        </main>
    </div>

    <script>
        function updateStatus() {
            const checkbox = document.getElementById('enabled');
            const badge = document.getElementById('status-badge');
            if (checkbox.checked) {
                badge.textContent = '🔴 ACTIF';
                badge.className = 'status-badge active';
            } else {
                badge.textContent = '🟢 INACTIF';
                badge.className = 'status-badge inactive';
            }
        }
        
        function previewImage(url) {
            const preview = document.getElementById('imagePreview');
            if (url) {
                preview.innerHTML = '<img src="' + url + '" alt="Aperçu" onerror="this.parentElement.innerHTML=\'\'">';
            } else {
                preview.innerHTML = '';
            }
        }
    </script>
</body>
</html>
