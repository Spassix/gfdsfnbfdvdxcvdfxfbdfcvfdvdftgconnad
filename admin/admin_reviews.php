<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$reviews = [];
$error = null;
$success = null;
$filter = $_GET['filter'] ?? 'all';

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'approve' && isset($_POST['id'])) {
            try {
                $supabase->updateReview($_POST['id'], ['approved' => true]);
                $success = 'Avis approuvé avec succès !';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            try {
                $supabase->deleteReview($_POST['id']);
                $success = 'Avis supprimé avec succès !';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

try {
    // Récupérer les avis depuis Supabase
    $reviews = $supabase->request('GET', 'reviews', null, true);
    
    // Trier par date (plus récents en premier)
    usort($reviews, function($a, $b) {
        $dateA = strtotime($a['created_at'] ?? $a['createdAt'] ?? '0');
        $dateB = strtotime($b['created_at'] ?? $b['createdAt'] ?? '0');
        return $dateB - $dateA;
    });
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Filtrer les avis
$filteredReviews = $reviews;
if ($filter === 'approved') {
    $filteredReviews = array_filter($reviews, fn($r) => ($r['approved'] ?? false) === true);
} elseif ($filter === 'pending') {
    $filteredReviews = array_filter($reviews, fn($r) => ($r['approved'] ?? false) !== true);
}

function renderStars($rating) {
    $rating = (int)$rating;
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;
    return str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);
}

function formatDate($date) {
    if (!$date) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date('d F Y à H:i', $timestamp);
}

$pageTitle = 'Avis Clients - Panel Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../shop/assets/css/style.css">
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
            background: #000;
        }
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 18rem;
            background: #000;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 50;
            overflow-y: auto;
        }
        .admin-content {
            margin-left: 18rem;
            flex: 1;
            min-height: 100vh;
            padding: 2rem;
        }
        .page-header {
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        @media (min-width: 640px) {
            .page-title {
                font-size: 1.875rem;
            }
        }
        @media (min-width: 1024px) {
            .page-title {
                font-size: 2.25rem;
            }
        }
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .filter-btn.active {
            background: rgba(37, 99, 235, 1);
            color: #fff;
        }
        .filter-btn:not(.active) {
            background: rgba(55, 65, 81, 1);
            color: #fff;
        }
        .filter-btn:hover {
            opacity: 0.8;
        }
        .reviews-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 1024px) {
            .reviews-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .review-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
        }
        .review-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .review-author {
            color: #fff;
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }
        .review-stars {
            color: #fbbf24;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .review-date {
            color: #fff;
            font-size: 0.75rem;
            opacity: 0.7;
        }
        .review-status {
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .review-status.approved {
            background: rgba(34, 197, 94, 0.2);
            color: rgba(74, 222, 128, 1);
        }
        .review-status.pending {
            background: rgba(234, 179, 8, 0.2);
            color: rgba(250, 204, 21, 1);
        }
        .review-text {
            color: #fff;
            margin-bottom: 1rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .review-image {
            width: 100%;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1rem;
            max-height: 16rem;
        }
        .review-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .review-actions {
            display: flex;
            gap: 0.5rem;
        }
        .action-btn {
            flex: 1;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .approve-btn {
            background: rgba(34, 197, 94, 1);
            color: #fff;
        }
        .approve-btn:hover {
            background: rgba(22, 163, 74, 1);
        }
        .delete-btn {
            background: rgba(239, 68, 68, 1);
            color: #fff;
        }
        .delete-btn:hover {
            background: rgba(220, 38, 38, 1);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: #fff;
        }
        .error-message {
            background: rgba(127, 29, 29, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .error-message p {
            color: rgba(248, 113, 113, 1);
        }
        @media (max-width: 1024px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 4rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <button id="mobile-menu-btn" 
                style="position: fixed; top: 1rem; left: 1rem; z-index: 60; padding: 0.75rem; background: #000; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff; display: none; cursor: pointer;"
                onclick="document.querySelector('.admin-sidebar').classList.toggle('open'); document.getElementById('mobile-overlay').style.display = document.querySelector('.admin-sidebar').classList.contains('open') ? 'block' : 'none';">
            <span style="font-size: 1.5rem;">☰</span>
        </button>
        <div id="mobile-overlay" 
             style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); z-index: 40; backdrop-filter: blur(4px);"
             onclick="document.querySelector('.admin-sidebar').classList.remove('open'); document.getElementById('mobile-overlay').style.display = 'none';"></div>
        <?php include __DIR__ . '/components/sidebar.php'; ?>
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title">💬 Avis Clients</h1>
                <div class="filter-buttons">
                    <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        Tous (<?php echo count($reviews); ?>)
                    </a>
                    <a href="?filter=approved" class="filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                        Approuvés (<?php echo count(array_filter($reviews, fn($r) => ($r['approved'] ?? false) === true)); ?>)
                    </a>
                    <a href="?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                        En attente (<?php echo count(array_filter($reviews, fn($r) => ($r['approved'] ?? false) !== true)); ?>)
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <p>Erreur: <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message" style="background: rgba(20, 83, 45, 0.2); border: 1px solid rgba(34, 197, 94, 0.5); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                    <p style="color: rgba(74, 222, 128, 1);"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($filteredReviews)): ?>
                <div class="empty-state">
                    <p style="font-size: 1.125rem;">Aucun avis <?php echo $filter === 'all' ? '' : ($filter === 'approved' ? 'approuvé' : 'en attente'); ?></p>
                </div>
            <?php else: ?>
                <div class="reviews-grid">
                    <?php foreach ($filteredReviews as $review): 
                        $approved = ($review['approved'] ?? false) === true;
                        $author = $review['author'] ?? $review['name'] ?? 'Anonyme';
                        $rating = (int)($review['rating'] ?? 5);
                        $text = $review['text'] ?? $review['comment'] ?? '';
                        $image = $review['image'] ?? $review['photo'] ?? '';
                        $date = $review['created_at'] ?? $review['createdAt'] ?? '';
                    ?>
                        <div class="review-card neon-border">
                            <div class="review-header">
                                <div>
                                    <div class="review-author"><?php echo htmlspecialchars($author); ?></div>
                                    <div class="review-stars"><?php echo renderStars($rating); ?></div>
                                    <?php if ($date): ?>
                                        <div class="review-date"><?php echo formatDate($date); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="review-status <?php echo $approved ? 'approved' : 'pending'; ?>">
                                    <?php echo $approved ? '✓ Approuvé' : '⏳ En attente'; ?>
                                </div>
                            </div>
                            <div class="review-text"><?php echo nl2br(htmlspecialchars($text)); ?></div>
                            <?php if ($image): ?>
                                <div class="review-image">
                                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Avis">
                                </div>
                            <?php endif; ?>
                            <div class="review-actions">
                                <?php if (!$approved): ?>
                                    <form method="POST" style="flex: 1;" onsubmit="return confirm('Êtes-vous sûr de vouloir approuver cet avis ?');">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" class="action-btn approve-btn">✓ Approuver</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" class="action-btn delete-btn">🗑️ Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobile-menu-btn').style.display = 'block';
        }
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 1024) {
                document.getElementById('mobile-menu-btn').style.display = 'block';
            } else {
                document.getElementById('mobile-menu-btn').style.display = 'none';
                document.querySelector('.admin-sidebar').classList.remove('open');
                document.getElementById('mobile-overlay').style.display = 'none';
            }
        });
        document.querySelectorAll('.admin-sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    document.querySelector('.admin-sidebar').classList.remove('open');
                    document.getElementById('mobile-overlay').style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

