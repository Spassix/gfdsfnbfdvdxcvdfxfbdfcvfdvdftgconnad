<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

// Récupérer les statistiques
try {
    $products = $supabase->getProducts(['active' => true]);
    $categories = $supabase->getCategories();
    $farms = $supabase->getFarms();
    $orders = $supabase->getOrders(['limit' => 100]);
    
    $stats = [
        'products' => count($products),
        'categories' => count($categories),
        'farms' => count($farms),
        'orders' => count($orders),
        'pending_orders' => count(array_filter($orders, fn($o) => $o['status'] === 'pending'))
    ];
} catch (Exception $e) {
    $stats = [
        'products' => 0,
        'categories' => 0,
        'farms' => 0,
        'orders' => 0,
        'pending_orders' => 0
    ];
    $error = $e->getMessage();
}

$pageTitle = 'Dashboard - Panel Admin';
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
            font-size: 2.25rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        .page-subtitle {
            color: #fff;
        }
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .nav-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .nav-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .nav-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .nav-card:hover {
            transform: scale(1.05);
            background: rgba(15, 23, 42, 0.7);
        }
        .nav-card-icon {
            font-size: 3rem;
            margin-bottom: 0.75rem;
        }
        .nav-card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        .nav-card-description {
            color: #fff;
            font-size: 0.875rem;
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
        <!-- Mobile menu button -->
        <button id="mobile-menu-btn" 
                style="position: fixed; top: 1rem; left: 1rem; z-index: 60; padding: 0.75rem; background: #000; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff; display: none; cursor: pointer;"
                onclick="document.querySelector('.admin-sidebar').classList.toggle('open'); document.getElementById('mobile-overlay').style.display = document.querySelector('.admin-sidebar').classList.contains('open') ? 'block' : 'none';">
            <span style="font-size: 1.5rem;">☰</span>
        </button>

        <!-- Overlay mobile -->
        <div id="mobile-overlay" 
             style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 40; backdrop-filter: blur(4px);"
             onclick="document.querySelector('.admin-sidebar').classList.remove('open'); document.getElementById('mobile-overlay').style.display = 'none';"></div>

        <?php include __DIR__ . '/components/sidebar.php'; ?>

        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Bienvenue dans le panel d'administration</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <p>Erreur: <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <!-- Navigation rapide -->
            <div class="nav-grid">
                <a href="products.php" class="nav-card neon-border">
                    <div class="nav-card-icon">📦</div>
                    <h3 class="nav-card-title">Produits</h3>
                    <p class="nav-card-description">Gérer vos produits</p>
                </a>

                <a href="categories.php" class="nav-card neon-border">
                    <div class="nav-card-icon">📁</div>
                    <h3 class="nav-card-title">Catégories</h3>
                    <p class="nav-card-description">Organiser vos catégories</p>
                </a>

                <a href="farms.php" class="nav-card neon-border">
                    <div class="nav-card-icon">🌾</div>
                    <h3 class="nav-card-title">Farms</h3>
                    <p class="nav-card-description">Gérer vos farms</p>
                </a>

                <a href="settings.php" class="nav-card neon-border">
                    <div class="nav-card-icon">⚙️</div>
                    <h3 class="nav-card-title">Configuration</h3>
                    <p class="nav-card-description">Paramètres généraux</p>
                </a>

                <a href="events.php" class="nav-card neon-border">
                    <div class="nav-card-icon">🎉</div>
                    <h3 class="nav-card-title">Événements</h3>
                    <p class="nav-card-description">Noël, Pâques, etc.</p>
                </a>
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
