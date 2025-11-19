        </main>
    </div>
    <script>
        // Gestion du menu mobile
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.getElementById('mobile-overlay');
        const menuBtn = document.getElementById('mobile-menu-btn');
        
        if (window.innerWidth <= 1024) {
            if (menuBtn) menuBtn.style.display = 'block';
            
            function toggleSidebar() {
                sidebar.classList.toggle('open');
                if (overlay) {
                    overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
                }
            }
            
            if (menuBtn) {
                menuBtn.onclick = toggleSidebar;
            }
            
            // Fermer la sidebar quand on clique sur un lien
            document.querySelectorAll('.admin-sidebar a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 1024) {
                        sidebar.classList.remove('open');
                        if (overlay) overlay.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html>

