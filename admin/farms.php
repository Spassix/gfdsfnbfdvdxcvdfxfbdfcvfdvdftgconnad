<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$farms = [];
$error = null;
$success = null;

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        try {
            $supabase->deleteFarm($_POST['id']);
            $success = 'Farm supprimée avec succès !';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

try {
    $farms = $supabase->getFarms();
} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = 'Farms - Panel Admin';
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
        .farms-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .farms-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .farms-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (min-width: 1280px) {
            .farms-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .farm-card {
            border: 1px solid rgba(55, 65, 81, 1);
            border-radius: 0.75rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.5);
            transition: all 0.3s;
        }
        .farm-card:hover {
            background: rgba(0, 0, 0, 0.7);
        }
        .farm-name {
            color: #fff;
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: 0.75rem;
        }
        .farm-description {
            color: #fff;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .farm-actions {
            display: flex;
            gap: 0.5rem;
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
                        <h1 class="page-title">Gestion des Farms</h1>
                        <p class="page-subtitle"><?php echo count($farms); ?> farm(s) au total</p>
                    </div>
                    <a href="farm_form.php" class="add-button">
                        <span>➕</span>
                        <span>Ajouter une farm</span>
                    </a>
                </div>
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
            <div class="farms-grid">
                <?php foreach ($farms as $farm): ?>
                    <div class="farm-card">
                        <h3 class="farm-name">🌾 <?php echo htmlspecialchars($farm['name'] ?? ''); ?></h3>
                        <p class="farm-description"><?php echo htmlspecialchars($farm['description'] ?? ''); ?></p>
                        <div class="farm-actions">
                            <a href="farm_form.php?id=<?php echo $farm['id']; ?>" class="action-btn">✏️ Modifier</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette farm ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $farm['id']; ?>">
                                <button type="submit" class="action-btn" style="border: none; background: none; cursor: pointer; width: 100%;">🗑️ Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
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
