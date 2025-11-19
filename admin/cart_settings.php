<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$settings = [
    'services' => [
        ['name' => 'Livraison', 'label' => '🚚 Livraison', 'description' => 'Livraison à domicile', 'fee' => 0, 'enabled' => true, 'slots' => []],
        ['name' => 'Meetup', 'label' => '🤝 Meetup', 'description' => 'Rendez-vous en personne', 'fee' => 0, 'enabled' => true, 'slots' => []],
        ['name' => 'Envoi', 'label' => '📦 Envoi postal', 'description' => 'Envoi par la poste', 'fee' => 0, 'enabled' => true, 'slots' => []]
    ],
    'payments' => [
        ['label' => '💵 Espèces', 'enabled' => true],
        ['label' => '₿ Crypto', 'enabled' => true]
    ],
    'alertEnabled' => false,
    'alertMessage' => '',
    'promosEnabled' => true,
    'contactLinks' => []
];

$error = null;
$success = null;

// Charger les paramètres existants
try {
    $cartResult = $supabase->request('GET', 'settings?key=eq.cart', null, true);
    if (!empty($cartResult) && isset($cartResult[0]['value'])) {
        $cartData = $cartResult[0]['value'];
        if (is_string($cartData)) {
            $cartData = json_decode($cartData, true);
        }
        if (is_array($cartData)) {
            $settings = array_merge($settings, $cartData);
        }
    }
} catch (Exception $e) {
    error_log("Erreur chargement cart settings: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Traiter les services
        $services = [];
        if (isset($_POST['services']) && is_array($_POST['services'])) {
            foreach ($_POST['services'] as $index => $service) {
                // Ignorer les services complètement vides
                $label = trim($service['label'] ?? '');
                $description = trim($service['description'] ?? '');
                
                // Si le label est vide, ignorer ce service
                if (empty($label)) {
                    continue;
                }
                
                // Générer un nom unique si non fourni
                $serviceName = $service['name'] ?? '';
                if (empty($serviceName)) {
                    // Utiliser le label comme base pour le nom, ou générer un nom unique
                    $labelBase = preg_replace('/[^a-zA-Z0-9]/', '', $label);
                    $serviceName = !empty($labelBase) ? $labelBase : 'Service' . $index;
                }
                
                // Traiter les slots - ils peuvent être un tableau ou une chaîne vide
                $slots = [];
                if (isset($service['slots']) && is_array($service['slots'])) {
                    // Filtrer les slots vides
                    $slots = array_filter($service['slots'], function($slot) {
                        return !empty(trim($slot));
                    });
                    $slots = array_values($slots); // Réindexer
                }
                
                $services[] = [
                    'name' => $serviceName,
                    'label' => $label,
                    'description' => $description,
                    'fee' => floatval($service['fee'] ?? 0),
                    'enabled' => isset($service['enabled']) && ($service['enabled'] === 'on' || $service['enabled'] === true || $service['enabled'] === '1'),
                    'slots' => $slots
                ];
            }
        }
        
        // Traiter les paiements
        $payments = [];
        if (isset($_POST['payments']) && is_array($_POST['payments'])) {
            foreach ($_POST['payments'] as $payment) {
                $payments[] = [
                    'label' => $payment['label'] ?? '',
                    'enabled' => isset($payment['enabled'])
                ];
            }
        }
        
        // Traiter les liens de contact
        $contactLinks = [];
        if (isset($_POST['contactLinks']) && is_array($_POST['contactLinks'])) {
            foreach ($_POST['contactLinks'] as $link) {
                $contactLinks[] = [
                    'name' => $link['name'] ?? '',
                    'url' => $link['url'] ?? '',
                    'services' => $link['services'] ?? []
                ];
            }
        }
        
        // Construire les données à sauvegarder
        $cartData = [
            'services' => $services,
            'payments' => $payments,
            'alertEnabled' => isset($_POST['alertEnabled']),
            'alertMessage' => $_POST['alertMessage'] ?? '',
            'promosEnabled' => isset($_POST['promosEnabled']),
            'contactLinks' => $contactLinks
        ];
        
        // Sauvegarder dans Supabase
        $settingsData = [
            'key' => 'cart',
            'value' => json_encode($cartData, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        $existingCart = $supabase->request('GET', 'settings?key=eq.cart', null, true);
        if (!empty($existingCart)) {
            $result = $supabase->request('PATCH', 'settings?key=eq.cart', $settingsData, true);
            if (empty($result)) {
                throw new Exception('Erreur lors de la mise à jour des paramètres');
            }
        } else {
            $settingsData['created_at'] = date('Y-m-d\TH:i:s.u\Z');
            $result = $supabase->request('POST', 'settings', $settingsData, true);
            if (empty($result)) {
                throw new Exception('Erreur lors de la création des paramètres');
            }
        }
        
        $success = 'Paramètres du panier sauvegardés !';
        
        // Recharger les données depuis Supabase pour afficher les services sauvegardés
        $cartResult = $supabase->request('GET', 'settings?key=eq.cart', null, true);
        if (!empty($cartResult) && isset($cartResult[0]['value'])) {
            $cartData = $cartResult[0]['value'];
            if (is_string($cartData)) {
                $cartData = json_decode($cartData, true);
            }
            if (is_array($cartData)) {
                // Remplacer complètement les settings par ceux de Supabase
                $settings = $cartData;
                // S'assurer que les clés par défaut existent
                if (!isset($settings['services'])) $settings['services'] = [];
                if (!isset($settings['payments'])) $settings['payments'] = [];
                if (!isset($settings['contactLinks'])) $settings['contactLinks'] = [];
            }
        }
    } catch (Exception $e) {
        $error = 'Erreur: ' . $e->getMessage();
        error_log("Erreur sauvegarde cart settings: " . $e->getMessage());
        error_log("POST data: " . print_r($_POST, true));
        error_log("Services traités: " . print_r($services ?? [], true));
    }
}

$pageTitle = 'Paramètres du Panier - Panel Admin';
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
        .save-btn {
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
            gap: 0.5rem;
        }
        .save-btn:hover {
            background: linear-gradient(to right, #7e22ce, #be185d);
        }
        .settings-section {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            margin-bottom: 1.5rem;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1rem;
        }
        .service-card {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-label {
            color: #fff;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1rem;
        }
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        }
        .form-checkbox label {
            color: #fff;
            cursor: pointer;
        }
        .slots-container {
            border-top: 1px solid rgba(55, 65, 81, 0.5);
            padding-top: 1rem;
            margin-top: 1rem;
        }
        .slots-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.5rem;
        }
        .slot-item {
            background: rgba(15, 23, 42, 0.5);
            padding: 0.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .slot-text {
            color: #fff;
            font-size: 0.875rem;
        }
        .remove-slot-btn {
            background: rgba(239, 68, 68, 0.2);
            color: rgba(248, 113, 113, 1);
            border: none;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
        }
        .add-btn {
            padding: 0.5rem 1rem;
            background: rgba(139, 92, 246, 1);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
        }
        .add-btn:hover {
            background: rgba(124, 58, 237, 1);
        }
        .payment-item {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 0.5rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }
        .payment-input {
            flex: 1;
        }
        .delete-btn {
            background: rgba(239, 68, 68, 0.2);
            color: rgba(248, 113, 113, 1);
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem;
            cursor: pointer;
        }
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
        }
        .contact-link-card {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .services-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .service-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(15, 23, 42, 0.5);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
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
        @media (max-width: 767px) {
            .form-grid {
                grid-template-columns: 1fr !important;
            }
            .settings-section {
                padding: 1rem;
            }
            .slots-grid {
                grid-template-columns: 1fr !important;
            }
            .add-btn, .delete-btn {
                font-size: 0.875rem;
                padding: 0.5rem 0.75rem;
            }
            .service-card {
                padding: 0.75rem;
            }
            .section-title {
                font-size: 1rem;
            }
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
                    <h1 class="page-title">🛒 Paramètres du Panier</h1>
                    <p class="page-subtitle">Configurez les services, paiements et horaires</p>
                </div>
                <button type="submit" form="cart-settings-form" class="save-btn">
                    <span>💾</span>
                    <span>Sauvegarder</span>
                </button>
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

            <form method="POST" id="cart-settings-form" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <!-- Services -->
                <div class="settings-section neon-border">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <h2 class="section-title" style="margin-bottom: 0;">🛠️ Services</h2>
                        <button type="button" 
                                class="add-btn"
                                onclick="addService()">
                            + Ajouter un service
                        </button>
                    </div>
                    <div id="services-container">
                    <?php 
                    // Réindexer les services pour éviter les problèmes d'indexation
                    $servicesList = array_values($settings['services']);
                    foreach ($servicesList as $serviceIndex => $service): 
                    ?>
                        <div class="service-card">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Label</label>
                                    <input type="text" 
                                           name="services[<?php echo $serviceIndex; ?>][label]" 
                                           value="<?php echo htmlspecialchars($service['label']); ?>"
                                           class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <input type="text" 
                                           name="services[<?php echo $serviceIndex; ?>][description]" 
                                           value="<?php echo htmlspecialchars($service['description']); ?>"
                                           class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Frais (€)</label>
                                    <input type="number" 
                                           name="services[<?php echo $serviceIndex; ?>][fee]" 
                                           value="<?php echo htmlspecialchars($service['fee']); ?>"
                                           step="0.01"
                                           class="form-input">
                                </div>
                                <div class="form-group" style="justify-content: flex-end;">
                                    <div class="form-checkbox">
                                        <input type="checkbox" 
                                               name="services[<?php echo $serviceIndex; ?>][enabled]" 
                                               id="service_<?php echo $serviceIndex; ?>_enabled"
                                               <?php echo $service['enabled'] ? 'checked' : ''; ?>>
                                        <label for="service_<?php echo $serviceIndex; ?>_enabled">Activé</label>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="services[<?php echo $serviceIndex; ?>][name]" value="<?php echo htmlspecialchars($service['name']); ?>">
                            
                            <div class="slots-container">
                                <div class="slots-header">
                                    <label class="form-label">⏰ Créneaux horaires</label>
                                    <button type="button" 
                                            class="add-btn"
                                            onclick="addSlot(<?php echo $serviceIndex; ?>)">
                                        + Ajouter
                                    </button>
                                </div>
                                <div class="slots-grid" id="slots_<?php echo $serviceIndex; ?>">
                                    <?php foreach ($service['slots'] as $slotIndex => $slot): ?>
                                        <div class="slot-item">
                                            <span class="slot-text"><?php echo htmlspecialchars($slot); ?></span>
                                            <input type="hidden" name="services[<?php echo $serviceIndex; ?>][slots][]" value="<?php echo htmlspecialchars($slot); ?>">
                                            <button type="button" 
                                                    class="remove-slot-btn"
                                                    onclick="removeSlot(<?php echo $serviceIndex; ?>, this)">
                                                ✕
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                                <button type="button" 
                                        class="delete-btn"
                                        onclick="removeService(this)">
                                    🗑️ Supprimer ce service
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>

                <!-- Moyens de paiement -->
                <div class="settings-section neon-border">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <h2 class="section-title" style="margin-bottom: 0;">💳 Moyens de paiement</h2>
                        <button type="button" 
                                class="add-btn"
                                onclick="addPayment()">
                            + Ajouter
                        </button>
                    </div>
                    <div id="payments-container">
                        <?php foreach ($settings['payments'] as $paymentIndex => $payment): ?>
                            <div class="payment-item">
                                <div class="payment-input">
                                    <input type="text" 
                                           name="payments[<?php echo $paymentIndex; ?>][label]" 
                                           value="<?php echo htmlspecialchars($payment['label']); ?>"
                                           class="form-input">
                                </div>
                                <div class="form-checkbox">
                                    <input type="checkbox" 
                                           name="payments[<?php echo $paymentIndex; ?>][enabled]" 
                                           id="payment_<?php echo $paymentIndex; ?>_enabled"
                                           <?php echo $payment['enabled'] ? 'checked' : ''; ?>>
                                    <label for="payment_<?php echo $paymentIndex; ?>_enabled">Activé</label>
                                </div>
                                <button type="button" 
                                        class="delete-btn"
                                        onclick="removePayment(this)">
                                    🗑️
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Message d'alerte -->
                <div class="settings-section neon-border">
                    <h2 class="section-title">⚠️ Message d'alerte</h2>
                    <div class="form-checkbox" style="margin-bottom: 1rem;">
                        <input type="checkbox" 
                               name="alertEnabled" 
                               id="alertEnabled"
                               <?php echo $settings['alertEnabled'] ? 'checked' : ''; ?>
                               onchange="toggleAlertMessage()">
                        <label for="alertEnabled">Afficher un message d'alerte</label>
                    </div>
                    <div id="alert-message-container" style="display: <?php echo $settings['alertEnabled'] ? 'block' : 'none'; ?>;">
                        <textarea name="alertMessage" 
                                  class="form-textarea"
                                  placeholder="ATTENTION AU SCAM ON VOUS DM JAMAIS !!"><?php echo htmlspecialchars($settings['alertMessage']); ?></textarea>
                    </div>
                </div>

                <!-- Codes promo -->
                <div class="settings-section neon-border">
                    <h2 class="section-title">🎟️ Codes promo</h2>
                    <div class="form-checkbox">
                        <input type="checkbox" 
                               name="promosEnabled" 
                               id="promosEnabled"
                               <?php echo $settings['promosEnabled'] ? 'checked' : ''; ?>>
                        <label for="promosEnabled">Activer les codes promo</label>
                    </div>
                    <p style="color: #fff; font-size: 0.875rem; margin-top: 0.5rem;">
                        Gérez vos codes promo dans la page "Codes Promo" du menu
                    </p>
                </div>

                <!-- Liens de contact -->
                <div class="settings-section neon-border">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <h2 class="section-title" style="margin-bottom: 0;">📱 Liens de contact</h2>
                        <button type="button" 
                                class="add-btn"
                                onclick="addContactLink()">
                            + Ajouter un lien
                        </button>
                    </div>
                    <div id="contact-links-container">
                        <?php foreach ($settings['contactLinks'] as $linkIndex => $link): ?>
                            <div class="contact-link-card">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label">Nom</label>
                                        <input type="text" 
                                               name="contactLinks[<?php echo $linkIndex; ?>][name]" 
                                               value="<?php echo htmlspecialchars($link['name'] ?? ''); ?>"
                                               placeholder="WhatsApp"
                                               class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">URL</label>
                                        <input type="text" 
                                               name="contactLinks[<?php echo $linkIndex; ?>][url]" 
                                               value="<?php echo htmlspecialchars($link['url'] ?? ''); ?>"
                                               placeholder="http://wa.me/33620056669"
                                               class="form-input">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Disponible pour les services (laisse vide pour tous)</label>
                                    <div class="services-checkboxes">
                                        <?php foreach ($settings['services'] as $serviceIdx => $service): ?>
                                            <div class="service-checkbox">
                                                <input type="checkbox" 
                                                       name="contactLinks[<?php echo $linkIndex; ?>][services][]" 
                                                       value="<?php echo htmlspecialchars($service['name']); ?>"
                                                       id="link_<?php echo $linkIndex; ?>_service_<?php echo $serviceIdx; ?>"
                                                       <?php echo in_array($service['name'], $link['services'] ?? []) ? 'checked' : ''; ?>>
                                                <label for="link_<?php echo $linkIndex; ?>_service_<?php echo $serviceIdx; ?>">
                                                    <?php echo htmlspecialchars($service['label']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                                    <button type="button" 
                                            class="delete-btn"
                                            onclick="removeContactLink(this)">
                                        🗑️ Supprimer
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </main>
    </div>
    <script>
        // Calculer le prochain index disponible
        let serviceCount = <?php echo count($settings['services']); ?>;
        let maxServiceIndex = serviceCount;
        
        // Trouver l'index maximum existant
        document.querySelectorAll('[name^="services["]').forEach(input => {
            const match = input.name.match(/services\[(\d+)\]/);
            if (match) {
                const idx = parseInt(match[1]);
                if (idx >= maxServiceIndex) {
                    maxServiceIndex = idx + 1;
                }
            }
        });
        serviceCount = maxServiceIndex;
        
        function addService() {
            const container = document.getElementById('services-container');
            const serviceCard = document.createElement('div');
            serviceCard.className = 'service-card';
            const currentIndex = serviceCount;
            serviceCard.innerHTML = `
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Label</label>
                        <input type="text" 
                               name="services[${currentIndex}][label]" 
                               value=""
                               placeholder="🚚 Nouveau service"
                               class="form-input"
                               required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <input type="text" 
                               name="services[${currentIndex}][description]" 
                               value=""
                               placeholder="Description du service"
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Frais (€)</label>
                        <input type="number" 
                               name="services[${currentIndex}][fee]" 
                               value="0"
                               step="0.01"
                               class="form-input">
                    </div>
                    <div class="form-group" style="justify-content: flex-end;">
                        <div class="form-checkbox">
                            <input type="checkbox" 
                                   name="services[${currentIndex}][enabled]" 
                                   id="service_${currentIndex}_enabled"
                                   checked>
                            <label for="service_${currentIndex}_enabled">Activé</label>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="services[${currentIndex}][name]" value="Service${currentIndex}">
                
                <div class="slots-container">
                    <div class="slots-header">
                        <label class="form-label">⏰ Créneaux horaires</label>
                        <button type="button" 
                                class="add-btn"
                                onclick="addSlot(${currentIndex})">
                            + Ajouter
                        </button>
                    </div>
                    <div class="slots-grid" id="slots_${currentIndex}">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" 
                            class="delete-btn"
                            onclick="removeService(this)">
                        🗑️ Supprimer ce service
                    </button>
                </div>
            `;
            container.appendChild(serviceCard);
            serviceCount++;
        }
        
        function removeService(btn) {
            if (confirm('Supprimer ce service ?')) {
                btn.closest('.service-card').remove();
            }
        }
        
        function addSlot(serviceIndex) {
            const slot = prompt('Nouveau créneau (texte libre, ex: 9h-12h, Disponible 7/7 14h-22h, etc.)');
            if (slot && slot.trim()) {
                const container = document.getElementById('slots_' + serviceIndex);
                const slotItem = document.createElement('div');
                slotItem.className = 'slot-item';
                slotItem.innerHTML = `
                    <span class="slot-text">${slot}</span>
                    <input type="hidden" name="services[${serviceIndex}][slots][]" value="${slot}">
                    <button type="button" class="remove-slot-btn" onclick="removeSlot(${serviceIndex}, this)">✕</button>
                `;
                container.appendChild(slotItem);
            }
        }
        
        function removeSlot(serviceIndex, btn) {
            btn.closest('.slot-item').remove();
        }
        
        let paymentCount = <?php echo count($settings['payments']); ?>;
        function addPayment() {
            const label = prompt('Nouveau moyen de paiement (avec emoji si souhaité)');
            if (label) {
                const container = document.getElementById('payments-container');
                const paymentItem = document.createElement('div');
                paymentItem.className = 'payment-item';
                paymentItem.innerHTML = `
                    <div class="payment-input">
                        <input type="text" name="payments[${paymentCount}][label]" value="${label}" class="form-input">
                    </div>
                    <div class="form-checkbox">
                        <input type="checkbox" name="payments[${paymentCount}][enabled]" id="payment_${paymentCount}_enabled" checked>
                        <label for="payment_${paymentCount}_enabled">Activé</label>
                    </div>
                    <button type="button" class="delete-btn" onclick="removePayment(this)">🗑️</button>
                `;
                container.appendChild(paymentItem);
                paymentCount++;
            }
        }
        
        function removePayment(btn) {
            if (confirm('Supprimer ce moyen de paiement ?')) {
                btn.closest('.payment-item').remove();
            }
        }
        
        function toggleAlertMessage() {
            const checkbox = document.getElementById('alertEnabled');
            const container = document.getElementById('alert-message-container');
            container.style.display = checkbox.checked ? 'block' : 'none';
        }
        
        let contactLinkCount = <?php echo count($settings['contactLinks']); ?>;
        function addContactLink() {
            const name = prompt('Nom du lien (ex: WhatsApp, Instagram, Contact)');
            if (name) {
                const container = document.getElementById('contact-links-container');
                const linkCard = document.createElement('div');
                linkCard.className = 'contact-link-card';
                linkCard.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Nom</label>
                            <input type="text" name="contactLinks[${contactLinkCount}][name]" value="${name}" placeholder="WhatsApp" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">URL</label>
                            <input type="text" name="contactLinks[${contactLinkCount}][url]" value="" placeholder="http://wa.me/33620056669" class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Disponible pour les services (laisse vide pour tous)</label>
                        <div class="services-checkboxes">
                            <?php foreach ($settings['services'] as $serviceIdx => $service): ?>
                                <div class="service-checkbox">
                                    <input type="checkbox" name="contactLinks[${contactLinkCount}][services][]" value="<?php echo htmlspecialchars($service['name']); ?>" id="link_${contactLinkCount}_service_<?php echo $serviceIdx; ?>">
                                    <label for="link_${contactLinkCount}_service_<?php echo $serviceIdx; ?>"><?php echo htmlspecialchars($service['label']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                        <button type="button" class="delete-btn" onclick="removeContactLink(this)">🗑️ Supprimer</button>
                    </div>
                `;
                container.appendChild(linkCard);
                contactLinkCount++;
            }
        }
        
        function removeContactLink(btn) {
            if (confirm('Supprimer ce lien ?')) {
                btn.closest('.contact-link-card').remove();
            }
        }
        
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
