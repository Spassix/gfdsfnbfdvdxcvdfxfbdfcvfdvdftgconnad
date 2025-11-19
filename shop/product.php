<?php
// Activer le buffer de sortie pour éviter le flash blanc
ob_start();

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

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: products.php');
    exit;
}

$product = getProduct($id);
if (!$product) {
    header('Location: products.php');
    exit;
}

$categories = getCategories();
$farms = getFarms();

// Construire les médias
$medias = [];
if (!empty($product['photo'])) {
    $medias[] = $product['photo'];
}
if (!empty($product['image']) && $product['image'] !== ($product['photo'] ?? '')) {
    $medias[] = $product['image'];
}
if (!empty($product['video'])) {
    $medias[] = $product['video'];
}
if (!empty($product['media']) && !in_array($product['media'], $medias)) {
    $medias[] = $product['media'];
}
if (!empty($product['medias']) && is_array($product['medias'])) {
    foreach ($product['medias'] as $media) {
        if ($media && !in_array($media, $medias)) {
            $medias[] = $media;
        }
    }
}

$selectedMedia = $_GET['media'] ?? 0;
$selectedMedia = isset($medias[$selectedMedia]) ? $selectedMedia : 0;
$currentMedia = $medias[$selectedMedia] ?? ($medias[0] ?? null);

// Construire les variantes
$variants = [];
if (!empty($product['variants']) && is_array($product['variants'])) {
    $variants = $product['variants'];
} elseif (!empty($product['quantities']) && is_array($product['quantities'])) {
    foreach ($product['quantities'] as $qty) {
        $variants[] = [
            'name' => ($qty['grammage'] ?? '') . ($qty['unit'] ?? ''),
            'price' => ($qty['price'] ?? 0) . '€'
        ];
    }
} elseif (!empty($product['price'])) {
    $variants = [[
        'name' => 'Standard',
        'price' => (is_numeric($product['price']) ? $product['price'] . '€' : $product['price'])
    ]];
} else {
    $variants = [['name' => 'Standard', 'price' => 'Prix sur demande']];
}

$selectedVariant = $_GET['variant'] ?? 0;
$selectedVariant = isset($variants[$selectedVariant]) ? $selectedVariant : 0;
$currentVariant = $variants[$selectedVariant] ?? $variants[0];

// Trouver les noms de catégorie et farm avec leurs emojis
$categoryName = null;
$categoryEmoji = null;
$farmName = null;
$farmEmoji = null;

if (is_numeric($product['category'] ?? null)) {
    $cat = array_filter($categories, fn($c) => $c['id'] == $product['category']);
    if (!empty($cat)) {
        $catData = reset($cat);
        $categoryName = $catData['name'] ?? null;
        $categoryEmoji = $catData['emoji'] ?? null;
    }
}

if (is_numeric($product['farm'] ?? null)) {
    $f = array_filter($farms, fn($fm) => $fm['id'] == $product['farm']);
    if (!empty($f)) {
        $farmData = reset($f);
        $farmName = $farmData['name'] ?? null;
        $farmEmoji = $farmData['emoji'] ?? null;
    }
}

// Récupérer les paramètres de commande
$orderSettings = getSettings('order');
$orderLink = $orderSettings['orderLink'] ?? '#';
$orderButtonText = $orderSettings['orderButtonText'] ?? 'Commander';

// Récupérer les avis pour ce produit
$productReviews = [];
$averageRating = 0;
$reviewCount = 0;
try {
    global $supabase;
    $reviewsResult = $supabase->request('GET', 'reviews?product_id=eq.' . $id . '&approved=eq.true&select=*', null, false);
    $productReviews = $reviewsResult ?? [];
    $reviewCount = count($productReviews);
    if ($reviewCount > 0) {
        $totalRating = 0;
        foreach ($productReviews as $rev) {
            $totalRating += intval($rev['rating'] ?? 5);
        }
        $averageRating = round($totalRating / $reviewCount, 1);
    }
} catch (Exception $e) {
    error_log("Erreur récupération avis produit: " . $e->getMessage());
}

