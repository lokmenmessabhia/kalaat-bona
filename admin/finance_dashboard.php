<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();
if (($admin['role'] ?? '') !== 'financier' && ($admin['role'] ?? '') !== 'main') {
    die('Accès refusé. Réservé au profil Financier.');
}

$pdo = db();

// ── Key Metrics ──────────────────────────────────────────
$totalRevenue    = (float)$pdo->query("SELECT COALESCE(SUM(montant),0) FROM paiement WHERE statut='Complété'")->fetchColumn();
$thisMonthRevenue = (float)$pdo->query("SELECT COALESCE(SUM(montant),0) FROM paiement WHERE statut='Complété' AND MONTH(date_paiement)=MONTH(CURRENT_DATE()) AND YEAR(date_paiement)=YEAR(CURRENT_DATE())")->fetchColumn();
$lastMonthRevenue = (float)$pdo->query("SELECT COALESCE(SUM(montant),0) FROM paiement WHERE statut='Complété' AND MONTH(date_paiement)=MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(date_paiement)=YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)")->fetchColumn();
$totalTx         = (int)$pdo->query("SELECT COUNT(*) FROM paiement WHERE statut='Complété'")->fetchColumn();
$avgPmt          = (float)$pdo->query("SELECT COALESCE(AVG(montant),0) FROM paiement WHERE statut='Complété'")->fetchColumn();

$totalInscriptions = (int)$pdo->query("SELECT COUNT(*) FROM inscription")->fetchColumn();
$paidInscriptions  = (int)$pdo->query("SELECT COUNT(DISTINCT id_inscription) FROM paiement WHERE id_inscription IS NOT NULL")->fetchColumn();
$collectionRate    = $totalInscriptions > 0 ? round($paidInscriptions / $totalInscriptions * 100, 1) : 0;
$pendingCount      = $totalInscriptions - $paidInscriptions;

