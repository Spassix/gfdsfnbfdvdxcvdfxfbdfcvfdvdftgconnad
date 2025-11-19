<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$category = null;
$isEdit = false;
$error = null;
$success = null;

// Récupérer la catégorie si on est en mode édition
if (isset($_GET['id'])) {
    $isEdit = true;
    try {
        $categories = $supabase->getCategories();
        $category = array_filter($categories, fn($c) => $c['id'] == $_GET['id']);
        $category = !empty($category) ? reset($category) : null;
        if (!$category) {
            header('Location: categories.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $icon = $_POST['icon'] ?? '🎁';
    $description = $_POST['description'] ?? '';
    
    if (empty($name)) {
        $error = 'Le nom de la catégorie est requis';
    } else {
        try {
            $categoryData = [
                'name' => $name,
                'icon' => $icon,
                'description' => $description,
                'enabled' => true,
                'created_at' => $isEdit ? ($category['created_at'] ?? date('Y-m-d\TH:i:s.u\Z')) : date('Y-m-d\TH:i:s.u\Z'),
                'updated_at' => date('Y-m-d\TH:i:s.u\Z')
            ];
            
            if ($isEdit) {
                $categoryData['id'] = $category['id'];
                $supabase->updateCategory($category['id'], $categoryData);
                $success = 'Catégorie modifiée avec succès !';
            } else {
                $supabase->createCategory($categoryData);
                $success = 'Catégorie créée avec succès !';
            }
            
            // Rediriger après 1 seconde
            header('Refresh: 1; url=categories.php');
        } catch (Exception $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

$pageTitle = ($isEdit ? 'Modifier' : 'Ajouter') . ' une catégorie - Panel Admin';
?>
<?php include __DIR__ . '/components/layout.php'; ?>
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">
                <?php echo $isEdit ? 'Modifier la catégorie' : 'Ajouter une catégorie'; ?>
            </h1>
            <a href="categories.php" style="color: #8b5cf6; text-decoration: none;">← Retour à la liste</a>
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

        <form method="POST" style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 1rem; padding: 2rem; backdrop-filter: blur(4px);">
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- Nom -->
                <div>
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Nom de la catégorie *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required
                           style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                </div>

                <!-- Icône -->
                <div>
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Icône (emoji ou URL d'image)</label>
                    <input type="text" name="icon" value="<?php echo htmlspecialchars($category['icon'] ?? '🎁'); ?>"
                           placeholder="🎁 ou https://example.com/icon.png"
                           style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                    <?php if (!empty($category['icon']) && strpos($category['icon'], 'http') === 0): ?>
                        <div style="margin-top: 0.5rem;">
                            <img src="<?php echo htmlspecialchars($category['icon']); ?>" alt="Aperçu" style="max-width: 100px; max-height: 100px; border-radius: 0.5rem;">
                        </div>
                    <?php elseif (!empty($category['icon'])): ?>
                        <div style="margin-top: 0.5rem; font-size: 3rem; text-align: center;">
                            <?php echo htmlspecialchars($category['icon']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div>
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Description</label>
                    <textarea name="description" rows="3"
                              style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem; resize: vertical;"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                </div>

                <!-- Boutons -->
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" style="flex: 1; padding: 0.75rem 1.5rem; background: linear-gradient(to right, #9333ea, #ec4899); color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 1rem;">
                        Enregistrer
                    </button>
                    <a href="categories.php" style="flex: 1; padding: 0.75rem 1.5rem; background: rgba(55, 65, 81, 1); color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; text-align: center; text-decoration: none; display: block; font-size: 1rem;">
                        Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>
<?php include __DIR__ . '/components/footer.php'; ?>

