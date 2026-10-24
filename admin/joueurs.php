<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/settings.php';
$s = get_settings();

require_admin();
$admin = admin_current();
$pdo = db();

$filter = $_GET['age'] ?? 'all';
if (!in_array($filter, ['all', 'senior', 'junior'], true)) $filter = 'all';

$ageCondition = '';
if ($filter === 'senior') {
    $ageCondition = 'AND TIMESTAMPDIFF(YEAR, j.date_naissance, CURDATE()) >= 18';
} elseif ($filter === 'junior') {
    $ageCondition = 'AND TIMESTAMPDIFF(YEAR, j.date_naissance, CURDATE()) < 18';
}

$joueurs = $pdo->query("
    SELECT DISTINCT j.id_joueur, j.prenom, j.nom, j.date_naissance, j.sexe, j.photo, j.email, j.telephone, j.taille, j.poids, j.maladie_chronique,
           TIMESTAMPDIFF(YEAR, j.date_naissance, CURDATE()) AS age,
           p.nom AS nom_parent, pf.poste 
    FROM joueur j 
    LEFT JOIN parent p ON j.id_parent = p.id_parent 
    LEFT JOIN profil_football pf ON j.id_joueur = pf.id_joueur 
    JOIN inscription i ON i.id_joueur = j.id_joueur AND i.statut = 'acceptee'
    WHERE 1=1 $ageCondition
    ORDER BY j.nom, j.prenom
")->fetchAll();

$counts = [];
$all = $pdo->query("SELECT DISTINCT j.id_joueur, TIMESTAMPDIFF(YEAR, j.date_naissance, CURDATE()) AS age FROM joueur j JOIN inscription i ON i.id_joueur = j.id_joueur AND i.statut = 'acceptee'")->fetchAll();
$counts['all'] = count($all);
$counts['senior'] = count(array_filter($all, fn($r) => (int)$r['age'] >= 18));
$counts['junior'] = count(array_filter($all, fn($r) => (int)$r['age'] < 18));

$admin_page_title = 'Joueurs';
$admin_current_page = 'joueurs';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); overflow:hidden; }
    .card .hd { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; display:flex; align-items:center; justify-content: space-between; }
    .card .hd h2 { font-size: 1.2rem; color: var(--c-navy); }
    .card .bd { padding: 0; overflow-x: auto;}
    
    table { width: 100%; border-collapse: collapse; min-width: 800px; }
    th, td { padding: 16px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 0.95rem; }
    th { background: #f8fafc; color: var(--c-navy); font-weight: 700; }
    tr:hover { background: rgba(0,0,0,0.01); }
    
    .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #e2e8f0; border: 2px solid var(--c-sky); }
    .player-info { display: flex; align-items: center; gap: 12px; }
    .badge { display:inline-block; padding: 5px 10px; border-radius: 999px; background: rgba(108,173,223,0.10); color: var(--c-navy); font-weight: 800; font-size: 0.82rem; }
    .action-link { text-decoration: none; color: #fff; background: var(--c-navy); padding: 8px 16px; border-radius: 8px; font-weight: bold; font-size: 0.9rem; transition: 0.2s; display:inline-flex; align-items:center; gap:6px;}
    .action-link:hover { background: var(--c-navy-soft); box-shadow: 0 4px 10px rgba(0,24,56,0.3); }
    .muted { color: #64748b; font-size: 0.85rem; }
</style>
        <div class="card">
            <div class="hd">
                <h2>Répertoire des Joueurs</h2>
                <div class="muted"><?php echo count($joueurs); ?> joueur(s)</div>
            </div>
            <div style="padding:12px 16px; border-bottom:1px solid #e2e8f0; display:flex; gap:4px;">
                <a href="?age=all" style="padding:7px 16px; border-radius:8px; font-weight:700; font-size:0.82rem; text-decoration:none; transition:all .2s; <?php echo $filter==='all' ? 'background:var(--c-navy);color:#fff;' : 'color:#64748b;background:#f1f5f9;'; ?>">Tous <span style="opacity:0.7;">(<?php echo $counts['all']; ?>)</span></a>
                <a href="?age=senior" style="padding:7px 16px; border-radius:8px; font-weight:700; font-size:0.82rem; text-decoration:none; transition:all .2s; <?php echo $filter==='senior' ? 'background:var(--c-navy);color:#fff;' : 'color:#64748b;background:#f1f5f9;'; ?>">Seniors (18+) <span style="opacity:0.7;">(<?php echo $counts['senior']; ?>)</span></a>
                <a href="?age=junior" style="padding:7px 16px; border-radius:8px; font-weight:700; font-size:0.82rem; text-decoration:none; transition:all .2s; <?php echo $filter==='junior' ? 'background:var(--c-navy);color:#fff;' : 'color:#64748b;background:#f1f5f9;'; ?>">Juniors (&lt;18) <span style="opacity:0.7;">(<?php echo $counts['junior']; ?>)</span></a>
            </div>
            <div class="bd">
                <table>
                    <thead>
                        <tr>
                            <th>Joueur</th>
                            <th>Contact</th>
                            <th>Âge</th>
                            <th>Poste</th>
                            <th>Physique</th>
                            <th>Parent/Tuteur</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$joueurs): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 30px;" class="muted">Aucun joueur trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach($joueurs as $j): ?>
                                <tr>
                                    <td>
                                        <div class="player-info">
                                            <?php if(!empty($j['photo'])): ?>
                                                <img src="<?php echo htmlspecialchars(url_path('/uploads/'.$j['photo']), ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Photo">
                                            <?php else: ?>
                                                <div class="avatar" style="display:flex; align-items:center; justify-content:center; color: #ccc; font-weight:bold;">?</div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($j['nom'] . ' ' . $j['prenom'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <div class="muted">ID: #<?php echo (int)$j['id_joueur']; ?> - Sexe: <?php echo htmlspecialchars((string)$j['sexe'], ENT_QUOTES, 'UTF-8'); ?><?php if(!empty($j['maladie_chronique'])): ?> <span style="color:#ef4444;font-weight:700;" title="<?php echo htmlspecialchars($j['maladie_chronique'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-triangle-exclamation"></i></span><?php endif; ?></div>
                                            </div>
                                        </div>
                                    <td>
                                        <div class="muted"><?php echo htmlspecialchars((string)$j['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="muted"><?php echo htmlspecialchars((string)$j['telephone'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo isset($j['age']) ? (int)$j['age'].' ans' : htmlspecialchars((string)$j['date_naissance'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if(!empty($j['poste'])): ?>
                                            <span class="badge"><?php echo htmlspecialchars($j['poste'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="muted"><?php
                                            $parts = [];
                                            if (!empty($j['taille'])) $parts[] = (int)$j['taille'] . ' cm';
                                            if (!empty($j['poids'])) $parts[] = (int)$j['poids'] . ' kg';
                                            echo htmlspecialchars(implode(' / ', $parts) ?: '-', ENT_QUOTES, 'UTF-8');
                                        ?></span>
                                    </td>
                                    <td>
                                        <?php if(!empty($j['nom_parent'])): ?>
                                            <strong><?php echo htmlspecialchars($j['nom_parent'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php else: ?>
                                            <span class="muted">Non assigné</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="<?php echo htmlspecialchars(url_path('/admin/joueur_profil.php?id='.(int)$j['id_joueur']), ENT_QUOTES, 'UTF-8'); ?>" class="action-link">Profil / Suivi</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
