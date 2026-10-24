<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';
$s = get_settings();

session_start();
if (!isset($_SESSION['joueur_id'])) {
    header('Location: ' . url_path('/portal_login?type=joueur'));
    exit;
}

$id_joueur = (int)$_SESSION['joueur_id'];
$pdo = db();

$stmt = $pdo->prepare('
    SELECT j.*, pf.poste, pf.niveau, pf.experience, pf.club_precedent
    FROM joueur j 
    LEFT JOIN profil_football pf ON j.id_joueur = pf.id_joueur 
    WHERE j.id_joueur = ? 
');
$stmt->execute([$id_joueur]);
$joueur = $stmt->fetch();

if (!$joueur) {
    session_destroy();
    header('Location: ' . url_path('/portal_login?type=joueur'));
    exit;
}

$progs = $pdo->prepare('SELECT p.nom_programme FROM inscription i JOIN programme p ON i.id_programme = p.id_programme WHERE i.id_joueur = ? AND i.statut = ?');
$progs->execute([$id_joueur, 'acceptee']);
$programmes = $progs->fetchAll();

$suivisQuery = $pdo->prepare('SELECT * FROM suivi WHERE id_joueur = ? ORDER BY date_suivi DESC');
$suivisQuery->execute([$id_joueur]);
$suivis = $suivisQuery->fetchAll();

$present = count(array_filter($suivis, fn($s) => ($s['presence']??'')==='Présent'));
$absent  = count(array_filter($suivis, fn($s) => ($s['presence']??'')==='Absent'));
$blesse  = count(array_filter($suivis, fn($s) => ($s['presence']??'')==='Blessé'));
$maladie = count(array_filter($suivis, fn($s) => ($s['presence']??'')==='Maladie'));
$total   = count($suivis);
$presPct = $total > 0 ? round($present / $total * 100) : 0;

$age = $joueur['date_naissance']
    ? (int)(new DateTime())->diff(new DateTime($joueur['date_naissance']))->y
    : null;

$dpStmt = $pdo->prepare('SELECT * FROM demande_changement WHERE id_joueur = ? ORDER BY date_demande DESC LIMIT 5');
$dpStmt->execute([$id_joueur]);
$demandes = $dpStmt->fetchAll();
$hasPending = false;
foreach ($demandes as $d) { if ($d['statut'] === 'en_attente') { $hasPending = true; break; } }

$page_title = 'Mon Espace — ' . htmlspecialchars((string)$s['site_name']);
$hide_footer = true;
$extra_styles = <<<CSS
.dash-wrap { max-width:1100px; margin:0 auto; padding:24px; }

/* ── TOP BAR ────────────────────────────── */
.top-bar {
    display:flex; align-items:center; justify-content:space-between;
    background:white; border:1px solid var(--c-border);
    border-radius:var(--r-xl); padding:14px 20px;
    box-shadow:var(--shadow-card); margin-bottom:24px;
}
.top-bar-left { display:flex; align-items:center; gap:14px; }
.top-bar-avatar {
    width:44px; height:44px; border-radius:50%; object-fit:cover;
    border:2px solid var(--c-sky);
    background:var(--c-sky-pale); display:flex;
    align-items:center; justify-content:center; font-size:1.2rem; color:var(--c-sky); flex-shrink:0;
}
.top-bar-info .tbi-name { font-weight:800; color:var(--c-navy); font-size:1rem; }
.top-bar-info .tbi-role { font-size:0.78rem; color:var(--c-text-muted); }
.top-bar-actions { display:flex; gap:8px; }
.top-bar-btn {
    padding:8px 16px; border-radius:var(--r-md); font-weight:700; font-size:0.82rem;
    text-decoration:none; transition:all var(--t-base); display:flex; align-items:center; gap:6px;
}
.top-bar-btn-primary { background:var(--c-navy); color:white; }
.top-bar-btn-primary:hover { background:var(--c-navy-soft); transform:translateY(-1px); }
.top-bar-btn-outline { color:var(--c-text-muted); border:1px solid var(--c-border); background:transparent; }
.top-bar-btn-outline:hover { border-color:var(--c-sky); color:var(--c-navy); }

/* ── GREETING BANNER ────────────────────── */
.greeting {
    text-align:center; padding:28px 20px 0; margin-bottom:20px;
}
.greeting h1 { font-size:1.6rem; font-weight:900; color:var(--c-navy); }
.greeting p { color:var(--c-text-muted); font-size:0.9rem; margin-top:4px; }

/* ── STATS ROW ──────────────────────────── */
.stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:28px; }
@media(max-width:700px){ .stats-row{grid-template-columns:repeat(2,1fr);} }
.stat-card {
    background:white; border:1px solid var(--c-border);
    border-radius:var(--r-lg); padding:18px 16px;
    text-align:center; box-shadow:var(--shadow-card);
    transition:all var(--t-base);
}
.stat-card:hover { box-shadow:var(--shadow-hover); transform:translateY(-2px); }
.stat-card .stat-icon { font-size:1.3rem; margin-bottom:6px; }
.stat-card .stat-num { font-size:1.6rem; font-weight:900; color:var(--c-navy); line-height:1.1; }
.stat-card .stat-label { font-size:0.75rem; color:var(--c-text-muted); font-weight:600; margin-top:4px; text-transform:uppercase; letter-spacing:0.3px; }
.stat-bar { height:4px; background:var(--c-border); border-radius:99px; overflow:hidden; margin-top:8px; }
.stat-bar-fill { height:100%; background:linear-gradient(90deg,var(--c-sky),#3bd6ff); border-radius:99px; transition:width 1s ease; }

/* ── CONTENT GRID ───────────────────────── */
.content-grid { display:grid; grid-template-columns:280px 1fr; gap:24px; }
@media(max-width:850px){ .content-grid{grid-template-columns:1fr;} }

/* ── SIDECARD ───────────────────────────── */
.side-card {
    background:white; border:1px solid var(--c-border);
    border-radius:var(--r-lg); box-shadow:var(--shadow-card); overflow:hidden; margin-bottom:16px;
}
.side-card-head {
    padding:14px 18px; border-bottom:1px solid var(--c-border);
    display:flex; align-items:center; gap:8px;
}
.side-card-head i { color:var(--c-sky); font-size:1rem; }
.side-card-head h3 { font-size:0.88rem; font-weight:700; color:var(--c-navy); }
.side-card-body { padding:16px 18px; }

.profile-card { text-align:center; padding:24px 18px 18px; }
.profile-card .pc-avatar {
    width:72px; height:72px; border-radius:50%; object-fit:cover;
    border:3px solid var(--c-sky); margin:0 auto 10px;
    background:var(--c-sky-pale); display:flex; align-items:center; justify-content:center; font-size:1.8rem; color:var(--c-sky);
}
.profile-card .pc-name { font-weight:800; color:var(--c-navy); font-size:1.1rem; }
.profile-card .pc-role { font-size:0.78rem; color:var(--c-text-muted); margin:2px 0 12px; }
.pc-details { display:flex; justify-content:center; gap:16px; flex-wrap:wrap; }
.pc-detail { text-align:center; }
.pc-detail .val { font-weight:800; color:var(--c-navy); font-size:0.9rem; }
.pc-detail .lbl { font-size:0.65rem; text-transform:uppercase; color:var(--c-text-muted); letter-spacing:0.5px; font-weight:600; }

.prog-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 12px; border-radius:var(--r-full); font-size:0.78rem; font-weight:600;
    background:var(--c-sky-pale); color:var(--c-navy); border:1px solid rgba(108,173,223,0.15);
    margin:2px;
}

/* ── TIMELINE ───────────────────────────── */
.timeline { display:flex; flex-direction:column; gap:10px; }
.t-item {
    padding:14px 18px; border-radius:var(--r-md);
    border:1px solid var(--c-border); background:white;
    transition:all var(--t-base);
}
.t-item:hover { border-color:var(--c-sky); box-shadow:var(--shadow-card); }
.t-item .t-date { font-size:0.75rem; font-weight:700; color:var(--c-sky); margin-bottom:3px; display:flex; align-items:center; gap:6px; }
.t-item .t-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; }
.t-item .t-perf { font-weight:700; color:var(--c-navy); font-size:0.9rem; }
.t-item .t-note { font-size:0.82rem; color:var(--c-text-muted); line-height:1.5; margin-top:5px; padding-top:5px; border-top:1px dashed var(--c-border); }
.empty-state { text-align:center; padding:48px 20px; color:var(--c-text-muted); }
.empty-state i { font-size:2.5rem; opacity:0.12; margin-bottom:12px; display:block; }
.empty-state h4 { color:var(--c-navy); margin-bottom:4px; font-size:1rem; }
.empty-state p { font-size:0.82rem; }

