<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/peaksmm.php';

header('Content-Type: application/json');

$ref = trim($_GET['ref'] ?? '');
if (!$ref) { echo json_encode(['status' => 'error']); exit; }

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_ref=?");
$stmt->execute([$ref]);
$order = $stmt->fetch();

if (!$order) { echo json_encode(['status' => 'error']); exit; }

// Si commande en processing, mettre à jour depuis PeakSMM
if ($order['status'] === 'processing' && $order['peaksmm_order']) {
    $ps = peaksmm_order_status((int)$order['peaksmm_order']);
    if (isset($ps['status'])) {
        $ps_status = strtolower($ps['status']);
        $map = ['completed' => 'completed', 'partial' => 'partial', 'cancelled' => 'cancelled', 'in progress' => 'processing'];
        $new_status = $map[$ps_status] ?? 'processing';
        $db->prepare("UPDATE orders SET status=? WHERE order_ref=?")->execute([$new_status, $ref]);
        $order['status'] = $new_status;
    }
}

echo json_encode(['status' => $order['status']]);
