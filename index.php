<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/peaksmm.php';
require_once __DIR__ . '/db.php';

// Charger les services depuis le cache DB
$db = getDB();
$services = $db->query("SELECT * FROM services_cache WHERE active=1 ORDER BY platform, service_type")->fetchAll();

// Grouper par plateforme
$by_platform = [];
foreach ($services as $s) {
    $by_platform[$s['platform']][] = $s;
}

$platform_icons = [
    'tiktok'    => '🎵',
    'instagram' => '📸',
    'facebook'  => '👥',
    'youtube'   => '▶️',
    'twitter'   => '🐦',
    'snapchat'  => '👻',
    'other'     => '🌐',
];

$platform_labels = [
    'tiktok'    => 'TikTok',
    'instagram' => 'Instagram',
    'facebook'  => 'Facebook',
    'youtube'   => 'YouTube',
    'twitter'   => 'Twitter / X',
    'snapchat'  => 'Snapchat',
    'other'     => 'Autres',
];

$type_icons = [
    'followers' => '👥',
    'likes'     => '❤️',
    'views'     => '👁️',
    'comments'  => '💬',
    'shares'    => '🔁',
    'other'     => '⭐',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= SITE_NAME ?> — Abonnés, Likes, Vues</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="logo">⚡ <?= SITE_NAME ?></div>
    <div class="nav-links">
        <a href="#services">Services</a>
        <a href="status.php">Suivre commande</a>
        <a href="https://wa.me/<?= SUPPORT_WHATSAPP ?>" target="_blank">💬 Support</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <h1>Boostez votre présence<br><span>sur les réseaux sociaux</span></h1>
    <p>Abonnés, likes, vues, commentaires — livraison rapide sur TikTok, Instagram, Facebook, YouTube et plus.</p>
</section>

<!-- STATS -->
<div class="stats">
    <div class="stat-item"><div class="number">5 000+</div><div class="label">Commandes livrées</div></div>
    <div class="stat-item"><div class="number">98%</div><div class="label">Satisfaction client</div></div>
    <div class="stat-item"><div class="number">24h/7</div><div class="label">Support disponible</div></div>
    <div class="stat-item"><div class="number">MTN & Orange</div><div class="label">Paiement Mobile Money</div></div>
</div>

<!-- FILTRES PLATEFORMES -->
<div class="platforms" id="platform-filters">
    <div class="platform-badge active" data-platform="all">🌐 Tous</div>
    <?php foreach (array_keys($by_platform) as $plat): ?>
    <div class="platform-badge" data-platform="<?= $plat ?>">
        <?= $platform_icons[$plat] ?? '🌐' ?> <?= $platform_labels[$plat] ?? ucfirst($plat) ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- SERVICES -->
<section class="section" id="services">
    <div class="section-title">Nos Services</div>

    <?php if (empty($services)): ?>
    <div class="alert error">
        Aucun service disponible pour l'instant. Veuillez réessayer plus tard.
    </div>
    <?php else: ?>
    <div class="services-grid" id="services-grid">
        <?php foreach ($services as $s):
            $price_500  = calculate_price_xaf((float)$s['rate_usd'], 500);
        ?>
        <div class="service-card" data-platform="<?= $s['platform'] ?>">
            <div class="platform-icon"><?= $type_icons[$s['service_type']] ?? '⭐' ?></div>
            <div style="font-size:0.75rem;color:var(--muted);margin-bottom:0.3rem">
                <?= $platform_icons[$s['platform']] ?? '🌐' ?> <?= htmlspecialchars($platform_labels[$s['platform']] ?? $s['platform']) ?>
            </div>
            <h3><?= htmlspecialchars($s['name']) ?></h3>
            <div class="service-cat">
                Min <?= number_format($s['min_qty']) ?> — Max <?= number_format($s['max_qty']) ?>
            </div>
            <div class="price-line">
                <div class="price">
                    <?= number_format($price_500) ?> XAF
                    <span>/ 500</span>
                </div>
                <button class="btn-order" onclick="openOrder(<?= htmlspecialchars(json_encode($s)) ?>)">
                    Commander
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- MODAL COMMANDE -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal()">×</button>
        <h2 id="modal-title">Passer une commande</h2>

        <!-- Étape 1 : Formulaire -->
        <div id="step-form">
            <form id="order-form">
                <input type="hidden" id="f-service-id">
                <input type="hidden" id="f-rate-usd">
                <input type="hidden" id="f-min-qty">
                <input type="hidden" id="f-max-qty">

                <div class="form-group">
                    <label>🔗 Lien de votre profil / publication</label>
                    <input type="url" id="f-url" placeholder="https://www.tiktok.com/@votrenom" required>
                </div>

                <div class="form-group">
                    <label>📊 Quantité</label>
                    <input type="number" id="f-quantity" placeholder="500" required>
                    <div class="qty-row" id="qty-chips"></div>
                </div>

                <div class="form-group">
                    <label>📞 Numéro MTN ou Orange Money (avec indicatif)</label>
                    <input type="tel" id="f-phone" placeholder="237690000000" required>
                </div>

                <div class="form-group">
                    <label>📧 Email (pour le suivi, optionnel)</label>
                    <input type="email" id="f-email" placeholder="votre@email.com">
                </div>

                <div class="price-summary">
                    <div class="row">
                        <span>Service</span>
                        <span id="sum-service">—</span>
                    </div>
                    <div class="row">
                        <span>Quantité</span>
                        <span id="sum-qty">—</span>
                    </div>
                    <div class="row total">
                        <span>Total à payer</span>
                        <span id="sum-price">— XAF</span>
                    </div>
                </div>

                <div id="form-error" class="alert error" style="display:none"></div>

                <button type="submit" class="btn-pay" id="btn-pay">
                    💳 Procéder au paiement
                </button>
            </form>
        </div>

        <!-- Étape 2 : Attente paiement -->
        <div id="step-payment" style="display:none">
            <div class="payment-instructions">
                <p>Montant à payer</p>
                <div class="amount-big" id="pay-amount"></div>
                <p style="margin-top:0.5rem">depuis le numéro <strong id="pay-phone"></strong></p>
            </div>
            <div class="alert success">
                ✅ Une demande de paiement a été envoyée sur votre téléphone.<br>
                Validez sur votre téléphone puis patientez.
            </div>
            <div style="text-align:center;color:var(--muted);font-size:0.9rem">
                <div class="loader"></div> Vérification en cours...
            </div>
            <p style="margin-top:1rem;font-size:0.85rem;color:var(--muted);text-align:center">
                Référence commande : <strong id="pay-ref"></strong>
            </p>
        </div>

        <!-- Étape 3 : Succès -->
        <div id="step-success" style="display:none;text-align:center">
            <div style="font-size:4rem">🎉</div>
            <h3 style="margin:1rem 0">Commande confirmée !</h3>
            <p style="color:var(--muted)">Votre commande est en cours de traitement.</p>
            <p style="margin:1rem 0;font-size:0.9rem">
                Référence : <strong id="success-ref" style="color:var(--accent)"></strong>
            </p>
            <a id="track-link" href="#" class="btn-pay" style="display:inline-block;text-decoration:none;margin-top:0.5rem">
                📦 Suivre ma commande
            </a>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <p><?= SITE_NAME ?> — Paiement sécurisé via MTN & Orange Money</p>
    <p style="margin-top:0.5rem">
        <a href="https://wa.me/<?= SUPPORT_WHATSAPP ?>" target="_blank">💬 Support WhatsApp</a> &nbsp;|&nbsp;
        <a href="status.php">Suivre une commande</a>
    </p>
</footer>

<script>
let currentService = null;

// Filtres plateforme
document.querySelectorAll('.platform-badge').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.platform-badge').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const plat = btn.dataset.platform;
        document.querySelectorAll('.service-card').forEach(card => {
            card.style.display = (plat === 'all' || card.dataset.platform === plat) ? '' : 'none';
        });
    });
});

