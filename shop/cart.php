<?php
// Démarrer la session avant tout output
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
require_once __DIR__ . '/../telegram_guard.php';
require_once __DIR__ . '/config.php';

// Vérifier si la maintenance est activée
checkMaintenance();

// Récupérer l'image de fond
$backgroundImage = getBackgroundImage();

// Actions du panier
$action = $_GET['action'] ?? '';
$step = intval($_SESSION['cart_step'] ?? 1);

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $item = [
        'product_id' => $_POST['product_id'],
        'product_name' => $_POST['product_name'],
        'variant_name' => $_POST['variant_name'] ?? '',
        'price' => floatval($_POST['price']),
        'quantity' => intval($_POST['quantity'])
    ];
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $_SESSION['cart'][] = $item;
    $_SESSION['success_message'] = '✅ Produit ajouté au panier avec succès !';
    header('Location: cart.php');
    exit;
}

if ($action === 'remove') {
    $index = intval($_GET['index']);
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $_SESSION['success_message'] = '✅ Produit retiré du panier';
    }
    header('Location: cart.php');
    exit;
}

if ($action === 'update_quantity') {
    $index = intval($_GET['index']);
    $quantity = intval($_GET['quantity']);
    if (isset($_SESSION['cart'][$index]) && $quantity > 0) {
        $_SESSION['cart'][$index]['quantity'] = $quantity;
        $_SESSION['success_message'] = '✅ Quantité mise à jour';
    }
    header('Location: cart.php');
    exit;
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    $_SESSION['cart_step'] = 1;
    header('Location: cart.php');
    exit;
}

if ($action === 'set_step') {
    $_SESSION['cart_step'] = intval($_GET['step']);
    header('Location: cart.php');
    exit;
}

// Gérer les étapes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    $_SESSION['cart_step'] = intval($_POST['step']);
    if (isset($_POST['service'])) $_SESSION['selected_service'] = $_POST['service'];
    if (isset($_POST['slot'])) $_SESSION['selected_slot'] = $_POST['slot'];
    if (isset($_POST['payment'])) $_SESSION['selected_payment'] = $_POST['payment'];
    if (isset($_POST['firstName'])) $_SESSION['client_firstName'] = $_POST['firstName'];
    if (isset($_POST['lastName'])) $_SESSION['client_lastName'] = $_POST['lastName'];
    if (isset($_POST['phone'])) $_SESSION['client_phone'] = $_POST['phone'];
    if (isset($_POST['address'])) $_SESSION['client_address'] = $_POST['address'];
    if (isset($_POST['complement'])) $_SESSION['client_complement'] = $_POST['complement'];
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$selectedService = $_SESSION['selected_service'] ?? '';
$selectedSlot = $_SESSION['selected_slot'] ?? '';
$selectedPayment = $_SESSION['selected_payment'] ?? '';
$clientInfo = [
    'firstName' => $_SESSION['client_firstName'] ?? '',
    'lastName' => $_SESSION['client_lastName'] ?? '',
    'phone' => $_SESSION['client_phone'] ?? '',
    'address' => $_SESSION['client_address'] ?? '',
    'complement' => $_SESSION['client_complement'] ?? ''
];

// Calculer le total
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Gérer le code promo
$promoCode = $_SESSION['promo_code'] ?? '';
$promoDiscount = 0;
$promoApplied = false;
$promoError = '';

