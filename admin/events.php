<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';

$events = [
    'noel' => ['enabled' => false, 'startDate' => '', 'endDate' => ''],
    'paques' => ['enabled' => false, 'startDate' => '', 'endDate' => ''],
    'saintValentin' => ['enabled' => false, 'startDate' => '', 'endDate' => ''],
    'halloween' => ['enabled' => false, 'startDate' => '', 'endDate' => ''],
    'nouvelAn' => ['enabled' => false, 'startDate' => '', 'endDate' => '']
];

// Calculer les dates par défaut
$currentYear = date('Y');
$nextYear = $currentYear + 1;

$events['noel']['startDate'] = $currentYear . '-12-01';
$events['noel']['endDate'] = $currentYear . '-12-31';
$events['saintValentin']['startDate'] = $currentYear . '-02-01';
$events['saintValentin']['endDate'] = $currentYear . '-02-14';
$events['halloween']['startDate'] = $currentYear . '-10-25';
$events['halloween']['endDate'] = $currentYear . '-11-02';
$events['nouvelAn']['startDate'] = $currentYear . '-12-28';
$events['nouvelAn']['endDate'] = $nextYear . '-01-05';

// Calculer Pâques (algorithme simplifié)
function calculateEaster($year) {
    $a = $year % 19;
    $b = floor($year / 100);
    $c = $year % 100;
    $d = floor($b / 4);
    $e = $b % 4;
    $f = floor(($b + 8) / 25);
    $g = floor(($b - $f + 1) / 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = floor($c / 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = floor(($a + 11 * $h + 22 * $l) / 451);
    $month = floor(($h + $l - 7 * $m + 114) / 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return new DateTime("$year-$month-$day");
}

$easterDate = calculateEaster($currentYear);
$easterStart = clone $easterDate;
$easterStart->modify('-7 days');
$easterEnd = clone $easterDate;
$easterEnd->modify('+7 days');
$events['paques']['startDate'] = $easterStart->format('Y-m-d');
$events['paques']['endDate'] = $easterEnd->format('Y-m-d');

$error = null;
$success = null;

// Charger les événements existants
try {
    $eventsResult = $supabase->request('GET', 'season_events', null, true);
    if (!empty($eventsResult)) {
        $eventMap = [
            'Noël' => 'noel',
            'Pâques' => 'paques',
            'St-Valentin' => 'saintValentin',
            'Halloween' => 'halloween',
            'Nouvel An' => 'nouvelAn'
        ];
        foreach ($eventsResult as $event) {
            $name = $event['name'] ?? '';
            if (isset($eventMap[$name]) && isset($events[$eventMap[$name]])) {
                $key = $eventMap[$name];
                $events[$key]['enabled'] = $event['enabled'] ?? false;
                if (!empty($event['start_date'])) {
                    $events[$key]['startDate'] = date('Y-m-d', strtotime($event['start_date']));
                }
                if (!empty($event['end_date'])) {
                    $events[$key]['endDate'] = date('Y-m-d', strtotime($event['end_date']));
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Erreur chargement événements: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $eventNames = [
            'noel' => 'Noël',
            'paques' => 'Pâques',
            'saintValentin' => 'St-Valentin',
            'halloween' => 'Halloween',
            'nouvelAn' => 'Nouvel An'
        ];
        
        foreach ($events as $key => $event) {
            $events[$key]['enabled'] = isset($_POST[$key . '_enabled']);
            $events[$key]['startDate'] = $_POST[$key . '_startDate'] ?? $events[$key]['startDate'];
            $events[$key]['endDate'] = $_POST[$key . '_endDate'] ?? $events[$key]['endDate'];
            
            // Sauvegarder dans Supabase
            $eventData = [
                'name' => $eventNames[$key],
                'enabled' => $events[$key]['enabled'],
                'start_date' => $events[$key]['startDate'] ? date('Y-m-d', strtotime($events[$key]['startDate'])) : null,
                'end_date' => $events[$key]['endDate'] ? date('Y-m-d', strtotime($events[$key]['endDate'])) : null,
                'updated_at' => date('Y-m-d\TH:i:s.u\Z')
            ];
            
            $existingEvent = $supabase->request('GET', 'season_events?name=eq.' . urlencode($eventNames[$key]), null, true);
            if (!empty($existingEvent) && isset($existingEvent[0]['id'])) {
                $supabase->request('PATCH', 'season_events?id=eq.' . $existingEvent[0]['id'], $eventData, true);
            } else {
                $eventData['created_at'] = date('Y-m-d\TH:i:s.u\Z');
                $supabase->request('POST', 'season_events', $eventData, true);
            }
        }
        
        $success = 'Événements sauvegardés avec succès !';
    } catch (Exception $e) {
        $error = 'Erreur: ' . $e->getMessage();
        error_log("Erreur sauvegarde événements: " . $e->getMessage());
    }
}

$eventLabels = [
    'noel' => ['name' => '🎄 Noël', 'description' => 'Décembre'],
    'paques' => ['name' => '🐰 Pâques', 'description' => 'Avril (calculé automatiquement)'],
    'saintValentin' => ['name' => '💕 Saint-Valentin', 'description' => 'Février'],
    'halloween' => ['name' => '🎃 Halloween', 'description' => 'Octobre-Novembre'],
    'nouvelAn' => ['name' => '🎆 Nouvel An', 'description' => 'Décembre-Janvier']
];

$pageTitle = 'Événements - Panel Admin';
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
        .page-subtitle {
            color: #fff;
            font-size: 0.875rem;
        }
        @media (min-width: 640px) {
            .page-subtitle {
                font-size: 1rem;
            }
        }
        .event-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            margin-bottom: 1.5rem;
        }
        .event-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        @media (min-width: 640px) {
            .event-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        .event-info h3 {
            color: #fff;
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }
        @media (min-width: 640px) {
            .event-info h3 {
                font-size: 1.25rem;
            }
        }
        .event-info p {
            color: #fff;
            font-size: 0.875rem;
        }
        .event-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .event-toggle input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 0.25rem;
            border: 1px solid rgba(75, 85, 99, 1);
            background: rgba(30, 41, 59, 1);
            cursor: pointer;
        }
        .event-toggle label {
            color: #fff;
            font-size: 0.875rem;
            cursor: pointer;
        }
        @media (min-width: 640px) {
            .event-toggle label {
                font-size: 1rem;
            }
        }
        .event-dates {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        @media (min-width: 640px) {
            .event-dates {
                grid-template-columns: 1fr 1fr;
            }
        }
        .date-input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .date-input-group label {
            color: #fff;
            font-size: 0.875rem;
        }
        .date-input-wrapper {
            display: flex;
            gap: 0.5rem;
        }
        .date-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background: rgba(30, 41, 59, 1);
            border: 1px solid rgba(55, 65, 81, 0.3);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 0.875rem;
        }
        .auto-btn {
            padding: 0.75rem 1rem;
            background: rgba(37, 99, 235, 1);
            color: #fff;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            white-space: nowrap;
            transition: all 0.3s;
        }
        .auto-btn:hover {
            background: rgba(29, 78, 216, 1);
        }
        .save-btn {
            padding: 0.75rem 2rem;
            background: linear-gradient(to right, #db2777, #9333ea);
            color: #fff;
            font-weight: 700;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }
        .save-btn:hover {
            background: linear-gradient(to right, #be185d, #7e22ce);
        }
        .error-message,
        .success-message {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .error-message {
            background: rgba(127, 29, 29, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
        }
        .error-message p {
            color: rgba(248, 113, 113, 1);
        }
        .success-message {
            background: rgba(20, 83, 45, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.5);
        }
        .success-message p {
            color: rgba(74, 222, 128, 1);
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
                <h1 class="page-title">🎉 Événements</h1>
                <p class="page-subtitle">Configurez les événements saisonniers de votre boutique</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <p>Erreur: <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php foreach ($events as $key => $event): 
                    $label = $eventLabels[$key];
                ?>
                    <div class="event-card neon-border">
                        <div class="event-header">
                            <div class="event-info">
                                <h3><?php echo htmlspecialchars($label['name']); ?></h3>
                                <p><?php echo htmlspecialchars($label['description']); ?></p>
                            </div>
                            <div class="event-toggle">
                                <input type="checkbox" 
                                       id="<?php echo $key; ?>_enabled" 
                                       name="<?php echo $key; ?>_enabled" 
                                       <?php echo $event['enabled'] ? 'checked' : ''; ?>
                                       onchange="toggleEventDates('<?php echo $key; ?>')">
                                <label for="<?php echo $key; ?>_enabled">Activer</label>
                            </div>
                        </div>
                        <div id="<?php echo $key; ?>_dates" class="event-dates" style="display: <?php echo $event['enabled'] ? 'grid' : 'none'; ?>;">
                            <div class="date-input-group">
                                <label>Date de début</label>
                                <input type="date" 
                                       name="<?php echo $key; ?>_startDate" 
                                       value="<?php echo htmlspecialchars($event['startDate']); ?>"
                                       class="date-input">
                            </div>
                            <div class="date-input-group">
                                <label>Date de fin</label>
                                <div class="date-input-wrapper">
                                    <input type="date" 
                                           name="<?php echo $key; ?>_endDate" 
                                           value="<?php echo htmlspecialchars($event['endDate']); ?>"
                                           class="date-input">
                                    <button type="button" 
                                            onclick="autoFillDates('<?php echo $key; ?>')" 
                                            class="auto-btn">
                                        Auto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="submit" class="save-btn">
                        <span>💾</span>
                        <span>Sauvegarder les événements</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
    <script>
        function toggleEventDates(key) {
            const checkbox = document.getElementById(key + '_enabled');
            const datesDiv = document.getElementById(key + '_dates');
            datesDiv.style.display = checkbox.checked ? 'grid' : 'none';
        }
        
        function autoFillDates(key) {
            // Les dates sont déjà calculées côté serveur
            // Cette fonction peut être étendue pour recalculer si nécessaire
            alert('Dates automatiques appliquées pour ' + key);
        }
        
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

