<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';
require_once __DIR__ . '/helpers.php';

$products = [];
$categories = [];
$farms = [];
$error = null;
$success = null;

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        try {
            $supabase->deleteProduct($_POST['id']);
            $success = 'Produit supprimé avec succès !';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

try {
    $productsRaw = $supabase->getProducts();
    $products = normalizeProducts($productsRaw);
    $categories = $supabase->getCategories();
    $farms = $supabase->getFarms();
    
    usort($products, function($a, $b) {
        $catA = $a['category'] ?? $a['category_id'] ?? '';
        $catB = $b['category'] ?? $b['category_id'] ?? '';
        if ($catA !== $catB) {
            return strcmp($catA, $catB);
        }
        $farmA = $a['farm'] ?? $a['farm_id'] ?? '';
        $farmB = $b['farm'] ?? $b['farm_id'] ?? '';
        return strcmp($farmA, $farmB);
    });
} catch (Exception $e) {
    $error = $e->getMessage();
}

$search = $_GET['search'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterFarm = $_GET['farm'] ?? '';

$filteredProducts = $products;
if ($search) {
    $filteredProducts = array_filter($filteredProducts, function($p) use ($search) {
        return stripos($p['name'] ?? '', $search) !== false || 
               stripos($p['description'] ?? '', $search) !== false;
    });
}
if ($filterCategory) {
    $filteredProducts = array_filter($filteredProducts, function($p) use ($filterCategory) {
        $cat = $p['category'] ?? $p['category_id'] ?? '';
        return (string)$cat === (string)$filterCategory;
    });
}
if ($filterFarm) {
    $filteredProducts = array_filter($filteredProducts, function($p) use ($filterFarm) {
        $farm = $p['farm'] ?? $p['farm_id'] ?? '';
        return (string)$farm === (string)$filterFarm;
    });
}

function getCategoryName($categoryId, $categories) {
    if (empty($categoryId)) return 'Sans catégorie';
    if (is_numeric($categoryId) || is_string($categoryId)) {
        $cat = array_filter($categories, fn($c) => (string)$c['id'] === (string)$categoryId);
        return !empty($cat) ? reset($cat)['name'] : $categoryId;
    }
    return $categoryId;
}

function getFarmName($farmId, $farms) {
    if (empty($farmId)) return null;
    if (is_numeric($farmId) || is_string($farmId)) {
        $farm = array_filter($farms, fn($f) => (string)$f['id'] === (string)$farmId);
        return !empty($farm) ? reset($farm)['name'] : $farmId;
    }
    return $farmId;
}

function isVideo($url) {
    if (!$url) return false;
    $extensions = ['.mp4', '.webm', '.mov', '.MOV', '.avi', '.mkv', '.m4v', '.3gp'];
    foreach ($extensions as $ext) {
        if (stripos($url, $ext) !== false) return true;
    }
    return false;
}

function isCloudflareStreamIframe($url) {
    return $url && strpos($url, 'cloudflarestream.com') !== false && strpos($url, 'iframe') !== false;
}

$pageTitle = 'Produits - Panel Admin';
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
        .header-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        @media (min-width: 640px) {
            .header-actions {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        .add-button {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(to right, #9333ea, #db2777);
            color: #fff;
            font-weight: 600;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        @media (min-width: 640px) {
            .add-button {
                width: auto;
            }
        }
        .add-button:hover {
            background: linear-gradient(to right, #7e22ce, #be185d);
        }
        .filters-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        @media (min-width: 768px) {
            .filters-grid {
                grid-template-columns: 2fr 1fr 1fr;
            }
        }
        .filter-input,
        .filter-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #000;
            font-size: 1rem;
        }
        .filter-input::placeholder {
            color: #6b7280;
        }
        .products-container {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            overflow: hidden;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
        }
        .products-grid-mobile {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            padding: 0.75rem;
        }
        @media (min-width: 640px) {
            .products-grid-mobile {
                gap: 1rem;
                padding: 1rem;
            }
        }
        @media (min-width: 1024px) {
            .products-grid-mobile {
                display: none;
            }
        }
        .product-card-mobile {
            background: rgba(30, 41, 59, 0.3);
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            gap: 0.75rem;
        }
        .product-image-mobile {
            width: 5rem;
            height: 5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            background: rgba(30, 41, 59, 1);
            flex-shrink: 0;
        }
        .product-image-mobile img,
        .product-image-mobile video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info-mobile {
            flex: 1;
            min-width: 0;
        }
        .product-name-mobile {
            color: #fff;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .product-description-mobile {
            color: #fff;
            font-size: 0.875rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-top: 0.25rem;
        }
        .product-price-mobile {
            color: #fff;
            font-weight: 600;
            margin-top: 0.25rem;
        }
        .product-meta-mobile {
            color: #fff;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        .product-actions-mobile {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .action-btn {
            flex: 1;
            padding: 0.5rem 0.75rem;
            background: rgba(55, 65, 81, 0.2);
            border: 1px solid rgba(75, 85, 99, 0.5);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }
        .action-btn:hover {
            background: rgba(75, 85, 99, 0.3);
        }
        .products-table {
            display: none;
            width: 100%;
            min-width: 800px;
        }
        @media (min-width: 1024px) {
            .products-table {
                display: table;
            }
        }
        .table-header {
            background: rgba(30, 41, 59, 0.5);
        }
        .table-header th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
        }
        @media (min-width: 1280px) {
            .table-header th {
                padding: 1rem 1.5rem;
                font-size: 1rem;
            }
        }
        .table-body tr {
            border-top: 1px solid rgba(30, 41, 59, 1);
            transition: background 0.3s;
        }
        .table-body tr:hover {
            background: rgba(30, 41, 59, 0.3);
        }
        .table-body td {
            padding: 0.75rem 1rem;
            color: #fff;
        }
        @media (min-width: 1280px) {
            .table-body td {
                padding: 1rem 1.5rem;
            }
        }
        .table-image {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            overflow: hidden;
            background: rgba(30, 41, 59, 1);
        }
        @media (min-width: 1280px) {
            .table-image {
                width: 4rem;
                height: 4rem;
            }
        }
        .table-image img,
        .table-image video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .table-name {
            font-weight: 500;
            font-size: 0.875rem;
        }
        @media (min-width: 1280px) {
            .table-name {
                font-size: 1rem;
            }
        }
        .table-description {
            color: #fff;
            font-size: 0.75rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-top: 0.25rem;
        }
        @media (min-width: 1280px) {
            .table-description {
                font-size: 0.875rem;
            }
        }
        .table-price {
            font-weight: 600;
            font-size: 0.875rem;
        }
        @media (min-width: 1280px) {
            .table-price {
                font-size: 1rem;
            }
        }
        .table-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.25rem;
        }
        @media (min-width: 1280px) {
            .table-actions {
                gap: 0.5rem;
            }
        }
        .table-action-btn {
            padding: 0.375rem 0.75rem;
            background: rgba(55, 65, 81, 0.2);
            border: 1px solid rgba(75, 85, 99, 0.5);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        @media (min-width: 1280px) {
            .table-action-btn {
                padding: 0.5rem 1rem;
                font-size: 1rem;
            }
        }
        .table-action-btn:hover {
            background: rgba(75, 85, 99, 0.3);
        }
        .error-message {
            background: rgba(127, 29, 29, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .error-message p {
            color: rgba(248, 113, 113, 1);
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
                <div class="header-actions">
                    <div>
                        <h1 class="page-title">Gestion des Produits</h1>
                        <p class="page-subtitle"><?php echo count($filteredProducts); ?> produit(s) affiché(s) sur <?php echo count($products); ?> au total</p>
                    </div>
                    <a href="product_form.php" class="add-button">
                        <span>➕</span>
                        <span>Ajouter un produit</span>
                    </a>
                </div>
                <form method="GET" class="filters-grid">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="🔍 Rechercher un produit..."
                           class="filter-input">
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="farm" class="filter-select" onchange="this.form.submit()">
                        <option value="">Toutes les farms</option>
                        <?php foreach ($farms as $farm): ?>
                            <option value="<?php echo $farm['id']; ?>" <?php echo $filterFarm == $farm['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($farm['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php if ($error): ?>
                <div class="error-message">
                    <p>Erreur: <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message" style="background: rgba(20, 83, 45, 0.2); border: 1px solid rgba(34, 197, 94, 0.5); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                    <p style="color: rgba(74, 222, 128, 1);"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            <div class="products-container neon-border">
                <div class="products-grid-mobile">
                    <?php foreach ($filteredProducts as $product): 
                        $image = $product['photo'] ?? $product['photo_url'] ?? $product['image'] ?? '';
                        $video = $product['video'] ?? $product['video_url'] ?? $product['media'] ?? '';
                        $media = $image ?: $video;
                        
                        $variants = is_string($product['variants'] ?? null) ? json_decode($product['variants'], true) : ($product['variants'] ?? []);
                        $quantities = is_string($product['quantities'] ?? null) ? json_decode($product['quantities'], true) : ($product['quantities'] ?? []);
                        
                        $price = '0€';
                        if (!empty($variants) && isset($variants[0]['price'])) {
                            $price = $variants[0]['price'];
                        } elseif (!empty($quantities) && isset($quantities[0]['price'])) {
                            $price = $quantities[0]['price'] . '€';
                        } elseif (!empty($product['price'])) {
                            $price = is_numeric($product['price']) ? $product['price'] . '€' : $product['price'];
                        }
                    ?>
                        <div class="product-card-mobile">
                            <div class="product-image-mobile">
                                <?php if ($media): ?>
                                    <?php if (isCloudflareStreamIframe($media)): ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: rgba(15, 23, 42, 1);">🎥</div>
                                    <?php elseif (isVideo($media)): ?>
                                        <video src="<?php echo htmlspecialchars($media); ?>" muted></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($media); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">📦</div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info-mobile">
                                <div class="product-name-mobile"><?php echo htmlspecialchars($product['name'] ?? ''); ?></div>
                                <div class="product-description-mobile"><?php echo htmlspecialchars($product['description'] ?? ''); ?></div>
                                <div class="product-price-mobile"><?php echo htmlspecialchars($price); ?></div>
                                <div class="product-meta-mobile">
                                    <?php echo htmlspecialchars(getCategoryName($product['category'] ?? '', $categories)); ?>
                                    <?php if (!empty($product['farm'])): ?>
                                        | 🌾 <?php echo htmlspecialchars(getFarmName($product['farm'], $farms)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="product-actions-mobile">
                                <a href="product_form.php?id=<?php echo $product['id']; ?>" class="action-btn">✏️ Modifier</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="action-btn" style="border: none; background: none; cursor: pointer; width: 100%;">🗑️ Supprimer</button>
                                </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="overflow-x: auto;">
                    <table class="products-table">
                        <thead class="table-header">
                            <tr>
                                <th>Image</th>
                                <th>Nom</th>
                                <th>Prix</th>
                                <th>Catégorie</th>
                                <th>Farm</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            <?php foreach ($filteredProducts as $product): 
                                $image = $product['photo'] ?? $product['photo_url'] ?? $product['image'] ?? '';
                                $video = $product['video'] ?? $product['video_url'] ?? $product['media'] ?? '';
                                $media = $image ?: $video;
                                
                                $variants = is_string($product['variants'] ?? null) ? json_decode($product['variants'], true) : ($product['variants'] ?? []);
                                $quantities = is_string($product['quantities'] ?? null) ? json_decode($product['quantities'], true) : ($product['quantities'] ?? []);
                                
                                $price = '0€';
                                if (!empty($variants) && isset($variants[0]['price'])) {
                                    $price = $variants[0]['price'];
                                } elseif (!empty($quantities) && isset($quantities[0]['price'])) {
                                    $price = $quantities[0]['price'] . '€';
                                } elseif (!empty($product['price'])) {
                                    $price = is_numeric($product['price']) ? $product['price'] . '€' : $product['price'];
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="table-image">
                                            <?php if ($media): ?>
                                                <?php if (isCloudflareStreamIframe($media)): ?>
                                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: rgba(15, 23, 42, 1);">🎥</div>
                                                <?php elseif (isVideo($media)): ?>
                                                    <video src="<?php echo htmlspecialchars($media); ?>" muted></video>
                                                <?php else: ?>
                                                    <img src="<?php echo htmlspecialchars($media); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">📦</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-name"><?php echo htmlspecialchars($product['name'] ?? ''); ?></div>
                                        <div class="table-description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <div class="table-price"><?php echo htmlspecialchars($price); ?></div>
                                    </td>
                                    <td>
                                        <div style="color: #fff; font-size: 0.875rem;">
                                            <?php 
                                            $catId = $product['category'] ?? $product['category_id'] ?? '';
                                            echo htmlspecialchars(getCategoryName($catId, $categories)); 
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="color: #fff; font-size: 0.875rem;">
                                            <?php 
                                            $farmId = $product['farm'] ?? $product['farm_id'] ?? '';
                                            echo !empty($farmId) ? htmlspecialchars(getFarmName($farmId, $farms)) : '-'; 
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="product_form.php?id=<?php echo $product['id']; ?>" class="table-action-btn">✏️</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="table-action-btn" style="border: none; background: none; cursor: pointer;">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
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
