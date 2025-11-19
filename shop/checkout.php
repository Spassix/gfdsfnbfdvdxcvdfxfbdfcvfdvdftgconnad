<?php
// Démarrer la session avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// Calculer le total
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Préparer les données pour checkout.php principal
    $products = [];
    foreach ($cart as $item) {
        $products[] = [
            'name' => $item['product_name'],
            'weight' => $item['variant_name'],
            'qty' => $item['quantity'],
            'price' => $item['price']
        ];
    }
    
    // Rediriger vers le checkout principal
    $checkoutUrl = '../checkout.php';
    
    // Créer un formulaire temporaire pour envoyer les données
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Redirection...</title>
    </head>
    <body>
        <form id="checkoutForm" method="POST" action="<?php echo $checkoutUrl; ?>">
            <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            <input type="hidden" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
            <input type="hidden" name="address_extra" value="<?php echo htmlspecialchars($_POST['address_extra'] ?? ''); ?>">
            <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($_POST['payment_method'] ?? ''); ?>">
            <input type="hidden" name="delivery_method" value="<?php echo htmlspecialchars($_POST['delivery_method'] ?? ''); ?>">
            <input type="hidden" name="products" value="<?php echo htmlspecialchars(json_encode($products)); ?>">
        </form>
        <script>
            document.getElementById('checkoutForm').submit();
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Boutique CBD</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0d0f17;
            color: #fff;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }
        input, select {
            width: 100%;
            padding: 12px;
            background: rgba(26, 29, 41, 0.8);
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
        }
        .cart-summary {
            background: rgba(26, 29, 41, 0.8);
            border: 1px solid #333;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #333;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <div class="container">
        <h1 style="font-size: 36px; margin-bottom: 30px;">💳 Finaliser la commande</h1>
        
        <div class="cart-summary">
            <h2 style="margin-bottom: 20px;">Récapitulatif</h2>
            <?php foreach ($cart as $item): ?>
                <div class="cart-item">
                    <div>
                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                        <?php if (!empty($item['variant_name'])): ?>
                            <br><small style="color: #ccc;"><?php echo htmlspecialchars($item['variant_name']); ?></small>
                        <?php endif; ?>
                        <br><small style="color: #ccc;">x<?php echo $item['quantity']; ?></small>
                    </div>
                    <div style="color: #667eea; font-weight: bold;">
                        <?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?>€
                    </div>
                </div>
            <?php endforeach; ?>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #333; text-align: right;">
                <h2 style="color: #667eea;">Total: <?php echo number_format($total, 2, ',', ' '); ?>€</h2>
            </div>
        </div>
        
        <form method="POST">
            <h2 style="margin-bottom: 20px;">Informations de livraison</h2>
            
            <div class="form-group">
                <label>Prénom *</label>
                <input type="text" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="last_name" required>
            </div>
            
            <div class="form-group">
                <label>Téléphone *</label>
                <input type="tel" name="phone" required>
            </div>
            
            <div class="form-group">
                <label>Adresse *</label>
                <input type="text" name="address" required>
            </div>
            
            <div class="form-group">
                <label>Complément d'adresse</label>
                <input type="text" name="address_extra">
            </div>
            
            <div class="form-group">
                <label>Méthode de livraison *</label>
                <select name="delivery_method" required>
                    <option value="Livraison">Livraison</option>
                    <option value="Meetup">Meetup</option>
                    <option value="Envoi">Envoi postal</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Méthode de paiement *</label>
                <select name="payment_method" required>
                    <option value="Espèces">Espèces</option>
                    <option value="Carte bancaire">Carte bancaire</option>
                    <option value="Virement">Virement</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Valider la commande</button>
        </form>
    </div>
</body>
</html>

