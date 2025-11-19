<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$socials = [];
$error = null;
$success = null;
$showModal = false;
$editingSocial = null;

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            try {
                $supabase->request('DELETE', 'socials?id=eq.' . $_POST['id']);
                $success = 'Réseau social supprimé avec succès !';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } elseif ($_POST['action'] === 'save') {
            try {
                $socialData = [
                    'name' => $_POST['name'] ?? '',
                    'url' => $_POST['url'] ?? '',
                    'icon' => $_POST['icon'] ?? '🌐',
                    'enabled' => isset($_POST['enabled']),
                    'sort_order' => intval($_POST['sort_order'] ?? 0),
                    'updated_at' => date('Y-m-d\TH:i:s.u\Z')
                ];
                
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    // Modifier
                    $supabase->request('PATCH', 'socials?id=eq.' . $_POST['id'], $socialData);
                    $success = 'Réseau social modifié avec succès !';
                } else {
                    // Créer
                    $socialData['created_at'] = date('Y-m-d\TH:i:s.u\Z');
                    $supabase->request('POST', 'socials', $socialData);
                    $success = 'Réseau social créé avec succès !';
                }
                $showModal = false;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

if (isset($_GET['edit'])) {
    try {
        $result = $supabase->request('GET', 'socials?id=eq.' . $_GET['edit']);
        if (!empty($result)) {
            $editingSocial = $result[0];
            $showModal = true;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['add'])) {
    $showModal = true;
}

// Charger les réseaux sociaux (tous, pas seulement les activés)
try {
    $socials = $supabase->request('GET', 'socials?order=sort_order.asc');
} catch (Exception $e) {
    $error = $e->getMessage();
    $socials = [];
}

$pageTitle = 'Réseaux Sociaux - Panel Admin';
?>
<?php include __DIR__ . '/components/layout.php'; ?>
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">🌐 Réseaux Sociaux</h1>
                    <p style="color: #fff; opacity: 0.7;">Gérer les liens vers les réseaux sociaux</p>
                </div>
                <a href="?add=1" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(to right, #9333ea, #ec4899); color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; text-decoration: none; cursor: pointer;">
                    <span>➕</span>
                    <span>Ajouter un réseau</span>
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(127, 29, 29, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                <p style="color: rgba(248, 113, 113, 1);">Erreur: <?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: rgba(20, 83, 45, 0.2); border: 1px solid rgba(34, 197, 94, 0.5); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                <p style="color: rgba(74, 222, 128, 1);"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($socials as $social): ?>
                <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 1rem; padding: 1.5rem; backdrop-filter: blur(4px);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2rem;"><?php echo htmlspecialchars($social['icon'] ?? '🌐'); ?></div>
                        <div style="flex: 1;">
                            <h3 style="color: #fff; font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">
                                <?php echo htmlspecialchars($social['name'] ?? ''); ?>
                            </h3>
                            <a href="<?php echo htmlspecialchars($social['url'] ?? '#'); ?>" target="_blank" 
                               style="color: #8b5cf6; text-decoration: none; font-size: 0.875rem; word-break: break-all;">
                                <?php echo htmlspecialchars($social['url'] ?? ''); ?>
                            </a>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <a href="?edit=<?php echo $social['id']; ?>" 
                           style="flex: 1; padding: 0.5rem; background: rgba(55, 65, 81, 0.3); border: 1px solid rgba(75, 85, 99, 0.5); border-radius: 0.5rem; color: #fff; text-align: center; text-decoration: none; font-size: 0.875rem;">
                            ✏️ Modifier
                        </a>
                        <form method="POST" style="flex: 1; display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce réseau social ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $social['id']; ?>">
                            <button type="submit" style="width: 100%; padding: 0.5rem; background: rgba(127, 29, 29, 0.3); border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 0.5rem; color: #fff; cursor: pointer; font-size: 0.875rem;">
                                🗑️ Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($socials)): ?>
            <div style="text-align: center; padding: 3rem; color: #fff; opacity: 0.7;">
                <p style="font-size: 1.125rem;">Aucun réseau social configuré</p>
                <p style="margin-top: 0.5rem;">Cliquez sur "Ajouter un réseau" pour commencer</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <?php if ($showModal): ?>
        <div id="modal" style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(4px); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1rem;">
            <div style="background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 1rem; padding: 2rem; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: #fff;">
                        <?php echo $editingSocial ? 'Modifier le réseau' : 'Ajouter un réseau'; ?>
                    </h2>
                    <a href="socials.php" style="color: #fff; text-decoration: none; font-size: 1.5rem; cursor: pointer;">×</a>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <?php if ($editingSocial): ?>
                        <input type="hidden" name="id" value="<?php echo $editingSocial['id']; ?>">
                    <?php endif; ?>

                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <!-- Nom -->
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Nom du réseau *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($editingSocial['name'] ?? ''); ?>" required
                                   placeholder="Ex: Facebook, Instagram, Twitter..."
                                   style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                        </div>

                        <!-- Lien -->
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Lien (URL) *</label>
                            <input type="url" name="url" value="<?php echo htmlspecialchars($editingSocial['url'] ?? ''); ?>" required
                                   placeholder="https://facebook.com/votre-page"
                                   style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                        </div>

                        <!-- Icône -->
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Icône (emoji)</label>
                            <input type="text" name="icon" value="<?php echo htmlspecialchars($editingSocial['icon'] ?? '🌐'); ?>"
                                   placeholder="🌐"
                                   style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                            <p style="color: #fff; opacity: 0.7; font-size: 0.875rem; margin-top: 0.25rem;">Exemples: 📘 Facebook, 📷 Instagram, 🐦 Twitter</p>
                        </div>

                        <!-- Ordre -->
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Ordre d'affichage</label>
                            <input type="number" name="sort_order" value="<?php echo htmlspecialchars($editingSocial['sort_order'] ?? 0); ?>"
                                   min="0"
                                   style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                        </div>

                        <!-- Activé -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 0.5rem; color: #fff; cursor: pointer;">
                                <input type="checkbox" name="enabled" <?php echo ($editingSocial['enabled'] ?? true) ? 'checked' : ''; ?>
                                       style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                <span>Activer ce réseau social</span>
                            </label>
                        </div>

                        <!-- Boutons -->
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" style="flex: 1; padding: 0.75rem 1.5rem; background: linear-gradient(to right, #9333ea, #ec4899); color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 1rem;">
                                Enregistrer
                            </button>
                            <a href="socials.php" style="flex: 1; padding: 0.75rem 1.5rem; background: rgba(55, 65, 81, 1); color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; text-align: center; text-decoration: none; display: block; font-size: 1rem;">
                                Annuler
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php include __DIR__ . '/components/footer.php'; ?>
