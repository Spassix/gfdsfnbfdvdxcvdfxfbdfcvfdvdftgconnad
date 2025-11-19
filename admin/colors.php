<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$colors = [
    'continueButton' => ['bg' => '#ffffff', 'text' => '#000000', 'hoverBg' => '#e5e7eb'],
    'backButton' => ['bg' => '#374151', 'text' => '#ffffff', 'hoverBg' => '#4b5563'],
    'clearCartButton' => ['text' => '#f87171', 'hoverText' => '#fca5a5'],
    'promoButton' => ['bg' => '#ffffff', 'text' => '#000000', 'hoverBg' => '#e5e7eb'],
    'selectedService' => ['bg' => 'transparent', 'text' => '#000000', 'border' => '#000000'],
    'unselectedService' => ['bg' => 'rgba(15, 23, 42, 0.5)', 'text' => '#ffffff', 'border' => '#4b5563'],
    'selectedSlot' => ['bg' => 'transparent', 'text' => '#000000', 'border' => '#000000'],
    'unselectedSlot' => ['bg' => 'rgba(15, 23, 42, 0.5)', 'text' => '#ffffff', 'border' => '#4b5563'],
    'selectedPayment' => ['bgFrom' => '#ffffff', 'bgTo' => '#f3f4f6', 'text' => '#000000', 'border' => '#ffffff'],
    'unselectedPayment' => ['bg' => '#1e293b', 'text' => '#ffffff', 'border' => '#374151'],
    'sendButton' => ['bgFrom' => '#9333ea', 'bgTo' => '#2563eb', 'hoverFrom' => '#7e22ce', 'hoverTo' => '#1d4ed8', 'text' => '#ffffff'],
    'copyButton' => ['bg' => '#374151', 'text' => '#ffffff', 'hoverBg' => '#4b5563']
];

$error = null;
$success = null;

