<?php
/**
 * Test de connexion Supabase
 */

require_once __DIR__ . '/config.php';

echo "<h2>🧪 Test de connexion Supabase</h2>";
echo "<pre>";

echo "URL: " . SUPABASE_URL . "\n";
echo "Key (premiers 20 caractères): " . substr(SUPABASE_KEY, 0, 20) . "...\n\n";

// Test 1: Test basique avec cURL
echo "=== Test 1: Connexion basique ===\n";
$ch = curl_init(SUPABASE_URL . '/rest/v1/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Réponse: " . substr($response, 0, 200) . "\n\n";

// Test 2: Test avec la table admins
echo "=== Test 2: Accès à la table admins ===\n";
$ch = curl_init(SUPABASE_URL . '/rest/v1/admins?limit=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
if ($error) {
    echo "Erreur cURL: $error\n";
}
echo "Réponse: " . $response . "\n\n";

// Test 3: Création d'un admin (test)
echo "=== Test 3: Tentative de création admin ===\n";
$testData = [
    'username' => 'test_' . time(),
    'email' => 'test@test.com',
    'password_hash' => password_hash('test123', PASSWORD_BCRYPT),
    'role' => 'admin',
    'active' => true
];

$ch = curl_init(SUPABASE_URL . '/rest/v1/admins');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
    'Content-Type: application/json',
    'Prefer: return=representation'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
if ($error) {
    echo "Erreur cURL: $error\n";
}
echo "Réponse: " . $response . "\n";

echo "</pre>";

echo "<h3>💡 Instructions:</h3>";
echo "<p>1. Vérifiez que votre clé Supabase est correcte dans <code>config.php</code></p>";
echo "<p>2. Pour créer un admin, vous devez utiliser la <strong>service_role key</strong> (pas l'anon key)</p>";
echo "<p>3. Récupérez votre service_role key dans Supabase Dashboard > Settings > API</p>";
echo "<p>4. La service_role key commence généralement par <code>eyJ...</code> (JWT token)</p>";

