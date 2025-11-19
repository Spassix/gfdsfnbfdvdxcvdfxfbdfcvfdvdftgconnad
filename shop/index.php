<?php
// Démarrer la session avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../telegram_guard.php';
require_once __DIR__ . '/config.php';

// Vérifier si la maintenance est activée
checkMaintenance();

// Récupérer les settings pour le Hero
$generalSettings = getSettings('general');
$sectionsSettings = getSettings('sections');

$heroTitle = '';
$heroSubtitle = '';
$sections = [];

if ($generalSettings && is_array($generalSettings)) {
    $heroTitle = $generalSettings['heroTitle'] ?? '';
    $heroSubtitle = $generalSettings['heroSubtitle'] ?? '';
}

if ($sectionsSettings && is_array($sectionsSettings)) {
    // Les sections peuvent être directement dans $sectionsSettings ou dans $sectionsSettings['sections']
    $sections = $sectionsSettings['sections'] ?? (isset($sectionsSettings[0]) ? $sectionsSettings : []);
}

// Récupérer les produits
$products = getProducts(['active' => true]);
$featuredProducts = array_slice($products, 0, 6);

// Récupérer l'image de fond
$backgroundImage = getBackgroundImage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique - Accueil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
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
            background: rgba(0, 0, 0, 0.7);
        }
        <?php endif; ?>
        .hero-section {
            position: relative;
            min-height: 100vh;
            overflow-y: auto;
        }
        .hero-content {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 8rem 1rem 4rem;
            padding-top: 5rem;
            max-width: 80rem;
            margin: 0 auto;
        }
        @media (max-width: 767px) {
            .hero-content {
                padding-top: 4.5rem;
            }
        }
        .hero-title-container {
            display: inline-block;
            background: rgba(17, 17, 17, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 9999px;
            padding: 2.5rem 4rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hero-title-container:hover {
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #fff;
        }
        .hero-subtitle {
            font-size: 1.125rem;
            font-weight: 300;
            color: #fff;
        }
        .menu-sections {
            margin-top: 3rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .menu-section {
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            background: rgba(17, 17, 17, 0.6);
            backdrop-filter: blur(20px);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        .menu-section:hover {
            transform: scale(1.05) translateY(-8px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 12px 48px rgba(255, 255, 255, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.15);
        }
        .menu-section-header {
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .menu-section-icon {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .menu-section-icon img {
            width: 3rem;
            height: 3rem;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        .menu-section-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: #fff;
            transition: all 0.3s;
        }
        .menu-section:hover .menu-section-title {
            background: linear-gradient(135deg, #ffffff 0%, #aaaaaa 50%, #ffffff 100%);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 3s ease infinite;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
        }
        .menu-section-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s, opacity 0.3s;
            opacity: 0;
        }
        .menu-section.open .menu-section-content {
            max-height: 500px;
            opacity: 1;
            padding: 0.5rem 1rem 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 0.5rem;
            padding-top: 0.75rem;
        }
        .menu-section-content-text {
            color: #fff;
            font-size: 0.875rem;
            line-height: 1.75;
            white-space: pre-line;
        }
        .animated-bg {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .bg-element {
            position: absolute;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.03);
            filter: blur(80px);
            animation: float 15s ease-in-out infinite, pulse-slow 8s ease-in-out infinite;
        }
        .bg-element-1 {
            top: 5rem;
            left: 2.5rem;
            width: 18rem;
            height: 18rem;
        }
        .bg-element-2 {
            bottom: 5rem;
            right: 2.5rem;
            width: 24rem;
            height: 24rem;
            animation-delay: 1s;
        }
        .bg-element-3 {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 31.25rem;
            height: 31.25rem;
            animation-delay: 2s;
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.8; }
        }
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            33% { transform: translateY(-20px) translateX(10px); }
            66% { transform: translateY(10px) translateX(-10px); }
        }
        @media (min-width: 768px) {
            .hero-title {
                font-size: 3.75rem;
            }
            .hero-subtitle {
                font-size: 1.25rem;
            }
        }
    </style>
    <script src="devtools_blocker.js"></script>
    <script src="assets/js/loading.js"></script>
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <div class="cosmic-bg hero-section">
        <!-- Animated Background Elements -->
        <div class="animated-bg">
            <div class="bg-element bg-element-1"></div>
            <div class="bg-element bg-element-2"></div>
            <div class="bg-element bg-element-3"></div>
        </div>

        <!-- Content -->
        <div class="hero-content">
            <?php if ($heroTitle || $heroSubtitle): ?>
                <div class="hero-title-container">
                    <?php if ($heroTitle): ?>
                        <h1 class="hero-title"><?php echo htmlspecialchars($heroTitle); ?></h1>
                    <?php endif; ?>
                    <?php if ($heroSubtitle): ?>
                        <p class="hero-subtitle"><?php echo htmlspecialchars($heroSubtitle); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Menu Sections -->
            <?php if (!empty($sections)): ?>
                <div class="menu-sections">
                    <?php foreach ($sections as $index => $section): 
                        $isImageUrl = !empty($section['icon']) && strpos($section['icon'], 'http') !== false;
                    ?>
                        <div class="menu-section" onclick="toggleSection(<?php echo $index; ?>)">
                            <div class="menu-section-header">
                                <div class="menu-section-icon">
                                    <?php if ($isImageUrl): ?>
                                        <img src="<?php echo htmlspecialchars($section['icon']); ?>" 
                                             alt="<?php echo htmlspecialchars($section['title']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                        <span style="display: none; font-size: 1.875rem;"><?php echo htmlspecialchars($section['icon'] ?? '📦'); ?></span>
                                    <?php else: ?>
                                        <span style="font-size: 1.875rem;"><?php echo htmlspecialchars($section['icon'] ?? '📦'); ?></span>
                                    <?php endif; ?>
                                    <span class="menu-section-title"><?php echo htmlspecialchars($section['title']); ?></span>
                                </div>
                                <svg class="section-arrow" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #fff; transition: transform 0.3s;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div class="menu-section-content">
                                <p class="menu-section-content-text"><?php echo nl2br(htmlspecialchars($section['content'] ?? '')); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSection(index) {
            const sections = document.querySelectorAll('.menu-section');
            const section = sections[index];
            const arrow = section.querySelector('.section-arrow');
            
            if (section.classList.contains('open')) {
                section.classList.remove('open');
                arrow.style.transform = 'rotate(0deg)';
            } else {
                // Fermer toutes les autres sections
                sections.forEach((s, i) => {
                    if (i !== index) {
                        s.classList.remove('open');
                        s.querySelector('.section-arrow').style.transform = 'rotate(0deg)';
                    }
                });
                section.classList.add('open');
                arrow.style.transform = 'rotate(180deg)';
            }
        }
    </script>
</body>
</html>