if (isset($_POST['apply_promo'])) {
    $code = strtoupper(trim($_POST['promo_code'] ?? ''));
    if ($code) {
        try {
            // Utiliser une URL relative pour l'API
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
            $apiUrl = $protocol . '://' . $host . '/api/promos.php?code=' . urlencode($code);
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if ($result && $result['valid']) {
                    // Vérifier le montant minimum
                    if ($result['min_amount'] > 0 && $subtotal < $result['min_amount']) {
                        $promoError = 'Le montant minimum de ' . number_format($result['min_amount'], 2, ',', ' ') . '€ n\'est pas atteint';
                    } else {
                        $promoCode = $code;
                        $_SESSION['promo_code'] = $code;
                        $_SESSION['promo_data'] = $result;
                        $promoApplied = true;
                        
                        // Calculer la réduction
                        if ($result['type'] === 'percentage') {
                            $promoDiscount = ($subtotal * $result['value']) / 100;
                        } else {
                            $promoDiscount = $result['value'];
                        }
                        // Ne pas dépasser le sous-total
                        if ($promoDiscount > $subtotal) {
                            $promoDiscount = $subtotal;
                        }
                    }
                } else {
                    $promoError = $result['message'] ?? 'Code promo invalide';
                }
            } else {
                $promoError = 'Erreur lors de la vérification du code';
            }
        } catch (Exception $e) {
            $promoError = 'Erreur: ' . $e->getMessage();
        }
    }
} elseif (isset($_POST['remove_promo'])) {
    unset($_SESSION['promo_code']);
    unset($_SESSION['promo_data']);
    $promoCode = '';
    $promoDiscount = 0;
} elseif (!empty($promoCode) && isset($_SESSION['promo_data'])) {
    // Recalculer la réduction si un code est déjà appliqué
    $promoData = $_SESSION['promo_data'];
    if ($promoData['min_amount'] <= $subtotal) {
        if ($promoData['type'] === 'percentage') {
            $promoDiscount = ($subtotal * $promoData['value']) / 100;
        } else {
            $promoDiscount = $promoData['value'];
        }
        if ($promoDiscount > $subtotal) {
            $promoDiscount = $subtotal;
        }
        $promoApplied = true;
    } else {
        unset($_SESSION['promo_code']);
        unset($_SESSION['promo_data']);
        $promoCode = '';
        $promoDiscount = 0;
        $promoError = 'Le montant minimum n\'est plus atteint';
    }
}

// Récupérer les paramètres du panier
$cartSettings = getSettings('cart');
$defaultSettings = [
    'services' => [
        ['name' => 'Livraison', 'label' => '🚚 Livraison', 'description' => 'Livraison à domicile', 'fee' => 5, 'enabled' => true, 'slots' => ['9h-12h', '12h-15h', '15h-18h', '18h-21h']],
        ['name' => 'Meetup', 'label' => '🤝 Meetup', 'description' => 'Rendez-vous en personne', 'fee' => 0, 'enabled' => true, 'slots' => ['10h', '14h', '16h', '20h']],
        ['name' => 'Envoi', 'label' => '📦 Envoi postal', 'description' => 'Envoi par la poste', 'fee' => 3, 'enabled' => true, 'slots' => ['Envoi sous 24h', 'Envoi sous 48h', 'Envoi express']]
    ],
    'payments' => [
        ['label' => '💵 Espèces', 'enabled' => true],
        ['label' => '💳 Carte bancaire', 'enabled' => true],
        ['label' => '🏦 Virement', 'enabled' => true]
    ],
    'alertEnabled' => false,
    'alertMessage' => '',
    'promosEnabled' => false,
    'contactLinks' => []
];

// Fusionner les settings récupérés avec les valeurs par défaut
if ($cartSettings && is_array($cartSettings)) {
    $settings = array_merge($defaultSettings, $cartSettings);
    // S'assurer que services et payments sont des tableaux
    if (!isset($settings['services']) || !is_array($settings['services'])) {
        $settings['services'] = $defaultSettings['services'];
    }
    if (!isset($settings['payments']) || !is_array($settings['payments'])) {
        $settings['payments'] = $defaultSettings['payments'];
    }
} else {
    $settings = $defaultSettings;
}

$serviceFee = 0;
if ($selectedService) {
    foreach ($settings['services'] ?? [] as $service) {
        if ($service['name'] === $selectedService) {
            $serviceFee = $service['fee'] ?? 0;
            break;
        }
    }
}

$total = $subtotal - $promoDiscount + $serviceFee;
if ($total < 0) {
    $total = 0;
}

