<?php
// Nouveau header avec sidebar
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
    <!-- Mobile menu button -->
    <button id="mobile-menu-btn" 
        style="position: fixed; top: 1rem; left: 1rem; z-index: 60; padding: 0.75rem; background: rgba(0, 0, 0, 0.9); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff; display: none; cursor: pointer; transition: all 0.3s;"
        onclick="toggleAdminMobileMenu()">
        <span style="font-size: 1.5rem;">☰</span>
    </button>

<!-- Overlay mobile -->
<div id="admin-mobile-overlay" 
     style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 40; backdrop-filter: blur(4px);"
     onclick="closeAdminMobileMenu()"></div>

<?php include __DIR__ . '/sidebar.php'; ?>

<!-- Main content wrapper -->
<div class="admin-main-content" style="margin-left: 18rem; min-height: 100vh; padding: 2rem;">
    <!-- Content will be inserted here by each page -->
</div>

<style>
@media (max-width: 1024px) {
    .admin-main-content {
        margin-left: 0 !important;
        padding: 1rem !important;
        padding-top: 4rem !important;
    }
    #mobile-menu-btn {
        display: block !important;
    }
    .admin-sidebar {
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
    }
    #admin-mobile-overlay.show {
        display: block !important;
    }
}
</style>

<script>
function toggleAdminMobileMenu() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('admin-mobile-overlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    }
}

function closeAdminMobileMenu() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('admin-mobile-overlay');
    if (sidebar && overlay) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }
}

// Fermer le menu si on clique en dehors
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.admin-sidebar');
    const btn = document.getElementById('mobile-menu-btn');
    const overlay = document.getElementById('admin-mobile-overlay');
    
    if (sidebar && btn && overlay && sidebar.classList.contains('open')) {
        if (!sidebar.contains(event.target) && !btn.contains(event.target)) {
            closeAdminMobileMenu();
        }
    }
});
</script>
