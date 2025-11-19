<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

if (!function_exists('isActive')) {
    function isActive($page, $currentPage, $currentPath) {
        if ($page === 'dashboard' && ($currentPage === 'dashboard' || $currentPage === 'index')) {
            return true;
        }
        return $currentPage === $page;
    }
}

if (!function_exists('isConfigPage')) {
    function isConfigPage($currentPage) {
        $configPages = ['settings', 'colors', 'cart_settings', 'promos', 'admin_reviews', 'events', 'loading'];
        return in_array($currentPage, $configPages);
    }
}

// Récupérer le rôle de l'utilisateur depuis la session
$userRole = $_SESSION['admin_role'] ?? 'admin';
?>
<aside class="admin-sidebar" style="position: fixed; left: 0; top: 0; height: 100vh; width: 18rem; background: #000; border-right: 1px solid rgba(255, 255, 255, 0.2); z-index: 50; overflow-y: auto;">
    <div style="padding: 1.5rem; display: flex; flex-direction: column; height: 100%;">
        <!-- Header -->
        <div style="margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.5rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">Panel Admin</h1>
            <p style="color: #fff; font-size: 0.875rem; margin-bottom: 1rem;">Gestion de la boutique</p>
            <div style="display: inline-flex; align-items: center; padding: 0.375rem 0.75rem; background: linear-gradient(to right, rgba(139, 92, 246, 0.3), rgba(236, 72, 153, 0.3)); border: 1px solid rgba(139, 92, 246, 0.5); border-radius: 0.5rem;">
                <span style="font-size: 1rem; margin-right: 0.5rem;">👑</span>
                <span style="color: #fff; font-size: 0.875rem; font-weight: 600;">Administrateur</span>
            </div>
        </div>

        <!-- Navigation -->
        <nav style="flex: 1; display: flex; flex-direction: column; gap: 0.25rem; overflow-y: auto;">
            <!-- Dashboard -->
            <a href="dashboard.php" 
               class="nav-item <?php echo isActive('dashboard', $currentPage, $currentPath) ? 'active' : ''; ?>"
               style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('dashboard', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.25rem;">📊</span>
                <span style="color: #fff; font-weight: 500;">Dashboard</span>
            </a>

            <!-- Produits -->
            <a href="products.php" 
               class="nav-item <?php echo isActive('products', $currentPage, $currentPath) ? 'active' : ''; ?>"
               style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('products', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.25rem;">📦</span>
                <span style="color: #fff; font-weight: 500;">Produits</span>
            </a>

            <!-- Catégories -->
            <a href="categories.php" 
               class="nav-item <?php echo isActive('categories', $currentPage, $currentPath) ? 'active' : ''; ?>"
               style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('categories', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.25rem;">🏷️</span>
                <span style="color: #fff; font-weight: 500;">Catégories</span>
            </a>

            <!-- Farms -->
            <a href="farms.php" 
               class="nav-item <?php echo isActive('farms', $currentPage, $currentPath) ? 'active' : ''; ?>"
               style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('farms', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.25rem;">🌾</span>
                <span style="color: #fff; font-weight: 500;">Farms</span>
            </a>

            <!-- Réseaux Sociaux -->
            <a href="socials.php" 
               class="nav-item <?php echo isActive('socials', $currentPage, $currentPath) ? 'active' : ''; ?>"
               style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('socials', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.25rem;">🌐</span>
                <span style="color: #fff; font-weight: 500;">Réseaux Sociaux</span>
            </a>

            <!-- Typographie -->
            <a href="typography.php" 
               class="nav-item <?php echo isActive('typography', $currentPage, $currentPath) ? 'active' : ''; ?>"
               style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('typography', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.25rem;">✍️</span>
                <span style="color: #fff; font-weight: 500;">Typographie</span>
            </a>

            <!-- Maintenance -->
            <a href="maintenance.php" 
               class="nav-item <?php echo isActive('maintenance', $currentPage, $currentPath) ? 'active' : ''; ?>"
               style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('maintenance', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.5);' : ''; ?>">
                <span style="font-size: 1.25rem;">🔧</span>
                <span style="color: #fff; font-weight: 500;">Maintenance</span>
            </a>

            <!-- Configuration (avec sous-menus) -->
            <div style="margin-top: 0.5rem;">
                <a href="settings.php" 
                   class="nav-item config-main <?php echo isConfigPage($currentPage) ? 'active' : ''; ?>"
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; background: <?php echo isConfigPage($currentPage) ? 'linear-gradient(to right, rgba(139, 92, 246, 0.3), rgba(236, 72, 153, 0.3))' : 'transparent'; ?>; border: 1px solid <?php echo isConfigPage($currentPage) ? 'rgba(139, 92, 246, 0.5)' : 'transparent'; ?>;">
                    <span style="font-size: 1.25rem;">⚙️</span>
                    <span style="color: #fff; font-weight: 600;">Configuration</span>
                </a>

                <!-- Sous-menus Configuration -->
                <div id="config-submenu" style="margin-left: 1rem; margin-top: 0.5rem; display: <?php echo isConfigPage($currentPage) ? 'flex' : 'none'; ?>; flex-direction: column; gap: 0.25rem;">
                    <a href="colors.php" 
                       class="nav-item-sub <?php echo isActive('colors', $currentPage, $currentPath) ? 'active' : ''; ?>"
                       style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('colors', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2);' : ''; ?>">
                        <span style="font-size: 1rem;">🎨</span>
                        <span style="color: #fff; font-size: 0.875rem;">Couleurs</span>
                    </a>
                    <a href="cart_settings.php" 
                       class="nav-item-sub <?php echo isActive('cart_settings', $currentPage, $currentPath) ? 'active' : ''; ?>"
                       style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('cart_settings', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2);' : ''; ?>">
                        <span style="font-size: 1rem;">🛒</span>
                        <span style="color: #fff; font-size: 0.875rem;">Panier</span>
                    </a>
                    <a href="promos.php" 
                       class="nav-item-sub <?php echo isActive('promos', $currentPage, $currentPath) ? 'active' : ''; ?>"
                       style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('promos', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2);' : ''; ?>">
                        <span style="font-size: 1rem;">🎟️</span>
                        <span style="color: #fff; font-size: 0.875rem;">Codes Promo</span>
                    </a>
                    <a href="admin_reviews.php" 
                       class="nav-item-sub <?php echo isActive('admin_reviews', $currentPage, $currentPath) ? 'active' : ''; ?>"
                       style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('admin_reviews', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2);' : ''; ?>">
                        <span style="font-size: 1rem;">💬</span>
                        <span style="color: #fff; font-size: 0.875rem;">Avis</span>
                    </a>
                    <a href="events.php" 
                       class="nav-item-sub <?php echo isActive('events', $currentPage, $currentPath) ? 'active' : ''; ?>"
                       style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('events', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2);' : ''; ?>">
                        <span style="font-size: 1rem;">🎉</span>
                        <span style="color: #fff; font-size: 0.875rem;">Événements</span>
                    </a>
                    <a href="loading.php" 
                       class="nav-item-sub <?php echo isActive('loading', $currentPage, $currentPath) ? 'active' : ''; ?>"
                       style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.3s; <?php echo isActive('loading', $currentPage, $currentPath) ? 'background: rgba(139, 92, 246, 0.2);' : ''; ?>">
                        <span style="font-size: 1rem;">⏳</span>
                        <span style="color: #fff; font-size: 0.875rem;">Chargement</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Footer -->
        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.2); display: flex; flex-direction: column; gap: 0.5rem;">
            <a href="../shop/index.php" 
               target="_blank"
               style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #000; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; text-decoration: none; color: #fff; transition: all 0.3s;">
                <span>👁️</span>
                <span>Boutique</span>
            </a>
            <a href="logout.php" 
               style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #000; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; text-decoration: none; color: #ef4444; transition: all 0.3s;">
                <span>🚪</span>
                <span>Déconnexion</span>
            </a>
        </div>
    </div>
</aside>

<style>
.admin-sidebar {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}
.admin-sidebar::-webkit-scrollbar {
    width: 6px;
}
.admin-sidebar::-webkit-scrollbar-track {
    background: transparent;
}
.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}
.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.05) !important;
}

.nav-item.active {
    background: rgba(139, 92, 246, 0.2) !important;
    border: 1px solid rgba(139, 92, 246, 0.5) !important;
}

.nav-item-sub:hover {
    background: rgba(255, 255, 255, 0.05) !important;
}

.nav-item-sub.active {
    background: rgba(139, 92, 246, 0.2) !important;
}

.config-main.active {
    background: linear-gradient(to right, rgba(139, 92, 246, 0.3), rgba(236, 72, 153, 0.3)) !important;
    border: 1px solid rgba(139, 92, 246, 0.5) !important;
}

@media (max-width: 1024px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }
    .admin-sidebar.open {
        transform: translateX(0);
    }
}
</style>

<script>
function toggleConfigSubmenu() {
    const submenu = document.getElementById('config-submenu');
    if (submenu) {
        const isVisible = submenu.style.display === 'flex';
        submenu.style.display = isVisible ? 'none' : 'flex';
    }
}
</script>