// ── Revenue by Programme ─────────────────────────────────
$byProgramme = $pdo->query("
    SELECT pr.nom_programme, COUNT(p.id_paiement) AS nb, COALESCE(SUM(p.montant),0) AS total
    FROM programme pr
    LEFT JOIN inscription i ON i.id_programme = pr.id_programme
    LEFT JOIN paiement p ON p.id_inscription = i.id_inscription AND p.statut='Complété'
    GROUP BY pr.id_programme
    ORDER BY total DESC
")->fetchAll();

// ── Players with NO payment ──────────────────────────────
$unpaidPlayers = $pdo->query("
    SELECT j.id_joueur, j.prenom, j.nom, pr.nom_programme, i.date_inscription
    FROM joueur j
    JOIN inscription i ON i.id_joueur = j.id_joueur
    LEFT JOIN programme pr ON pr.id_programme = i.id_programme
    WHERE i.id_inscription NOT IN (SELECT DISTINCT id_inscription FROM paiement WHERE id_inscription IS NOT NULL)
    ORDER BY i.date_inscription DESC
    LIMIT 20
")->fetchAll();
$unpaidCount = count($unpaidPlayers);

// ── Monthly chart (6 months) ─────────────────────────────
$chartRaw = $pdo->query("
    SELECT DATE_FORMAT(date_paiement, '%Y-%m') AS mo, SUM(montant) AS val
    FROM paiement
    WHERE statut='Complété'
    GROUP BY mo ORDER BY mo DESC LIMIT 6
")->fetchAll(PDO::FETCH_KEY_PAIR);

$labels   = [];
$dataVals = [];
for ($i = 5; $i >= 0; $i--) {
    $m         = date('Y-m', strtotime("-$i months"));
    $labels[]  = date('M Y', strtotime("-$i months"));
    $dataVals[] = (float)($chartRaw[$m] ?? 0);
}

// ── Programme chart labels/values ────────────────────────
$progLabels = [];
$progVals   = [];
foreach ($byProgramme as $r) {
    $progLabels[] = $r['nom_programme'];
    $progVals[]   = (float)$r['total'];
}

// ── Recent transactions ──────────────────────────────────
$recent = $pdo->query("
    SELECT p.id_paiement, p.montant, p.date_paiement, i.id_joueur, j.prenom, j.nom
    FROM paiement p
    LEFT JOIN inscription i ON p.id_inscription = i.id_inscription
    LEFT JOIN joueur j ON i.id_joueur = j.id_joueur
    ORDER BY p.date_paiement DESC LIMIT 15
")->fetchAll();

$admin_page_title    = 'Dashboard Financier';
$admin_current_page  = 'finance_dashboard';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
    @media (max-width:900px) { .grid-2 { grid-template-columns: 1fr; } }
    .card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); padding: 24px; border: 1px solid #e2e8f0; }
    .card-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom: 14px; }
    .card-hd h2 { font-size: 0.9rem; color: #64748b; margin:0; text-transform:uppercase; letter-spacing:.3px; font-weight:700; }
    .kpi { font-size: 2rem; font-weight: 900; color: var(--c-navy); line-height:1.2; }
    .kpi.green { color: #10b981; }
    .kpi.orange { color: #f59e0b; }
    .kpi.sub { font-size: 1rem; font-weight:600; color:#64748b; }
    .kpi-label { font-size:0.82rem; color:#94a3b8; margin-top:4px; }
    .trend-up { color:#10b981; font-size:0.85rem; font-weight:700; }
    .trend-down { color:#ef4444; font-size:0.85rem; font-weight:700; }
    .chart-box { position:relative; height:260px; width:100%; margin-top:10px; }
    table { width:100%; border-collapse:collapse; margin-top:8px; }
    th, td { padding:10px 12px; border-bottom:1px solid #e2e8f0; text-align:left; font-size:0.9rem; }
    th { background:#f8fafc; color:var(--c-navy); font-weight:700; }
    td:last-child, th:last-child { text-align:right; }
    .muted { color:#6b7280; }
    .badge { display:inline-block; padding:3px 9px; border-radius:6px; font-size:0.78rem; font-weight:700; white-space:nowrap; }
    .badge-green { background:#d1fae5; color:#065f46; }
    .badge-yellow { background:#fef3c7; color:#92400e; }
    .badge-red { background:#fee2e2; color:#991b1b; }
    .badge-sky { background:#dbeafe; color:#1e40af; }
    .empty-state { text-align:center; padding:40px 20px; color:#94a3b8; }
    .empty-state i { font-size:2.5rem; margin-bottom:12px; opacity:.5; }
    .growth-indicator { display:inline-flex; align-items:center; gap:4px; }
    .progress-bar { height:6px; border-radius:99px; background:#e2e8f0; margin-top:8px; overflow:hidden; }
    .progress-fill { height:100%; border-radius:99px; background:var(--c-sky); }
</style>

<div class="grid">
    <div class="card">
        <div class="card-hd"><h2>Revenu Total</h2><i class="fa-solid fa-wallet" style="color:var(--c-sky);"></i></div>
        <div class="kpi"><?php echo number_format($totalRevenue, 2, ',', ' '); ?> <span class="kpi-label">DZD</span></div>
    </div>
    <div class="card">
        <div class="card-hd"><h2>Revenu ce mois</h2><i class="fa-solid fa-arrow-trend-up" style="color:#10b981;"></i></div>
        <div class="kpi green"><?php echo number_format($thisMonthRevenue, 2, ',', ' '); ?> <span class="kpi-label">DZD</span></div>
        <?php if ($lastMonthRevenue > 0): $diff = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100; ?>
        <div class="<?php echo $diff >= 0 ? 'trend-up' : 'trend-down'; ?> growth-indicator" style="margin-top:6px;">
            <i class="fa-solid fa-<?php echo $diff >= 0 ? 'caret-up' : 'caret-down'; ?>"></i>
            <?php echo number_format(abs($diff), 1); ?>% vs mois dernier
        </div>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-hd"><h2>Taux de recouvrement</h2><i class="fa-solid fa-percentage" style="color:#10b981;"></i></div>
        <div class="kpi green"><?php echo $collectionRate; ?>%</div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $collectionRate; ?>%"></div></div>
        <div style="display:flex;justify-content:space-between;margin-top:10px;font-size:0.82rem;color:#64748b;">
            <span><?php echo $paidInscriptions; ?> payés</span>
            <span><?php echo $pendingCount; ?> en attente</span>
        </div>
    </div>
    <div class="card">
        <div class="card-hd"><h2>Transactions</h2><i class="fa-solid fa-receipt" style="color:#64748b;"></i></div>
        <div class="kpi"><?php echo $totalTx; ?></div>
        <div class="kpi-label">Moy. <?php echo number_format($avgPmt, 0, ',', ' '); ?> DZD / transaction</div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-hd"><h2>Revenus (6 derniers mois)</h2></div>
        <div class="chart-box"><canvas id="revenueChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-hd"><h2>Revenus par Programme</h2></div>
        <div class="chart-box"><canvas id="programChart"></canvas></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-hd">
            <h2><i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;margin-right:6px;"></i>Joueurs sans paiement</h2>
            <?php if ($unpaidCount): ?><span class="badge badge-yellow"><?php echo $unpaidCount; ?></span><?php endif; ?>
        </div>
        <?php if ($unpaidCount): ?>
        <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>Joueur</th><th>Programme</th><th>Inscrit le</th></tr></thead>
                <tbody>
                    <?php foreach ($unpaidPlayers as $u): ?>
                    <tr>
                        <td><a href="<?php echo url_path('/admin/joueur_profil?id=' . $u['id_joueur']); ?>" style="color:var(--c-navy);font-weight:600;text-decoration:none;"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></a></td>
                        <td class="muted"><?php echo htmlspecialchars((string)($u['nom_programme'] ?? '-')); ?></td>
                        <td class="muted"><?php echo htmlspecialchars((string)$u['date_inscription']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-circle-check" style="color:#10b981;"></i>
            <p>Tous les inscrits ont un paiement enregistré.</p>
        </div>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-hd"><h2>Transactions Récentes</h2></div>
        <div style="overflow-x:auto;max-height:400px;overflow-y:auto;">
            <table>
                <thead><tr><th>#</th><th>Date</th><th>Montant</th><th>Joueur</th></tr></thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                    <tr>
                        <td class="muted">#<?php echo str_pad((string)$r['id_paiement'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td class="muted"><?php echo htmlspecialchars((string)$r['date_paiement']); ?></td>
                        <td style="font-weight:700;color:var(--c-navy);"><?php echo number_format((float)$r['montant'], 2, ',', ' '); ?> <span class="muted" style="font-weight:400;">DZD</span></td>
                        <td class="muted"><?php echo $r['id_joueur'] ? htmlspecialchars($r['prenom'] . ' ' . $r['nom']) : '<em>Générique</em>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const navy = '#001838', sky = '#6caddf';

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Revenus (DZD)',
            data: <?php echo json_encode($dataVals); ?>,
            backgroundColor: sky + '33',
            borderColor: sky,
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() + ' DZD' } } }
    }
});

new Chart(document.getElementById('programChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($progLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($progVals); ?>,
            backgroundColor: ['#001838','#1e3a6e','#2d5a8e','#4a7fb5','#6caddf','#8fc5ec','#b0d9f4'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
