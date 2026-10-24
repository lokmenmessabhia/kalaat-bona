<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();

$pdo = db();
$counts = [
    'joueurs' => (int)$pdo->query('SELECT COUNT(*) AS c FROM joueur')->fetch()['c'],
    'parents' => (int)$pdo->query('SELECT COUNT(*) AS c FROM parent')->fetch()['c'],
    'inscriptions' => (int)$pdo->query('SELECT COUNT(*) AS c FROM inscription')->fetch()['c'],
    'contacts' => 0,
];

$recentContacts = [];
$hasContactSite = (bool)$pdo->query("SHOW TABLES LIKE 'contact_site'")->fetch();
if ($hasContactSite) {
    $counts['contacts'] = (int)$pdo->query('SELECT COUNT(*) AS c FROM contact_site')->fetch()['c'];
    $recentContacts = $pdo->query(
        'SELECT id_message, nom_complet, email, telephone, message, date_envoi FROM contact_site ORDER BY date_envoi DESC LIMIT 15'
    )->fetchAll();
}

$recent = $pdo->query(
    'SELECT i.id_inscription, i.date_inscription, p.nom_programme,
            j.id_joueur, j.prenom, j.nom, j.sexe, j.date_naissance,
            j.telephone, j.email
     FROM inscription i
     JOIN joueur j ON j.id_joueur = i.id_joueur
     JOIN programme p ON p.id_programme = i.id_programme
     ORDER BY i.date_inscription DESC
     LIMIT 20'
 )->fetchAll();

 $admin_page_title = 'Dashboard Admin';
 $admin_current_page = 'dashboard';
 require_once __DIR__ . '/../lib/admin_header.php';
 ?>
<style>
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 16px; }
    .card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); overflow:hidden; border: 1px solid #e2e8f0; }
    .card .hd { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; display:flex; align-items:center; justify-content: space-between; }
    .card .hd h2 { font-size: 1rem; color: var(--c-navy); }
    .card .bd { padding: 16px; }
    .kpi { font-size: 2rem; font-weight: 900; color: var(--c-navy); }
    .sub { color: #64748b; margin-top: 4px; font-size: 0.9rem; }
    .accent { width: 10px; height: 10px; border-radius: 50%; background: var(--c-sky); box-shadow: 0 0 0 4px rgba(108,173,223,0.18); }
    .table-card { margin-top: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.92rem; }
    th { background: #f8fafc; color: var(--c-navy); font-weight: 700; position: sticky; top: 0; }
    .muted { color: #64748b; }
    .badge { display:inline-block; padding: 5px 10px; border-radius: 999px; background: rgba(108,173,223,0.10); color: var(--c-navy); border: 1px solid rgba(108,173,223,0.15); font-weight: 800; font-size: 0.82rem; }
</style>
        <div class="grid">
            <div class="card">
                <div class="hd"><h2>Joueurs</h2><span class="accent"></span></div>
                <div class="bd">
                    <div class="kpi"><?php echo (int)$counts['joueurs']; ?></div>
                    <div class="sub">Total joueurs enregistrés</div>
                </div>
            </div>
            <div class="card">
                <div class="hd"><h2>Parents</h2><span class="accent"></span></div>
                <div class="bd">
                    <div class="kpi"><?php echo (int)$counts['parents']; ?></div>
                    <div class="sub">Total parents/tuteurs</div>
                </div>
            </div>
            <div class="card">
                <div class="hd"><h2>Inscriptions</h2><span class="accent"></span></div>
                <div class="bd">
                    <div class="kpi"><?php echo (int)$counts['inscriptions']; ?></div>
                    <div class="sub">Total inscriptions (programme)</div>
                </div>
            </div>
            <div class="card">
                <div class="hd"><h2>Messages site</h2><span class="accent"></span></div>
                <div class="bd">
                    <div class="kpi"><?php echo (int)$counts['contacts']; ?></div>
                    <div class="sub"><?php echo $hasContactSite ? 'Formulaire accueil (contact)' : 'Importez database/contact_site.sql'; ?></div>
                </div>
            </div>
        </div>

        <div class="card table-card">
            <div class="hd">
                <h2>Dernières inscriptions</h2>
                <div class="muted">20 dernières</div>
            </div>
            <div class="bd" style="padding:0; overflow:auto; max-height: 520px;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Joueur</th>
                            <th>Programme</th>
                            <th>Contact</th>
                            <th>Sexe</th>
                            <th>Naissance</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="6" class="muted" style="padding:16px;">Aucune inscription pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent as $r): ?>
                            <tr>
                                <td class="muted"><?php echo htmlspecialchars((string)$r['date_inscription'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <strong>#<?php echo (int)$r['id_joueur']; ?></strong>
                                    <?php echo htmlspecialchars($r['prenom'] . ' ' . $r['nom'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td><span class="badge"><?php echo htmlspecialchars((string)$r['nom_programme'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="muted">
                                    <?php echo htmlspecialchars((string)($r['telephone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($r['email'])): ?>
                                        <div><?php echo htmlspecialchars((string)$r['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="muted"><?php echo htmlspecialchars((string)($r['sexe'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="muted"><?php echo htmlspecialchars((string)($r['date_naissance'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($hasContactSite): ?>
        <div class="card table-card" id="messages-site">
            <div class="hd">
                <h2>Messages « Contactez-nous »</h2>
                <div class="muted">15 derniers</div>
            </div>
            <div class="bd" style="padding:0; overflow:auto; max-height: 420px;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recentContacts): ?>
                        <tr><td colspan="5" class="muted" style="padding:16px;">Aucun message.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentContacts as $c): ?>
                            <tr>
                                <td class="muted"><?php echo htmlspecialchars((string)$c['date_envoi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$c['nom_complet'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="muted"><?php echo htmlspecialchars((string)$c['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="muted"><?php echo htmlspecialchars((string)$c['telephone'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="muted" style="max-width:280px;"><?php echo nl2br(htmlspecialchars((string)$c['message'], ENT_QUOTES, 'UTF-8')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>

