<?php
// Démarrer la session avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../telegram_guard.php';
require_once __DIR__ . '/config.php';

// Vérifier si la maintenance est activée
checkMaintenance();

$allProducts = getProducts(['active' => true]);
$categories = getCategories();
$farms = getFarms();

// Filtres
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$farm = $_GET['farm'] ?? '';

// Filtrer les produits
$products = $allProducts;
if ($search) {
    $products = array_filter($products, function($p) use ($search) {
        return stripos($p['name'], $search) !== false || 
               stripos($p['description'] ?? '', $search) !== false;
    });
}
if ($category) {
    $products = array_filter($products, function($p) use ($category, $categories) {
        $productCategory = $p['category'] ?? null;
        if ($productCategory === null) {
            return false;
        }
        if (is_numeric($productCategory)) {
            return $productCategory == $category;
        }
        $cat = array_filter($categories, fn($c) => $c['id'] == $category);
        return !empty($cat) && $productCategory == reset($cat)['name'];
    });
}
if ($farm) {
    $products = array_filter($products, function($p) use ($farm, $farms) {
        $productFarm = $p['farm'] ?? null;
        if ($productFarm === null) {
            return false;
        }
        if (is_numeric($productFarm)) {
            return $productFarm == $farm;
        }
        $f = array_filter($farms, fn($fm) => $fm['id'] == $farm);
        return !empty($f) && $productFarm == reset($f)['name'];
    });
}

// Grouper par catégorie
$groupedByCategory = [];
foreach ($products as $product) {
    $productCategory = $product['category'] ?? null;
    $catId = 'sans-categorie';
    
    if ($productCategory !== null) {
        if (is_numeric($productCategory)) {
            $catId = $productCategory;
        } else {
            $matchingCat = array_filter($categories, fn($c) => $c['name'] == $productCategory);
            if (!empty($matchingCat)) {
                $catId = reset($matchingCat)['id'];
            }
        }
    }
    
    if (!isset($groupedByCategory[$catId])) {
        $cat = array_filter($categories, fn($c) => $c['id'] == $catId);
        $groupedByCategory[$catId] = [
            'id' => $catId,
            'name' => !empty($cat) ? reset($cat)['name'] : ($productCategory ?? 'Sans catégorie'),
            'icon' => !empty($cat) ? reset($cat)['icon'] : '🏷️',
            'products' => []
        ];
    }
    $groupedByCategory[$catId]['products'][] = $product;
}

