<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$config = [
    'enabled' => false,
    'text' => 'Chargement...',
    'duration' => 2000,
    'backgroundColor' => '#0d0f17',
    'style' => 'spinner',
    'backgroundType' => 'color',
    'backgroundImage' => '',
    'backgroundVideo' => ''
];

$error = null;
$success = null;

// Charger la configuration existante
try {
    $loadingResult = $supabase->request('GET', 'loading_page?order=created_at.desc&limit=1', null, true);
    if (!empty($loadingResult)) {
        $loading = $loadingResult[0];
        $config['enabled'] = $loading['enabled'] ?? false;
        $config['text'] = $loading['text'] ?? 'Chargement...';
        $config['duration'] = $loading['duration_ms'] ?? 2000;
        $config['backgroundColor'] = $loading['background_color'] ?? '#0d0f17';
        $config['style'] = $loading['style'] ?? 'spinner';
        $config['backgroundType'] = $loading['background_type'] ?? 'color';
        $config['backgroundImage'] = $loading['background_image_url'] ?? '';
        $config['backgroundVideo'] = $loading['background_video_url'] ?? '';
    }
} catch (Exception $e) {
    error_log("Erreur chargement loading: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $config['enabled'] = isset($_POST['enabled']);
        $config['text'] = $_POST['text'] ?? 'Chargement...';
        $config['duration'] = (int)($_POST['duration'] ?? 2000);
        $config['backgroundColor'] = $_POST['backgroundColor'] ?? '#0d0f17';
        $config['style'] = $_POST['style'] ?? 'spinner';
        $config['backgroundType'] = $_POST['backgroundType'] ?? 'color';
        $config['backgroundImage'] = $_POST['backgroundImage'] ?? '';
        $config['backgroundVideo'] = $_POST['backgroundVideo'] ?? '';
        
        // Sauvegarder dans Supabase
        $loadingData = [
            'enabled' => $config['enabled'],
            'text' => $config['text'],
            'duration_ms' => $config['duration'],
            'background_color' => $config['backgroundColor'],
            'style' => $config['style'],
            'background_type' => $config['backgroundType'],
            'background_image_url' => $config['backgroundImage'] ?: null,
            'background_video_url' => $config['backgroundVideo'] ?: null,
            'updated_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        $existingLoading = $supabase->request('GET', 'loading_page?order=created_at.desc&limit=1', null, true);
        if (!empty($existingLoading) && isset($existingLoading[0]['id'])) {
            $supabase->request('PATCH', 'loading_page?id=eq.' . $existingLoading[0]['id'], $loadingData, true);
        } else {
            $loadingData['created_at'] = date('Y-m-d\TH:i:s.u\Z');
            $supabase->request('POST', 'loading_page', $loadingData, true);
        }
        
        $success = 'Configuration sauvegardée avec succès !';
        
        // Recharger les données
        $loadingResult = $supabase->request('GET', 'loading_page?order=created_at.desc&limit=1', null, true);
        if (!empty($loadingResult)) {
            $loading = $loadingResult[0];
            $config['enabled'] = $loading['enabled'] ?? false;
            $config['text'] = $loading['text'] ?? 'Chargement...';
            $config['duration'] = $loading['duration_ms'] ?? 2000;
            $config['backgroundColor'] = $loading['background_color'] ?? '#0d0f17';
            $config['style'] = $loading['style'] ?? 'spinner';
            $config['backgroundType'] = $loading['background_type'] ?? 'color';
            $config['backgroundImage'] = $loading['background_image_url'] ?? '';
            $config['backgroundVideo'] = $loading['background_video_url'] ?? '';
        }
    } catch (Exception $e) {
        $error = 'Erreur: ' . $e->getMessage();
        error_log("Erreur sauvegarde loading: " . $e->getMessage());
    }
}

$styles = [
    ['value' => 'spinner', 'label' => 'Spinner', 'icon' => '🌀'],
    ['value' => 'progress', 'label' => 'Progression', 'icon' => '📊'],
    ['value' => 'dots', 'label' => 'Points', 'icon' => '⚫'],
    ['value' => 'pulse', 'label' => 'Pulsation', 'icon' => '💫'],
    ['value' => 'wave', 'label' => 'Vague', 'icon' => '🌊'],
    ['value' => 'orbit', 'label' => 'Orbite', 'icon' => '🪐'],
    ['value' => 'bars', 'label' => 'Barres', 'icon' => '📊'],
    ['value' => 'circle', 'label' => 'Cercles', 'icon' => '⭕']
];

$pageTitle = 'Page de Chargement - Panel Admin';
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
        .loading-config {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 1024px) {
            .loading-config {
                grid-template-columns: 1fr 1fr;
            }
        }
        .config-section {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
        }
        .section-title {
            color: #fff;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .toggle-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .toggle-switch input[type="checkbox"] {
            width: 3.5rem;
            height: 1.75rem;
            appearance: none;
            background: rgba(55, 65, 81, 1);
            border-radius: 9999px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
        }
        .toggle-switch input[type="checkbox"]:checked {
            background: rgba(37, 99, 235, 1);
        }
        .toggle-switch input[type="checkbox"]::before {
            content: '';
            position: absolute;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background: #fff;
            top: 0.125rem;
            left: 0.125rem;
            transition: transform 0.3s;
        }
        .toggle-switch input[type="checkbox"]:checked::before {
            transform: translateX(1.75rem);
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1rem;
        }
        .form-label {
            display: block;
            color: #fff;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .style-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
        }
        .style-btn {
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 2px solid rgba(55, 65, 81, 1);
            background: rgba(30, 41, 59, 1);
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .style-btn.active {
            border-color: rgba(37, 99, 235, 1);
            background: rgba(37, 99, 235, 0.2);
        }
        .style-btn-icon {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        .style-btn-label {
            color: #fff;
            font-size: 0.75rem;
        }
        .slider {
            width: 100%;
            height: 0.5rem;
            border-radius: 0.25rem;
            background: rgba(55, 65, 81, 1);
            outline: none;
            -webkit-appearance: none;
        }
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            background: rgba(37, 99, 235, 1);
            cursor: pointer;
        }
        .slider::-moz-range-thumb {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            background: rgba(37, 99, 235, 1);
            cursor: pointer;
            border: none;
        }
        .background-type-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }
        .background-type-btn {
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 2px solid rgba(55, 65, 81, 1);
            background: rgba(30, 41, 59, 1);
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            color: #fff;
        }
        .background-type-btn.active {
            border-color: rgba(37, 99, 235, 1);
            background: rgba(37, 99, 235, 0.2);
        }
        .color-input-wrapper {
            display: flex;
            gap: 0.75rem;
        }
        .color-picker {
            width: 5rem;
            height: 3rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(55, 65, 81, 1);
            cursor: pointer;
        }
        .preview-container {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }
        .preview-box {
            width: 100%;
            height: 24rem;
            border-radius: 0.5rem;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .preview-box img,
        .preview-box video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        .preview-icon {
            font-size: 3rem;
        }
        .preview-text {
            color: #fff;
            font-size: 1.125rem;
            font-weight: 700;
        }
        .save-btn {
            width: 100%;
            padding: 1rem;
            background: #000;
            color: #fff;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .save-btn:hover {
            background: rgba(17, 24, 39, 1);
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
            .preview-container {
                position: static;
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
                <h1 class="page-title">⏳ Page de Chargement</h1>
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

            <form method="POST" id="loading-form">
                <div class="loading-config">
                    <div>
                        <!-- Activer -->
                        <div class="config-section neon-border" style="margin-bottom: 1.5rem;">
                            <div class="toggle-switch">
                                <label class="section-title">Activer</label>
                                <input type="checkbox" 
                                       name="enabled" 
                                       id="enabled"
                                       <?php echo $config['enabled'] ? 'checked' : ''; ?>
                                       onchange="updatePreview()">
                            </div>
                        </div>

                        <!-- Texte -->
                        <div class="config-section neon-border" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Texte</label>
                            <input type="text" 
                                   name="text" 
                                   id="text"
                                   value="<?php echo htmlspecialchars($config['text']); ?>"
                                   class="form-input"
                                   oninput="updatePreview()">
                        </div>

                        <!-- Style -->
                        <div class="config-section neon-border" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Style</label>
                            <div class="style-grid">
                                <?php foreach ($styles as $style): ?>
                                    <button type="button" 
                                            class="style-btn <?php echo $config['style'] === $style['value'] ? 'active' : ''; ?>"
                                            onclick="selectStyle('<?php echo $style['value']; ?>')">
                                        <div class="style-btn-icon"><?php echo $style['icon']; ?></div>
                                        <div class="style-btn-label"><?php echo htmlspecialchars($style['label']); ?></div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="style" id="style" value="<?php echo htmlspecialchars($config['style']); ?>">
                        </div>

                        <!-- Durée -->
                        <div class="config-section neon-border" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Durée : <span id="duration-value"><?php echo $config['duration']; ?></span>ms</label>
                            <input type="range" 
                                   name="duration" 
                                   id="duration"
                                   min="1000" 
                                   max="10000" 
                                   step="100" 
                                   value="<?php echo $config['duration']; ?>"
                                   class="slider"
                                   oninput="document.getElementById('duration-value').textContent = this.value; updatePreview();">
                        </div>

                        <!-- Type de fond -->
                        <div class="config-section neon-border" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Type de fond</label>
                            <div class="background-type-grid">
                                <button type="button" 
                                        class="background-type-btn <?php echo $config['backgroundType'] === 'color' ? 'active' : ''; ?>"
                                        onclick="selectBackgroundType('color')">
                                    🎨 Couleur
                                </button>
                                <button type="button" 
                                        class="background-type-btn <?php echo $config['backgroundType'] === 'image' ? 'active' : ''; ?>"
                                        onclick="selectBackgroundType('image')">
                                    🖼️ Image
                                </button>
                                <button type="button" 
                                        class="background-type-btn <?php echo $config['backgroundType'] === 'video' ? 'active' : ''; ?>"
                                        onclick="selectBackgroundType('video')">
                                    🎥 Vidéo
                                </button>
                            </div>
                            <input type="hidden" name="backgroundType" id="backgroundType" value="<?php echo htmlspecialchars($config['backgroundType']); ?>">
                        </div>

                        <!-- Couleur -->
                        <div class="config-section neon-border" data-section="color" style="margin-bottom: 1.5rem; display: <?php echo $config['backgroundType'] === 'color' ? 'block' : 'none'; ?>;">
                            <label class="form-label">Couleur de fond</label>
                            <div class="color-input-wrapper">
                                <input type="color" 
                                       name="backgroundColor" 
                                       id="backgroundColor"
                                       value="<?php echo htmlspecialchars($config['backgroundColor']); ?>"
                                       class="color-picker"
                                       onchange="updatePreview()">
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($config['backgroundColor']); ?>"
                                       class="form-input"
                                       oninput="document.getElementById('backgroundColor').value = this.value; updatePreview();">
                            </div>
                        </div>

                        <!-- Image -->
                        <div class="config-section neon-border" data-section="image" style="margin-bottom: 1.5rem; display: <?php echo $config['backgroundType'] === 'image' ? 'block' : 'none'; ?>;">
                            <label class="form-label">Image de fond</label>
                            <div id="loading-image-preview-container" style="margin-bottom: 1rem; <?php echo empty($config['backgroundImage']) ? 'display: none;' : ''; ?>">
                                <?php if ($config['backgroundImage']): ?>
                                    <div style="margin-bottom: 0.75rem;">
                                        <img src="<?php echo htmlspecialchars($config['backgroundImage']); ?>" 
                                             alt="Fond" 
                                             id="loading-image-preview"
                                             style="width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">📤 Upload depuis votre appareil</label>
                                    <input type="file" 
                                           id="loading-image-upload"
                                           accept="image/*" 
                                           class="form-input"
                                           onchange="uploadLoadingImage(this)">
                                    <div id="loading-upload-status" style="margin-top: 0.5rem; font-size: 0.75rem; color: #fff;"></div>
                                </div>
                                <div>
                                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">🔗 Ou entrez une URL</label>
                                    <input type="url" 
                                           name="backgroundImage" 
                                           id="loading-backgroundImage-url"
                                           value="<?php echo htmlspecialchars($config['backgroundImage'] ?? ''); ?>"
                                           placeholder="https://example.com/image.jpg"
                                           onchange="updateLoadingImagePreview(this.value)"
                                           class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Vidéo -->
                        <div class="config-section neon-border" data-section="video" style="margin-bottom: 1.5rem; display: <?php echo $config['backgroundType'] === 'video' ? 'block' : 'none'; ?>;">
                            <label class="form-label">Vidéo de fond</label>
                            <?php if ($config['backgroundVideo']): ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <video src="<?php echo htmlspecialchars($config['backgroundVideo']); ?>" 
                                           style="width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem;" 
                                           muted></video>
                                </div>
                            <?php endif; ?>
                            <input type="file" 
                                   accept="video/*" 
                                   class="form-input"
                                   onchange="handleVideoUpload(this)">
                            <input type="hidden" name="backgroundVideo" id="backgroundVideo" value="<?php echo htmlspecialchars($config['backgroundVideo']); ?>">
                        </div>

                        <button type="submit" class="save-btn">
                            <span>💾</span>
                            <span>Sauvegarder</span>
                        </button>
                    </div>

                    <!-- Aperçu -->
                    <div class="preview-container">
                        <div class="config-section neon-border">
                            <h3 class="section-title" style="text-align: center; margin-bottom: 1rem;">👁️ Aperçu</h3>
                            <div class="preview-box" id="preview-box">
                                <div class="preview-content">
                                    <div class="preview-icon" id="preview-icon">⏳</div>
                                    <div class="preview-text" id="preview-text"><?php echo htmlspecialchars($config['text']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
    <script>
        function selectStyle(style) {
            document.getElementById('style').value = style;
            document.querySelectorAll('.style-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.style-btn').classList.add('active');
            updatePreview();
        }
        
        function selectBackgroundType(type) {
            document.getElementById('backgroundType').value = type;
            document.querySelectorAll('.background-type-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.background-type-btn').classList.add('active');
            
            // Afficher/masquer les sections appropriées sans recharger
            const colorSection = document.querySelector('[data-section="color"]');
            const imageSection = document.querySelector('[data-section="image"]');
            const videoSection = document.querySelector('[data-section="video"]');
            
            if (colorSection) colorSection.style.display = type === 'color' ? 'block' : 'none';
            if (imageSection) imageSection.style.display = type === 'image' ? 'block' : 'none';
            if (videoSection) videoSection.style.display = type === 'video' ? 'block' : 'none';
            
            updatePreview();
        }
        
        function updatePreview() {
            const enabled = document.getElementById('enabled').checked;
            const text = document.getElementById('text').value;
            const style = document.getElementById('style').value;
            const backgroundType = document.getElementById('backgroundType').value;
            const backgroundColor = document.getElementById('backgroundColor')?.value || '#0d0f17';
            const backgroundImage = document.getElementById('loading-backgroundImage-url')?.value || '';
            const backgroundVideo = document.getElementById('backgroundVideo')?.value || '';
            
            const previewBox = document.getElementById('preview-box');
            const previewText = document.getElementById('preview-text');
            const previewIcon = document.getElementById('preview-icon');
            
            previewText.textContent = text;
            
            // Style icon
            const styleIcons = {
                'spinner': '🌀',
                'progress': '📊',
                'dots': '⚫',
                'pulse': '💫',
                'wave': '🌊',
                'orbit': '🪐',
                'bars': '📊',
                'circle': '⭕'
            };
            previewIcon.textContent = styleIcons[style] || '⏳';
            
            // Background
            previewBox.style.backgroundColor = backgroundType === 'color' ? backgroundColor : '#000';
            
            // Remove existing background media
            const existingImg = previewBox.querySelector('img');
            const existingVideo = previewBox.querySelector('video');
            if (existingImg) existingImg.remove();
            if (existingVideo) existingVideo.remove();
            
            if (backgroundType === 'image' && backgroundImage) {
                const img = document.createElement('img');
                img.src = backgroundImage;
                img.style.position = 'absolute';
                img.style.inset = '0';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                previewBox.appendChild(img);
            } else if (backgroundType === 'video' && backgroundVideo) {
                const video = document.createElement('video');
                video.src = backgroundVideo;
                video.autoplay = true;
                video.loop = true;
                video.muted = true;
                video.style.position = 'absolute';
                video.style.inset = '0';
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.objectFit = 'cover';
                previewBox.appendChild(video);
            }
        }
        
        // Fonction pour uploader l'image de fond de la page de chargement
        async function uploadLoadingImage(input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            const statusDiv = document.getElementById('loading-upload-status');
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
                    document.getElementById('loading-backgroundImage-url').value = result.url;
                    
                    // Afficher l'aperçu
                    const container = document.getElementById('loading-image-preview-container');
                    let preview = document.getElementById('loading-image-preview');
                    
                    if (!preview) {
                        const previewDiv = document.createElement('div');
                        previewDiv.style.marginBottom = '0.75rem';
                        preview = document.createElement('img');
                        preview.id = 'loading-image-preview';
                        preview.alt = 'Fond';
                        preview.style.cssText = 'width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem;';
                        previewDiv.appendChild(preview);
                        container.appendChild(previewDiv);
                    }
                    
                    preview.src = result.url;
                    if (container) {
                        container.style.display = 'block';
                    }
                    
                    // Mettre à jour le preview
                    updatePreview();
                    
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
        function updateLoadingImagePreview(url) {
            if (url) {
                const container = document.getElementById('loading-image-preview-container');
                let preview = document.getElementById('loading-image-preview');
                
                if (!preview) {
                    const previewDiv = document.createElement('div');
                    previewDiv.style.marginBottom = '0.75rem';
                    preview = document.createElement('img');
                    preview.id = 'loading-image-preview';
                    preview.alt = 'Fond';
                    preview.style.cssText = 'width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem;';
                    previewDiv.appendChild(preview);
                    container.appendChild(previewDiv);
                }
                
                preview.src = url;
                if (container) {
                    container.style.display = 'block';
                }
                
                // Mettre à jour le preview
                updatePreview();
            }
        }
        
        // Ancienne fonction pour compatibilité
        function handleImageUpload(input) {
            // Rediriger vers la nouvelle fonction
            uploadLoadingImage(input);
        }
        
        function handleVideoUpload(input) {
            // À implémenter avec upload vers Supabase Storage
            console.log('Upload video:', input.files[0]);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('backgroundVideo').value = e.target.result;
                    updatePreview();
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Initialiser l'aperçu
        updatePreview();
        
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

