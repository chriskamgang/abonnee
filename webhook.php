<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/peaksmm.php';

// Lire le callback Freemopay
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    exit;
}

$status      = $input['status']     ?? '';
$externalId  = $input['externalId'] ?? '';  // = order_ref
$freeRef     = $input['reference']  ?? '';

$db = getDB();
$order = $db->prepare("SELECT * FROM orders WHERE order_ref=?")->execute([$externalId]);
$stmt  = $db->prepare("SELECT * FROM orders WHERE order_ref=?");
$stmt->execute([$externalId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit;
}

// Ignorer si déjà traité
if (in_array($order['status'], ['paid', 'processing', 'completed', 'failed', 'cancelled'])) {
    http_response_code(200);
    exit;
}

if ($status === 'SUCCESS') {
    // Paiement reçu — passer la commande sur PeakSMM
    $result = peaksmm_add_order(
        (int)$order['peaksmm_service'],
        $order['social_url'],
        (int)$order['quantity']
    );

    if (isset($result['order'])) {
        $db->prepare("UPDATE orders SET status='processing', peaksmm_order=?, freemopay_ref=? WHERE order_ref=?")
           ->execute([$result['order'], $freeRef, $externalId]);
    } else {
        $err = $result['error'] ?? 'Erreur PeakSMM';
        $db->prepare("UPDATE orders SET status='failed', status_message=?, freemopay_ref=? WHERE order_ref=?")
           ->execute([$err, $freeRef, $externalId]);
    }

} elseif ($status === 'FAILED') {
    $msg = $input['message'] ?? 'Paiement échoué';
    $db->prepare("UPDATE orders SET status='failed', status_message=? WHERE order_ref=?")->execute([$msg, $externalId]);
}

http_response_code(200);
echo 'OK';
