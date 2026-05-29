<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/freemopay.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

// Validation
$service_id = (int)($input['service_id'] ?? 0);
$url        = trim($input['url'] ?? '');
$quantity   = (int)($input['quantity'] ?? 0);
$phone      = preg_replace('/\s+/', '', $input['phone'] ?? '');
$email      = trim($input['email'] ?? '');

if (!$service_id || !$url || !$quantity || !$phone) {
    echo json_encode(['success' => false, 'error' => 'Tous les champs obligatoires doivent être remplis.']);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'Le lien du profil est invalide.']);
    exit;
}

if (!preg_match('/^237[0-9]{9}$/', $phone)) {
    echo json_encode(['success' => false, 'error' => 'Numéro invalide. Format : 237XXXXXXXXX (9 chiffres après 237)']);
    exit;
}

// Récupérer le service depuis le cache
$db      = getDB();
$service = $db->prepare("SELECT * FROM services_cache WHERE peaksmm_id=? AND active=1")->execute([$service_id]);
$service = $db->prepare("SELECT * FROM services_cache WHERE peaksmm_id=? AND active=1");
$service->execute([$service_id]);
$service = $service->fetch();

if (!$service) {
    echo json_encode(['success' => false, 'error' => 'Service introuvable.']);
    exit;
}

if ($quantity < $service['min_qty'] || $quantity > $service['max_qty']) {
    echo json_encode(['success' => false, 'error' => "Quantité invalide. Min: {$service['min_qty']}, Max: {$service['max_qty']}"]);
    exit;
}

// Calculer le prix
require_once __DIR__ . '/peaksmm.php';
$price_xaf = calculate_price_xaf((float)$service['rate_usd'], $quantity);
$price_usd = ($service['rate_usd'] / 1000) * $quantity;

// Créer la commande en base
$order_ref = generateOrderRef();

$stmt = $db->prepare("
    INSERT INTO orders (order_ref, email, phone, platform, service_type, service_name, peaksmm_service, social_url, quantity, price_xaf, price_usd, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");
$stmt->execute([
    $order_ref, $email, $phone,
    $service['platform'], $service['service_type'], $service['name'],
    $service['peaksmm_id'], $url, $quantity, $price_xaf, $price_usd
]);

// Initier le paiement Freemopay
$payment = freemopay_init_payment(
    $phone,
    $price_xaf,
    $order_ref,
    SITE_NAME . ' — ' . $quantity . ' ' . $service['service_type'] . ' ' . $service['platform']
);

if (isset($payment['error']) || !isset($payment['reference'])) {
    $err = $payment['error'] ?? 'Erreur paiement';
    $db->prepare("UPDATE orders SET status='failed', status_message=? WHERE order_ref=?")->execute([$err, $order_ref]);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'initiation du paiement. Réessayez.']);
    exit;
}

// Sauvegarder la référence Freemopay
$db->prepare("UPDATE orders SET freemopay_ref=? WHERE order_ref=?")->execute([$payment['reference'], $order_ref]);

echo json_encode([
    'success'   => true,
    'order_ref' => $order_ref,
    'amount'    => $price_xaf,
    'phone'     => $phone,
]);
