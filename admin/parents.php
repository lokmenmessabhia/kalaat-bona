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

$parents = $pdo->query('
    SELECT p.id_parent, p.nom, p.telephone, p.email, COUNT(j.id_joueur) as nb_enfants
    FROM parent p
    LEFT JOIN joueur j ON p.id_parent = j.id_parent
    GROUP BY p.id_parent
    ORDER BY p.nom
')->fetchAll();

$admin_page_title = 'Parents';
$admin_current_page = 'parents';
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
    
    .badge { display:inline-block; padding: 5px 10px; border-radius: 999px; background: rgba(108,173,223,0.10); color: var(--c-navy); font-weight: 800; font-size: 0.82rem; }
    .action-link { text-decoration: none; color: #fff; background: var(--c-navy); padding: 8px 16px; border-radius: 8px; font-weight: bold; font-size: 0.9rem; transition: 0.2s; display:inline-flex; align-items:center; gap:6px;}
    .action-link:hover { background: var(--c-navy-soft); box-shadow: 0 4px 10px rgba(0,24,56,0.3); }
    .muted { color: #64748b; font-size: 0.85rem; }
</style>
        <div class="card">
            <div class="hd">
                <h2>Répertoire des Parents / Tuteurs</h2>
                <div class="muted"><?php echo count($parents); ?> parents au total</div>
            </div>
            <div class="bd">
                <table>
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Contact</th>
                            <th>Enfants inscrits</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$parents): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 30px;" class="muted">Aucun parent trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach($parents as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string)$p['nom'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="muted">ID: #<?php echo (int)$p['id_parent']; ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars((string)$p['telephone'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="muted"><?php echo htmlspecialchars((string)$p['email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge"><?php echo (int)$p['nb_enfants']; ?> enfant(s)</span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="<?php echo htmlspecialchars(url_path('/admin/parent_profil.php?id='.(int)$p['id_parent']), ENT_QUOTES, 'UTF-8'); ?>" class="action-link">Profil et Famille</a>
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
