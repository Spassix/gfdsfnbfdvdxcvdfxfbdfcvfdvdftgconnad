<?php
/**
 * API Reviews
 * GET /api/reviews.php - Liste les avis approuvés
 * POST /api/reviews.php - Soumettre un nouvel avis
 */

global $supabase;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $reviews = $supabase->getReviews([], false);
        // Filtrer pour ne retourner que les avis approuvés
        $approvedReviews = array_filter($reviews, function($review) {
            return ($review['approved'] ?? false) === true;
        });
        
        // Trier par date (plus récents en premier)
        usort($approvedReviews, function($a, $b) {
            $dateA = strtotime($a['created_at'] ?? $a['createdAt'] ?? '0');
            $dateB = strtotime($b['created_at'] ?? $b['createdAt'] ?? '0');
            return $dateB - $dateA;
        });
        
        jsonResponse(array_values($approvedReviews));
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} elseif ($method === 'POST') {
    // Soumettre un nouvel avis
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['author']) || empty($data['text']) || empty($data['rating'])) {
        jsonError('Champs requis: author, text, rating', 400);
    }
    
    try {
        $reviewData = [
            'author' => $data['author'],
            'text' => $data['text'],
            'rating' => (int)$data['rating'],
            'approved' => false, // Nécessite validation admin
            'image' => $data['image'] ?? null,
            'created_at' => date('Y-m-d\TH:i:s.u\Z')
        ];
        
        $result = $supabase->request('POST', 'reviews', $reviewData, false);
        jsonResponse(['success' => true, 'review' => $result], 201);
    } catch (Exception $e) {
        jsonError('Erreur: ' . $e->getMessage(), 500);
    }
} else {
    jsonError('Méthode non autorisée', 405);
}

