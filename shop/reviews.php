<?php
// Démarrer la session avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../telegram_guard.php';
require_once __DIR__ . '/config.php';

// Vérifier si la maintenance est activée
checkMaintenance();

// Récupérer l'image de fond
$backgroundImage = getBackgroundImage();

// Récupérer les avis approuvés
$reviews = [];
try {
    global $supabase;
    $result = $supabase->request('GET', 'reviews?approved=eq.true', null, false);
    $reviews = $result ?? [];
} catch (Exception $e) {
    error_log("Erreur récupération avis: " . $e->getMessage());
}

// Récupérer tous les produits pour le formulaire
$allProducts = getProducts(['active' => true]);

// Traiter la soumission du formulaire
$success = false;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'submit') {
    $author = trim($_POST['author'] ?? '');
    $rating = intval($_POST['rating'] ?? 5);
    $text = trim($_POST['text'] ?? '');
    $productId = $_POST['product_id'] ?? null;
    $imageUrl = trim($_POST['image_url'] ?? '');
    
    if (empty($author) || empty($text) || $rating < 1 || $rating > 5) {
        $error = 'Veuillez remplir tous les champs correctement';
    } else {
        try {
            // Construire les données de base (sans product_id ni created_at qui sont optionnels/automatiques)
            $reviewData = [
                'author' => trim($author),
                'text' => trim($text),
                'rating' => intval($rating),
                'approved' => false
            ];
            
            // Ajouter product_id seulement s'il est valide (UUID valide) ET si la colonne existe
            $cleanProductId = trim($productId ?? '');
            if (!empty($cleanProductId) && $cleanProductId !== '0' && strlen($cleanProductId) > 10) {
                // Vérifier le format UUID
                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $cleanProductId)) {
                    // Ne pas ajouter product_id si la colonne n'existe pas dans le schéma
                    // On essaie quand même mais on ne l'inclut que si c'est valide
                    $reviewData['product_id'] = $cleanProductId;
                }
            }
            // Si product_id est vide ou invalide, on ne l'inclut PAS du tout dans $reviewData
            
            // Ajouter image_url seulement s'il est rempli
            $cleanImageUrl = trim($imageUrl ?? '');
            if (!empty($cleanImageUrl)) {
                $reviewData['image_url'] = $cleanImageUrl;
            }
            
            global $supabase;
            // Utiliser la clé anonyme pour l'insertion (les politiques RLS doivent permettre l'insertion)
            // Ne pas inclure product_id s'il est null ou vide
            $finalData = array_filter($reviewData, function($value, $key) {
                // Exclure product_id si null ou vide
                if ($key === 'product_id' && (empty($value) || $value === null)) {
                    return false;
                }
                // Ne pas inclure les valeurs null
                return $value !== null && $value !== '';
            }, ARRAY_FILTER_USE_BOTH);
            
            // S'assurer que les champs requis sont présents
            if (empty($finalData['author']) || empty($finalData['text']) || !isset($finalData['rating'])) {
                throw new Exception('Champs requis manquants');
            }
            
            // Utiliser la clé anonyme (false) pour permettre l'insertion publique
            $result = $supabase->request('POST', 'reviews', $finalData, false);
            $success = true;
        } catch (Exception $e) {
            $error = 'Erreur lors de l\'envoi de l\'avis: ' . $e->getMessage();
            error_log("Erreur insertion avis: " . $e->getMessage() . " | Data: " . json_encode($reviewData ?? []));
        }
    }
}

