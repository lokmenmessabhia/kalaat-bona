<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();
$pdo = db();

$role = $admin['role'] ?? 'admin';
$isCoach = $role === 'coach';
$adminId = (int)$admin['id_admin'];

$pdo->exec("CREATE TABLE IF NOT EXISTS demande_changement (
    id_demande INT AUTO_INCREMENT PRIMARY KEY,
    id_joueur INT NOT NULL,
    poste_actuel VARCHAR(50) DEFAULT NULL,
    poste_souhaite VARCHAR(100) NOT NULL,
    motif TEXT DEFAULT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut VARCHAR(20) DEFAULT 'en_attente',
    FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$error = null;
$success = null;

// ── Handle actions (POST only for state changes) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action === 'accept' && $id) {
    try {
        $req = $pdo->prepare('SELECT dc.*, j.id_joueur FROM demande_changement dc JOIN joueur j ON dc.id_joueur = j.id_joueur WHERE dc.id_demande = ?');
        $req->execute([$id]);
        $demande = $req->fetch();
        if ($demande) {
            $pdo->beginTransaction();
            $pf = $pdo->prepare('SELECT id_profil FROM profil_football WHERE id_joueur = ?');
            $pf->execute([$demande['id_joueur']]);
            if ($pf->fetch()) {
                $pdo->prepare('UPDATE profil_football SET poste = ? WHERE id_joueur = ?')->execute([$demande['poste_souhaite'], $demande['id_joueur']]);
            } else {
                $pdo->prepare('INSERT INTO profil_football (id_joueur, poste) VALUES (?, ?)')->execute([$demande['id_joueur'], $demande['poste_souhaite']]);
            }
            $pdo->prepare('UPDATE demande_changement SET statut = ? WHERE id_demande = ?')->execute(['acceptee', $id]);
            $pdo->commit();
            $success = "Demande acceptée — le poste du joueur a été mis à jour.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur : " . $e->getMessage();
    }
}

if ($action === 'reject' && $id) {
    $pdo->prepare('UPDATE demande_changement SET statut = ? WHERE id_demande = ?')->execute(['refusee', $id]);
    $success = "Demande refusée.";
}

if ($action === 'delete' && $id) {
    $pdo->prepare('DELETE FROM demande_changement WHERE id_demande = ?')->execute([$id]);
    $success = "Demande supprimée.";
}
}

