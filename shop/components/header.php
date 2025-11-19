<?php
// Gestion du panier en session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartCount = count($_SESSION['cart']);
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

// Déterminer si la page est active
function isActive($page, $currentPage, $currentPath) {
    if ($page === 'index' && ($currentPage === 'index' || $currentPath === '/shop/' || $currentPath === '/shop/index.php')) {
        return true;
    }
    return $currentPage === $page;
}

// Récupérer les événements saisonniers actifs
$activeEvents = getActiveSeasonEvents();
?>
<header class="event-header" style="position: fixed; top: 0; left: 0; right: 0; z-index: 50; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(20px); border-bottom: 2px solid rgba(255, 255, 255, 0.1); box-shadow: 0 4px 24px rgba(0, 0, 0, 0.8);">
    <!-- Bannière événements saisonniers -->
    <?php if (!empty($activeEvents)): ?>
        <?php foreach ($activeEvents as $event): ?>
            <div class="season-event-banner" style="background: linear-gradient(135deg, #111111 0%, #2a2a2a 100%); padding: 0.75rem; text-align: center; color: #fff; font-weight: 600; font-size: 0.875rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.6); border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                🎉 <?php echo htmlspecialchars($event['name'] ?? ''); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Navigation (toujours visible) -->
    <nav class="main-nav" style="max-width: 80rem; margin: 0 auto; padding: 0.75rem 1rem;">
        <div style="display: flex; align-items: center; justify-content: space-around;">
            <a href="index.php" 
               class="nav-item <?php echo isActive('index', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem; text-decoration: none; color: #fff; padding: 0.3125rem; transition: all 0.3s;">
                <span style="font-size: 1.5rem; transition: all 0.3s; <?php echo isActive('index', $currentPage, $currentPath) ? 'filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8));' : 'opacity: 0.7;'; ?>">
                    🏠
                </span>
                <span style="font-size: 0.75rem; font-weight: 500; transition: all 0.3s; <?php echo isActive('index', $currentPage, $currentPath) ? 'font-weight: 700;' : ''; ?>">
                    Accueil
                </span>
            </a>
            <a href="products.php" 
               class="nav-item <?php echo isActive('products', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem; text-decoration: none; color: #fff; padding: 0.3125rem; transition: all 0.3s;">
                <span style="font-size: 1.5rem; transition: all 0.3s; <?php echo isActive('products', $currentPage, $currentPath) ? 'filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8));' : 'opacity: 0.7;'; ?>">
                    🛍️
                </span>
                <span style="font-size: 0.75rem; font-weight: 500; transition: all 0.3s; <?php echo isActive('products', $currentPage, $currentPath) ? 'font-weight: 700;' : ''; ?>">
                    Produits
                </span>
            </a>
            <a href="reviews.php" 
               class="nav-item <?php echo isActive('reviews', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem; text-decoration: none; color: #fff; padding: 0.3125rem; transition: all 0.3s;">
                <span style="font-size: 1.5rem; transition: all 0.3s; <?php echo isActive('reviews', $currentPage, $currentPath) ? 'filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8));' : 'opacity: 0.7;'; ?>">
                    💬
                </span>
                <span style="font-size: 0.75rem; font-weight: 500; transition: all 0.3s; <?php echo isActive('reviews', $currentPage, $currentPath) ? 'font-weight: 700;' : ''; ?>">
                    Avis
                </span>
            </a>
            <a href="contact.php" 
               class="nav-item <?php echo isActive('contact', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem; text-decoration: none; color: #fff; padding: 0.3125rem; transition: all 0.3s;">
                <span style="font-size: 1.5rem; transition: all 0.3s; <?php echo isActive('contact', $currentPage, $currentPath) ? 'filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8));' : 'opacity: 0.7;'; ?>">
                    ✉️
                </span>
                <span style="font-size: 0.75rem; font-weight: 500; transition: all 0.3s; <?php echo isActive('contact', $currentPage, $currentPath) ? 'font-weight: 700;' : ''; ?>">
                    Contact
                </span>
            </a>
            <a href="cart.php" 
               class="nav-item <?php echo isActive('cart', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem; text-decoration: none; color: #fff; padding: 0.3125rem; position: relative; transition: all 0.3s;">
                <span style="font-size: 1.5rem; position: relative; transition: all 0.3s; <?php echo isActive('cart', $currentPage, $currentPath) ? 'filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8));' : 'opacity: 0.7;'; ?>">
                    🛒
                    <?php if ($cartCount > 0): ?>
                        <span style="position: absolute; top: -0.25rem; right: -0.5rem; background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%); color: white; border-radius: 50%; width: 1.25rem; height: 1.25rem; display: flex; align-items: center; justify-content: center; font-size: 0.625rem; font-weight: 700; border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 0 10px rgba(255, 255, 255, 0.1); animation: pulse-slow 2s ease-in-out infinite;">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                </span>
                <span style="font-size: 0.75rem; font-weight: 500; transition: all 0.3s; <?php echo isActive('cart', $currentPage, $currentPath) ? 'font-weight: 700;' : ''; ?>">
                    Panier
                </span>
            </a>
        </div>
    </nav>
</header>
<style>
@keyframes pulse-slow {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}

.nav-item:hover span:first-child {
    opacity: 1 !important;
    transform: scale(1.1);
    filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.3)) !important;
}
.nav-item:hover span:last-child {
    color: #fff;
    font-weight: 700 !important;
}

/* Navigation responsive - toujours visible */
.main-nav {
    display: flex !important;
}

.main-nav > div {
    display: flex;
    align-items: center;
    justify-content: space-around;
    width: 100%;
    flex-wrap: wrap;
    gap: 0.5rem;
}

@media (max-width: 767px) {
    .main-nav {
        padding: 0.5rem 0.5rem !important;
    }
    
    .nav-item {
        flex: 1;
        min-width: 0;
        padding: 0.25rem !important;
    }
    
    .nav-item span:first-child {
        font-size: 1.25rem !important;
    }
    
    .nav-item span:last-child {
        font-size: 0.65rem !important;
    }
}

@media (min-width: 768px) {
    .nav-item span:first-child {
        font-size: 1.875rem;
    }
    .nav-item span:last-child {
        font-size: 0.875rem;
    }
}
</style>


