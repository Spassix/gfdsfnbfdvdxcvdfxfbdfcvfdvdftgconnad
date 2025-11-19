<?php
/**
 * checkout.php - Traitement des commandes
 */

require_once __DIR__ . '/supabase_client.php';

function clean($value) {
    if (is_null($value)) return '';
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    return $value;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $errors[] = 'Cette page ne peut être accédée que via un formulaire POST.';
    displayError($errors);
    exit;
}

// Récupération des données
$first_name = isset($_POST['first_name']) ? clean($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? clean($_POST['last_name']) : '';
$phone = isset($_POST['phone']) ? clean($_POST['phone']) : '';
$address = isset($_POST['address']) ? clean($_POST['address']) : '';
$address_extra = isset($_POST['address_extra']) ? clean($_POST['address_extra']) : '';
$payment_method = isset($_POST['payment_method']) ? clean($_POST['payment_method']) : '';
$delivery_method = isset($_POST['delivery_method']) ? clean($_POST['delivery_method']) : '';
$products_json = isset($_POST['products']) ? $_POST['products'] : '';

// Validations
if (empty($first_name)) $errors[] = 'Le prénom est obligatoire.';
if (empty($last_name)) $errors[] = 'Le nom est obligatoire.';
if (empty($phone)) $errors[] = 'Le téléphone est obligatoire.';
if (empty($address)) $errors[] = 'L\'adresse est obligatoire.';
if (empty($payment_method)) $errors[] = 'La méthode de paiement est obligatoire.';
if (empty($delivery_method)) $errors[] = 'La méthode de livraison est obligatoire.';
if (empty($products_json)) $errors[] = 'Aucun produit n\'a été sélectionné.';

$products = [];
if (!empty($products_json)) {
    $products = json_decode($products_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'Format JSON des produits invalide.';
        $products = [];
    }
}

if (!empty($errors)) {
    displayError($errors);
    exit;
}

// Nettoyer le téléphone
$phone = preg_replace('/[^0-9+]/', '', $phone);

// Traiter les produits
$processed_products = [];
foreach ($products as $product) {
    $name = isset($product['name']) ? clean($product['name']) : '';
    $weight = isset($product['weight']) ? clean($product['weight']) : '';
    $qty = isset($product['qty']) ? floatval($product['qty']) : 0;
    $price = isset($product['price']) ? floatval($product['price']) : 0;
    
    if (empty($name) || $qty <= 0 || $price < 0) continue;
    
    $processed_products[] = [
        'name' => $name,
        'weight' => $weight,
        'qty' => $qty,
        'price' => $price,
        'total' => $qty * $price
    ];
}

if (empty($processed_products)) {
    $errors[] = 'Aucun produit valide trouvé.';
    displayError($errors);
    exit;
}

// Calculs
$subtotal = array_sum(array_column($processed_products, 'total'));
$total = $subtotal;

// Construction de la commande
$order = [
    "customer" => [
        "first_name" => $first_name,
        "last_name" => $last_name,
        "phone" => $phone,
        "address" => $address,
        "address_extra" => $address_extra
    ],
    "delivery" => $delivery_method,
    "payment" => $payment_method,
    "products" => $processed_products,
    "subtotal" => $subtotal,
    "total" => $total
];

// Sauvegarde dans Supabase
$orderSaved = false;
$orderNumber = null;
try {
    $orderData = [
        'customer' => json_encode($order['customer']),
        'delivery_method' => $delivery_method,
        'payment_method' => $payment_method,
        'products' => json_encode($processed_products),
        'subtotal' => number_format($subtotal, 2, '.', ''),
        'total' => number_format($total, 2, '.', ''),
        'status' => 'pending',
        'whatsapp_sent' => false
    ];
    
    $savedOrder = $supabase->createOrder($orderData);
    
    if (!empty($savedOrder) && is_array($savedOrder)) {
        $orderSaved = true;
        $order['id'] = $savedOrder[0]['id'] ?? null;
        $order['order_number'] = $savedOrder[0]['order_number'] ?? null;
        $orderNumber = $order['order_number'];
    }
} catch (Exception $e) {
    error_log("Erreur Supabase: " . $e->getMessage());
}

// Message WhatsApp
$whatsappMessage = "Nouvelle commande CBD :\n\n";
$whatsappMessage .= "Client : {$first_name} {$last_name}\n";
$whatsappMessage .= "Téléphone : {$phone}\n";
$whatsappMessage .= "Adresse : {$address}\n";
if (!empty($address_extra)) {
    $whatsappMessage .= "Complément : {$address_extra}\n";
}
if ($orderNumber) {
    $whatsappMessage .= "N° Commande : {$orderNumber}\n";
}
$whatsappMessage .= "\nProduits :\n";
foreach ($processed_products as $product) {
    $whatsappMessage .= "- {$product['name']}";
    if (!empty($product['weight'])) {
        $whatsappMessage .= " {$product['weight']}";
    }
    $whatsappMessage .= " x{$product['qty']} : {$product['price']}€\n";
}
$whatsappMessage .= "\nMéthode de livraison : {$delivery_method}\n";
$whatsappMessage .= "Paiement : {$payment_method}\n\n";
$whatsappMessage .= "TOTAL : " . number_format($total, 2, ',', ' ') . "€";

$whatsapp_number = "33612345678"; // ⚠️ MODIFIEZ avec votre numéro
$whatsappLink = "https://wa.me/{$whatsapp_number}?text=" . urlencode($whatsappMessage);

// Affichage HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif de commande</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .success-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        .content { padding: 30px; }
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .section:last-child { border-bottom: none; }
        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #555;
        }
        .info-label { font-weight: 600; color: #333; }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .products-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .products-table td { padding: 12px; border-bottom: 1px solid #e0e0e0; }
        .total-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 16px;
        }
        .total-final {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #667eea;
        }
        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            flex: 1;
            min-width: 200px;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-whatsapp { background: #25D366; color: white; }
        .btn-copy { background: #667eea; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="font-size: 48px; margin-bottom: 10px;">✅</div>
            <h1>Commande reçue !</h1>
            <p>Votre commande a été traitée avec succès</p>
            <?php if ($orderSaved && $orderNumber): ?>
            <div class="success-badge">N° Commande: <?php echo htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <div class="section">
                <h2>👤 Informations client</h2>
                <div class="info-row">
                    <span class="info-label">Nom complet :</span>
                    <span><?php echo htmlspecialchars($first_name . ' ' . $last_name, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Téléphone :</span>
                    <span><?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Adresse :</span>
                    <span><?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <?php if (!empty($address_extra)): ?>
                <div class="info-row">
                    <span class="info-label">Complément :</span>
                    <span><?php echo htmlspecialchars($address_extra, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="section">
                <h2>📦 Produits</h2>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processed_products as $product): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if (!empty($product['weight'])): ?>
                                    <br><small><?php echo htmlspecialchars($product['weight'], ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $product['qty']; ?></td>
                            <td><?php echo number_format($product['price'], 2, ',', ' '); ?>€</td>
                            <td><strong><?php echo number_format($product['total'], 2, ',', ' '); ?>€</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>🚚 Livraison & Paiement</h2>
                <div class="info-row">
                    <span class="info-label">Méthode de livraison :</span>
                    <span><?php echo htmlspecialchars($delivery_method, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Méthode de paiement :</span>
                    <span><?php echo htmlspecialchars($payment_method, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>

            <div class="total-section">
                <div class="total-row">
                    <span>Sous-total :</span>
                    <span><?php echo number_format($subtotal, 2, ',', ' '); ?>€</span>
                </div>
                <div class="total-row total-final">
                    <span>TOTAL :</span>
                    <span><?php echo number_format($total, 2, ',', ' '); ?>€</span>
                </div>
            </div>

            <div class="buttons">
                <a href="<?php echo htmlspecialchars($whatsappLink, ENT_QUOTES, 'UTF-8'); ?>" 
                   target="_blank" 
                   class="btn btn-whatsapp">
                    📱 Envoyer via WhatsApp
                </a>
                <button onclick="copyOrder()" class="btn btn-copy">
                    📋 Copier la commande
                </button>
            </div>
        </div>
    </div>

    <script>
        function copyOrder() {
            const orderText = `<?php echo addslashes($whatsappMessage); ?>`;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(orderText).then(() => {
                    alert('✅ Commande copiée !');
                });
            } else {
                alert('❌ Fonction non disponible');
            }
        }
    </script>
</body>
</html>

<?php
function displayError($errors) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Erreur</title>
        <style>
            body {
                font-family: sans-serif;
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 12px;
                padding: 30px;
                max-width: 500px;
            }
            h1 { color: #f5576c; margin-bottom: 20px; }
            ul { list-style: none; padding: 0; }
            li {
                padding: 10px;
                margin-bottom: 10px;
                background: #ffe0e0;
                border-left: 4px solid #f5576c;
                border-radius: 4px;
            }
            a {
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 6px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>❌ Erreur de validation</h1>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="javascript:history.back()">← Retour</a>
        </div>
    </body>
    </html>
    <?php
}
?>