// ── Fetch requests ───────────────────────────────────────────────
if ($isCoach) {
    $stmt = $pdo->prepare('
        SELECT dc.*, j.prenom, j.nom, j.photo, j.telephone
        FROM demande_changement dc
        JOIN joueur j ON dc.id_joueur = j.id_joueur
        JOIN inscription i ON i.id_joueur = j.id_joueur
        JOIN programme p ON i.id_programme = p.id_programme
        WHERE p.id_coach = ?
        GROUP BY dc.id_demande
        ORDER BY dc.date_demande DESC
    ');
    $stmt->execute([$adminId]);
} else {
    $stmt = $pdo->query('
        SELECT dc.*, j.prenom, j.nom, j.photo, j.telephone
        FROM demande_changement dc
        JOIN joueur j ON dc.id_joueur = j.id_joueur
        ORDER BY dc.date_demande DESC
    ');
}
$requests = $stmt->fetchAll();

$total = count($requests);
$pending = count(array_filter($requests, fn($r) => $r['statut'] === 'en_attente'));

$admin_page_title = 'Demandes des joueurs';
$admin_current_page = 'player_requests';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .stats-mini { display:flex; gap:14px; margin-bottom:16px; }
    .stats-mini .sm { background:white; border:1px solid var(--c-border); border-radius:12px; padding:14px 20px; flex:1; }
    .stats-mini .sm .num { font-size:1.4rem; font-weight:900; color:var(--c-navy); }
    .stats-mini .sm .lbl { font-size:0.78rem; color:var(--c-text-muted); font-weight:600; margin-top:2px; }
    .stats-mini .sm.pending .num { color:#f59e0b; }
    .stats-mini .sm.done .num { color:#10b981; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px 14px; border-bottom:1px solid var(--c-border); text-align:left; font-size:0.88rem; }
    th { background:var(--c-offwhite); color:var(--c-navy); font-weight:700; position:sticky; top:0; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.3px; }
    td { vertical-align:middle; }
    .player-cell { display:flex; align-items:center; gap:10px; }
    .player-cell img { width:34px; height:34px; border-radius:50%; object-fit:cover; background:var(--c-sky-pale); }
    .player-cell .no-avatar { width:34px; height:34px; border-radius:50%; background:var(--c-sky-pale); display:flex; align-items:center; justify-content:center; font-size:0.8rem; color:var(--c-sky); }
    .badge-status { display:inline-block; padding:4px 10px; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
    .badge-en_attente { background:#fef3c7; color:#92400e; }
    .badge-acceptee { background:#d1fae5; color:#065f46; }
    .badge-refusee { background:#fee2e2; color:#991b1b; }
    .actions-cell { display:flex; gap:6px; }
    .btn-act { padding:6px 14px; border-radius:8px; font-weight:700; font-size:0.78rem; border:none; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:all .2s; }
    .btn-accept { background:#10b981; color:white; }
    .btn-accept:hover { opacity:.85; }
    .btn-reject { background:#f59e0b; color:white; }
    .btn-reject:hover { opacity:.85; }
    .btn-delete { background:#ef4444; color:white; }
    .btn-delete:hover { opacity:.85; }
    .empty-state { text-align:center; padding:60px 20px; }
    .empty-state i { font-size:3rem; opacity:0.1; margin-bottom:16px; }
    .empty-state h3 { color:var(--c-navy); margin-bottom:6px; }
    .muted { color:var(--c-text-muted); }
    .motif-cell { max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--c-text-muted); }
    .table-wrap { overflow-x:auto; background:white; border-radius:12px; border:1px solid var(--c-border); box-shadow:var(--shadow-card); }
</style>

<div class="stats-mini">
    <div class="sm"><div class="num"><?php echo $total; ?></div><div class="lbl">Total</div></div>
    <div class="sm pending"><div class="num"><?php echo $pending; ?></div><div class="lbl">En attente</div></div>
    <div class="sm done"><div class="num"><?php echo $total - $pending; ?></div><div class="lbl">Traitées</div></div>
</div>

<?php if ($success): ?>
    <div style="padding:12px 16px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:10px;margin-bottom:16px;font-weight:600;font-size:0.85rem;display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div style="padding:12px 16px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:10px;margin-bottom:16px;font-weight:600;font-size:0.85rem;display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="table-wrap">
    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <h3>Aucune demande</h3>
            <p class="muted">Les joueurs peuvent demander un changement de poste depuis leur profil.</p>
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Joueur</th>
                <th>Poste actuel</th>
                <th>Poste souhaité</th>
                <th>Motif</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($requests as $r): ?>
            <tr>
                <td>
                    <div class="player-cell">
                        <?php if(!empty($r['photo'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($r['photo']); ?>" alt="">
                        <?php else: ?>
                            <div class="no-avatar"><i class="fa-solid fa-futbol"></i></div>
                        <?php endif; ?>
                        <strong><?php echo htmlspecialchars($r['prenom'] . ' ' . $r['nom']); ?></strong>
                    </div>
                </td>
                <td class="muted"><?php echo htmlspecialchars($r['poste_actuel'] ?? '—'); ?></td>
                <td><strong><?php echo htmlspecialchars($r['poste_souhaite']); ?></strong></td>
                <td><div class="motif-cell" title="<?php echo htmlspecialchars($r['motif'] ?? ''); ?>"><?php echo htmlspecialchars($r['motif'] ?? '—'); ?></div></td>
                <td class="muted" style="white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($r['date_demande'])); ?></td>
                <td><span class="badge-status badge-<?php echo $r['statut']; ?>"><?php echo $r['statut'] === 'en_attente' ? 'En attente' : ($r['statut'] === 'acceptee' ? 'Acceptée' : 'Refusée'); ?></span></td>
                <td>
                    <?php if ($r['statut'] === 'en_attente'): ?>
                    <div class="actions-cell">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Accepter cette demande ? Le poste du joueur sera mis à jour.');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="accept">
                            <input type="hidden" name="id" value="<?php echo $r['id_demande']; ?>">
                            <button type="submit" class="btn-act btn-accept"><i class="fa-solid fa-check"></i> Accepter</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Refuser cette demande ?');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="id" value="<?php echo $r['id_demande']; ?>">
                            <button type="submit" class="btn-act btn-reject"><i class="fa-solid fa-xmark"></i> Refuser</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette demande ?');">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $r['id_demande']; ?>">
                        <button type="submit" class="btn-act btn-delete"><i class="fa-solid fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