// Fonction pour détecter si c'est une vidéo
function isVideo($url) {
    if (!$url) return false;
    $videoExtensions = ['.mp4', '.webm', '.mov', '.MOV', '.avi', '.mkv', '.m4v'];
    foreach ($videoExtensions as $ext) {
        if (stripos($url, $ext) !== false) return true;
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Boutique</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-detail-page {
            padding-top: 5rem;
            padding-bottom: 4rem;
        }
        .breadcrumb {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #fff;
            font-size: 0.875rem;
        }
        .breadcrumb a {
            color: #fff;
            text-decoration: none;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        .breadcrumb a:hover {
            background: rgba(0, 0, 0, 1);
        }
        .breadcrumb span:not(:has(a)) {
            color: #fff;
        }
        .breadcrumb .product-name {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            color: #fff;
            display: inline-block;
        }
        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        @media (min-width: 1024px) {
            .product-detail-grid {
                grid-template-columns: 1fr 1fr;
                gap: 3rem;
            }
        }
        .media-gallery {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .main-media {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            overflow: hidden;
            background: rgba(17, 17, 17, 0.6);
            backdrop-filter: blur(4px);
            aspect-ratio: 1 / 1;
        }
        .main-media-content {
            width: 100%;
            height: 100%;
        }
        .main-media-content img,
        .main-media-content video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .main-media-content video {
            object-fit: contain;
            background: #000;
        }
        .media-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9rem;
        }
        .media-thumbnails {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
        }
        .media-thumbnail {
            aspect-ratio: 1 / 1;
            border-radius: 0.5rem;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid rgba(55, 65, 81, 0.3);
            transition: all 0.3s;
        }
        .media-thumbnail:hover {
            border-color: rgba(255, 255, 255, 0.5);
        }
        .media-thumbnail.active {
            border-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        .media-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-thumbnail-video {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(30, 41, 59, 1);
            font-size: 1.5rem;
        }
        .product-info-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .product-title {
            font-size: 2.25rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1rem;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        @media (min-width: 768px) {
            .product-title {
                font-size: 3rem;
            }
        }
        .product-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .product-badge {
            padding: 0.25rem 0.75rem;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 9999px;
            color: #fff;
            font-size: 0.875rem;
        }
        .info-card {
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(24px);
        }
        .info-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1rem;
        }
        .info-card p {
            color: #fff;
            line-height: 1.75;
            white-space: pre-line;
        }
        .variants-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .variant-item {
            width: 100%;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 2px solid rgba(55, 65, 81, 0.3);
            background: rgba(30, 41, 59, 0.5);
            color: #fff;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .variant-item:hover {
            border-color: rgba(255, 255, 255, 0.5);
        }
        .variant-item.active {
            border-color: #fff;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .variant-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .variant-check {
            font-size: 1.5rem;
        }
        .variant-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
        }
        .variant-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        .quantity-selector {
            margin-bottom: 1rem;
        }
        .quantity-selector label {
            display: block;
            color: #fff;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .quantity-button {
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(55, 65, 81, 1);
            border: none;
            border-radius: 0.25rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            cursor: pointer;
        }
        .quantity-button:hover {
            background: rgba(75, 85, 99, 1);
        }
        .quantity-value {
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            width: 3rem;
            text-align: center;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .action-button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .action-button-primary {
            background: linear-gradient(135deg, #111111 0%, #2a2a2a 100%);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
        }
        .action-button-primary:hover {
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        .action-button-secondary {
            background: linear-gradient(135deg, #2a2a2a 0%, #111111 100%);
            color: #fff;
            font-size: 1.125rem;
            padding: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
        }
        .action-button-secondary:hover {
            background: linear-gradient(135deg, #333333 0%, #1a1a1a 100%);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
            transform: scale(1.05) translateY(-2px);
        }
        .error-message {
            background: rgba(127, 29, 29, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
        }
        .error-message p {
            color: rgba(248, 113, 113, 1);
            font-size: 1.25rem;
            margin-bottom: 1rem;
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
            background: rgba(17, 17, 17, 0.7);
        }
        <?php endif; ?>
    </style>
    <script src="devtools_blocker.js"></script>
    <script src="assets/js/loading.js"></script>
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <div class="cosmic-bg product-detail-page">
        <div class="max-w-7xl mx-auto px-4">
            <?php if (!$product): ?>
                <div class="error-message">
                    <p>❌ Produit non trouvé</p>
                    <a href="products.php" style="color: #fff; text-decoration: none; padding: 0.75rem 1.5rem; background: #fff; color: #000; border-radius: 0.5rem; display: inline-block;">
                        ← Retour aux produits
                    </a>
                </div>
            <?php else: ?>
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="index.php">Accueil</a>
                    <span>/</span>
                    <a href="products.php">Produits</a>
                    <span>/</span>
                    <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                </div>

                <div class="product-detail-grid">
                    <!-- Galerie Médias -->
                    <div class="media-gallery">
                        <!-- Média Principal -->
                        <div class="main-media neon-border">
                            <div class="main-media-content">
                                <?php if ($currentMedia): ?>
                                    <?php if (isVideo($currentMedia)): ?>
                                        <video src="<?php echo htmlspecialchars($currentMedia); ?>" 
                                               controls 
                                               loop 
                                               playsinline
                                               class="w-full h-full"></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($currentMedia); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="w-full h-full">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="media-placeholder">🎁</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Miniatures -->
                        <?php if (count($medias) > 1): ?>
                            <div class="media-thumbnails">
                                <?php foreach ($medias as $index => $media): ?>
                                    <div class="media-thumbnail <?php echo $selectedMedia == $index ? 'active' : ''; ?>" 
                                         onclick="window.location='?id=<?php echo $id; ?>&media=<?php echo $index; ?>&variant=<?php echo $selectedVariant; ?>'">
                                        <?php if (isVideo($media)): ?>
                                            <div class="media-thumbnail-video">🎥</div>
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($media); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?> <?php echo $index + 1; ?>">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Informations Produit -->
                    <div class="product-info-section">
                        <!-- Titre et Badges -->
                        <div>
                            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                            
                            <!-- Avis et étoiles -->
                            <?php if ($reviewCount > 0): ?>
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin: 1rem 0;">
                                    <div style="color: #fbbf24; font-size: 1.25rem;">
                                        <?php echo str_repeat('⭐', round($averageRating)); ?><?php echo str_repeat('☆', 5 - round($averageRating)); ?>
                                    </div>
                                    <span style="color: #fff; font-size: 1rem; font-weight: 600;"><?php echo $averageRating; ?>/5</span>
                                    <span style="color: #9ca3af; font-size: 0.875rem;">(<?php echo $reviewCount; ?> avis<?php echo $reviewCount > 1 ? 's' : ''; ?>)</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-badges">
                                <?php if ($categoryName): ?>
                                    <span class="product-badge">
                                        <?php if ($categoryEmoji): ?>
                                            <?php echo htmlspecialchars($categoryEmoji); ?> 
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($categoryName); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($farmName): ?>
                                    <span class="product-badge">
                                        <?php if ($farmEmoji): ?>
                                            <?php echo htmlspecialchars($farmEmoji); ?> 
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($farmName); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Description -->
                        <?php if (!empty($product['description'])): ?>
                            <div class="info-card neon-border">
                                <h3>📝 Description</h3>
                                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Variantes -->
                        <div class="info-card neon-border">
                            <h3>💰 Options & Prix</h3>
                            <div class="variants-list">
                                <?php foreach ($variants as $index => $variant): ?>
                                    <div class="variant-item <?php echo $selectedVariant == $index ? 'active' : ''; ?>" 
                                         onclick="window.location='?id=<?php echo $id; ?>&variant=<?php echo $index; ?>&media=<?php echo $selectedMedia; ?>'">
                                        <div class="variant-info">
                                            <span class="variant-check"><?php echo $selectedVariant == $index ? '✓' : '○'; ?></span>
                                            <div>
                                                <div class="variant-name"><?php echo htmlspecialchars($variant['name']); ?></div>
                                            </div>
                                        </div>
                                        <div class="variant-price"><?php echo htmlspecialchars($variant['price']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Quantité et Boutons -->
                        <div class="info-card neon-border">
                            <form method="POST" action="cart.php?action=add">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                <input type="hidden" name="variant_name" value="<?php echo htmlspecialchars($currentVariant['name']); ?>">
                                <input type="hidden" name="price" value="<?php echo is_numeric($currentVariant['price']) ? $currentVariant['price'] : preg_replace('/[^0-9.]/', '', $currentVariant['price']); ?>">
                                
                                <!-- Sélecteur de quantité -->
                                <div class="quantity-selector">
                                    <label>Quantité</label>
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-button" onclick="changeQuantity(-1)">-</button>
                                        <span class="quantity-value" id="quantityValue">1</span>
                                        <button type="button" class="quantity-button" onclick="changeQuantity(1)">+</button>
                                    </div>
                                    <input type="hidden" name="quantity" id="quantityInput" value="1">
                                </div>

                                <!-- Boutons d'action -->
                                <div class="action-buttons">
                                    <button type="submit" class="action-button action-button-primary">
                                        <span>🛒</span>
                                        <span>Ajouter au panier</span>
                                    </button>
                                    
                                    <button type="button" class="action-button action-button-secondary" onclick="buyNow()">
                                        <span>⚡</span>
                                        <span>Acheter maintenant</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Section Avis -->
                        <?php if ($reviewCount > 0): ?>
                            <div class="info-card neon-border" style="margin-top: 2rem;">
                                <h3 style="font-size: 1.5rem; font-weight: 700; color: #fff; margin-bottom: 1.5rem;">💬 Avis Clients (<?php echo $reviewCount; ?>)</h3>
                                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                    <?php foreach ($productReviews as $review): ?>
                                        <div style="border-bottom: 1px solid rgba(55, 65, 81, 0.5); padding-bottom: 1.5rem;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                                                <div>
                                                    <div style="font-weight: 700; color: #fff; font-size: 1.125rem; margin-bottom: 0.25rem;">
                                                        <?php echo htmlspecialchars($review['author'] ?? 'Anonyme'); ?>
                                                    </div>
                                                    <div style="color: #fbbf24; font-size: 0.875rem;">
                                                        <?php 
                                                        $rating = intval($review['rating'] ?? 5);
                                                        echo str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);
                                                        ?>
                                                    </div>
                                                </div>
                                                <?php if (!empty($review['created_at'])): ?>
                                                    <div style="color: #9ca3af; font-size: 0.75rem;">
                                                        <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <p style="color: #fff; white-space: pre-wrap; margin-bottom: 0.75rem;">
                                                <?php echo nl2br(htmlspecialchars($review['text'] ?? '')); ?>
                                            </p>
                                            <?php if (!empty($review['image_url'])): ?>
                                                <div style="margin-top: 0.75rem;">
                                                    <img src="<?php echo htmlspecialchars($review['image_url']); ?>" 
                                                         alt="Avis" 
                                                         style="max-width: 200px; border-radius: 0.5rem;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let quantity = 1;
        
        function changeQuantity(delta) {
            quantity = Math.max(1, quantity + delta);
            document.getElementById('quantityValue').textContent = quantity;
            document.getElementById('quantityInput').value = quantity;
        }
        
        function buyNow() {
            const form = document.querySelector('form');
            const formData = new FormData(form);
            
            // Ajouter au panier via fetch
            fetch('cart.php?action=add', {
                method: 'POST',
                body: formData
            }).then(() => {
                window.location.href = 'cart.php';
            });
        }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
