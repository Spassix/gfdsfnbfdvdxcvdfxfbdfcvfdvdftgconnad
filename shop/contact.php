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

// Récupérer les réseaux sociaux
$socials = [];
try {
    global $supabase;
    $result = $supabase->request('GET', 'socials', null, false);
    $socials = $result ?? [];
} catch (Exception $e) {
    error_log("Erreur récupération réseaux sociaux: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Boutique</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .contact-page {
            padding-top: 5rem;
            padding-bottom: 4rem;
        }
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            display: flex;
            justify-content: center;
        }
        .page-title-container {
            display: inline-block;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(24px);
            border-radius: 9999px;
            padding: 2.5rem 4rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
        }
        .page-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #fff;
        }
        .page-subtitle {
            font-size: 1.125rem;
            color: #fff;
        }
        .socials-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            max-width: 42rem;
            margin: 0 auto;
        }
        .socials-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .socials-title span {
            font-size: 2.25rem;
            margin-right: 0.75rem;
        }
        .socials-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .social-link {
            display: flex;
            align-items: flex-start;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 0.75rem;
            border: 1px solid rgba(55, 65, 81, 0.2);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
        }
        .social-link:hover {
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.02) translateX(0.3125rem);
        }
        .social-icon {
            font-size: 1.875rem;
            margin-right: 1rem;
            transition: transform 0.3s;
        }
        .social-link:hover .social-icon {
            transform: scale(1.1);
        }
        .social-info {
            flex: 1;
        }
        .social-name {
            color: #fff;
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }
        .social-description {
            color: #fff;
            font-size: 0.875rem;
        }
        .social-arrow {
            color: #fff;
            font-size: 1.25rem;
            transition: transform 0.3s;
        }
        .social-link:hover .social-arrow {
            transform: translateX(0.25rem);
        }
        @media (min-width: 768px) {
            .page-title {
                font-size: 4.5rem;
            }
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
    
    <div class="cosmic-bg contact-page">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="page-header">
                <div class="page-title-container">
                    <h1 class="page-title">Contactez-Nous</h1>
                    <p class="page-subtitle">Une question ? N'hésitez pas à nous contacter</p>
                </div>
            </div>

            <!-- Réseaux Sociaux -->
            <div class="socials-card neon-border">
                <h3 class="socials-title">
                    <span>🌐</span>
                    Réseaux Sociaux
                </h3>
                <div class="socials-list">
                    <?php foreach ($socials as $social): ?>
                        <a href="<?php echo htmlspecialchars($social['url'] ?? '#'); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="social-link">
                            <span class="social-icon"><?php echo htmlspecialchars($social['icon'] ?? '🔗'); ?></span>
                            <div class="social-info">
                                <h4 class="social-name"><?php echo htmlspecialchars($social['name'] ?? ''); ?></h4>
                                <p class="social-description"><?php echo htmlspecialchars($social['description'] ?? ''); ?></p>
                            </div>
                            <span class="social-arrow">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