function openOrder(service) {
    currentService = service;
    document.getElementById('modal-title').textContent = service.name;
    document.getElementById('f-service-id').value = service.peaksmm_id;
    document.getElementById('f-rate-usd').value   = service.rate_usd;
    document.getElementById('f-min-qty').value    = service.min_qty;
    document.getElementById('f-max-qty').value    = service.max_qty;
    document.getElementById('f-quantity').min     = service.min_qty;
    document.getElementById('f-quantity').max     = service.max_qty;
    document.getElementById('f-quantity').value   = service.min_qty;

    // Chips de quantités suggérées
    const chips = document.getElementById('qty-chips');
    chips.innerHTML = '';
    const suggestions = [100, 250, 500, 1000, 2000, 5000].filter(
        q => q >= service.min_qty && q <= service.max_qty
    ).slice(0, 5);
    suggestions.forEach(q => {
        const c = document.createElement('div');
        c.className = 'qty-chip';
        c.textContent = q.toLocaleString('fr');
        c.onclick = () => {
            document.querySelectorAll('.qty-chip').forEach(x => x.classList.remove('active'));
            c.classList.add('active');
            document.getElementById('f-quantity').value = q;
            updatePrice();
        };
        chips.appendChild(c);
    });

    // Reset steps
    document.getElementById('step-form').style.display = '';
    document.getElementById('step-payment').style.display = 'none';
    document.getElementById('step-success').style.display = 'none';
    document.getElementById('form-error').style.display = 'none';

    updatePrice();
    document.getElementById('modal').classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

function updatePrice() {
    const qty     = parseInt(document.getElementById('f-quantity').value) || 0;
    const rateUsd = parseFloat(document.getElementById('f-rate-usd').value) || 0;
    const name    = currentService ? currentService.name : '';

    const costUsd    = (rateUsd / 1000) * qty;
    const withMargin = costUsd * (1 + <?= MARGE_POURCENT ?> / 100);
    const xaf        = Math.ceil((withMargin * <?= USD_TO_XAF ?>) / 50) * 50;

    document.getElementById('sum-service').textContent = name.substring(0, 30);
    document.getElementById('sum-qty').textContent     = qty.toLocaleString('fr');
    document.getElementById('sum-price').textContent   = xaf.toLocaleString('fr') + ' XAF';
}

document.getElementById('f-quantity').addEventListener('input', updatePrice);

// Soumission commande
document.getElementById('order-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-pay');
    btn.disabled = true;
    btn.innerHTML = '<span class="loader"></span> Initialisation...';

    const data = {
        service_id: document.getElementById('f-service-id').value,
        url:        document.getElementById('f-url').value,
        quantity:   document.getElementById('f-quantity').value,
        phone:      document.getElementById('f-phone').value,
        email:      document.getElementById('f-email').value,
    };

    try {
        const res  = await fetch('order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
        });
        const json = await res.json();

        if (json.success) {
            document.getElementById('pay-amount').textContent = json.amount.toLocaleString('fr') + ' XAF';
            document.getElementById('pay-phone').textContent  = json.phone;
            document.getElementById('pay-ref').textContent    = json.order_ref;
            document.getElementById('step-form').style.display    = 'none';
            document.getElementById('step-payment').style.display = '';
            pollPayment(json.order_ref);
        } else {
            document.getElementById('form-error').textContent     = json.error || 'Erreur inconnue';
            document.getElementById('form-error').style.display   = '';
            btn.disabled = false;
            btn.innerHTML = '💳 Procéder au paiement';
        }
    } catch(err) {
        document.getElementById('form-error').textContent   = 'Erreur réseau, réessayez.';
        document.getElementById('form-error').style.display = '';
        btn.disabled = false;
        btn.innerHTML = '💳 Procéder au paiement';
    }
});

async function pollPayment(orderRef) {
    for (let i = 0; i < 30; i++) {
        await new Promise(r => setTimeout(r, 5000));
        try {
            const res  = await fetch('poll.php?ref=' + orderRef);
            const json = await res.json();
            if (json.status === 'paid' || json.status === 'processing' || json.status === 'completed') {
                document.getElementById('step-payment').style.display = 'none';
                document.getElementById('step-success').style.display = '';
                document.getElementById('success-ref').textContent    = orderRef;
                document.getElementById('track-link').href = 'status.php?ref=' + orderRef;
                return;
            }
            if (json.status === 'failed' || json.status === 'cancelled') {
                document.getElementById('step-payment').style.display = 'none';
                document.getElementById('step-form').style.display    = '';
                document.getElementById('form-error').textContent     = 'Paiement échoué ou annulé. Réessayez.';
                document.getElementById('form-error').style.display   = '';
                document.getElementById('btn-pay').disabled = false;
                document.getElementById('btn-pay').innerHTML = '💳 Procéder au paiement';
                return;
            }
        } catch(e) {}
    }
}

// Fermer modal en cliquant dehors
document.getElementById('modal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('modal')) closeModal();
});
</script>
</body>
</html>
