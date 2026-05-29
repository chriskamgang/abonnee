<?php
// Script à exécuter une fois pour importer les services PeakSMM en base
// Accès : https://votre-domaine.com/admin/sync_services.php?secret=VOTRE_SECRET
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../peaksmm.php';

$secret = $_GET['secret'] ?? '';
if ($secret !== 'CHANGEZ_CE_SECRET') {
    http_response_code(403);
    die('Accès refusé.');
}

$services = peaksmm_get_services();

if (!is_array($services) || isset($services['error'])) {
    die('Erreur PeakSMM : ' . json_encode($services));
}

$db = getDB();
$inserted = 0;
$updated  = 0;

foreach ($services as $s) {
    $platform = detect_platform($s['name']);
    $type     = detect_service_type($s['name']);

    $stmt = $db->prepare("
        INSERT INTO services_cache (peaksmm_id, name, category, platform, service_type, rate_usd, min_qty, max_qty)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name=VALUES(name), category=VALUES(category), platform=VALUES(platform),
            service_type=VALUES(service_type), rate_usd=VALUES(rate_usd),
            min_qty=VALUES(min_qty), max_qty=VALUES(max_qty), updated_at=NOW()
    ");
    $stmt->execute([
        $s['service'], $s['name'], $s['category'] ?? '',
        $platform, $type, $s['rate'], $s['min'], $s['max']
    ]);

    if ($stmt->rowCount() === 1) $inserted++;
    else $updated++;
}

echo "<h2>Synchronisation terminée</h2>";
echo "<p>✅ $inserted nouveaux services | 🔄 $updated mis à jour</p>";
echo "<p>Total: " . count($services) . " services récupérés</p>";
echo '<p><a href="../index.php">Voir le site</a></p>';