/* ── DARK MODE ──────────────────────────── */
body.dark .top-bar, body.dark .stat-card, body.dark .side-card, body.dark .t-item { background:var(--c-navy-soft); border-color:#1e3a5a; }
body.dark .top-bar-info .tbi-name, body.dark .stat-card .stat-num, body.dark .side-card-head h3, body.dark .profile-card .pc-name, body.dark .t-item .t-perf, body.dark .empty-state h4 { color:var(--c-sky); }
body.dark .top-bar-btn-outline { color:var(--c-sky-light); border-color:#1e3a5a; }
body.dark .top-bar-btn-outline:hover { border-color:var(--c-sky); }
CSS;

require_once __DIR__ . '/lib/header.php';
?>
<div class="dash-wrap">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-left">
            <?php if(!empty($joueur['photo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($joueur['photo']); ?>" class="top-bar-avatar" alt="">
            <?php else: ?>
                <div class="top-bar-avatar"><i class="fa-solid fa-futbol"></i></div>
            <?php endif; ?>
            <div class="top-bar-info">
                <div class="tbi-name"><?php echo htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']); ?></div>
                <div class="tbi-role"><?php echo htmlspecialchars($joueur['poste'] ?? 'Joueur'); ?> &middot; <?php echo htmlspecialchars($joueur['niveau'] ?? 'Academy'); ?></div>
            </div>
        </div>
        <div class="top-bar-actions">
            <a href="player_profile" class="top-bar-btn top-bar-btn-primary"><i class="fa-solid fa-user-gear"></i> <span data-i18n="role_player">Profil</span></a>
            <a href="portal_logout" class="top-bar-btn top-bar-btn-outline"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </div>

    <!-- Greeting -->
    <div class="greeting">
        <h1><span data-i18n="greeting_hello">Bonjour,</span> <?php echo htmlspecialchars($joueur['prenom']); ?> !</h1>
        <p data-i18n="greeting_subtitle">Heureux de te revoir sur le terrain.</p>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="color:var(--c-sky);"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="stat-num"><?php echo $total; ?></div>
            <div class="stat-label" data-i18n="stat_sessions">Sessions</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#10b981;"><i class="fa-solid fa-check-circle"></i></div>
            <div class="stat-num"><?php echo $present; ?></div>
            <div class="stat-label" data-i18n="stat_present">Présent</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#ef4444;"><i class="fa-solid fa-times-circle"></i></div>
            <div class="stat-num"><?php echo $absent; ?></div>
            <div class="stat-label" data-i18n="stat_absent">Absent</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#f59e0b;"><i class="fa-solid fa-chart-line"></i></div>
            <div class="stat-num"><?php echo $presPct; ?>%</div>
            <div class="stat-label" data-i18n="stat_presence">Présence</div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width:<?php echo $presPct; ?>%;"></div></div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Sidebar -->
        <div>
            <div class="side-card">
                <div class="profile-card">
                    <?php if(!empty($joueur['photo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($joueur['photo']); ?>" class="pc-avatar" alt="">
                    <?php else: ?>
                        <div class="pc-avatar"><i class="fa-solid fa-futbol"></i></div>
                    <?php endif; ?>
                    <div class="pc-name"><?php echo htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']); ?></div>
                    <div class="pc-role" data-i18n="role_player">Joueur</div>
                    <div class="pc-details">
                        <?php if($age): ?>
                        <div class="pc-detail">
                            <div class="val"><?php echo $age; ?></div>
                            <div class="lbl" data-i18n="years_old">ans</div>
                        </div>
                        <?php endif; ?>
                        <div class="pc-detail">
                            <div class="val"><?php echo htmlspecialchars($joueur['poste'] ?? '—'); ?></div>
                            <div class="lbl" data-i18n="position">Poste</div>
                        </div>
                        <div class="pc-detail">
                            <div class="val"><?php echo htmlspecialchars($joueur['niveau'] ?? 'Club'); ?></div>
                            <div class="lbl" data-i18n="level">Niveau</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="side-card">
                <div class="side-card-head"><i class="fa-solid fa-layer-group"></i><h3 data-i18n="side_programmes">Programmes</h3></div>
                <div class="side-card-body">
                    <?php if(empty($programmes)): ?>
                        <p style="color:var(--c-text-muted);font-size:0.82rem;" data-i18n="no_programmes">Aucun programme actif.</p>
                    <?php else: ?>
                        <?php foreach($programmes as $p): ?>
                            <span class="prog-badge"><i class="fa-solid fa-futbol"></i> <?php echo htmlspecialchars($p['nom_programme']); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="side-card">
                <div class="side-card-head"><i class="fa-solid fa-info-circle"></i><h3 data-i18n="side_infos">Infos</h3></div>
                <div class="side-card-body" style="font-size:0.82rem;color:var(--c-text-muted);">
                    <div style="display:flex;justify-content:space-between;padding:4px 0;"><span data-i18n="info_label_nationality">Nationalité</span><span style="font-weight:700;color:var(--c-navy);"><?php echo htmlspecialchars($joueur['nationalite'] ?? '—'); ?></span></div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--c-border);"><span data-i18n="info_label_physique">Physique</span><span style="font-weight:700;color:var(--c-navy);"><?php echo (!empty($joueur['taille']) ? (int)$joueur['taille'].' cm' : '—').' / '.(!empty($joueur['poids']) ? (int)$joueur['poids'].' kg' : '—'); ?></span></div>
                    <?php if(!empty($joueur['maladie_chronique'])): ?>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--c-border);color:#991b1b;"><span><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i><span data-i18n="info_label_chronic_disease">Maladie chronique</span></span><span style="font-weight:700;"><?php echo htmlspecialchars($joueur['maladie_chronique']); ?></span></div>
                    <?php endif; ?>
                    <?php if(!empty($joueur['telephone'])): ?>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--c-border);"><span data-i18n="info_label_phone">Téléphone</span><span style="font-weight:700;color:var(--c-navy);"><?php echo htmlspecialchars($joueur['telephone']); ?></span></div>
                    <?php endif; ?>
                    <?php if($age): ?>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-top:1px solid var(--c-border);"><span data-i18n="info_label_birth_date">Date naissance</span><span style="font-weight:700;color:var(--c-navy);"><?php echo date('d/m/Y', strtotime($joueur['date_naissance'])); ?></span></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="side-card">
                <div class="side-card-head"><i class="fa-solid fa-arrows-rotate"></i><h3 data-i18n="change_position">Changer de Poste</h3></div>
                <div class="side-card-body">
                    <?php if($hasPending): ?>
                        <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:8px;padding:10px;font-size:0.82rem;color:#92400e;">
                            <i class="fa-solid fa-hourglass-half" style="margin-right:4px;"></i><span data-i18n="pending_position_request">Demande en attente de validation...</span>
                        </div>
                    <?php else: ?>
                        <form id="posteForm" onsubmit="return false;" style="display:flex;flex-direction:column;gap:10px;">
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;color:var(--c-text-muted);display:block;margin-bottom:4px;" data-i18n="current_position">Poste actuel</label>
                                <div style="padding:8px 12px;border:1px solid var(--c-border);border-radius:8px;font-weight:700;color:var(--c-navy);font-size:0.9rem;background:var(--c-sky-pale);"><?php echo htmlspecialchars($joueur['poste'] ?? 'Non défini'); ?></div>
                            </div>
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;color:var(--c-text-muted);display:block;margin-bottom:4px;" data-i18n="desired_position">Nouveau poste souhaité</label>
                                <select id="posteDemande" style="padding:8px 12px;border:2px solid var(--c-border);border-radius:8px;font-size:0.85rem;width:100%;">
                                    <option value="" data-i18n="choose_one">-- Choisir --</option>
                                    <option value="Gardien" data-i18n="position_goalkeeper">Gardien</option>
                                    <option value="Défenseur" data-i18n="position_defender">Défenseur</option>
                                    <option value="Milieu" data-i18n="position_midfielder">Milieu</option>
                                    <option value="Attaquant" data-i18n="position_forward">Attaquant</option>
                                    <option value="Ailier" data-i18n="position_winger">Ailier</option>
                                    <option value="Buteur" data-i18n="position_striker">Buteur</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:0.78rem;font-weight:700;color:var(--c-text-muted);display:block;margin-bottom:4px;" data-i18n="reason_label">Raison</label>
                                <textarea id="posteRaison" rows="2" data-i18n-placeholder="reason_placeholder" placeholder="Explique ta demande..." style="padding:8px 12px;border:2px solid var(--c-border);border-radius:8px;font-size:0.85rem;resize:vertical;width:100%;box-sizing:border-box;"></textarea>
                            </div>
                            <button type="button" onclick="submitPoste()" style="padding:8px 16px;border:none;background:var(--c-navy);color:white;border-radius:8px;font-weight:700;font-size:0.82rem;cursor:pointer;transition:all .2s;" onmouseover="this.style.background='var(--c-sky)'" onmouseout="this.style.background='var(--c-navy)'"><i class="fa-solid fa-paper-plane" style="margin-right:4px;"></i><span data-i18n="send_request">Envoyer la demande</span></button>
                            <span id="posteStatus" style="font-size:0.78rem;font-weight:700;"></span>
                        </form>
                    <?php endif; ?>
                    <?php if(!empty($demandes)): ?>
                        <div style="margin-top:12px;border-top:1px solid var(--c-border);padding-top:10px;">
                            <div style="font-size:0.75rem;font-weight:700;color:var(--c-text-muted);margin-bottom:6px;text-transform:uppercase;" data-i18n="history_title">Historique</div>
                            <?php foreach($demandes as $d): 
                                $badge = match($d['statut']) {
                                    'acceptee' => ['background:rgba(16,185,129,0.1);color:#10b981;','Approuvée'],
                                    'refusee' => ['background:rgba(239,68,68,0.1);color:#ef4444;','Refusée'],
                                    default => ['background:rgba(245,158,11,0.1);color:#f59e0b;','En attente'],
                                };
                                $badgeKey = match($d['statut']) {
                                    'acceptee' => 'badge_approved',
                                    'refusee' => 'badge_rejected',
                                    default => 'badge_pending',
                                };
                            ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:5px 0;border-bottom:1px dashed var(--c-border);font-size:0.8rem;">
                                    <span><strong><?php echo htmlspecialchars($d['poste_actuel'] ?? '—'); ?></strong> <i class="fa-solid fa-arrow-right" style="color:#94a3b8;font-size:0.65rem;margin:0 4px;"></i> <strong><?php echo htmlspecialchars($d['poste_souhaite']); ?></strong></span>
                                    <span style="padding:2px 8px;border-radius:5px;font-size:0.7rem;font-weight:700;<?php echo $badge[0]; ?>" data-i18n="<?php echo $badgeKey; ?>"><?php echo $badge[1]; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main: Suivis -->
        <div>
            <div class="side-card">
                <div class="side-card-head">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <h3 data-i18n="progress_tracking">Suivi de Progression</h3>
                    <span style="margin-left:auto;font-size:0.78rem;color:var(--c-text-muted);"><?php echo $total; ?> <span data-i18n="sessions_count">session<?php echo $total>1?'s':''; ?></span></span>
                </div>
                <div class="side-card-body">
                    <?php if(empty($suivis)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-chart-line"></i>
                            <h4 data-i18n="no_followups_yet">Bientôt tes premiers suivis !</h4>
                            <p data-i18n="no_followups_desc">Les coachs ajouteront les évaluations après les entraînements.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach($suivis as $s):
                                $presence = $s['presence'] ?? 'Présent';
                                $badgeColor = match($presence) {
                                    'Présent' => '#10b981', 'Absent' => '#ef4444',
                                    'Blessé' => '#f59e0b', 'Retard' => '#6366f1',
                                    'Maladie' => '#8b5cf6',
                                    default => '#10b981',
                                };
                            ?>
                                <div class="t-item">
                                    <div class="t-date"><i class="fa-regular fa-calendar"></i><?php echo date('d/m/Y', strtotime($s['date_suivi'])); ?></div>
                                    <div class="t-top">
                                        <span class="t-perf"><?php echo htmlspecialchars($s['performance']); ?></span>
                                        <span style="font-size:0.7rem;font-weight:700;padding:2px 10px;border-radius:5px;background:<?php echo $badgeColor; ?>12;color:<?php echo $badgeColor; ?>;"><?php echo htmlspecialchars($presence); ?></span>
                                    </div>
                                    <?php if(!empty($s['remarque'])): ?>
                                        <div class="t-note"><?php echo nl2br(htmlspecialchars($s['remarque'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
async function submitPoste() {
    const status = document.getElementById('posteStatus');
    const poste = document.getElementById('posteDemande').value;
    const raison = document.getElementById('posteRaison').value.trim();
    if (!poste) { status.textContent = uiManager.t('poste_request_choose_position'); status.style.color = '#ef4444'; return; }
    status.textContent = uiManager.t('poste_request_loading'); status.style.color = '#64748b';
    try {
        const data = new URLSearchParams();
        data.append('id_joueur', '<?php echo $id_joueur; ?>');
        data.append('poste_demande', poste);
        data.append('raison', raison);
        const res = await fetch('<?php echo url_path("/api/demande_poste.php"); ?>', { method: 'POST', body: data });
        const json = await res.json();
        if (json.ok) {
            status.textContent = '✓ ' + uiManager.t('poste_request_success'); status.style.color = '#10b981';
            setTimeout(() => location.reload(), 800);
        } else {
            status.textContent = json.error || uiManager.t('poste_request_error'); status.style.color = '#ef4444';
        }
    } catch (e) {
        status.textContent = uiManager.t('poste_request_network_error'); status.style.color = '#ef4444';
    }
}
</script>
<?php require_once __DIR__ . '/lib/footer.php'; ?>
