<?php
/**
 * test_supabase.php - Test de connexion Supabase
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/supabase_client.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Supabase</title>
    <style>
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
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test de connexion Supabase</h1>
        
        <?php
        echo '<div class="test-result info">';
        echo '<strong>📡 Configuration:</strong><br>';
        echo 'URL: ' . SUPABASE_URL . '<br>';
        echo 'Key: ' . substr(SUPABASE_KEY, 0, 20) . '...<br>';
        echo '</div>';
        
        // Test 1: Récupérer les catégories
        echo '<div class="test-result">';
        echo '<strong>1️⃣ Test récupération catégories...</strong><br>';
        try {
            $categories = $supabase->getCategories();
            echo '<div class="success">✅ OK - ' . count($categories) . ' catégorie(s) trouvée(s)</div>';
            if (!empty($categories)) {
                echo '<pre>' . print_r($categories, true) . '</pre>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo '</div>';
        
        // Test 2: Créer une commande test
        echo '<div class="test-result">';
        echo '<strong>2️⃣ Test création commande...</strong><br>';
        try {
            $testOrder = [
                'customer' => json_encode([
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'phone' => '0612345678',
                    'address' => '123 Rue Test',
                    'address_extra' => ''
                ]),
                'delivery_method' => 'Livraison',
                'payment_method' => 'Espèces',
                'products' => json_encode([
                    [
                        'name' => 'Produit Test',
                        'weight' => '3.5g',
                        'qty' => 1,
                        'price' => 10,
                        'total' => 10
                    ]
                ]),
                'subtotal' => '10.00',
                'total' => '10.00',
                'status' => 'pending',
                'whatsapp_sent' => false
            ];
            
            $order = $supabase->createOrder($testOrder);
            if (!empty($order) && is_array($order)) {
                echo '<div class="success">✅ Commande créée avec succès!</div>';
                echo '<pre>ID: ' . ($order[0]['id'] ?? 'N/A') . "\n";
                echo 'N°: ' . ($order[0]['order_number'] ?? 'N/A') . '</pre>';
            } else {
                echo '<div class="error">❌ Réponse invalide</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo '</div>';
        
        // Test 3: Récupérer les commandes
        echo '<div class="test-result">';
        echo '<strong>3️⃣ Test récupération commandes...</strong><br>';
        try {
            $orders = $supabase->getOrders(['limit' => 5]);
            echo '<div class="success">✅ OK - ' . count($orders) . ' commande(s) trouvée(s)</div>';
            if (!empty($orders)) {
                echo '<pre>' . print_r(array_slice($orders, 0, 2), true) . '</pre>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo '</div>';
        
        echo '<div class="test-result success">';
        echo '<strong>🎉 Tests terminés!</strong>';
        echo '</div>';
        ?>
        
        <div style="margin-top: 30px;">
            <a href="index.php" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">
                ← Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>