// Fonction helper pour les couleurs
function getColor($settings, $category, $property, $default) {
    return $settings['colors'][$category][$property] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - Boutique</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cart-page {
            padding-top: 5rem;
            padding-bottom: 4rem;
        }
        @media (max-width: 767px) {
            .cart-page {
                padding-top: 4.5rem;
            }
        }
        .progress-steps {
            margin-bottom: 2rem;
        }
        .steps-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        .step-item {
            display: flex;
            align-items: center;
        }
        .step-circle {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }
        .step-circle.completed {
            background: #10b981;
            color: #fff;
        }
        .step-circle.active {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        .step-circle.inactive {
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .step-label {
            margin-left: 0.5rem;
            color: #fff;
            font-size: 0.875rem;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        @media (max-width: 640px) {
            .step-label {
                display: none;
            }
            .cart-page {
                padding-top: 5rem;
                padding-bottom: 2rem;
            }
            .cart-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .summary-card {
                position: relative;
                top: 0;
            }
            .step-title {
                font-size: 1.5rem;
                padding: 0.75rem 1rem;
            }
            .cart-item-card {
                flex-direction: column;
            }
            .cart-item-image {
                width: 100%;
                height: 200px;
            }
            .quantity-controls {
                flex-wrap: wrap;
            }
        }
        .step-connector {
            width: 2rem;
            height: 0.125rem;
            background: rgba(55, 65, 81, 1);
            margin: 0 0.5rem;
        }
        .alert-message {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(113, 63, 18, 0.5);
            border: 1px solid rgba(234, 179, 8, 0.5);
            border-radius: 0.5rem;
        }
        .alert-message p {
            color: rgba(254, 243, 199, 1);
            text-align: center;
        }
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 1024px) {
            .cart-grid {
                grid-template-columns: 2fr 1fr;
            }
        }
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .cart-item-card {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(55, 65, 81, 1);
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            gap: 1rem;
        }
        .cart-item-image {
            width: 6rem;
            height: 6rem;
            border-radius: 0.5rem;
            overflow: hidden;
            background: rgba(30, 41, 59, 1);
            flex-shrink: 0;
        }
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            display: inline-block;
            margin-bottom: 0.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .cart-item-variant {
            font-size: 0.875rem;
            color: #fff;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            display: inline-block;
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .cart-item-price {
            font-weight: 700;
            color: #fff;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            display: inline-block;
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            font-size: 1.125rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .quantity-btn {
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            transition: all 0.3s;
        }
        .quantity-btn:hover {
            background: rgba(0, 0, 0, 1);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1);
        }
        .quantity-value {
            color: #fff;
            font-weight: 700;
            min-width: 3rem;
            text-align: center;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 1.125rem;
        }
        .remove-btn {
            margin-left: auto;
            color: rgba(248, 113, 113, 1);
            background: none;
            border: none;
            font-weight: 700;
            cursor: pointer;
        }
        .remove-btn:hover {
            color: rgba(252, 165, 165, 1);
        }
        .summary-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
            position: sticky;
            top: 6rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        .summary-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            display: inline-block;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        .promo-section {
            margin-bottom: 1rem;
        }
        .promo-input-group {
            display: flex;
            gap: 0.5rem;
        }
        .promo-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .promo-input::placeholder {
            color: #9ca3af;
        }
        .promo-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 0.875rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            color: #fff;
            transition: all 0.3s;
        }
        .promo-btn:hover {
            background: rgba(0, 0, 0, 1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .summary-totals {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            color: #fff;
            margin-bottom: 1rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .summary-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: rgba(34, 197, 94, 1);
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            border-top: 2px solid rgba(34, 197, 94, 0.5);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        .action-button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            backdrop-filter: blur(8px);
        }
        .action-button-primary {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .action-button-primary:hover {
            background: rgba(0, 0, 0, 1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .action-button-secondary {
            margin-top: 0.5rem;
            background: transparent;
            color: rgba(248, 113, 113, 1);
        }
        .action-button-secondary:hover {
            color: rgba(252, 165, 165, 1);
        }
        .step-content {
            max-width: 42rem;
            margin: 0 auto;
        }
        .step-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #fff;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        .service-card {
            padding: 1.5rem;
            border-radius: 0.75rem;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            margin-bottom: 1rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .service-card.selected {
            border-color: #fff;
        }
        .service-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .service-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            margin-bottom: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .service-fee {
            font-weight: 700;
            color: #fff;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            font-size: 1.125rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .service-description {
            font-size: 0.875rem;
            color: #fff;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
            margin-top: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            line-height: 1.6;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .slot-button {
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 700;
            border: 2px solid;
            cursor: pointer;
        }
        .slot-button.selected {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(8px);
        }
        .slot-button.unselected {
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(4px);
        }
        .slot-button.unselected:hover {
            background: rgba(0, 0, 0, 0.9);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .form-card {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(55, 65, 81, 1);
            border-radius: 0.75rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        .form-label {
            display: block;
            color: #fff;
            font-size: 0.875rem;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        .form-input,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 1);
            border-radius: 0.5rem;
            color: #000;
        }
        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .payment-button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 700;
            border: 2px solid;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .payment-button.selected {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(8px);
        }
        .payment-button.unselected {
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(4px);
        }
        .payment-button.unselected:hover {
            background: rgba(0, 0, 0, 0.9);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .navigation-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .nav-button {
            flex: 1;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s;
        }
        .nav-button-back {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            backdrop-filter: blur(8px);
        }
        .nav-button-back:hover {
            background: rgba(0, 0, 0, 1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .nav-button-continue {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            backdrop-filter: blur(8px);
        }
        .nav-button-continue:hover {
            background: rgba(0, 0, 0, 1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .nav-button-continue:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: rgba(0, 0, 0, 0.5);
        }
        .empty-cart {
            text-align: center;
            padding: 4rem 1rem;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        .empty-cart p {
            color: #fff;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 700;
        }
        .recap-section {
            margin-bottom: 1.5rem;
        }
        .recap-title {
            font-weight: 700;
            color: #fff;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        .recap-item {
            color: #fff;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            display: inline-block;
            margin-bottom: 0.25rem;
        }
        <?php if ($backgroundImage): ?>
        body {
            background-image: url('<?php echo htmlspecialchars($backgroundImage); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        .cosmic-bg {
            background-image: url('<?php echo htmlspecialchars($backgroundImage); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        .cosmic-bg::before {
            background: rgba(15, 23, 42, 0.7);
        }
        <?php endif; ?>
    </style>
    <script src="devtools_blocker.js"></script>
    <script src="assets/js/loading.js"></script>
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <div class="cosmic-bg cart-page">
        <div class="max-w-6xl mx-auto px-4">
            <?php if (empty($cart) && $step === 1): ?>
                <div class="empty-cart">
                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(55, 65, 81, 1); border-radius: 0.75rem; padding: 2rem;">
                        <p>🛒 Votre panier est vide</p>
                        <a href="products.php" style="display: inline-block; padding: 0.75rem 1.5rem; background: #fff; color: #000; border-radius: 0.5rem; text-decoration: none; font-weight: 700; margin-top: 1rem;">
                            Continuer mes achats
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Progress Steps -->
                <div class="progress-steps">
                    <div class="steps-container">
                        <?php 
                        $steps = ['Panier', 'Service', 'Informations', 'Récapitulatif'];
                        foreach ($steps as $index => $label): 
                            $stepNum = $index + 1;
                            $class = $step > $stepNum ? 'completed' : ($step === $stepNum ? 'active' : 'inactive');
                        ?>
                            <div class="step-item">
                                <div class="step-circle <?php echo $class; ?>">
                                    <?php echo $step > $stepNum ? '✓' : $stepNum; ?>
                                </div>
                                <span class="step-label"><?php echo $label; ?></span>
                                <?php if ($index < count($steps) - 1): ?>
                                    <div class="step-connector"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Messages de succès/erreur -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert-message" style="background: rgba(20, 83, 45, 0.5); border: 1px solid rgba(34, 197, 94, 0.5);">
                        <p style="color: rgba(74, 222, 128, 1);"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <!-- Alert Message -->
                <?php if (!empty($settings['alertEnabled']) && !empty($settings['alertMessage'])): ?>
                    <div class="alert-message">
                        <p>⚠️ <?php echo htmlspecialchars($settings['alertMessage']); ?></p>
                    </div>
        <?php endif; ?>
        
                <!-- ÉTAPE 1 : PANIER -->
                <?php if ($step === 1): ?>
                    <div class="cart-grid">
                        <div class="cart-items">
                            <h1 class="step-title">Mon Panier (<?php echo count($cart); ?>)</h1>
                            
                            <?php foreach ($cart as $index => $item): 
                                $itemTotal = $item['price'] * $item['quantity'];
                                $product = getProduct($item['product_id']);
                                $image = $product['photo'] ?? '';
                            ?>
                                <div class="cart-item-card neon-border">
                                    <div class="cart-item-image">
                                        <?php if ($image): ?>
                                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2rem;">🎁</div>
                                        <?php endif; ?>
            </div>
                                    <div class="cart-item-info">
                                        <div class="cart-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <?php if (!empty($item['variant_name'])): ?>
                                            <div class="cart-item-variant"><?php echo htmlspecialchars($item['variant_name']); ?></div>
                        <?php endif; ?>
                                        <div class="cart-item-price"><?php echo number_format($itemTotal, 2, ',', ' '); ?>€</div>
                                        
                                        <div class="quantity-controls">
                                            <a href="cart.php?action=update_quantity&index=<?php echo $index; ?>&quantity=<?php echo max(1, $item['quantity'] - 1); ?>" class="quantity-btn">-</a>
                                            <span class="quantity-value"><?php echo $item['quantity']; ?></span>
                                            <a href="cart.php?action=update_quantity&index=<?php echo $index; ?>&quantity=<?php echo $item['quantity'] + 1; ?>" class="quantity-btn">+</a>
                                            <a href="cart.php?action=remove&index=<?php echo $index; ?>" class="remove-btn">🗑️ Supprimer</a>
                    </div>
                    </div>
                </div>
            <?php endforeach; ?>
                        </div>

                        <div class="summary-card neon-border">
                            <h2 class="summary-title">Résumé</h2>
                            
                            <?php if (!empty($settings['promosEnabled'])): ?>
                            <div class="promo-section">
                                <?php if ($promoApplied): ?>
                                    <div style="margin-bottom: 0.5rem; padding: 0.5rem; background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(74, 222, 128, 0.5); border-radius: 0.5rem;">
                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                            <span style="color: rgba(74, 222, 128, 1); font-size: 0.875rem; font-weight: 600;">
                                                ✅ Code: <?php echo htmlspecialchars($promoCode); ?>
                                            </span>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="remove_promo" value="1">
                                                <button type="submit" style="background: none; border: none; color: rgba(248, 113, 113, 1); cursor: pointer; font-size: 0.875rem;">✕</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" class="promo-input-group">
                                        <input type="text" 
                                               name="promo_code" 
                                               value="<?php echo htmlspecialchars($promoCode); ?>"
                                               placeholder="Code promo"
                                               class="promo-input"
                                               style="text-transform: uppercase;"
                                               oninput="this.value = this.value.toUpperCase();">
                                        <button type="submit" name="apply_promo" class="promo-btn">Appliquer</button>
                                    </form>
                                    <?php if ($promoError): ?>
                                        <div style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(127, 29, 29, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 0.5rem;">
                                            <span style="color: rgba(248, 113, 113, 1); font-size: 0.75rem;"><?php echo htmlspecialchars($promoError); ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="summary-totals">
                                <div class="summary-row">
                                    <span>Sous-total</span>
                                    <span style="font-weight: 700;"><?php echo number_format($subtotal, 2, ',', ' '); ?>€</span>
                                </div>
                                <?php if ($promoDiscount > 0): ?>
                                    <div class="summary-row" style="color: rgba(74, 222, 128, 1);">
                                        <span>Réduction (<?php echo htmlspecialchars($promoCode); ?>)</span>
                                        <span style="font-weight: 700;">-<?php echo number_format($promoDiscount, 2, ',', ' '); ?>€</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="cart.php">
                                <input type="hidden" name="step" value="2">
                                <button type="submit" class="action-button action-button-primary">
                                    Continuer →
                                </button>
                            </form>

                            <a href="cart.php?action=clear" class="action-button action-button-secondary" style="text-align: center; text-decoration: none; display: block;">
                                Vider le panier
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ÉTAPE 2 : SERVICE -->
                <?php if ($step === 2): ?>
                    <div class="step-content">
                        <h1 class="step-title">Choisissez votre service</h1>

                        <form method="POST" action="cart.php">
                            <input type="hidden" name="step" value="3">
                            
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php 
                                $hasServices = false;
                                foreach ($settings['services'] ?? [] as $service): 
                                    // Vérifier si le service est activé (enabled peut être bool ou int)
                                    $isEnabled = isset($service['enabled']) && ($service['enabled'] === true || $service['enabled'] === 1 || $service['enabled'] === '1');
                                    if (!$isEnabled) continue;
                                    $hasServices = true;
                                    $isSelected = $selectedService === $service['name'];
                                ?>
                                    <div class="service-card <?php echo $isSelected ? 'selected' : ''; ?>" 
                                         onclick="document.getElementById('service_<?php echo $service['name']; ?>').checked = true; this.closest('form').querySelectorAll('.service-card').forEach(c => c.classList.remove('selected')); this.classList.add('selected');">
                                        <input type="radio" 
                                               name="service" 
                                               id="service_<?php echo $service['name']; ?>" 
                                               value="<?php echo htmlspecialchars($service['name']); ?>" 
                                               <?php echo $isSelected ? 'checked' : ''; ?>
                                               style="display: none;">
                                        <div class="service-header">
                                            <h3 class="service-name"><?php echo htmlspecialchars($service['label'] ?? $service['name']); ?></h3>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <?php if (!empty($service['fee']) && $service['fee'] > 0): ?>
                                                    <span class="service-fee">+<?php echo number_format($service['fee'], 2, ',', ' '); ?>€</span>
                                                <?php endif; ?>
                                                <?php if ($isSelected): ?>
                                                    <span style="font-size: 1.5rem;">✓</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($service['description'])): ?>
                                            <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (!$hasServices): ?>
                                    <div style="padding: 2rem; text-align: center; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.5rem;">
                                        <p style="color: #fff; background: rgba(239, 68, 68, 0.9); backdrop-filter: blur(8px); padding: 1rem 1.5rem; border-radius: 0.5rem; border: 2px solid rgba(255, 255, 255, 0.3); font-weight: 600; font-size: 1rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);">
                                            ⚠️ Aucun service de livraison disponible pour le moment. Veuillez contacter l'administrateur.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Créneaux horaires -->
                            <?php if ($selectedService): 
                                $currentService = null;
                                foreach ($settings['services'] ?? [] as $s) {
                                    if ($s['name'] === $selectedService) {
                                        $currentService = $s;
                                        break;
                                    }
                                }
                                if ($currentService && !empty($currentService['slots'])):
                            ?>
                                <div style="margin-top: 1.5rem;">
                                    <h3 class="recap-title">⏰ Choisissez un créneau</h3>
                                    <div class="slots-grid">
                                        <?php foreach ($currentService['slots'] as $slot): 
                                            $isSelected = $selectedSlot === $slot;
                                        ?>
                                            <button type="button" 
                                                    class="slot-button <?php echo $isSelected ? 'selected' : 'unselected'; ?>"
                                                    onclick="document.getElementById('slot_<?php echo md5($slot); ?>').checked = true; this.closest('.slots-grid').querySelectorAll('.slot-button').forEach(b => { b.classList.remove('selected'); b.classList.add('unselected'); }); this.classList.remove('unselected'); this.classList.add('selected');">
                                                <input type="radio" 
                                                       name="slot" 
                                                       id="slot_<?php echo md5($slot); ?>" 
                                                       value="<?php echo htmlspecialchars($slot); ?>" 
                                                       <?php echo $isSelected ? 'checked' : ''; ?>
                                                       style="display: none;">
                                                <?php echo htmlspecialchars($slot); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; endif; ?>

                            <div class="navigation-buttons">
                                <a href="cart.php?action=set_step&step=1" class="nav-button nav-button-back">← Retour</a>
                                <button type="submit" class="nav-button nav-button-continue" <?php echo !$selectedService ? 'disabled' : ''; ?>>
                                    Continuer →
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- ÉTAPE 3 : INFORMATIONS CLIENT -->
                <?php if ($step === 3): ?>
                    <div class="step-content">
                        <h1 class="step-title">Vos informations</h1>

                        <form method="POST" action="cart.php" class="form-card neon-border">
                            <input type="hidden" name="step" value="4">
                            <input type="hidden" name="service" value="<?php echo htmlspecialchars($selectedService); ?>">
                            <input type="hidden" name="slot" value="<?php echo htmlspecialchars($selectedSlot); ?>">

                            <div class="form-group-row">
                                <div class="form-group">
                                    <label class="form-label">Prénom *</label>
                                    <input type="text" name="firstName" value="<?php echo htmlspecialchars($clientInfo['firstName']); ?>" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nom *</label>
                                    <input type="text" name="lastName" value="<?php echo htmlspecialchars($clientInfo['lastName']); ?>" class="form-input" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Téléphone *</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($clientInfo['phone']); ?>" class="form-input" required>
                            </div>

                            <?php if ($selectedService !== 'Meetup'): ?>
                                <div class="form-group">
                                    <label class="form-label">Adresse *</label>
                                    <input type="text" name="address" value="<?php echo htmlspecialchars($clientInfo['address']); ?>" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Complément d'adresse</label>
                                    <input type="text" name="complement" value="<?php echo htmlspecialchars($clientInfo['complement']); ?>" class="form-input">
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label class="form-label">Moyen de paiement *</label>
                                <div class="payment-options">
                                    <?php foreach ($settings['payments'] ?? [] as $payment): 
                                        if (empty($payment['enabled'])) continue;
                                        $isSelected = $selectedPayment === $payment['label'];
                                    ?>
                                        <button type="button" 
                                                class="payment-button <?php echo $isSelected ? 'selected' : 'unselected'; ?>"
                                                onclick="document.getElementById('payment_<?php echo md5($payment['label']); ?>').checked = true; this.closest('.payment-options').querySelectorAll('.payment-button').forEach(b => { b.classList.remove('selected'); b.classList.add('unselected'); }); this.classList.remove('unselected'); this.classList.add('selected');">
                                            <input type="radio" 
                                                   name="payment" 
                                                   id="payment_<?php echo md5($payment['label']); ?>" 
                                                   value="<?php echo htmlspecialchars($payment['label']); ?>" 
                                                   <?php echo $isSelected ? 'checked' : ''; ?>
                                                   required
                                                   style="display: none;">
                                            <span><?php echo htmlspecialchars($payment['label']); ?></span>
                                            <?php if ($isSelected): ?>
                                                <span style="font-size: 1.5rem;">✓</span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="navigation-buttons">
                                <a href="cart.php?action=set_step&step=2" class="nav-button nav-button-back">← Retour</a>
                                <button type="submit" class="nav-button nav-button-continue" 
                                        <?php echo empty($clientInfo['firstName']) || empty($clientInfo['lastName']) || empty($clientInfo['phone']) || empty($selectedPayment) || ($selectedService !== 'Meetup' && empty($clientInfo['address'])) ? 'disabled' : ''; ?>>
                                    Continuer →
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- ÉTAPE 4 : RÉCAPITULATIF -->
                <?php if ($step === 4): ?>
                    <div class="step-content">
                        <h1 class="step-title">Récapitulatif de votre commande</h1>

                        <div class="form-card neon-border">
                            <div class="recap-section">
                                <h3 class="recap-title">👤 Informations client</h3>
                                <div class="recap-item">Nom: <?php echo htmlspecialchars($clientInfo['firstName'] . ' ' . $clientInfo['lastName']); ?></div>
                                <div class="recap-item">Téléphone: <?php echo htmlspecialchars($clientInfo['phone']); ?></div>
                                <?php if ($selectedService !== 'Meetup' && !empty($clientInfo['address'])): ?>
                                    <div class="recap-item">Adresse: <?php echo htmlspecialchars($clientInfo['address']); ?></div>
                                    <?php if (!empty($clientInfo['complement'])): ?>
                                        <div class="recap-item">Complément: <?php echo htmlspecialchars($clientInfo['complement']); ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="recap-section">
                                <h3 class="recap-title">📦 Produits</h3>
                                <?php foreach ($cart as $item): 
                                    $itemTotal = $item['price'] * $item['quantity'];
                                ?>
                                    <div class="recap-item">
                                        <?php echo htmlspecialchars($item['product_name']); ?> 
                                        <?php if (!empty($item['variant_name'])): ?>
                                            (<?php echo htmlspecialchars($item['variant_name']); ?>)
                                        <?php endif; ?>
                                        x<?php echo $item['quantity']; ?> = <?php echo number_format($itemTotal, 2, ',', ' '); ?>€
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="recap-section">
                                <h3 class="recap-title">🚚 Service</h3>
                                <div class="recap-item"><?php echo htmlspecialchars($selectedService); ?></div>
                                <?php if ($selectedSlot): ?>
                                    <div class="recap-item">Horaire: <?php echo htmlspecialchars($selectedSlot); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="recap-section">
                                <h3 class="recap-title">💳 Paiement</h3>
                                <div class="recap-item"><?php echo htmlspecialchars($selectedPayment); ?></div>
                            </div>

                            <div class="recap-section" style="border-top: 1px solid rgba(55, 65, 81, 1); padding-top: 1rem; margin-top: 1rem;">
                                <div class="summary-totals">
                                    <div class="summary-row">
                                        <span>Sous-total</span>
                                        <span style="font-weight: 700;"><?php echo number_format($subtotal, 2, ',', ' '); ?>€</span>
                                    </div>
                                    <?php if ($serviceFee > 0): ?>
                                        <div class="summary-row">
                                            <span>Frais (<?php echo htmlspecialchars($selectedService); ?>)</span>
                                            <span style="font-weight: 700;"><?php echo number_format($serviceFee, 2, ',', ' '); ?>€</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="summary-row summary-total">
                                        <span>TOTAL</span>
                                        <span><?php echo number_format($total, 2, ',', ' '); ?>€</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem;">
                                <?php 
                                // Générer le texte de commande (format amélioré)
                                $orderText = "🛒 NOUVELLE COMMANDE\n\n";
                                $orderText .= "👤 Informations client\n";
                                $orderText .= "Nom: " . $clientInfo['firstName'] . " " . $clientInfo['lastName'] . "\n";
                                $orderText .= "Téléphone: " . $clientInfo['phone'] . "\n";
                                if ($selectedService !== 'Meetup' && !empty($clientInfo['address'])) {
                                    $orderText .= "Adresse: " . $clientInfo['address'] . "\n";
                                    if (!empty($clientInfo['complement'])) {
                                        $orderText .= "Complément: " . $clientInfo['complement'] . "\n";
                                    }
                                }
                                $orderText .= "\n📦 Produits\n";
                                foreach ($cart as $item) {
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $orderText .= $item['product_name'];
                                    if (!empty($item['variant_name'])) {
                                        $orderText .= " (" . $item['variant_name'] . ")";
                                    }
                                    $orderText .= " x" . $item['quantity'] . " = " . number_format($itemTotal, 2, ',', ' ') . "€\n";
                                }
                                $orderText .= "\n🚚 Service\n";
                                $orderText .= $selectedService . "\n";
                                if ($selectedSlot) {
                                    $orderText .= "Horaire: " . $selectedSlot . "\n";
                                }
                                $orderText .= "\n💳 Paiement\n";
                                $orderText .= $selectedPayment . "\n";
                                $orderText .= "\n💰 Total\n";
                                $orderText .= "Sous-total: " . number_format($subtotal, 2, ',', ' ') . "€\n";
                                if ($serviceFee > 0) {
                                    $orderText .= "Frais (" . $selectedService . "): " . number_format($serviceFee, 2, ',', ' ') . "€\n";
                                }
                                $orderText .= "TOTAL: " . number_format($total, 2, ',', ' ') . "€\n";
                                $encodedText = urlencode($orderText);
                                
                                // Afficher les liens de contact
                                $hasContactLinks = false;
                                foreach ($settings['contactLinks'] ?? [] as $link):
                                    $linkServices = $link['services'] ?? [];
                                    if (!empty($linkServices) && !in_array($selectedService, $linkServices)) continue;
                                    $hasContactLinks = true;
                                    
                                    // Construire l'URL - si c'est WhatsApp, utiliser le format WhatsApp, sinon utiliser l'URL directement
                                    $url = $link['url'];
                                    if (stripos($link['name'], 'whatsapp') !== false || stripos($url, 'whatsapp') !== false || stripos($url, 'wa.me') !== false) {
                                        // Format WhatsApp avec texte
                                        if (strpos($url, '?') !== false) {
                                            $url .= '&text=' . $encodedText;
                                        } else {
                                            $url .= '?text=' . $encodedText;
                                        }
                                    } else {
                                        // Pour les autres liens, ajouter le texte en paramètre
                                        if (strpos($url, '?') !== false) {
                                            $url .= '&text=' . $encodedText;
                                        } else {
                                            $url .= '?text=' . $encodedText;
                                        }
                                    }
                                ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>" 
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="action-button action-button-primary"
                                       style="text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                        <span>📱</span>
                                        <span>Envoyer via <?php echo htmlspecialchars($link['name']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                                
                                <button onclick="copyOrderToClipboard()" class="action-button" style="background: rgba(55, 65, 81, 1); color: #fff; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <span>📋</span>
                                    <span>Copier la commande</span>
                                </button>
                                
                                <script>
                                    // Stocker le texte de commande pour la fonction de copie
                                    const orderTextToCopy = <?php echo json_encode($orderText, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                                </script>
                            </div>

                            <a href="cart.php?action=set_step&step=3" class="nav-button nav-button-back" style="margin-top: 1rem; text-align: center; text-decoration: none; display: block;">
                                ← Retour
                            </a>
                        </div>
            </div>
        <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyOrder() {
            const orderText = `<?php echo addslashes($orderText); ?>`;
            navigator.clipboard.writeText(orderText).then(() => {
                alert('✅ Commande copiée dans le presse-papiers !');
            });
        }
    </script>
</body>
</html>
