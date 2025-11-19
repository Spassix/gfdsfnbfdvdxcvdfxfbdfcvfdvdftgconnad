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
<header class="event-header" style="position: fixed; top: 0; left: 0; right: 0; z-index: 50; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
    <!-- Bannière événements saisonniers -->
    <?php if (!empty($activeEvents)): ?>
        <?php foreach ($activeEvents as $event): ?>
            <div class="season-event-banner" style="background: linear-gradient(135deg, #db2777 0%, #9333ea 100%); padding: 0.75rem; text-align: center; color: #fff; font-weight: 600; font-size: 0.875rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);">
                🎉 <?php echo htmlspecialchars($event['name'] ?? ''); ?> - Événement en cours jusqu'au <?php echo !empty($event['end_date']) ? date('d/m/Y', strtotime($event['end_date'])) : ''; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Bouton hamburger mobile -->
    <button id="mobile-menu-btn" 
            style="position: fixed; top: 1rem; left: 1rem; z-index: 60; padding: 0.75rem; background: rgba(0, 0, 0, 0.9); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff; display: none; cursor: pointer; transition: all 0.3s;"
            onclick="toggleMobileMenu()">
        <span style="font-size: 1.5rem;">☰</span>
    </button>

    <!-- Overlay mobile -->
    <div id="mobile-overlay" 
         style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.8); z-index: 45; backdrop-filter: blur(4px);"
         onclick="closeMobileMenu()"></div>

    <!-- Navigation desktop -->
    <nav class="desktop-nav" style="max-width: 80rem; margin: 0 auto; padding: 1rem;">
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
                        <span style="position: absolute; top: -0.25rem; right: -0.5rem; background: #ef4444; color: white; border-radius: 50%; width: 1.25rem; height: 1.25rem; display: flex; align-items: center; justify-content: center; font-size: 0.625rem; font-weight: 700;">
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

    <!-- Menu mobile -->
    <nav id="mobile-menu" 
         style="position: fixed; top: 0; left: -100%; width: 18rem; height: 100vh; background: rgba(0, 0, 0, 0.95); backdrop-filter: blur(12px); border-right: 1px solid rgba(255, 255, 255, 0.2); z-index: 55; transition: left 0.3s ease-in-out; overflow-y: auto; padding: 1.5rem; padding-top: 4rem;">
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <a href="index.php" 
               class="mobile-nav-item <?php echo isActive('index', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               onclick="closeMobileMenu()"
               style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 0.5rem; text-decoration: none; color: #fff; transition: all 0.3s; <?php echo isActive('index', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.5rem;">🏠</span>
                <span style="font-weight: 500;">Accueil</span>
            </a>
            <a href="products.php" 
               class="mobile-nav-item <?php echo isActive('products', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               onclick="closeMobileMenu()"
               style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 0.5rem; text-decoration: none; color: #fff; transition: all 0.3s; <?php echo isActive('products', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.5rem;">🛍️</span>
                <span style="font-weight: 500;">Produits</span>
            </a>
            <a href="reviews.php" 
               class="mobile-nav-item <?php echo isActive('reviews', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               onclick="closeMobileMenu()"
               style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 0.5rem; text-decoration: none; color: #fff; transition: all 0.3s; <?php echo isActive('reviews', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.5rem;">💬</span>
                <span style="font-weight: 500;">Avis</span>
            </a>
            <a href="contact.php" 
               class="mobile-nav-item <?php echo isActive('contact', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               onclick="closeMobileMenu()"
               style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 0.5rem; text-decoration: none; color: #fff; transition: all 0.3s; <?php echo isActive('contact', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.5rem;">✉️</span>
                <span style="font-weight: 500;">Contact</span>
            </a>
            <a href="cart.php" 
               class="mobile-nav-item <?php echo isActive('cart', $currentPage, $currentPath) ? 'active' : ''; ?>" 
               onclick="closeMobileMenu()"
               style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 0.5rem; text-decoration: none; color: #fff; transition: all 0.3s; position: relative; <?php echo isActive('cart', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.5rem; position: relative;">
                    🛒
                    <?php if ($cartCount > 0): ?>
                        <span style="position: absolute; top: -0.25rem; right: -0.5rem; background: #ef4444; color: white; border-radius: 50%; width: 1.25rem; height: 1.25rem; display: flex; align-items: center; justify-content: center; font-size: 0.625rem; font-weight: 700;">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                </span>
                <span style="font-weight: 500;">Panier</span>
            </a>
        </div>
    </nav>
</header>
<style>
.nav-item:hover span:first-child {
    opacity: 1 !important;
    transform: scale(1.1);
}
.nav-item:hover span:last-child {
    color: #fff;
}
.mobile-nav-item:hover {
    background: rgba(255, 255, 255, 0.05) !important;
}
@media (min-width: 768px) {
    .nav-item span:first-child {
        font-size: 1.875rem;
    }
    .nav-item span:last-child {
        font-size: 0.875rem;
    }
}
/* Styles responsive */
@media (max-width: 767px) {
    .desktop-nav {
        display: none !important;
    }
    #mobile-menu-btn {
        display: block !important;
    }
    #mobile-menu.open {
        left: 0 !important;
    }
    #mobile-overlay.show {
        display: block !important;
    }
}
@media (min-width: 768px) {
    #mobile-menu-btn {
        display: none !important;
    }
    #mobile-menu {
        display: none !important;
    }
    #mobile-overlay {
        display: none !important;
    }
}
</style>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const overlay = document.getElementById('mobile-overlay');
    if (menu && overlay) {
        menu.classList.toggle('open');
        overlay.classList.toggle('show');
    }
}

function closeMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const overlay = document.getElementById('mobile-overlay');
    if (menu && overlay) {
        menu.classList.remove('open');
        overlay.classList.remove('show');
    }
}

// Fermer le menu si on clique en dehors
document.addEventListener('click', function(event) {
    const menu = document.getElementById('mobile-menu');
    const btn = document.getElementById('mobile-menu-btn');
    const overlay = document.getElementById('mobile-overlay');
    
    if (menu && btn && overlay && menu.classList.contains('open')) {
        if (!menu.contains(event.target) && !btn.contains(event.target)) {
            closeMobileMenu();
        }
    }
});
</script>

