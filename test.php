<?php
echo "✅ PHP fonctionne !<br>";
echo "Version PHP: " . phpversion() . "<br>";
echo "Répertoire: " . __DIR__ . "<br>";

// Test connexion Supabase
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/supabase_client.php';

try {
    echo "✅ Supabase client chargé<br>";
    $products = $supabase->getProducts(['limit' => 1]);
    echo "✅ Connexion Supabase OK - " . count($products) . " produit(s) trouvé(s)<br>";
} catch (Exception $e) {
    echo "❌ Erreur Supabase: " . $e->getMessage() . "<br>";
}

echo "<br><a href='shop/index.php'>→ Aller à la boutique</a><br>";
echo "<a href='admin/dashboard.php'>→ Aller au panel admin</a>";
?>