// Récupérer l'image de fond
$backgroundImage = getBackgroundImage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - Boutique</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .products-page {
            padding-top: 5rem;
            padding-bottom: 4rem;
        }
        @media (max-width: 767px) {
            .products-page {
                padding-top: 4.5rem;
            }
        }
        .page-header {
            text-align: center;
            margin-bottom: 2.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .page-title-container {
            display: inline-block;
            background: rgba(17, 17, 17, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 9999px;
            padding: 2.5rem 4rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .page-title-container:hover {
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        .page-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #fff;
        }
        .page-subtitle {
            font-size: 1.125rem;
            color: #d1d5db;
        }
        .search-container {
            max-width: 48rem;
            width: 100%;
            margin: 0 auto;
        }
        .search-input-wrapper {
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            padding-right: 6rem;
            background: rgba(17, 17, 17, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .search-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
            background: rgba(17, 17, 17, 0.7);
        }
        .search-input::placeholder {
            color: #9ca3af;
        }
        .search-button {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #111111 0%, #2a2a2a 100%);
            color: #fff;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
        }
        .search-button:hover {
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
            transform: translateY(-50%) scale(1.05);
        }
        .filters-panel {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(17, 17, 17, 0.6);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            display: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
        }
        .filters-panel.show {
            display: block;
        }
        .filters-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 200px;
        }
        @media (max-width: 767px) {
            .filter-group {
                min-width: 100%;
            }
        }
        .filter-group label {
            display: block;
            color: #9ca3af;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .filter-select {
            width: 100%;
            padding: 0.5rem 1rem;
            background: rgba(17, 17, 17, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .filter-select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
        }
        .reset-button {
            width: 100%;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            color: #fff;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
        }
        .reset-button:hover {
            background: linear-gradient(135deg, #222222 0%, #333333 100%);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        .category-section {
            margin-bottom: 3rem;
        }
        .category-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(17, 17, 17, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
        }
        .category-header-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .category-icon {
            width: 4rem;
            height: 4rem;
            flex-shrink: 0;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        .category-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .category-info h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        .category-info p {
            color: #fff;
            font-size: 0.875rem;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        @media (min-width: 1280px) {
            .products-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
        .product-card {
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            background: rgba(17, 17, 17, 0.6);
            backdrop-filter: blur(20px);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .product-card:hover {
            transform: scale(1.05) translateY(-8px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 12px 48px rgba(255, 255, 255, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.15);
        }
        .product-image-container {
            position: relative;
            height: 12rem;
            overflow: hidden;
            background: rgba(26, 26, 26, 1);
        }
        @media (min-width: 768px) {
            .product-image-container {
                height: 16rem;
            }
        }
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .product-card:hover .product-image {
            transform: scale(1.1);
        }
        .product-info {
            padding: 1rem 1.5rem;
        }
        .product-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-description {
            color: #9ca3af;
            font-size: 0.75rem;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-farm {
            margin-bottom: 0.75rem;
        }
        .farm-badge {
            padding: 0.125rem 0.5rem;
            background: rgba(55, 65, 81, 0.3);
            border: 1px solid rgba(75, 85, 99, 0.5);
            border-radius: 9999px;
            color: #9ca3af;
            font-size: 0.75rem;
        }
        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .product-options {
            font-size: 0.75rem;
            color: #9ca3af;
        }
        .view-button {
            padding: 0.375rem 0.75rem;
            background: linear-gradient(135deg, #111111 0%, #2a2a2a 100%);
            border-radius: 0.5rem;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
        }
        .view-button:hover {
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        @media (min-width: 768px) {
            .view-button {
                padding: 0.5rem 1rem;
                font-size: 1rem;
            }
        }
        .empty-state {
            text-align: center;
            padding: 5rem 1rem;
        }
        .empty-state p {
            color: #9ca3af;
            font-size: 1.25rem;
        }
        @media (min-width: 768px) {
            .page-title {
                font-size: 4.5rem;
            }
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
            background: rgba(0, 0, 0, 0.7);
        }
        <?php endif; ?>
    </style>
    <script src="devtools_blocker.js"></script>
    <script src="assets/js/loading.js"></script>
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <div class="cosmic-bg products-page">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="page-header">
                <div class="page-title-container">
                    <h1 class="page-title">Notre Boutique</h1>
                    <p class="page-subtitle">Découvrez notre sélection de produits choisis avec amour</p>
                </div>

                <!-- Barre de recherche -->
                <div class="search-container">
                    <div class="search-input-wrapper">
                        <input type="text" 
                               id="searchInput"
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Rechercher un produit..."
                               class="search-input"
                               onkeyup="if(event.key==='Enter') filterProducts()">
                        <button onclick="toggleFilters()" class="search-button">
                            <span>🔍</span>
                            <span class="hidden md:inline">Filtres</span>
                        </button>
                    </div>

                    <!-- Filtres déroulants -->
                    <div id="filtersPanel" class="filters-panel">
                        <form method="GET" class="filters-grid">
                            <div class="filter-group">
                                <label>Catégorie</label>
                                <select name="category" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Toutes les catégories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Farm</label>
                                <select name="farm" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Toutes les farms</option>
                                    <?php foreach ($farms as $f): ?>
                                        <option value="<?php echo $f['id']; ?>" <?php echo $farm == $f['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($f['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label style="opacity: 0;">Reset</label>
                                <button type="button" onclick="clearFilters()" class="reset-button">Réinitialiser</button>
                            </div>
                        </form>
                        <?php if ($search || $category || $farm): ?>
                            <div style="margin-top: 0.75rem; font-size: 0.875rem; color: #9ca3af;">
                                <?php echo count($products); ?> produit(s) trouvé(s)
                                <?php if ($search): ?> pour "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Products grouped by Category -->
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <p>Aucun produit disponible pour le moment</p>
                </div>
            <?php elseif (empty($groupedByCategory)): ?>
                <div class="empty-state">
                    <p>Aucun produit disponible pour le moment</p>
                </div>
            <?php else: ?>
                <div class="space-y-12">
                    <?php foreach ($groupedByCategory as $categoryGroup): ?>
                        <div class="category-section">
                            <!-- Category Header -->
                            <?php if (!$category || count($groupedByCategory) > 1): ?>
                                <div class="category-header">
                                    <div class="category-header-content">
                                        <?php if ($backgroundImage): ?>
                                            <div class="category-icon">
                                                <img src="<?php echo htmlspecialchars($backgroundImage); ?>" alt="Boutique">
                                            </div>
                                        <?php else: ?>
                                            <div class="category-icon" style="display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                                <?php 
                                                $icon = $categoryGroup['icon'] ?? '🏷️';
                                                if (strpos($icon, 'http') !== false): ?>
                                                    <img src="<?php echo htmlspecialchars($icon); ?>" alt="<?php echo htmlspecialchars($categoryGroup['name']); ?>">
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($icon); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="category-info">
                                            <h2><?php echo htmlspecialchars($categoryGroup['name']); ?></h2>
                                            <p><?php echo count($categoryGroup['products']); ?> produit<?php echo count($categoryGroup['products']) > 1 ? 's' : ''; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Products Grid -->
                            <?php if (!empty($categoryGroup['products'])): ?>
                                <div class="products-grid">
                                    <?php foreach ($categoryGroup['products'] as $product): 
                                        // Trouver les noms de catégorie et farm
                                        $productCategory = $product['category'] ?? null;
                                        $productFarm = $product['farm'] ?? null;
                                        
                                        $categoryName = 'Sans catégorie';
                                        $farmName = 'Non spécifiée';
                                        
                                        if ($productCategory !== null) {
                                            if (is_numeric($productCategory)) {
                                                $cat = array_filter($categories, fn($c) => $c['id'] == $productCategory);
                                                if (!empty($cat)) {
                                                    $categoryName = reset($cat)['name'];
                                        }
                                            } else {
                                                $categoryName = $productCategory;
                                            }
                                        }
                                        
                                        if ($productFarm !== null) {
                                            if (is_numeric($productFarm)) {
                                                $f = array_filter($farms, fn($fm) => $fm['id'] == $productFarm);
                                                if (!empty($f)) {
                                                    $farmName = reset($f)['name'];
                                                }
                                            } else {
                                                $farmName = $productFarm;
                                            }
                                        }
                                        
                                        // Construire les médias
                                        $allMedias = [];
                                        if (!empty($product['medias']) && is_array($product['medias'])) {
                                            $allMedias = $product['medias'];
                                        }
                                        if (!empty($product['photo']) && !in_array($product['photo'], $allMedias)) {
                                            $allMedias[] = $product['photo'];
                                        }
                                        if (!empty($product['image']) && !in_array($product['image'], $allMedias)) {
                                            $allMedias[] = $product['image'];
                                        }
                                        
                                        // Pour la carte, chercher une photo (pas vidéo)
                                        $displayImage = null;
                                        foreach ($allMedias as $media) {
                                            if ($media && !preg_match('/\.(mp4|webm|mov|MOV|avi|mkv|m4v|3gp)$/i', $media)) {
                                                $displayImage = $media;
                                                break;
                                            }
                                        }
                                        if (!$displayImage && !empty($allMedias)) {
                                            $displayImage = $allMedias[0];
                                        }
                                        
                                        // Prix
                                        $variants = is_string($product['variants']) ? json_decode($product['variants'], true) : ($product['variants'] ?? []);
                                        $basePrice = '0€';
                                        if (!empty($variants) && isset($variants[0]['price'])) {
                                            $basePrice = is_numeric($variants[0]['price']) ? $variants[0]['price'] . '€' : $variants[0]['price'];
                                        } elseif (!empty($product['price'])) {
                                            $basePrice = is_numeric($product['price']) ? $product['price'] . '€' : $product['price'];
                                        }
                                        
                                        // Récupérer les avis pour ce produit
                                        $productReviews = [];
                                        try {
                                            global $supabase;
                                            $reviewsResult = $supabase->request('GET', 'reviews?product_id=eq.' . $product['id'] . '&approved=eq.true', null, false);
                                            $productReviews = $reviewsResult ?? [];
                                        } catch (Exception $e) {
                                            // Ignorer les erreurs
                                        }
                                        
                                        // Calculer la moyenne des notes
                                        $averageRating = 0;
                                        $reviewCount = count($productReviews);
                                        if ($reviewCount > 0) {
                                            $totalRating = 0;
                                            foreach ($productReviews as $rev) {
                                                $totalRating += intval($rev['rating'] ?? 5);
                                            }
                                            $averageRating = round($totalRating / $reviewCount, 1);
                                        }
                                    ?>
                                        <div class="product-card neon-border" onclick="window.location='product.php?id=<?php echo $product['id']; ?>'">
                                            <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                                                <div class="product-image-container">
                                                    <?php if ($displayImage): ?>
                                                        <?php if (preg_match('/cloudflarestream\.com.*iframe/i', $displayImage)): ?>
                                                            <iframe src="<?php echo htmlspecialchars($displayImage); ?>" 
                                                                    class="w-full h-full" 
                                                                    allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;"
                                                                    allowfullscreen
                                                                    style="border: none;"></iframe>
                                                        <?php elseif (preg_match('/\.(mp4|webm|mov|MOV|avi|mkv|m4v|3gp)$/i', $displayImage)): ?>
                                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(26, 26, 26, 1);">
                                                                <div style="font-size: 3rem;">🎥</div>
                                                            </div>
                                                        <?php else: ?>
                                                            <img src="<?php echo htmlspecialchars($displayImage); ?>" 
                                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                                 class="product-image">
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                                            🎁
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="product-info">
                                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                                    <?php if (!empty($product['description'])): ?>
                                                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Avis et étoiles -->
                                                    <?php if ($reviewCount > 0): ?>
                                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin: 0.5rem 0;">
                                                            <div style="color: #fbbf24; font-size: 0.875rem;">
                                                                <?php echo str_repeat('⭐', round($averageRating)); ?><?php echo str_repeat('☆', 5 - round($averageRating)); ?>
                                                            </div>
                                                            <span style="color: #9ca3af; font-size: 0.75rem;">(<?php echo $reviewCount; ?> avis<?php echo $reviewCount > 1 ? 's' : ''; ?>)</span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($farmName): ?>
                                                        <div class="product-farm">
                                                            <span class="farm-badge">🌾 <?php echo htmlspecialchars($farmName); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="product-footer">
                                                        <?php if (!empty($variants) && count($variants) > 1): ?>
                                                            <p class="product-options"><?php echo count($variants); ?> options</p>
                                                        <?php endif; ?>
                                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="view-button">Voir</a>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleFilters() {
            const panel = document.getElementById('filtersPanel');
            panel.classList.toggle('show');
        }
        
        function clearFilters() {
            window.location.href = 'products.php';
        }
        
        function filterProducts() {
            const search = document.getElementById('searchInput').value;
            const url = new URL(window.location.href);
            if (search) {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
