<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/peaksmm.php';

$order = null;
$ref   = trim($_GET['ref'] ?? '');
$error = '';

if ($ref) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_ref=?");
    $stmt->execute([$ref]);
    $order = $stmt->fetch();

    if (!$order) {
        $error = 'Commande introuvable.';
    } elseif ($order['status'] === 'processing' && $order['peaksmm_order']) {
        // Rafraîchir depuis PeakSMM
        $ps = peaksmm_order_status((int)$order['peaksmm_order']);
        if (isset($ps['status'])) {
            $map = ['completed' => 'completed', 'partial' => 'partial', 'cancelled' => 'cancelled', 'in progress' => 'processing'];
            $new = $map[strtolower($ps['status'])] ?? 'processing';
            $db->prepare("UPDATE orders SET status=? WHERE order_ref=?")->execute([$new, $ref]);
            $order['status']  = $new;
            $order['remains'] = $ps['remains'] ?? null;
        }
    }
}

$status_labels = [
    'pending'    => 'En attente de paiement',
    'paid'       => 'Paiement reçu',
    'processing' => 'En cours de livraison',
    'completed'  => 'Livré ✅',
    'partial'    => 'Livraison partielle',
    'failed'     => 'Échoué',
    'cancelled'  => 'Annulé',
];

$progress_map = [
    'pending' => 5, 'paid' => 20, 'processing' => 60, 'completed' => 100, 'partial' => 70, 'failed' => 100, 'cancelled' => 100,
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suivi commande — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">⚡ <?= SITE_NAME ?></a>
    <div class="nav-links">
        <a href="index.php">Accueil</a>
        <a href="https://wa.me/<?= SUPPORT_WHATSAPP ?>" target="_blank">💬 Support</a>
    </div>
</nav>

<div style="max-width:600px;margin:3rem auto;padding:0 1rem">
    <h1 style="font-size:1.8rem;margin-bottom:2rem">📦 Suivre ma commande</h1>

    <form method="GET" style="display:flex;gap:0.5rem;margin-bottom:2rem">
        <input type="text" name="ref" value="<?= htmlspecialchars($ref) ?>"
               placeholder="Référence commande (ex: EST-XXXXXXXX)"
               style="flex:1;background:var(--card);border:1px solid var(--border);border-radius:10px;color:var(--text);padding:0.75rem 1rem;font-size:0.95rem;outline:none">
        <button type="submit" class="btn-order" style="padding:0.75rem 1.5rem">Chercher</button>
    </form>

    <?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
    <div class="status-card" style="margin:0">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
            <div>
                <div style="font-size:1.2rem;font-weight:700"><?= htmlspecialchars($order['order_ref']) ?></div>
                <div style="color:var(--muted);font-size:0.85rem"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
            </div>
            <span class="status-badge <?= $order['status'] ?>">
                <?= $status_labels[$order['status']] ?? $order['status'] ?>
            </span>
        </div>

        <div class="progress-bar">
            <div class="fill" style="width:<?= $progress_map[$order['status']] ?? 0 ?>%"></div>
        </div>

        <table style="width:100%;font-size:0.9rem;margin-top:1.5rem;border-collapse:collapse">
            <tr><td style="color:var(--muted);padding:0.4rem 0">Service</td>
                <td style="text-align:right"><?= htmlspecialchars($order['service_name']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:0.4rem 0">Plateforme</td>
                <td style="text-align:right"><?= ucfirst($order['platform']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:0.4rem 0">Quantité</td>
                <td style="text-align:right"><?= number_format($order['quantity']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:0.4rem 0">Montant payé</td>
                <td style="text-align:right;color:var(--accent);font-weight:700"><?= number_format($order['price_xaf']) ?> XAF</td></tr>
            <?php if (!empty($order['remains'])): ?>
            <tr><td style="color:var(--muted);padding:0.4rem 0">Reste à livrer</td>
                <td style="text-align:right"><?= number_format($order['remains']) ?></td></tr>
            <?php endif; ?>
        </table>

        <?php if ($order['status'] === 'processing'): ?>
        <div class="alert success" style="margin-top:1.5rem">
            <span class="loader"></span> Livraison en cours — les résultats apparaissent progressivement.
        </div>
        <script>setTimeout(() => location.reload(), 30000);</script>
        <?php endif; ?>

        <?php if ($order['status'] === 'failed'): ?>
        <div class="alert error" style="margin-top:1.5rem">
            ❌ <?= htmlspecialchars($order['status_message'] ?? 'Une erreur est survenue.') ?><br>
            <a href="https://wa.me/<?= SUPPORT_WHATSAPP ?>" style="color:inherit">Contacter le support</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<footer>
    <p><?= SITE_NAME ?> — <a href="https://wa.me/<?= SUPPORT_WHATSAPP ?>" target="_blank">💬 Support WhatsApp</a></p>
</footer>
</body>
</html>
