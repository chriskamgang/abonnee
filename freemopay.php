<?php
require_once __DIR__ . '/config.php';

function freemopay_get_token(): ?string {
    $ch = curl_init(FREEMOPAY_BASE_URL . '/api/v2/payment/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => FREEMOPAY_APP_KEY . ':' . FREEMOPAY_SECRET_KEY,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response['token'] ?? null;
}

function freemopay_init_payment(string $phone, int $amount, string $externalId, string $description): array {
    $token = freemopay_get_token();
    if (!$token) return ['error' => 'Impossible d\'obtenir le token Freemopay'];

    $body = json_encode([
        'payer'       => $phone,
        'amount'      => (string)$amount,
        'externalId'  => $externalId,
        'description' => $description,
        'callback'    => WEBHOOK_URL,
    ]);

    $ch = curl_init(FREEMOPAY_BASE_URL . '/api/v2/payment');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response ?? ['error' => 'Réponse invalide'];
}

function freemopay_get_status(string $reference): array {
    $token = freemopay_get_token();
    if (!$token) return ['error' => 'Token invalide'];

    $ch = curl_init(FREEMOPAY_BASE_URL . '/api/v2/payment/' . urlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response ?? ['error' => 'Réponse invalide'];
}
