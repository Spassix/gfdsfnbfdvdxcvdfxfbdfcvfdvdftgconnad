<?php
/**
 * Configuration Boutique
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../supabase_client.php';

// Initialiser le client Supabase si ce n'est pas déjà fait
if (!isset($GLOBALS['supabase'])) {
    $GLOBALS['supabase'] = new SupabaseClient();
}
$supabase = $GLOBALS['supabase'];

// Fonction pour récupérer les produits (utilise la clé anonyme pour la boutique publique)
function getProducts($filters = []) {
    global $supabase;
    if (!$supabase) {
        error_log("Erreur: Client Supabase non initialisé");
        return [];
    }
    try {
        $products = $supabase->getProducts($filters, false); // false = clé anonyme
        return $products;
    } catch (Exception $e) {
        error_log("Erreur récupération produits: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer les catégories (utilise la clé anonyme pour la boutique publique)
function getCategories() {
    global $supabase;
    if (!$supabase) {
        return [];
    }
    try {
        return $supabase->getCategories(false); // false = clé anonyme
    } catch (Exception $e) {
        error_log("Erreur récupération catégories: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer les farms (utilise la clé anonyme pour la boutique publique)
function getFarms() {
    global $supabase;
    if (!$supabase) {
        return [];
    }
    try {
        return $supabase->getFarms(false); // false = clé anonyme
    } catch (Exception $e) {
        error_log("Erreur récupération farms: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer un produit par ID (utilise la clé anonyme pour la boutique publique)
function getProduct($id) {
    global $supabase;
    if (!$supabase) {
        return null;
    }
    try {
        return $supabase->getProduct($id, false); // false = clé anonyme
    } catch (Exception $e) {
        error_log("Erreur récupération produit: " . $e->getMessage());
        return null;
    }
}

// Fonction pour récupérer les settings
function getSettings($key) {
    global $supabase;
    if (!$supabase) {
        return null;
    }
    try {
        $result = $supabase->request('GET', 'settings?key=eq.' . urlencode($key), null, false);
        if (!empty($result) && isset($result[0]['value'])) {
            $value = $result[0]['value'];
            // Si c'est une chaîne JSON, la décoder
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            }
            return $value;
        }
        return null;
    } catch (Exception $e) {
        error_log("Erreur récupération settings: " . $e->getMessage());
        return null;
    }
}

// Fonction pour obtenir l'image de fond
function getBackgroundImage() {
    $settings = getSettings('general');
    return $settings['backgroundImage'] ?? '';
}

/**
 * Vérifier si la maintenance est activée et rediriger si nécessaire
 */
function checkMaintenance() {
    try {
        global $supabase;
        $maintenanceResult = $supabase->request('GET', 'maintenance?order=created_at.desc&limit=1', null, false);
        if (!empty($maintenanceResult) && isset($maintenanceResult[0]['enabled']) && $maintenanceResult[0]['enabled'] === true) {
            // Ne pas rediriger si on est déjà sur la page de maintenance
            $currentPage = basename($_SERVER['PHP_SELF']);
            if ($currentPage !== 'maintenance.php') {
                header('Location: maintenance.php');
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Erreur vérification maintenance: " . $e->getMessage());
    }
}

/**
 * Récupérer les événements saisonniers actifs
 */
function getActiveSeasonEvents() {
    global $supabase;
    if (!$supabase) {
        return [];
    }
    try {
        $now = date('Y-m-d');
        // Récupérer tous les événements activés et filtrer en PHP pour plus de flexibilité
        $result = $supabase->request('GET', 'season_events?enabled=eq.true&order=start_date.asc', null, false);
        if (empty($result)) {
            return [];
        }
        
        // Filtrer les événements actifs (entre start_date et end_date)
        $activeEvents = [];
        foreach ($result as $event) {
            $startDate = $event['start_date'] ?? null;
            $endDate = $event['end_date'] ?? null;
            
            // Si pas de dates, considérer comme actif
            if (!$startDate && !$endDate) {
                $activeEvents[] = $event;
                continue;
            }
            
            // Vérifier si l'événement est actif
            $isActive = true;
            if ($startDate && strtotime($startDate) > strtotime($now)) {
                $isActive = false;
            }
            if ($endDate && strtotime($endDate) < strtotime($now)) {
                $isActive = false;
            }
            
            if ($isActive) {
                $activeEvents[] = $event;
            }
        }
        
        return $activeEvents;
    } catch (Exception $e) {
        error_log("Erreur récupération événements: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupérer la configuration de la page de chargement
 */
function getLoadingPageConfig() {
    global $supabase;
    if (!$supabase) {
        return null;
    }
    try {
        $result = $supabase->request('GET', 'loading_page?order=created_at.desc&limit=1', null, false);
        if (!empty($result) && isset($result[0]) && ($result[0]['enabled'] ?? false) === true) {
            return $result[0];
        }
        return null;
    } catch (Exception $e) {
        error_log("Erreur récupération loading page: " . $e->getMessage());
        return null;
    }
}

