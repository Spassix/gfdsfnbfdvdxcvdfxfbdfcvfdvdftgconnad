<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$settings = [
    'shopName' => '',
    'heroTitle' => '',
    'heroSubtitle' => '',
    'backgroundImage' => ''
];
$sections = [];
$error = null;
$success = null;

// Charger les paramètres existants
try {
    $generalResult = $supabase->request('GET', 'settings?key=eq.general', null, true);
    if (!empty($generalResult) && isset($generalResult[0]['value'])) {
        $generalData = $generalResult[0]['value'];
        if (is_string($generalData)) {
            $generalData = json_decode($generalData, true);
        }
        $settings = array_merge($settings, $generalData ?? []);
    }
    
    $sectionsResult = $supabase->request('GET', 'settings?key=eq.sections', null, true);
    if (!empty($sectionsResult) && isset($sectionsResult[0]['value'])) {
        $sectionsData = $sectionsResult[0]['value'];
        if (is_string($sectionsData)) {
            $sectionsData = json_decode($sectionsData, true);
        }
        $sections = $sectionsData['sections'] ?? [];
    }
} catch (Exception $e) {
    error_log("Erreur chargement settings: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings['shopName'] = $_POST['shopName'] ?? '';
        $settings['heroTitle'] = $_POST['heroTitle'] ?? '';
        $settings['heroSubtitle'] = $_POST['heroSubtitle'] ?? '';
        $settings['backgroundImage'] = $_POST['backgroundImage'] ?? '';
        
        // Traiter les sections
        if (isset($_POST['sections']) && is_array($_POST['sections'])) {
            $sections = [];
            foreach ($_POST['sections'] as $section) {
                if (!empty($section['title']) || !empty($section['content'])) {
                    $sections[] = [
                        'icon' => $section['icon'] ?? '',
                        'title' => $section['title'] ?? '',
                        'content' => $section['content'] ?? ''
                    ];
                }
            }
        }
        
        // Sauvegarder 'general' dans Supabase
        $generalData = [
            'key' => 'general',
            'value' => json_encode($settings),
            'updated_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        $existingGeneral = $supabase->request('GET', 'settings?key=eq.general', null, true);
        if (!empty($existingGeneral)) {
            $supabase->request('PATCH', 'settings?key=eq.general', $generalData, true);
        } else {
            $generalData['created_at'] = date('Y-m-d\TH:i:s.u\Z');
            $supabase->request('POST', 'settings', $generalData, true);
        }
        
        // Sauvegarder 'sections' dans Supabase
        $sectionsData = [
            'key' => 'sections',
            'value' => json_encode(['sections' => $sections]),
            'updated_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        $existingSections = $supabase->request('GET', 'settings?key=eq.sections', null, true);
        if (!empty($existingSections)) {
            $supabase->request('PATCH', 'settings?key=eq.sections', $sectionsData, true);
        } else {
            $sectionsData['created_at'] = date('Y-m-d\TH:i:s.u\Z');
            $supabase->request('POST', 'settings', $sectionsData, true);
        }
        
        $success = 'Paramètres sauvegardés avec succès !';
        
        // Recharger les données
        $generalResult = $supabase->request('GET', 'settings?key=eq.general', null, true);
        if (!empty($generalResult) && isset($generalResult[0]['value'])) {
            $generalData = $generalResult[0]['value'];
            if (is_string($generalData)) {
                $generalData = json_decode($generalData, true);
            }
            $settings = array_merge($settings, $generalData ?? []);
        }
        
        $sectionsResult = $supabase->request('GET', 'settings?key=eq.sections', null, true);
        if (!empty($sectionsResult) && isset($sectionsResult[0]['value'])) {
            $sectionsData = $sectionsResult[0]['value'];
            if (is_string($sectionsData)) {
                $sectionsData = json_decode($sectionsData, true);
            }
            $sections = $sectionsData['sections'] ?? [];
        }
    } catch (Exception $e) {
        $error = 'Erreur: ' . $e->getMessage();
        error_log("Erreur sauvegarde settings: " . $e->getMessage());
    }
}

$pageTitle = 'Configuration - Panel Admin';
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
        @media (min-width: 640px) {
            .page-subtitle {
                font-size: 1rem;
            }
        }
        .settings-form {
            max-width: 48rem;
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
        @media (min-width: 640px) {
            .section-title {
                font-size: 1.25rem;
            }
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
        .form-input::placeholder {
            color: #6b7280;
        }
        .form-textarea {
            resize: vertical;
        }
        .form-label {
            display: block;
            color: #fff;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .image-preview {
            width: 100%;
            height: 10rem;
            border-radius: 0.5rem;
            overflow: hidden;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 1);
            margin-bottom: 0.75rem;
            position: relative;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .section-item {
            border: 1px solid rgba(55, 65, 81, 1);
            border-radius: 0.5rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            margin-bottom: 1rem;
        }
        .section-item-header {
            display: flex;
            align-items: start;
            gap: 0.75rem;
        }
        .section-icon {
            width: 4rem;
            height: 4rem;
            border-radius: 0.5rem;
            overflow: hidden;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 1);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        .section-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .section-icon-input {
            width: 4rem;
            height: 4rem;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 1);
            border: 1px solid rgba(55, 65, 81, 1);
            border-radius: 0.5rem;
            color: #fff;
            text-align: center;
            font-size: 1.5rem;
        }
        .section-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .add-section-btn {
            padding: 0.5rem 1rem;
            background: rgba(139, 92, 246, 1);
            color: #fff;
            font-weight: 600;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .add-section-btn:hover {
            background: rgba(124, 58, 237, 1);
        }
        .remove-section-btn {
            padding: 0.5rem;
            background: rgba(127, 29, 29, 0.2);
            color: #fff;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .remove-section-btn:hover {
            background: rgba(153, 27, 27, 0.3);
        }
        .save-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, #9333ea, #db2777);
            color: #fff;
            font-weight: 700;
            font-size: 1.125rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .save-btn:hover {
            background: linear-gradient(to right, #7e22ce, #be185d);
        }
        .save-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
                <h1 class="page-title">⚙️ Configuration</h1>
                <p class="page-subtitle">Paramètres généraux de la boutique</p>
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

            <form method="POST" class="settings-form">
                <!-- Nom de la boutique -->
                <div class="settings-section neon-border">
                    <h3 class="section-title">🏪 Nom de la boutique</h3>
                    <input type="text" 
                           name="shopName" 
                           value="<?php echo htmlspecialchars($settings['shopName']); ?>"
                           placeholder="CALITEK"
                           class="form-input">
                </div>

                <!-- Image de fond -->
                <div class="settings-section neon-border">
                    <h3 class="section-title">🖼️ Image de fond du site</h3>
                    <div id="background-image-preview-container" style="margin-bottom: 1rem; <?php echo empty($settings['backgroundImage']) ? 'display: none;' : ''; ?>">
                        <div class="image-preview">
                            <img src="<?php echo htmlspecialchars($settings['backgroundImage'] ?? ''); ?>" alt="Fond actuel" id="background-image-preview">
                        </div>
                        <p style="color: #fff; font-size: 0.75rem; margin-top: 0.5rem;">Image de fond actuelle</p>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">📤 Upload depuis votre appareil</label>
                            <input type="file" 
                                   id="background-image-upload"
                                   accept="image/*" 
                                   class="form-input"
                                   onchange="uploadBackgroundImage(this)">
                            <div id="background-upload-status" style="margin-top: 0.5rem; font-size: 0.75rem; color: #fff;"></div>
                        </div>
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">🔗 Ou entrez une URL</label>
                            <input type="url" 
                                   name="backgroundImage" 
                                   id="backgroundImage-url"
                                   value="<?php echo htmlspecialchars($settings['backgroundImage'] ?? ''); ?>"
                                   placeholder="https://example.com/image.jpg"
                                   onchange="updateBackgroundPreview(this.value)"
                                   class="form-input">
                        </div>
                    </div>
                    <p style="color: #fff; font-size: 0.75rem; margin-top: 0.5rem;">Téléchargez une image depuis votre appareil ou entrez une URL. Cette image sera utilisée comme fond pour toutes les pages (boutique et admin)</p>
                </div>

                <!-- Textes page d'accueil -->
                <div class="settings-section neon-border">
                    <h3 class="section-title">🏠 Textes de la page d'accueil</h3>
                    <p style="color: #fff; font-size: 0.875rem; margin-bottom: 1rem;">Le texte s'affichera avec un fond pour être bien visible</p>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label">Titre principal (grand texte)</label>
                            <input type="text" 
                                   name="heroTitle" 
                                   value="<?php echo htmlspecialchars($settings['heroTitle']); ?>"
                                   placeholder="CALITEK"
                                   class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Sous-titre / Phrase d'accroche</label>
                            <textarea name="heroSubtitle" 
                                      rows="2"
                                      placeholder="PRISE DE COMMANDE SUR WHATSAPP OU TELEGRAM"
                                      class="form-textarea"><?php echo htmlspecialchars($settings['heroSubtitle']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sections page d'accueil -->
                <div class="settings-section neon-border">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <h3 class="section-title" style="margin-bottom: 0;">📋 Sections de la page d'accueil</h3>
                        <button type="button" 
                                onclick="addSection()" 
                                class="add-section-btn">
                            + Ajouter une section
                        </button>
                    </div>
                    <div id="sections-container">
                        <?php foreach ($sections as $index => $section): ?>
                            <div class="section-item">
                                <div class="section-item-header">
                                    <div class="section-icon">
                                        <?php if (!empty($section['icon']) && strpos($section['icon'], 'http') !== false): ?>
                                            <img src="<?php echo htmlspecialchars($section['icon']); ?>" alt="Icon">
                                        <?php else: ?>
                                            <span><?php echo htmlspecialchars($section['icon'] ?: '📦'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="section-content">
                                        <input type="text" 
                                               name="sections[<?php echo $index; ?>][icon]" 
                                               value="<?php echo htmlspecialchars($section['icon']); ?>"
                                               placeholder="📦"
                                               class="form-input"
                                               style="display: none;">
                                        <input type="text" 
                                               name="sections[<?php echo $index; ?>][title]" 
                                               value="<?php echo htmlspecialchars($section['title']); ?>"
                                               placeholder="Titre de la section"
                                               class="form-input">
                                        <textarea name="sections[<?php echo $index; ?>][content]" 
                                                  rows="2"
                                                  placeholder="Contenu de la section"
                                                  class="form-textarea"><?php echo htmlspecialchars($section['content']); ?></textarea>
                                    </div>
                                    <?php if (count($sections) > 1): ?>
                                        <button type="button" 
                                                onclick="removeSection(this)" 
                                                class="remove-section-btn">
                                            🗑️
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Bouton sauvegarder -->
                <button type="submit" class="save-btn">
                    Enregistrer les modifications
                </button>
            </form>
        </main>
    </div>
    <script>
        let sectionCount = <?php echo count($sections); ?>;
        
        function addSection() {
            const container = document.getElementById('sections-container');
            const section = document.createElement('div');
            section.className = 'section-item';
            section.innerHTML = `
                <div class="section-item-header">
                    <div class="section-icon">
                        <span>📦</span>
                    </div>
                    <div class="section-content">
                        <input type="text" 
                               name="sections[${sectionCount}][icon]" 
                               value="📦"
                               placeholder="📦"
                               class="form-input"
                               style="display: none;">
                        <input type="text" 
                               name="sections[${sectionCount}][title]" 
                               placeholder="Titre de la section"
                               class="form-input">
                        <textarea name="sections[${sectionCount}][content]" 
                                  rows="2"
                                  placeholder="Contenu de la section"
                                  class="form-textarea"></textarea>
                    </div>
                    <button type="button" 
                            onclick="removeSection(this)" 
                            class="remove-section-btn">
                        🗑️
                    </button>
                </div>
            `;
            container.appendChild(section);
            sectionCount++;
        }
        
        function removeSection(btn) {
            const container = document.getElementById('sections-container');
            if (container.children.length > 1) {
                btn.closest('.section-item').remove();
            }
        }
        
        // Fonction pour uploader l'image de fond
        async function uploadBackgroundImage(input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            const statusDiv = document.getElementById('background-upload-status');
            statusDiv.textContent = '⏳ Upload en cours...';
            statusDiv.style.color = '#fff';
            
            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', 'photo');
                
                const response = await fetch('upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.url) {
                    // Mettre à jour le champ URL
                    document.getElementById('backgroundImage-url').value = result.url;
                    
                    // Afficher l'aperçu
                    const preview = document.getElementById('background-image-preview');
                    const container = document.getElementById('background-image-preview-container');
                    if (preview) {
                        preview.src = result.url;
                    }
                    if (container) {
                        container.style.display = 'block';
                    }
                    
                    statusDiv.textContent = '✅ Upload réussi !';
                    statusDiv.style.color = '#4ade80';
                } else {
                    throw new Error(result.error || 'Erreur lors de l\'upload');
                }
            } catch (error) {
                statusDiv.textContent = '❌ Erreur: ' + error.message;
                statusDiv.style.color = '#f87171';
            }
        }
        
        // Fonction pour mettre à jour l'aperçu depuis une URL
        function updateBackgroundPreview(url) {
            if (url) {
                const preview = document.getElementById('background-image-preview');
                const container = document.getElementById('background-image-preview-container');
                if (preview) {
                    preview.src = url;
                }
                if (container) {
                    container.style.display = 'block';
                }
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

