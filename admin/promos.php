<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$promos = [];
$error = null;
$success = null;
$showModal = false;
$editingPromo = null;

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            // Supprimer un code promo
            try {
                $supabase->deletePromo($_POST['id']);
                $success = 'Code promo supprimé avec succès !';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } elseif ($_POST['action'] === 'save') {
            // Sauvegarder un code promo
            try {
                $promoData = [
                    'code' => strtoupper(trim($_POST['code'] ?? '')),
                    'type' => $_POST['discount_type'] ?? 'fixed',
                    'value' => floatval($_POST['discount'] ?? 0),
                    'min_amount' => floatval($_POST['min_amount'] ?? 0),
                    'enabled' => isset($_POST['enabled']),
                    'updated_at' => date('Y-m-d\TH:i:s.u\Z')
                ];
                
                // Gérer la date d'expiration si fournie
                if (!empty($_POST['expires_at'])) {
                    $promoData['expires_at'] = date('Y-m-d\TH:i:s.u\Z', strtotime($_POST['expires_at']));
                }
                
                // Gérer le nombre maximum d'utilisations
                if (!empty($_POST['max_usage'])) {
                    $promoData['max_usage'] = intval($_POST['max_usage']);
                }
                
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    // Mise à jour
                    $supabase->updatePromo($_POST['id'], $promoData);
                    $success = 'Code promo modifié avec succès !';
                } else {
                    // Création
                    $promoData['created_at'] = date('Y-m-d\TH:i:s.u\Z');
                    $promoData['usage_count'] = 0;
                    $supabase->createPromo($promoData);
                    $success = 'Code promo créé avec succès !';
                }
                $showModal = false;
            } catch (Exception $e) {
                $error = 'Erreur: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['edit'])) {
    try {
        $result = $supabase->request('GET', 'coupons?id=eq.' . $_GET['edit'], null, true);
        if (!empty($result)) {
            $editingPromo = $result[0];
            $showModal = true;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['add'])) {
    $showModal = true;
}

// Charger les codes promo
try {
    $promos = $supabase->getPromos(true);
} catch (Exception $e) {
    $error = $e->getMessage();
    $promos = [];
}

$pageTitle = 'Codes Promo - Panel Admin';
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
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
        .add-button {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(to right, #9333ea, #db2777);
            color: #fff;
            font-weight: 600;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .add-button:hover {
            background: linear-gradient(to right, #7e22ce, #be185d);
        }
        .promos-table {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            overflow: hidden;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
        }
        .table-header {
            background: rgba(30, 41, 59, 0.5);
        }
        .table-header th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
        }
        .table-body tr {
            border-top: 1px solid rgba(30, 41, 59, 1);
            transition: background 0.3s;
        }
        .table-body tr:hover {
            background: rgba(30, 41, 59, 0.3);
        }
        .table-body td {
            padding: 1rem 1.5rem;
            color: #fff;
        }
        .promo-code {
            font-weight: 700;
            font-size: 1.125rem;
        }
        .promo-discount {
            color: rgba(34, 197, 94, 1);
            font-weight: 700;
        }
        .promo-status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .promo-status.active {
            background: rgba(34, 197, 94, 0.2);
            color: rgba(74, 222, 128, 1);
        }
        .promo-status.inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #fff;
        }
        .action-buttons {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        .action-btn {
            padding: 0.5rem 0.75rem;
            background: rgba(55, 65, 81, 0.2);
            border: 1px solid rgba(75, 85, 99, 0.5);
            border-radius: 0.5rem;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .action-btn:hover {
            background: rgba(75, 85, 99, 0.3);
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-content {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
            width: 100%;
            max-width: 28rem;
        }
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            color: #fff;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .form-input,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1rem;
        }
        .form-input::placeholder {
            color: #6b7280;
        }
        .form-help {
            color: #fff;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            opacity: 0.7;
        }
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
        }
        .form-checkbox input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 0.25rem;
            border: 1px solid rgba(75, 85, 99, 1);
            background: rgba(30, 41, 59, 1);
            cursor: pointer;
        }
        .form-checkbox input[type="checkbox"]:checked {
            background: rgba(37, 99, 235, 1);
            border-color: rgba(37, 99, 235, 1);
        }
        .form-checkbox label {
            color: #fff;
            cursor: pointer;
        }
        .modal-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .btn-primary {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(to right, #9333ea, #db2777);
            color: #fff;
            font-weight: 700;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #7e22ce, #be185d);
        }
        .btn-secondary {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: rgba(55, 65, 81, 1);
            color: #fff;
            font-weight: 700;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-secondary:hover {
            background: rgba(75, 85, 99, 1);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: #fff;
        }
        .error-message,
        .success-message {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .error-message {
            background: rgba(127, 29, 29, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
        }
        .error-message p {
            color: rgba(248, 113, 113, 1);
        }
        .success-message {
            background: rgba(20, 83, 45, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.5);
        }
        .success-message p {
            color: rgba(74, 222, 128, 1);
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
                <div>
                    <h1 class="page-title">🎟️ Codes Promo</h1>
                    <p class="page-subtitle"><?php echo count($promos); ?> code(s) promo</p>
                </div>
                <a href="?add=1" class="add-button">
                    <span>➕</span>
                    <span>Ajouter un code</span>
                </a>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <p>Erreur: <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <div class="promos-table neon-border">
                <?php if (empty($promos)): ?>
                    <div class="empty-state">
                        <p style="font-size: 1.125rem; margin-bottom: 1rem;">Aucun code promo</p>
                        <a href="?add=1" class="add-button" style="display: inline-flex;">
                            <span>Créer mon premier code</span>
                        </a>
                    </div>
                <?php else: ?>
                    <table style="width: 100%;">
                        <thead class="table-header">
                            <tr>
                                <th>Code</th>
                                <th>Réduction</th>
                                <th>Statut</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            <?php foreach ($promos as $promo): ?>
                                <tr>
                                    <td>
                                        <span class="promo-code"><?php echo htmlspecialchars($promo['code'] ?? ''); ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <?php 
                                            $value = $promo['value'] ?? 0;
                                            $type = $promo['type'] ?? 'fixed';
                                            if ($type === 'percentage') {
                                                echo '<span class="promo-discount">-' . number_format($value, 0) . '%</span>';
                                            } else {
                                                echo '<span class="promo-discount">-' . number_format($value, 2) . '€</span>';
                                            }
                                            ?>
                                            <?php if (($promo['min_amount'] ?? 0) > 0): ?>
                                                <p style="color: #fff; font-size: 0.75rem; margin-top: 0.25rem;">Min: <?php echo number_format($promo['min_amount'], 2); ?>€</p>
                                            <?php endif; ?>
                                            <?php if (!empty($promo['expires_at'])): ?>
                                                <p style="color: #9ca3af; font-size: 0.75rem; margin-top: 0.25rem;">Expire: <?php echo date('d/m/Y', strtotime($promo['expires_at'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="promo-status <?php echo ($promo['enabled'] ?? false) ? 'active' : 'inactive'; ?>">
                                            <?php echo ($promo['enabled'] ?? false) ? '✅ Actif' : '❌ Inactif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $promo['id']; ?>" class="action-btn">✏️</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce code promo ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                                <button type="submit" class="action-btn">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <?php if ($showModal || isset($_GET['add']) || isset($_GET['edit'])): ?>
    <div class="modal-overlay" id="promo-modal" onclick="if(event.target === this) window.location.href='promos.php';">
        <div class="modal-content" onclick="event.stopPropagation();">
            <h2 class="modal-title"><?php echo $editingPromo ? 'Modifier le code' : 'Nouveau code promo'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <?php if ($editingPromo): ?>
                    <input type="hidden" name="id" value="<?php echo $editingPromo['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Code</label>
                    <input type="text" 
                           name="code" 
                           value="<?php echo htmlspecialchars($editingPromo['code'] ?? ''); ?>"
                           placeholder="BIENVENUE10"
                           required
                           class="form-input"
                           style="text-transform: uppercase;"
                           oninput="this.value = this.value.toUpperCase();">
                </div>

                <div class="form-group">
                    <label class="form-label">Type de réduction</label>
                    <select name="discount_type" id="discount-type" class="form-select" onchange="updateDiscountLabel()">
                        <option value="fixed" <?php echo ($editingPromo['type'] ?? 'fixed') === 'fixed' ? 'selected' : ''; ?>>Montant fixe (€)</option>
                        <option value="percentage" <?php echo ($editingPromo['type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>Pourcentage (%)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" id="discount-label">Réduction (€)</label>
                    <input type="number" 
                           name="discount" 
                           id="discount-input"
                           min="0" 
                           step="0.01" 
                           value="<?php echo htmlspecialchars($editingPromo['value'] ?? '5.00'); ?>"
                           placeholder="5.00"
                           required
                           class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Date d'expiration (optionnel)</label>
                    <input type="datetime-local" 
                           name="expires_at" 
                           value="<?php echo !empty($editingPromo['expires_at']) ? date('Y-m-d\TH:i', strtotime($editingPromo['expires_at'])) : ''; ?>"
                           class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre maximum d'utilisations (optionnel)</label>
                    <input type="number" 
                           name="max_usage" 
                           min="1"
                           value="<?php echo htmlspecialchars($editingPromo['max_usage'] ?? ''); ?>"
                           placeholder="Illimité"
                           class="form-input">
                    <p class="form-help">Laisser vide pour un usage illimité</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Montant minimum (€)</label>
                    <input type="number" 
                           name="min_amount" 
                           min="0" 
                           step="0.01" 
                           value="<?php echo htmlspecialchars($editingPromo['min_amount'] ?? '0'); ?>"
                           placeholder="0 (pas de minimum)"
                           class="form-input">
                    <p class="form-help">Le panier doit atteindre ce montant pour utiliser le code</p>
                </div>

                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" 
                               name="enabled" 
                               id="enabled"
                               <?php echo ($editingPromo['enabled'] ?? true) ? 'checked' : ''; ?>>
                        <label for="enabled">Code actif</label>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Enregistrer</button>
                    <button type="button" 
                            class="btn-secondary"
                            onclick="window.location.href='promos.php';">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function updateDiscountLabel() {
            const type = document.getElementById('discount-type').value;
            const label = document.getElementById('discount-label');
            const input = document.getElementById('discount-input');
            if (type === 'percentage') {
                label.textContent = 'Réduction (%)';
                input.step = '1';
                input.min = '0';
                input.max = '100';
            } else {
                label.textContent = 'Réduction (€)';
                input.step = '0.01';
                input.min = '0';
                input.max = '';
            }
        }
        
        // Initialiser le label au chargement
        updateDiscountLabel();
        
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
