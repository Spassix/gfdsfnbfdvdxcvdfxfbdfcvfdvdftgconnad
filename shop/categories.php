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

$categories = getCategories();
$products = getProducts(['active' => true]);

// Compter les produits par catégorie
$productCounts = [];
foreach ($products as $product) {
    if (empty($product['category'])) continue;
    
    $categoryId = null;
    if (is_numeric($product['category'])) {
        $categoryId = (string)$product['category'];
    } else {
        $foundCategory = array_filter($categories, fn($c) => $c['name'] === $product['category']);
        if (!empty($foundCategory)) {
            $categoryId = (string)reset($foundCategory)['id'];
        } else {
            $categoryId = $product['category'];
        }
    }
    
    if ($categoryId) {
        $productCounts[$categoryId] = ($productCounts[$categoryId] ?? 0) + 1;
    }
}

$gradients = [
    'from-white to-gray-200',
    'from-gray-200 to-white',
    'from-gray-300 to-gray-400',
    'from-gray-400 to-gray-500',
    'from-gray-500 to-gray-600',
    'from-white to-gray-300'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - Boutique</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .categories-page {
            padding-top: 5rem;
            padding-bottom: 4rem;
        }
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            display: flex;
            justify-content: center;
        }
        .page-title-container {
            display: inline-block;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(24px);
            border-radius: 9999px;
            padding: 2.5rem 4rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
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
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .categories-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .categories-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .category-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            overflow: hidden;
            background: rgba(17, 17, 17, 0.6);
            backdrop-filter: blur(4px);
            cursor: pointer;
            transition: all 0.3s;
        }
        .category-card:hover {
            transform: scale(1.05) translateY(-0.625rem);
        }
        .category-icon-section {
            position: relative;
            height: 10rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        @media (min-width: 768px) {
            .category-icon-section {
                height: 12rem;
            }
        }
        .category-icon-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .category-card:hover .category-icon-section img {
            transform: scale(1.1);
        }
        .category-icon-emoji {
            font-size: 5rem;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.5));
        }
        .category-count-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            color: #fff;
            font-weight: 700;
            font-size: 0.75rem;
        }
        @media (min-width: 768px) {
            .category-count-badge {
                top: 1rem;
                right: 1rem;
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }
        .category-info {
            padding: 1rem 1.5rem;
        }
        .category-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .category-description {
            color: #9ca3af;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .category-link {
            display: flex;
            align-items: center;
            color: #fff;
            font-weight: 600;
            font-size: 0.875rem;
            transition: transform 0.3s;
        }
        .category-card:hover .category-link {
            transform: translateX(0.5rem);
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
            background: rgba(17, 17, 17, 0.7);
        }
        <?php endif; ?>
    </style>
    <script src="devtools_blocker.js"></script>
    <script src="assets/js/loading.js"></script>
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <div class="cosmic-bg categories-page">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="page-header">
                <div class="page-title-container">
                    <h1 class="page-title">Nos Catégories</h1>
                    <p class="page-subtitle">Explorez nos différentes catégories de produits choisis avec amour</p>
                </div>
            </div>

            <!-- Categories Grid -->
            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <p>Aucune catégorie disponible pour le moment</p>
                </div>
            <?php else: ?>
                <div class="categories-grid">
                    <?php foreach ($categories as $index => $category): 
                        $count = $productCounts[(string)$category['id']] ?? 0;
                        $gradient = $gradients[$index % count($gradients)];
                        $isImageUrl = !empty($category['icon']) && strpos($category['icon'], 'http') !== false;
                    ?>
                        <div class="category-card neon-border" onclick="window.location='products.php?category=<?php echo $category['id']; ?>'">
                            <a href="products.php?category=<?php echo $category['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                                <div class="category-icon-section" style="<?php echo $isImageUrl ? 'background: rgba(30, 41, 59, 1);' : 'background: linear-gradient(to bottom right, ' . str_replace('from-', '', explode(' ', $gradient)[0]) . ', ' . str_replace('to-', '', explode(' ', $gradient)[2]) . ');'; ?>">
                                    <?php if ($isImageUrl): ?>
                                        <img src="<?php echo htmlspecialchars($category['icon']); ?>" 
                                             alt="<?php echo htmlspecialchars($category['name']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <div class="category-icon-emoji" style="display: none;"><?php echo htmlspecialchars($category['icon'] ?? '📦'); ?></div>
                                    <?php else: ?>
                                        <div class="category-icon-emoji"><?php echo htmlspecialchars($category['icon'] ?? '📦'); ?></div>
                                    <?php endif; ?>
                                    
                                    <!-- Count Badge -->
                                    <div class="category-count-badge">
                                        <?php echo $count; ?> produit<?php echo $count !== 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                                <div class="category-info">
                                    <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                                    <?php if (!empty($category['description'])): ?>
                                        <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="category-link">
                                        <span>Voir les produits</span>
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-left: 0.5rem;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

