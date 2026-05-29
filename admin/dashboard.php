<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$secret = $_GET['secret'] ?? $_SESSION['admin_secret'] ?? '';
if ($secret !== 'CHANGEZ_CE_SECRET') {
    http_response_code(403);
    die('Accès refusé. Ajoutez ?secret=VOTRE_SECRET à l\'URL');
}

$db = getDB();

$stats = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status IN ('completed','processing') THEN price_xaf ELSE 0 END) as revenue_xaf
    FROM orders
")->fetch();

$recent = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="logo">⚡ Admin — <?= SITE_NAME ?></div>
    <div class="nav-links">
        <a href="../index.php" target="_blank">Voir le site</a>
        <a href="sync_services.php?secret=CHANGEZ_CE_SECRET">Sync services</a>
    </div>
</nav>

<div style="max-width:1200px;margin:2rem auto;padding:0 1rem">
    <div class="services-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-bottom:2rem">
        <div class="service-card">
            <div style="color:var(--muted);font-size:0.85rem">Total commandes</div>
            <div style="font-size:2rem;font-weight:800"><?= $stats['total'] ?></div>
        </div>
        <div class="service-card">
            <div style="color:var(--muted);font-size:0.85rem">Revenus encaissés</div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--accent)"><?= number_format($stats['revenue_xaf']) ?> XAF</div>
        </div>
        <div class="service-card">
            <div style="color:var(--muted);font-size:0.85rem">En cours</div>
            <div style="font-size:2rem;font-weight:800;color:#3B82F6"><?= $stats['processing'] ?></div>
        </div>
        <div class="service-card">
            <div style="color:var(--muted);font-size:0.85rem">En attente paiement</div>
            <div style="font-size:2rem;font-weight:800;color:#F59E0B"><?= $stats['pending'] ?></div>
        </div>
        <div class="service-card">
            <div style="color:var(--muted);font-size:0.85rem">Livrées</div>
            <div style="font-size:2rem;font-weight:800;color:#10B981"><?= $stats['completed'] ?></div>
        </div>
        <div class="service-card">
            <div style="color:var(--muted);font-size:0.85rem">Échouées</div>
            <div style="font-size:2rem;font-weight:800;color:#EF4444"><?= $stats['failed'] ?></div>
        </div>
    </div>

    <h2 style="margin-bottom:1rem">Dernières commandes</h2>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem">
            <tr style="color:var(--muted);text-align:left;border-bottom:1px solid var(--border)">
                <th style="padding:0.6rem">Réf</th>
                <th>Date</th>
                <th>Service</th>
                <th>Qté</th>
                <th>Téléphone</th>
                <th>Prix</th>
                <th>Statut</th>
            </tr>
            <?php foreach ($recent as $o): ?>
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:0.6rem;font-family:monospace"><?= $o['order_ref'] ?></td>
                <td><?= date('d/m H:i', strtotime($o['created_at'])) ?></td>
                <td><?= htmlspecialchars(substr($o['service_name'], 0, 30)) ?>...</td>
                <td><?= number_format($o['quantity']) ?></td>
                <td><?= $o['phone'] ?></td>
                <td style="color:var(--accent)"><?= number_format($o['price_xaf']) ?> XAF</td>
                <td><span class="status-badge <?= $o['status'] ?>"><?= $o['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
