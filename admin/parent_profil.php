<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';

require_admin();
$admin = admin_current();
$pdo = db();

$id_parent = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM parent WHERE id_parent = ?');
$stmt->execute([$id_parent]);
$parent = $stmt->fetch();

if (!$parent) {
    die("Parent introuvable.");
}

$enfants = $pdo->prepare('
    SELECT j.*, pf.poste 
    FROM joueur j 
    LEFT JOIN profil_football pf ON j.id_joueur = pf.id_joueur 
    WHERE j.id_parent = ? 
    ORDER BY j.date_naissance DESC
');
$enfants->execute([$id_parent]);
$enfants_list = $enfants->fetchAll();

$admin_page_title = 'Profil Parent';
$admin_current_page = 'parents';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .wrap { max-width: 1000px; margin: 0; }
    
    .card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); overflow:hidden; margin-bottom: 20px;}
    .card .hd { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display:flex; align-items:center; justify-content: space-between; }
    .card .hd h2 { font-size: 1.15rem; color: var(--c-navy); }
    .card .bd { padding: 20px; }
    
    .profile-name { font-size: 1.8rem; font-weight: 900; margin-bottom: 10px; color: var(--c-navy); }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .info-item { display: flex; flex-direction: column; gap: 4px; }
    .info-lbl { font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
    .info-val { font-size: 1rem; font-weight: 600; }

    .child-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-top: 10px; }
    .child-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display:flex; align-items:center; justify-content:space-between; transition: 0.2s;}
    .child-card:hover { border-color: var(--c-sky); box-shadow: 0 5px 15px rgba(108,173,223,0.1); transform: translateY(-3px);}
    .child-info { display:flex; align-items:center; gap: 12px; }
    .avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; background: #e2e8f0; border: 2px solid var(--c-sky); display:flex; align-items:center; justify-content:center; color:#94a3b8; font-weight:bold;}
    .child-name { font-weight: 800; font-size: 1.1rem; color: var(--c-text); text-decoration: none;}
    .badge { display:inline-block; padding: 3px 8px; border-radius: 999px; background: rgba(108,173,223,0.10); color: var(--c-navy); font-weight: 800; font-size: 0.75rem; margin-top: 4px; }
    
    .btn { background: var(--c-navy); color: #fff; text-decoration:none; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: bold; display:inline-flex; align-items:center; gap:6px;}
    .btn:hover { background: var(--c-navy-soft); box-shadow: 0 4px 10px rgba(0,24,56,0.3); }
</style>
        <div class="card">
            <div class="hd"><h2>Informations du Parent / Tuteur</h2></div>
            <div class="bd">
                <div class="profile-name"><?php echo htmlspecialchars((string)$parent['nom'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-lbl">ID Système</span>
                        <span class="info-val">#<?php echo (int)$parent['id_parent']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-lbl">Téléphone principal</span>
                        <span class="info-val"><?php echo htmlspecialchars((string)($parent['telephone'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-lbl">Email</span>
                        <span class="info-val"><?php echo htmlspecialchars((string)($parent['email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="hd">
                <h2>Enfants inscrits (Joueurs)</h2>
                <div style="font-weight:bold; color:var(--burgundy);"><?php echo count($enfants_list); ?></div>
            </div>
            <div class="bd">
                <?php if(count($enfants_list) === 0): ?>
                    <div style="text-align:center; padding: 20px; color:var(--muted);">Aucun enfant associé à ce profil parent.</div>
                <?php else: ?>
                    <div class="child-grid">
                        <?php foreach($enfants_list as $e): ?>
                            <div class="child-card">
                                <div class="child-info">
                                    <?php if(!empty($e['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars(url_path('/uploads/'.$e['photo']), ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Photo">
                                    <?php else: ?>
                                        <div class="avatar">?</div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <a href="<?php echo htmlspecialchars(url_path('/admin/joueur_profil.php?id='.(int)$e['id_joueur']), ENT_QUOTES, 'UTF-8'); ?>" class="child-name">
                                            <?php echo htmlspecialchars($e['prenom'] . ' ' . $e['nom'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <div>
                                            <?php if(!empty($e['poste'])): ?>
                                                <span class="badge"><?php echo htmlspecialchars($e['poste'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?php echo htmlspecialchars(url_path('/admin/joueur_profil.php?id='.(int)$e['id_joueur']), ENT_QUOTES, 'UTF-8'); ?>" class="btn">Suivi</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
