<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$status = $_GET['status'] ?? '';
$orders = [];

try {
    $filters = [];
    if ($status) {
        $filters['status'] = $status;
    }
    $orders = $supabase->getOrders($filters);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Gérer le changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    try {
        $supabase->updateOrderStatus($_POST['order_id'], $_POST['status']);
        header('Location: orders.php?updated=1');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Panel Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0d0f17;
            color: #fff;
            min-height: 100vh;
        }
        .header {
            background: rgba(26, 29, 41, 0.95);
            border-bottom: 1px solid #333;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .filters {
            background: rgba(26, 29, 41, 0.8);
            border: 1px solid #333;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .filters select, .filters button {
            padding: 10px;
            background: #1a1d29;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            margin-right: 10px;
        }
        .orders-table {
            width: 100%;
            background: rgba(26, 29, 41, 0.8);
            border: 1px solid #333;
            border-radius: 16px;
            overflow: hidden;
        }
        .orders-table th {
            background: #1a1d29;
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #333;
        }
        .orders-table tr:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-processing { background: #17a2b8; color: #fff; }
        .status-completed { background: #28a745; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        .btn {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #764ba2;
        }
        .success {
            background: #28a745;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error {
            background: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛒 Commandes</h1>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="orders.php">Commandes</a>
            <a href="products.php">Produits</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>
    
    <div class="container">
        <h1 style="font-size: 36px; margin-bottom: 30px;">📦 Gestion des commandes</h1>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="success">✅ Statut mis à jour avec succès !</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">❌ Erreur: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="filters">
            <form method="GET" style="display: inline;">
                <select name="status">
                    <option value="">Tous les statuts</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>En traitement</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Terminée</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                </select>
                <button type="submit">Filtrer</button>
                <a href="orders.php" style="color: #667eea; text-decoration: none; margin-left: 10px;">Réinitialiser</a>
            </form>
        </div>
        
        <table class="orders-table">
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Client</th>
                    <th>Produits</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            Aucune commande trouvée
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): 
                        $customer = is_string($order['customer']) ? json_decode($order['customer'], true) : $order['customer'];
                        $products = is_string($order['products']) ? json_decode($order['products'], true) : $order['products'];
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($customer['first_name'] ?? ''); ?> 
                                <?php echo htmlspecialchars($customer['last_name'] ?? ''); ?><br>
                                <small style="color: #ccc;"><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></small>
                            </td>
                            <td>
                                <?php 
                                $productCount = count($products);
                                echo $productCount . ' produit(s)';
                                ?>
                            </td>
                            <td><strong><?php echo number_format($order['total'], 2, ',', ' '); ?>€</strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" style="padding: 5px; background: #1a1d29; border: 1px solid #333; border-radius: 6px; color: #fff;">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>En traitement</option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Terminée</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

