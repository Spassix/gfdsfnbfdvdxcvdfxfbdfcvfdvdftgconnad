<?php
/**
 * Page de maintenance
 * Affiche cette page si la maintenance est activée dans l'admin
 */

require_once __DIR__ . '/config.php';

// Récupérer les paramètres de maintenance
$maintenance = null;
try {
    global $supabase;
    $result = $supabase->request('GET', 'maintenance?order=created_at.desc&limit=1', null, false);
    if (!empty($result)) {
        $maintenance = $result[0];
    }
} catch (Exception $e) {
    error_log("Erreur récupération maintenance: " . $e->getMessage());
}

$enabled = $maintenance['enabled'] ?? false;
$message = $maintenance['message'] ?? 'Le site est actuellement en maintenance. Nous serons de retour bientôt !';
$imageUrl = $maintenance['image_url'] ?? '';

// Si la maintenance n'est pas activée, ne pas afficher cette page
if (!$enabled) {
    // Rediriger vers l'accueil
    header('Location: index.php');
    exit;
}

// Récupérer l'image de fond
$backgroundImage = getBackgroundImage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - Boutique</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .maintenance-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        .maintenance-content {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 1.5rem;
            padding: 4rem 3rem;
            max-width: 600px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.8);
        }
        .maintenance-icon {
            font-size: 5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .maintenance-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .maintenance-title-icon {
            font-size: 1.25rem;
        }
        .maintenance-message {
            font-size: 1rem;
            color: #fff;
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        .maintenance-team {
            font-size: 0.875rem;
            color: #9ca3af;
            margin-top: 2rem;
            font-weight: 600;
        }
        .loading-dots {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        .loading-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #fff;
            animation: pulse 1.5s ease-in-out infinite;
        }
        .loading-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        .loading-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 0.3;
                transform: scale(0.8);
            }
            50% {
                opacity: 1;
                transform: scale(1);
            }
        }
        .maintenance-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
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
</head>
<body>
    <div class="cosmic-bg maintenance-page">
        <div class="maintenance-content">
            <div class="maintenance-icon">🔧</div>
            <h1 class="maintenance-title">
                <span class="maintenance-title-icon">🔧</span>
                Site en maintenance
            </h1>
            <?php if ($imageUrl): ?>
                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Maintenance" class="maintenance-image" onerror="this.style.display='none'">
            <?php endif; ?>
            <p class="maintenance-message"><?php echo nl2br(htmlspecialchars($message)); ?></p>
            <div class="loading-dots">
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
            </div>
        </div>
    </div>
</body>
</html>

