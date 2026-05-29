<?php
require_once __DIR__ . '/config.php';

function peaksmm_request(array $params): array {
    $params['key'] = PEAKSMM_API_KEY;
    $ch = curl_init(PEAKSMM_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? ['error' => 'Invalid response'];
}

function peaksmm_get_services(): array {
    return peaksmm_request(['action' => 'services']);
}

function peaksmm_add_order(int $service, string $url, int $quantity): array {
    return peaksmm_request([
        'action'   => 'add',
        'service'  => $service,
        'link'     => $url,
        'quantity' => $quantity,
    ]);
}

function peaksmm_order_status(int $orderId): array {
    return peaksmm_request(['action' => 'status', 'order' => $orderId]);
}

// Detecte la plateforme et le type de service depuis le nom PeakSMM
function detect_platform(string $name): string {
    $name = strtolower($name);
    if (str_contains($name, 'tiktok'))     return 'tiktok';
    if (str_contains($name, 'instagram'))  return 'instagram';
    if (str_contains($name, 'facebook'))   return 'facebook';
    if (str_contains($name, 'youtube'))    return 'youtube';
    if (str_contains($name, 'twitter') || str_contains($name, 'x.com')) return 'twitter';
    if (str_contains($name, 'snapchat'))   return 'snapchat';
    return 'other';
}

function detect_service_type(string $name): string {
    $name = strtolower($name);
    if (str_contains($name, 'follower') || str_contains($name, 'abonné')) return 'followers';
    if (str_contains($name, 'like'))      return 'likes';
    if (str_contains($name, 'view'))      return 'views';
    if (str_contains($name, 'comment'))   return 'comments';
    if (str_contains($name, 'share'))     return 'shares';
    return 'other';
}

// Calcul prix XAF avec marge
function calculate_price_xaf(float $rateUsd, int $quantity): int {
    $cost_usd   = ($rateUsd / 1000) * $quantity;
    $with_margin = $cost_usd * (1 + MARGE_POURCENT / 100);
    $xaf        = $with_margin * USD_TO_XAF;
    // Arrondir au 50 XAF supérieur
    return (int)(ceil($xaf / 50) * 50);
}