// Charger les couleurs existantes
try {
    $themeResult = $supabase->request('GET', 'theme_settings?order=created_at.desc&limit=1', null, true);
    if (!empty($themeResult) && isset($themeResult[0]['colors'])) {
        $savedColors = $themeResult[0]['colors'];
        if (is_string($savedColors)) {
            $savedColors = json_decode($savedColors, true);
        }
        if (is_array($savedColors) && !empty($savedColors)) {
            // Fusionner intelligemment : garder la structure par défaut et remplacer seulement les valeurs existantes
            foreach ($savedColors as $key => $value) {
                if (isset($colors[$key]) && is_array($colors[$key]) && is_array($value)) {
                    $colors[$key] = array_merge($colors[$key], $value);
                } elseif (isset($colors[$key]) && is_array($colors[$key])) {
                    // Si la valeur sauvegardée n'est pas un tableau, on ignore
                    continue;
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Erreur chargement couleurs: " . $e->getMessage());
}

// S'assurer que $colors est toujours un tableau
if (!is_array($colors)) {
    $colors = [
        'continueButton' => ['bg' => '#ffffff', 'text' => '#000000', 'hoverBg' => '#e5e7eb'],
        'backButton' => ['bg' => '#374151', 'text' => '#ffffff', 'hoverBg' => '#4b5563'],
        'clearCartButton' => ['text' => '#f87171', 'hoverText' => '#fca5a5'],
        'promoButton' => ['bg' => '#ffffff', 'text' => '#000000', 'hoverBg' => '#e5e7eb'],
        'selectedService' => ['bg' => 'transparent', 'text' => '#000000', 'border' => '#000000'],
        'unselectedService' => ['bg' => 'rgba(15, 23, 42, 0.5)', 'text' => '#ffffff', 'border' => '#4b5563'],
        'selectedSlot' => ['bg' => 'transparent', 'text' => '#000000', 'border' => '#000000'],
        'unselectedSlot' => ['bg' => 'rgba(15, 23, 42, 0.5)', 'text' => '#ffffff', 'border' => '#4b5563'],
        'selectedPayment' => ['bgFrom' => '#ffffff', 'bgTo' => '#f3f4f6', 'text' => '#000000', 'border' => '#ffffff'],
        'unselectedPayment' => ['bg' => '#1e293b', 'text' => '#ffffff', 'border' => '#374151'],
        'sendButton' => ['bgFrom' => '#9333ea', 'bgTo' => '#2563eb', 'hoverFrom' => '#7e22ce', 'hoverTo' => '#1d4ed8', 'text' => '#ffffff'],
        'copyButton' => ['bg' => '#374151', 'text' => '#ffffff', 'hoverBg' => '#4b5563']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // S'assurer que $colors est un tableau
        if (!is_array($colors)) {
            throw new Exception('Erreur: $colors n\'est pas un tableau');
        }
        
        // Récupérer toutes les couleurs du formulaire
        $newColors = [];
        foreach ($colors as $key => $defaultValues) {
            // S'assurer que $defaultValues est un tableau
            if (!is_array($defaultValues)) {
                continue; // Ignorer cette clé si ce n'est pas un tableau
            }
            $newColors[$key] = [];
            foreach ($defaultValues as $subKey => $defaultValue) {
                $formKey = $key . '_' . $subKey;
                $newColors[$key][$subKey] = $_POST[$formKey] ?? $defaultValue;
            }
        }
        
        // Sauvegarder dans theme_settings
        $themeData = [
            'colors' => json_encode($newColors),
            'updated_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        $existingTheme = $supabase->request('GET', 'theme_settings?order=created_at.desc&limit=1', null, true);
        if (!empty($existingTheme) && isset($existingTheme[0]['id'])) {
            $supabase->request('PATCH', 'theme_settings?id=eq.' . $existingTheme[0]['id'], $themeData, true);
        } else {
            $themeData['preset'] = 'custom';
            $themeData['created_at'] = date('Y-m-d\TH:i:s.u\Z');
            $supabase->request('POST', 'theme_settings', $themeData, true);
        }
        
        $colors = $newColors;
        $success = 'Couleurs sauvegardées avec succès !';
    } catch (Exception $e) {
        $error = 'Erreur: ' . $e->getMessage();
        error_log("Erreur sauvegarde couleurs: " . $e->getMessage());
    }
}

$pageTitle = 'Couleurs - Panel Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../shop/assets/css/style.css">
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
            background: #000;
        }
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 18rem;
            background: #000;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 50;
            overflow-y: auto;
        }
        .admin-content {
            margin-left: 18rem;
            flex: 1;
            min-height: 100vh;
            padding: 2rem;
        }
        .page-header {
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        @media (min-width: 640px) {
            .page-title {
                font-size: 1.875rem;
            }
        }
        @media (min-width: 1024px) {
            .page-title {
                font-size: 2.25rem;
            }
        }
        .page-subtitle {
            color: #fff;
            font-size: 0.875rem;
        }
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
        .color-group {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .color-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .color-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .color-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .color-input-group {
            display: flex;
            flex-direction: column;
        }
        .color-label {
            color: #fff;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .color-input-wrapper {
            display: flex;
            gap: 0.5rem;
        }
        .color-picker {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(55, 65, 81, 1);
            cursor: pointer;
            flex-shrink: 0;
        }
        .color-text-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1rem;
        }
        .save-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, #9333ea, #db2777);
            color: #fff;
            font-weight: 700;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .save-btn:hover {
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
        @media (max-width: 1024px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 4rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <button id="mobile-menu-btn" 
                style="position: fixed; top: 1rem; left: 1rem; z-index: 60; padding: 0.75rem; background: #000; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff; display: none; cursor: pointer;"
                onclick="document.querySelector('.admin-sidebar').classList.toggle('open'); document.getElementById('mobile-overlay').style.display = document.querySelector('.admin-sidebar').classList.contains('open') ? 'block' : 'none';">
            <span style="font-size: 1.5rem;">☰</span>
        </button>
        <div id="mobile-overlay" 
             style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 40; backdrop-filter: blur(4px);"
             onclick="document.querySelector('.admin-sidebar').classList.remove('open'); document.getElementById('mobile-overlay').style.display = 'none';"></div>
        <?php include __DIR__ . '/components/sidebar.php'; ?>
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">🎨 Personnalisation des couleurs</h1>
                <p class="page-subtitle">Personnalisez 100% les couleurs de tous les boutons et textes du panier</p>
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
                    <h2 class="section-title">Bouton "Continuer"</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[continueButton][bg]" 
                                       value="<?php echo htmlspecialchars($colors['continueButton']['bg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['continueButton']['bg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[continueButton][text]" 
                                       value="<?php echo htmlspecialchars($colors['continueButton']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['continueButton']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Fond au survol</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[continueButton][hoverBg]" 
                                       value="<?php echo htmlspecialchars($colors['continueButton']['hoverBg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['continueButton']['hoverBg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Bouton "Retour"</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[backButton][bg]" 
                                       value="<?php echo htmlspecialchars($colors['backButton']['bg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['backButton']['bg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[backButton][text]" 
                                       value="<?php echo htmlspecialchars($colors['backButton']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['backButton']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Fond au survol</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[backButton][hoverBg]" 
                                       value="<?php echo htmlspecialchars($colors['backButton']['hoverBg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['backButton']['hoverBg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Bouton "Vider le panier"</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[clearCartButton][text]" 
                                       value="<?php echo htmlspecialchars($colors['clearCartButton']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['clearCartButton']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte au survol</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[clearCartButton][hoverText]" 
                                       value="<?php echo htmlspecialchars($colors['clearCartButton']['hoverText']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['clearCartButton']['hoverText']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Bouton "OK" code promo</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[promoButton][bg]" 
                                       value="<?php echo htmlspecialchars($colors['promoButton']['bg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['promoButton']['bg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[promoButton][text]" 
                                       value="<?php echo htmlspecialchars($colors['promoButton']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['promoButton']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Fond au survol</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[promoButton][hoverBg]" 
                                       value="<?php echo htmlspecialchars($colors['promoButton']['hoverBg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['promoButton']['hoverBg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Service sélectionné</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <input type="text" 
                                   name="colors[selectedService][bg]" 
                                   value="<?php echo htmlspecialchars($colors['selectedService']['bg']); ?>"
                                   class="color-text-input"
                                   placeholder="transparent ou #hex">
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[selectedService][text]" 
                                       value="<?php echo htmlspecialchars($colors['selectedService']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['selectedService']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Bordure</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[selectedService][border]" 
                                       value="<?php echo htmlspecialchars($colors['selectedService']['border']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['selectedService']['border']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Service non sélectionné</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <input type="text" 
                                   name="colors[unselectedService][bg]" 
                                   value="<?php echo htmlspecialchars($colors['unselectedService']['bg']); ?>"
                                   class="color-text-input"
                                   placeholder="rgba ou #hex">
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[unselectedService][text]" 
                                       value="<?php echo htmlspecialchars($colors['unselectedService']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['unselectedService']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Bordure</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[unselectedService][border]" 
                                       value="<?php echo htmlspecialchars($colors['unselectedService']['border']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['unselectedService']['border']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Créneau horaire sélectionné</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <input type="text" 
                                   name="colors[selectedSlot][bg]" 
                                   value="<?php echo htmlspecialchars($colors['selectedSlot']['bg']); ?>"
                                   class="color-text-input"
                                   placeholder="transparent ou #hex">
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[selectedSlot][text]" 
                                       value="<?php echo htmlspecialchars($colors['selectedSlot']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['selectedSlot']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Bordure</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[selectedSlot][border]" 
                                       value="<?php echo htmlspecialchars($colors['selectedSlot']['border']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['selectedSlot']['border']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Créneau horaire non sélectionné</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <input type="text" 
                                   name="colors[unselectedSlot][bg]" 
                                   value="<?php echo htmlspecialchars($colors['unselectedSlot']['bg']); ?>"
                                   class="color-text-input"
                                   placeholder="rgba ou #hex">
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[unselectedSlot][text]" 
                                       value="<?php echo htmlspecialchars($colors['unselectedSlot']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['unselectedSlot']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Bordure</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[unselectedSlot][border]" 
                                       value="<?php echo htmlspecialchars($colors['unselectedSlot']['border']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['unselectedSlot']['border']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Paiement sélectionné</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond début (gradient)</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[selectedPayment][bgFrom]" 
                                       value="<?php echo htmlspecialchars($colors['selectedPayment']['bgFrom']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['selectedPayment']['bgFrom']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Fond fin (gradient)</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[selectedPayment][bgTo]" 
                                       value="<?php echo htmlspecialchars($colors['selectedPayment']['bgTo']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['selectedPayment']['bgTo']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[selectedPayment][text]" 
                                       value="<?php echo htmlspecialchars($colors['selectedPayment']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['selectedPayment']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Bordure</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[selectedPayment][border]" 
                                       value="<?php echo htmlspecialchars($colors['selectedPayment']['border']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['selectedPayment']['border']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Paiement non sélectionné</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[unselectedPayment][bg]" 
                                       value="<?php echo htmlspecialchars($colors['unselectedPayment']['bg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['unselectedPayment']['bg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[unselectedPayment][text]" 
                                       value="<?php echo htmlspecialchars($colors['unselectedPayment']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['unselectedPayment']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Bordure</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[unselectedPayment][border]" 
                                       value="<?php echo htmlspecialchars($colors['unselectedPayment']['border']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['unselectedPayment']['border']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Bouton "Envoyer via"</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond début</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[sendButton][bgFrom]" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['bgFrom']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['bgFrom']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Fond fin</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[sendButton][bgTo]" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['bgTo']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['bgTo']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Fond début (hover)</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[sendButton][hoverFrom]" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['hoverFrom']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['hoverFrom']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Fond fin (hover)</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[sendButton][hoverTo]" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['hoverTo']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['hoverTo']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[sendButton][text]" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['sendButton']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-section neon-border">
                    <h2 class="section-title">Bouton "Copier la commande"</h2>
                    <div class="color-grid">
                        <div class="color-input-group">
                            <label class="color-label">Fond</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[copyButton][bg]" 
                                       value="<?php echo htmlspecialchars($colors['copyButton']['bg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['copyButton']['bg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Texte</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[copyButton][text]" 
                                       value="<?php echo htmlspecialchars($colors['copyButton']['text']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['copyButton']['text']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                        <div class="color-input-group">
                            <label class="color-label">Fond au survol</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="colors[copyButton][hoverBg]" 
                                       value="<?php echo htmlspecialchars($colors['copyButton']['hoverBg']); ?>"
                                       class="color-picker"
                                       onchange="updateTextInput(this)">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($colors['copyButton']['hoverBg']); ?>"
                                       class="color-text-input"
                                       oninput="updateColorPicker(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="save-btn">Sauvegarder</button>
            </form>
        </main>
    </div>
    <script>
        function updateTextInput(colorPicker) {
            const wrapper = colorPicker.closest('.color-input-wrapper');
            const textInput = wrapper.querySelector('.color-text-input');
            if (textInput) {
                textInput.value = colorPicker.value;
            }
        }
        
        function updateColorPicker(textInput) {
            const wrapper = textInput.closest('.color-input-wrapper');
            const colorPicker = wrapper.querySelector('.color-picker');
            if (colorPicker && /^#[0-9A-F]{6}$/i.test(textInput.value)) {
                colorPicker.value = textInput.value;
            }
        }
        
        if (window.innerWidth <= 1024) {
            document.getElementById('mobile-menu-btn').style.display = 'block';
        }
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 1024) {
                document.getElementById('mobile-menu-btn').style.display = 'block';
            } else {
                document.getElementById('mobile-menu-btn').style.display = 'none';
                document.querySelector('.admin-sidebar').classList.remove('open');
                document.getElementById('mobile-overlay').style.display = 'none';
            }
        });
        document.querySelectorAll('.admin-sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    document.querySelector('.admin-sidebar').classList.remove('open');
                    document.getElementById('mobile-overlay').style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
