<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$error = null;
$success = null;

// Charger les paramètres de typographie
$typography = null;
try {
    $result = $supabase->request('GET', 'typography?order=created_at.desc&limit=1', null, true);
    if (!empty($result)) {
        $typography = $result[0];
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fontFamily = trim($_POST['font_family'] ?? 'Inter');
        $fontWeights = [];
        
        if (isset($_POST['font_weights']) && is_array($_POST['font_weights'])) {
            $fontWeights = array_map('intval', $_POST['font_weights']);
        } else {
            $fontWeights = [400, 500, 600, 700];
        }
        
        $data = [
            'font_family' => $fontFamily,
            'font_weights' => json_encode($fontWeights),
            'updated_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        if ($typography && isset($typography['id'])) {
            // Mise à jour
            $supabase->request('PATCH', 'typography?id=eq.' . $typography['id'], $data, true);
            $success = 'Typographie mise à jour avec succès !';
        } else {
            // Création
            $data['created_at'] = date('Y-m-d\TH:i:s.u\Z');
            $supabase->request('POST', 'typography', $data, true);
            $success = 'Typographie créée avec succès !';
        }
        
        // Recharger les données
        $result = $supabase->request('GET', 'typography?order=created_at.desc&limit=1', null, true);
        if (!empty($result)) {
            $typography = $result[0];
        }
    } catch (Exception $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

$fontFamily = $typography['font_family'] ?? 'Inter';
$fontWeights = [];
if (!empty($typography['font_weights'])) {
    if (is_string($typography['font_weights'])) {
        $fontWeights = json_decode($typography['font_weights'], true) ?? [400, 500, 600, 700];
    } else {
        $fontWeights = $typography['font_weights'] ?? [400, 500, 600, 700];
    }
} else {
    $fontWeights = [400, 500, 600, 700];
}

$pageTitle = 'Typographie - Panel Admin';
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
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1rem;
        }
        .form-input::placeholder {
            color: #6b7280;
        }
        .form-help {
            color: #9ca3af;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-item input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 0.25rem;
            border: 1px solid rgba(75, 85, 99, 1);
            background: rgba(30, 41, 59, 1);
            cursor: pointer;
        }
        .checkbox-item input[type="checkbox"]:checked {
            background: rgba(37, 99, 235, 1);
            border-color: rgba(37, 99, 235, 1);
        }
        .checkbox-item label {
            color: #fff;
            cursor: pointer;
            font-size: 0.875rem;
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
        .preview-text {
            color: #fff;
            line-height: 1.75;
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/components/layout.php'; ?>
    
    <div class="admin-wrapper">
        <?php include __DIR__ . '/components/sidebar.php'; ?>
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">✍️ Typographie</h1>
                <p class="page-subtitle" style="color: #fff;">Personnaliser les polices et la typographie</p>
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
                    <h2 class="section-title">Famille de police</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Nom de la police</label>
                        <input type="text" 
                               name="font_family" 
                               value="<?php echo htmlspecialchars($fontFamily); ?>"
                               placeholder="Inter, Roboto, Arial..."
                               class="form-input"
                               required>
                        <p class="form-help">Utilisez le nom de la police (ex: Inter, Roboto, Arial). Assurez-vous que la police est chargée dans votre CSS.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Graisses de police disponibles</label>
                        <div class="checkbox-group">
                            <?php 
                            $availableWeights = [100, 200, 300, 400, 500, 600, 700, 800, 900];
                            foreach ($availableWeights as $weight): 
                                $checked = in_array($weight, $fontWeights) ? 'checked' : '';
                            ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" 
                                           name="font_weights[]" 
                                           value="<?php echo $weight; ?>"
                                           id="weight-<?php echo $weight; ?>"
                                           <?php echo $checked; ?>>
                                    <label for="weight-<?php echo $weight; ?>"><?php echo $weight; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="form-help">Sélectionnez les graisses de police disponibles pour votre site.</p>
                    </div>

                    <div class="preview-section">
                        <div class="preview-title">Aperçu</div>
                        <div class="preview-text" id="preview-text" style="font-family: '<?php echo htmlspecialchars($fontFamily); ?>', sans-serif;">
                            <p style="font-weight: 400; margin-bottom: 0.5rem;">Texte normal (400) - Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                            <p style="font-weight: 500; margin-bottom: 0.5rem;">Texte medium (500) - Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                            <p style="font-weight: 600; margin-bottom: 0.5rem;">Texte semi-bold (600) - Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                            <p style="font-weight: 700;">Texte bold (700) - Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary">💾 Enregistrer les modifications</button>
            </form>
        </main>
    </div>

    <script>
        // Mettre à jour l'aperçu en temps réel
        document.querySelector('input[name="font_family"]').addEventListener('input', function() {
            document.getElementById('preview-text').style.fontFamily = "'" + this.value + "', sans-serif";
        });
    </script>
</body>
</html>