$showForm = $_GET['showForm'] ?? false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis Clients - Boutique</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .reviews-page {
            padding-top: 5rem;
            padding-bottom: 4rem;
        }
        @media (max-width: 767px) {
            .reviews-page {
                padding-top: 4.5rem;
            }
        }
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .page-title-container {
            display: inline-block;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            padding: 1.5rem 2rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            margin-bottom: 1.5rem;
        }
        .page-title {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        .page-subtitle {
            font-size: 1.125rem;
            color: #fff;
        }
        .add-review-button {
            text-align: center;
            margin-bottom: 2rem;
        }
        .btn-add-review {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(to right, #db2777, #9333ea);
            color: #fff;
            font-weight: 700;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-add-review:hover {
            background: linear-gradient(to right, #be185d, #7e22ce);
        }
        .review-form-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            margin-bottom: 3rem;
        }
        .review-form-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.5rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1rem;
        }
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #9ca3af;
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-submit {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: #2563eb;
            color: #fff;
            font-weight: 700;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .form-submit:hover {
            background: #1d4ed8;
        }
        .form-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .image-preview {
            margin-top: 1rem;
        }
        .image-preview img {
            max-width: 300px;
            border-radius: 0.5rem;
        }
        .reviews-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 768px) {
            .reviews-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .reviews-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .review-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
        }
        .review-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .review-author {
            font-weight: 700;
            font-size: 1.125rem;
            color: #fff;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            display: inline-block;
        }
        .review-stars {
            color: #fbbf24;
            font-size: 0.875rem;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-block;
        }
        .review-text {
            color: #fff;
            margin-bottom: 1rem;
            white-space: pre-wrap;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            line-height: 1.6;
        }
        .review-image {
            margin-bottom: 1rem;
        }
        .review-image img {
            width: 100%;
            border-radius: 0.5rem;
        }
        .review-date {
            font-size: 0.75rem;
            color: #fff;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-block;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .empty-state p {
            color: #fff;
            font-size: 1.125rem;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            display: inline-block;
        }
        <?php if ($backgroundImage): ?>
        body {
            background-image: url('<?php echo htmlspecialchars($backgroundImage); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        .cosmic-bg {
            background-image: url('<?php echo htmlspecialchars($backgroundImage); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        .cosmic-bg::before {
            background: rgba(15, 23, 42, 0.7);
        }
        <?php endif; ?>
    </style>
    <script src="devtools_blocker.js"></script>
    <script src="assets/js/loading.js"></script>
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <div class="cosmic-bg reviews-page">
        <div class="max-w-6xl mx-auto px-4">
            <!-- Header -->
            <div class="page-header">
                <div class="page-title-container">
                    <h1 class="page-title">💬 Avis Clients</h1>
                    <p class="page-subtitle">Partagez votre expérience avec nous</p>
                </div>
            </div>

            <!-- Bouton pour ajouter un avis -->
            <div class="add-review-button">
                <button onclick="toggleForm()" class="btn-add-review">
                    <?php echo $showForm ? '✕ Annuler' : '+ Ajouter un avis'; ?>
                </button>
            </div>

            <!-- Messages de succès/erreur -->
            <?php if ($success): ?>
                <div class="alert-message" style="background: rgba(20, 83, 45, 0.9); border: 2px solid rgba(34, 197, 94, 1); margin-bottom: 2rem; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);">
                    <p style="color: #fff; font-weight: 700; font-size: 1.125rem; text-align: center;">✅ Votre avis a été envoyé ! Il sera publié après validation par l'administrateur.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-message" style="background: rgba(127, 29, 29, 0.9); border: 2px solid rgba(239, 68, 68, 1); margin-bottom: 2rem; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">
                    <p style="color: #fff; font-weight: 700; font-size: 1.125rem; text-align: center;">❌ <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout d'avis -->
            <?php if ($showForm): ?>
                <div class="review-form-card neon-border">
                    <h2>Votre avis</h2>
                    <form method="POST" action="reviews.php?action=submit" onsubmit="return submitReview(event)">
                        <div class="form-group">
                            <label>Votre nom *</label>
                            <input type="text" name="author" class="form-input" required value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Produit à noter (optionnel)</label>
                            <select name="product_id" class="form-select">
                                <option value="">Aucun produit spécifique</option>
                                <?php foreach ($allProducts as $prod): ?>
                                    <option value="<?php echo $prod['id']; ?>" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $prod['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prod['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note (1-5 étoiles) *</label>
                            <select name="rating" class="form-select" required>
                                <option value="5" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 5) ? 'selected' : ''; ?>>5 ⭐⭐⭐⭐⭐</option>
                                <option value="4" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 4) ? 'selected' : ''; ?>>4 ⭐⭐⭐⭐☆</option>
                                <option value="3" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 3) ? 'selected' : ''; ?>>3 ⭐⭐⭐☆☆</option>
                                <option value="2" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 2) ? 'selected' : ''; ?>>2 ⭐⭐☆☆☆</option>
                                <option value="1" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 1) ? 'selected' : ''; ?>>1 ⭐☆☆☆☆</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Votre avis *</label>
                            <textarea name="text" rows="5" class="form-textarea" placeholder="Partagez votre expérience..." required><?php echo htmlspecialchars($_POST['text'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Photo (optionnel)</label>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <input type="file" id="review-image-file" accept="image/*" class="form-input" onchange="uploadReviewImage(this)">
                                <small style="color: #9ca3af;">Ou entrez une URL</small>
                                <input type="url" name="image_url" id="review-image-url" class="form-input" placeholder="https://example.com/image.jpg" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>" onchange="previewImageUrl(this.value)">
                            </div>
                            <div id="imagePreview" class="image-preview"></div>
                            <div id="review-upload-status" style="margin-top: 0.5rem; font-size: 0.875rem;"></div>
                        </div>
                        <button type="submit" class="form-submit" id="submitBtn">📤 Envoyer mon avis</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Liste des avis -->
            <?php if (!empty($reviews)): ?>
                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card neon-border">
                            <div class="review-header">
                                <h3 class="review-author"><?php echo htmlspecialchars($review['author'] ?? ''); ?></h3>
                                <div class="review-stars">
                                    <?php 
                                    $rating = $review['rating'] ?? 5;
                                    echo str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);
                                    ?>
                                </div>
                            </div>
                            <?php if (!empty($review['products']) && is_array($review['products']) && !empty($review['products'][0])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <span style="color: #8b5cf6; font-size: 0.875rem; font-weight: 600;">📦 <?php echo htmlspecialchars($review['products'][0]['name'] ?? ''); ?></span>
                                </div>
                            <?php endif; ?>
                            <p class="review-text"><?php echo nl2br(htmlspecialchars($review['text'] ?? '')); ?></p>
                            <?php if (!empty($review['image_url'])): ?>
                                <div class="review-image">
                                    <img src="<?php echo htmlspecialchars($review['image_url']); ?>" alt="Avis">
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($review['created_at'])): ?>
                                <p class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></p>
                            <?php elseif (!empty($review['createdAt'])): ?>
                                <p class="review-date"><?php echo date('d/m/Y', strtotime($review['createdAt'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleForm() {
            const showForm = <?php echo $showForm ? 'false' : 'true'; ?>;
            window.location.href = 'reviews.php?showForm=' + showForm;
        }
        
        async function uploadReviewImage(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const statusDiv = document.getElementById('review-upload-status');
            statusDiv.textContent = '⏳ Upload en cours...';
            statusDiv.style.color = '#fff';
            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', 'photo');
                const response = await fetch('upload_review.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success && result.url) {
                    document.getElementById('review-image-url').value = result.url;
                    previewImageUrl(result.url);
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
        
        function previewImageUrl(url) {
            const preview = document.getElementById('imagePreview');
            if (url) {
                preview.innerHTML = '<img src="' + url + '" alt="Aperçu" style="max-width: 300px; border-radius: 0.5rem; margin-top: 0.5rem;">';
            } else {
                preview.innerHTML = '';
            }
        }
        
        function submitReview(event) {
            const form = event.target;
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Envoi...';
            
            // Le formulaire sera soumis normalement (pas de fetch)
            return true;
        }
    </script>
</body>
</html>

