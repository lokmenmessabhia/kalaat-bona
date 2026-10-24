<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';

$site_settings = get_settings();

session_start();
if (!isset($_SESSION['parent_id'])) {
    header('Location: ' . url_path('/portal_login.php?type=parent'));
    exit;
}

$id_parent    = (int)$_SESSION['parent_id'];
$parent_nom   = (string)$_SESSION['parent_nom'];
$pdo          = db();
$api_base     = url_path('/api/messages.php');

// ── Fetch parent info ────────────────────────────────────────────
$stmtParent = $pdo->prepare('SELECT * FROM parent WHERE id_parent = ?');
$stmtParent->execute([$id_parent]);
$parent = $stmtParent->fetch();

$enfants = $pdo->prepare('
    SELECT j.*, pf.poste, pf.niveau
    FROM joueur j
    LEFT JOIN profil_football pf ON j.id_joueur = pf.id_joueur
    WHERE j.id_parent = ?
    ORDER BY j.date_naissance DESC
');
$enfants->execute([$id_parent]);
$enfants_list = $enfants->fetchAll();

$enfantData = [];
foreach ($enfants_list as $e) {
    $progs = $pdo->prepare('
        SELECT p.nom_programme, a.nom AS coach_nom, a.id_admin AS coach_id, a.telephone AS coach_tel
        FROM inscription i
        JOIN programme p ON i.id_programme = p.id_programme
        LEFT JOIN admin a ON p.id_coach = a.id_admin
        WHERE i.id_joueur = ?
    ');
    $progs->execute([$e['id_joueur']]);
    $e['programmes'] = $progs->fetchAll();

    $suivis = $pdo->prepare('SELECT * FROM suivi WHERE id_joueur = ? ORDER BY date_suivi DESC LIMIT 8');
    $suivis->execute([$e['id_joueur']]);
    $e['suivis'] = $suivis->fetchAll();

    $e['age'] = $e['date_naissance']
        ? (int)(new DateTime())->diff(new DateTime($e['date_naissance']))->y
        : null;
    $enfantData[] = $e;
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Famille | <?php echo htmlspecialchars((string)$site_settings['site_name']); ?></title>
    <link rel="preload" href="fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="fonts/fonts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="js/ui-manager.js"></script>
    <style>
        :root { --bg: #f7f8fa; --surface: #fff; --border: #e2e8f0; --text: #1a2332; --muted: #64748b; }
        [data-theme="dark"] { --bg: #0f172a; --surface: #1e293b; --border: #334155; --text: #f1f5f9; --muted: #94a3b8; }
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;transition:background .3s,color .3s}

        /* Topnav */
        .topnav{position:sticky;top:0;z-index:500;background:rgba(255,255,255,.85);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:0 20px;display:flex;align-items:center;justify-content:space-between;height:52px}
        [data-theme="dark"] .topnav{background:rgba(30,41,59,.85)}
        .topnav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .topnav-logo{width:30px;height:30px;border-radius:8px;object-fit:cover}
        .topnav-title{font-weight:900;font-size:1rem;color:var(--c-navy)}
        [data-theme="dark"] .topnav-title{color:var(--c-sky)}
        .topnav-actions{display:flex;align-items:center;gap:6px}
        .nav-btn{padding:6px 14px;border-radius:8px;font-weight:700;font-size:0.78rem;text-decoration:none;border:none;cursor:pointer;transition:all .25s;background:var(--surface);color:var(--muted);border:1px solid var(--border)}
        .nav-btn:hover{border-color:var(--c-sky);color:var(--c-navy);transform:translateY(-1px)}
        .theme-toggle{width:34px;height:34px;border-radius:8px;background:var(--surface);border:1px solid var(--border);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.9rem;color:var(--muted);transition:all .3s}
        .theme-toggle:hover{border-color:var(--c-sky);color:var(--c-navy)}

        /* Page */
        .page-wrap{max-width:820px;margin:0 auto;padding:20px 16px 60px}

        .page-heading{margin-bottom:20px}
        .page-heading h1{font-size:1.3rem;font-weight:900;color:var(--c-navy)}
        .page-heading p{font-size:0.82rem;color:var(--muted);margin-top:2px}

        /* ── Parent Card ─────────────────────────────── */
        .parent-card{
            display:flex;align-items:center;gap:16px;
            background:var(--surface);border:1px solid var(--border);
            border-radius:var(--r-lg);padding:16px 20px;margin-bottom:24px;
            box-shadow:var(--shadow-card);
        }
        .parent-avatar{
            width:48px;height:48px;border-radius:50%;
            background:var(--c-sky-pale);border:2px solid var(--c-sky);
            display:flex;align-items:center;justify-content:center;
            font-size:1.2rem;color:var(--c-sky);flex-shrink:0;
        }
        .parent-info{flex:1;min-width:0}
        .parent-info .pi-name{font-weight:800;font-size:1rem;color:var(--text)}
        .parent-info .pi-contact{font-size:0.78rem;color:var(--muted);margin-top:2px}
        .parent-info .pi-contact span{margin-right:14px}
        .parent-info .pi-contact i{width:14px;margin-right:4px;color:var(--c-sky)}

        /* ── Kid Cards ───────────────────────────────── */
        .kid-list{display:flex;flex-direction:column;gap:8px}
        .kid-card{
            display:flex;align-items:center;gap:14px;padding:14px 16px;
            background:var(--surface);border:1px solid var(--border);
            border-radius:var(--r-lg);cursor:pointer;
            transition:all .2s;box-shadow:var(--shadow-card);
        }
        .kid-card:hover{border-color:var(--c-sky);transform:translateY(-1px);box-shadow:var(--shadow-hover)}
        .kid-card.open{border-color:var(--c-sky);border-bottom-left-radius:0;border-bottom-right-radius:0}
        .kid-avatar{
            width:48px;height:48px;border-radius:50%;object-fit:cover;flex-shrink:0;
            border:2px solid var(--c-sky);background:var(--c-sky-pale);
            display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--c-sky);
        }
        .kid-info{flex:1;min-width:0}
        .kid-name{font-weight:800;font-size:0.95rem;color:var(--text)}
        .kid-meta{font-size:0.78rem;color:var(--muted);margin-top:1px}
        .kid-progs{display:flex;gap:4px;flex-wrap:wrap;margin-top:6px}
        .kid-prog{
            font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:999px;
            background:var(--c-sky-pale);color:var(--c-navy);border:1px solid rgba(108,173,223,.15);
        }
        .kid-arrow{color:var(--muted);font-size:0.9rem;transition:transform .3s;flex-shrink:0}
        .kid-card:hover .kid-arrow{transform:translateX(4px);color:var(--c-sky)}

        /* ── Expandable Detail ───────────────────────── */
        .kid-detail{
            display:none;
            background:var(--surface);border:1px solid var(--border);
            border-top:none;border-radius:0 0 var(--r-lg) var(--r-lg);
            padding:0 16px 16px;margin-top:-1px;
            box-shadow:var(--shadow-card);
        }
        .kid-detail.open{display:block}

        .detail-section{margin-bottom:16px}
        .detail-section:last-child{margin-bottom:0}
        .ds-title{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px}

        .ds-banner{
            background:linear-gradient(135deg,var(--c-navy),#0a2a4a);border-radius:var(--r-lg);
            padding:18px;text-align:center;color:white;margin:4px 0 14px;
        }
        .ds-banner .db-avatar{width:56px;height:56px;border-radius:50%;object-fit:cover;border:3px solid rgba(108,173,223,.4);margin:0 auto 8px;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:1.3rem}
        .ds-banner .db-name{font-weight:800;font-size:1rem}
        .ds-banner .db-meta{font-size:.72rem;opacity:.7;margin-top:2px}
        .ds-banner .db-chips{display:flex;gap:4px;flex-wrap:wrap;justify-content:center;margin-top:6px}
        .ds-banner .db-chip{padding:2px 9px;border-radius:999px;font-size:.62rem;font-weight:700;background:rgba(108,173,223,.2);border:1px solid rgba(255,255,255,.12)}

        .ds-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border-radius:var(--r-sm);overflow:hidden;margin-bottom:6px}
        .ds-stat{background:var(--surface);padding:10px 0;text-align:center}
        .ds-stat .s-num{font-size:1.1rem;font-weight:900;color:var(--c-navy)}
        .ds-stat .s-lbl{font-size:.6rem;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);font-weight:700;margin-top:1px}

        .bar-wrap{padding:2px 0 8px}
        .bar-label{display:flex;justify-content:space-between;font-size:.7rem;margin-bottom:3px}
        .bar-label span:first-child{font-weight:700;color:var(--text)}
        .bar-label span:last-child{font-weight:800;color:var(--c-navy)}
        .bar-track{height:4px;background:var(--border);border-radius:99px;overflow:hidden}
        .bar-fill{height:100%;background:linear-gradient(90deg,var(--c-sky),#3bd6ff);border-radius:99px;transition:width 1s ease}

        .coach-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--r-sm);background:var(--bg);border:1px solid var(--border);margin-bottom:6px}
        .coach-row:hover{border-color:var(--c-sky)}
        .coach-icon{width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,var(--c-navy),#0a2a4a);color:white;display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0}
        .coach-info{flex:1;min-width:0}
        .coach-info .c-prog{font-size:.65rem;color:var(--muted);font-weight:600}
        .coach-info .c-name{font-weight:800;font-size:.8rem;color:var(--text)}

        .suivi-item{padding:10px 12px;border-radius:var(--r-sm);background:var(--bg);border:1px solid var(--border);margin-bottom:6px}
        .suivi-item:hover{border-color:var(--c-sky)}
        .s-row1{display:flex;justify-content:space-between;align-items:center;margin-bottom:3px}
        .s-perf{font-weight:800;font-size:.8rem;color:var(--text)}
        .s-date{font-size:.65rem;color:var(--muted)}
        .s-note{font-size:.74rem;color:var(--muted);margin-top:4px;padding-top:4px;border-top:1px dashed var(--border);line-height:1.4}
        .badge{padding:2px 7px;border-radius:4px;font-size:.62rem;font-weight:800;text-transform:uppercase}
        .badge-present{background:#d1fae5;color:#065f46}
        .badge-absent{background:#fee2e2;color:#991b1b}
        .badge-blesse{background:#fef3c7;color:#92400e}
        .badge-maladie{background:rgba(139,92,246,0.1);color:#8b5cf6}
        .badge-retard{background:#e0e7ff;color:#3730a3}

        .empty-sm{text-align:center;padding:24px 16px;color:var(--muted)}
        .empty-sm i{font-size:1.8rem;opacity:.12;margin-bottom:6px;display:block}
        .empty-sm p{font-size:.76rem}

        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;margin-top:4px}
        .gallery-item{border-radius:var(--r-sm);overflow:hidden;position:relative;aspect-ratio:4/3;background:var(--border)}
        .gallery-item img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
        .gallery-item:hover img{transform:scale(1.05)}
        .gallery-item .gl-overlay{position:absolute;bottom:0;left:0;right:0;padding:6px;background:linear-gradient(transparent,rgba(0,0,0,.8));color:white;font-size:.65rem;font-weight:700}
        .gallery-add{border-radius:var(--r-sm);aspect-ratio:4/3;border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;flex-direction:column;color:var(--muted)}
        .gallery-add i{font-size:1.3rem;margin-bottom:3px}
        .gallery-add span{font-weight:700;font-size:.65rem}
    </style>
</head>
<body>

<nav class="topnav">
    <a href="<?php echo url_path('/index'); ?>" class="topnav-brand">
        <img src="<?php echo htmlspecialchars((string)$site_settings['site_logo']); ?>" class="topnav-logo" alt="">
        <span class="topnav-title"><?php echo htmlspecialchars((string)$site_settings['site_name']); ?></span>
    </a>
    <div class="topnav-actions">
        <a href="<?php echo url_path('/services'); ?>" class="nav-btn" data-i18n="nav_services">Services</a>
        <a href="<?php echo url_path('/parent_profile'); ?>" class="nav-btn" data-i18n="nav_my_account">Mon Compte</a>
        <button class="theme-toggle" id="themeToggle" title="Thème" data-i18n-title="theme_toggle_title"><i class="fa-solid fa-moon" id="themeIcon"></i></button>
        <a href="<?php echo url_path('/portal_logout'); ?>" class="nav-btn" style="color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</nav>

<div class="page-wrap">
    <div class="page-heading">
        <h1 data-i18n="family_space">Espace Famille</h1>
        <p><?php echo htmlspecialchars($parent_nom); ?></p>
    </div>

    <!-- Parent Info -->
    <div class="parent-card">
        <div class="parent-avatar"><i class="fa-solid fa-user"></i></div>
        <div class="parent-info">
            <div class="pi-name"><?php echo htmlspecialchars($parent['nom'] ?? $parent_nom); ?></div>
            <div class="pi-contact">
                <?php if (!empty($parent['telephone'])): ?><span><i class="fa-solid fa-phone"></i><?php echo htmlspecialchars($parent['telephone']); ?></span><?php endif; ?>
                <?php if (!empty($parent['email'])): ?><span><i class="fa-solid fa-envelope"></i><?php echo htmlspecialchars($parent['email']); ?></span><?php endif; ?>
                <span><i class="fa-solid fa-users"></i><?php echo count($enfantData); ?> <span data-i18n="<?php echo count($enfantData)>1?'children_count':'children_singular' ?>"><?php echo count($enfantData)>1?'enfants':'enfant'; ?></span></span>
            </div>
        </div>
    </div>

    <?php if(empty($enfantData)): ?>
    <div class="kid-card" style="cursor:default;justify-content:center;padding:40px 20px;text-align:center;color:var(--muted);flex-direction:column;gap:8px;">
        <i class="fa-solid fa-user-slash" style="font-size:2rem;opacity:.3;"></i>
        <p data-i18n="no_children_registered">Aucun enfant inscrit. <a href="<?php echo url_path('/formulair'); ?>" style="color:var(--c-sky);font-weight:700;text-decoration:none;" data-i18n="register_player">Inscrire un joueur</a></p>
    </div>
    <?php else: ?>

    <div class="kid-list">
        <?php foreach($enfantData as $idx => $e):
            $present = count(array_filter($e['suivis'], fn($s) => ($s['presence']??'')=='Présent'));
            $absent  = count(array_filter($e['suivis'], fn($s) => ($s['presence']??'')=='Absent'));
            $presPct = count($e['suivis'])>0 ? round($present/count($e['suivis'])*100) : 0;
        ?>
        <div class="kid-card" onclick="toggleKid(<?php echo $idx; ?>)">
            <?php if(!empty($e['photo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($e['photo']); ?>" class="kid-avatar">
            <?php else: ?>
                <div class="kid-avatar"><i class="fa-solid fa-futbol"></i></div>
            <?php endif; ?>
            <div class="kid-info">
                <div class="kid-name"><?php echo htmlspecialchars($e['prenom'].' '.$e['nom']); ?></div>
                <div class="kid-meta"><?php echo $e['age'] ? $e['age'].' ans ('.date('d/m/Y', strtotime($e['date_naissance'])).')' : ''; ?> &middot; <?php echo htmlspecialchars($e['poste'] ?? 'Joueur'); ?><?php echo $e['niveau'] ? ' &middot; '.htmlspecialchars($e['niveau']) : ''; ?><?php if(!empty($e['taille']) || !empty($e['poids'])): ?> &middot; <?php echo (!empty($e['taille']) ? (int)$e['taille'].' cm' : '').(!empty($e['taille']) && !empty($e['poids']) ? ' / ' : '').(!empty($e['poids']) ? (int)$e['poids'].' kg' : ''); ?><?php endif; ?></div>
                <?php if(!empty($e['programmes'])): ?>
                <div class="kid-progs">
                    <?php foreach($e['programmes'] as $p): ?>
                        <span class="kid-prog"><?php echo htmlspecialchars($p['nom_programme']); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="kid-arrow" id="arrow-<?php echo $idx; ?>"><i class="fa-solid fa-chevron-down"></i></div>
        </div>

        <!-- Expandable Detail -->
        <div class="kid-detail" id="detail-<?php echo $idx; ?>">

            <div class="ds-banner">
                <?php if(!empty($e['photo'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($e['photo']); ?>" class="db-avatar">
                <?php else: ?>
                    <div class="db-avatar"><i class="fa-solid fa-futbol"></i></div>
                <?php endif; ?>
                <div class="db-name"><?php echo htmlspecialchars($e['prenom'].' '.$e['nom']); ?></div>
                <div class="db-meta"><?php echo $e['age'] ? $e['age'].' ans' : ''; ?> &middot; <?php echo htmlspecialchars($e['poste'] ?? 'Joueur'); ?><?php echo $e['niveau'] ? ' &middot; '.htmlspecialchars($e['niveau']) : ''; ?><?php if(!empty($e['taille']) || !empty($e['poids'])): ?> &middot; <?php echo (!empty($e['taille']) ? (int)$e['taille'].' cm' : '').(!empty($e['taille']) && !empty($e['poids']) ? ' / ' : '').(!empty($e['poids']) ? (int)$e['poids'].' kg' : ''); ?><?php endif; ?></div>
                <?php if(!empty($e['maladie_chronique'])): ?>
                <div style="margin-top:6px;font-size:0.72rem;color:#fca5a5;background:rgba(239,68,68,0.15);padding:3px 10px;border-radius:999px;display:inline-block;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:3px;"></i><?php echo htmlspecialchars($e['maladie_chronique']); ?></div>
                <?php endif; ?>
                <?php if(!empty($e['programmes'])): ?>
                <div class="db-chips">
                    <?php foreach($e['programmes'] as $p): ?>
                        <span class="db-chip"><?php echo htmlspecialchars($p['nom_programme']); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="detail-section">
                <div class="ds-stats">
                    <div class="ds-stat"><div class="s-num"><?php echo count($e['suivis']); ?></div><div class="s-lbl" data-i18n="stat_sessions_parent">Séances</div></div>
                    <div class="ds-stat"><div class="s-num" style="color:#059669;"><?php echo $present; ?></div><div class="s-lbl" data-i18n="stat_present_parent">Présent</div></div>
                    <div class="ds-stat"><div class="s-num" style="color:#dc2626;"><?php echo $absent; ?></div><div class="s-lbl" data-i18n="stat_absent_parent">Absent</div></div>
                </div>
                <?php if(count($e['suivis'])>0): ?>
                <div class="bar-wrap">
                    <div class="bar-label"><span data-i18n="attendance_rate">Taux de présence</span><span><?php echo $presPct; ?>%</span></div>
                    <div class="bar-track"><div class="bar-fill" style="width:<?php echo $presPct; ?>%;"></div></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Coaches -->
            <?php $hasCoaches = !empty(array_filter($e['programmes'], fn($p) => $p['coach_nom'])); ?>
            <?php if($hasCoaches): ?>
            <div class="detail-section">
                <div class="ds-title" data-i18n="assigned_coach">Coach Référent</div>
                <?php foreach($e['programmes'] as $p): ?>
                <div class="coach-row">
                    <div class="coach-icon"><i class="fa-solid fa-whistle"></i></div>
                    <div class="coach-info">
                        <div class="c-prog"><?php echo htmlspecialchars($p['nom_programme']); ?></div>
                        <div class="c-name"><?php if($p['coach_nom']): ?><?php echo htmlspecialchars($p['coach_nom']); ?><?php else: ?><span data-i18n="pending_assignment">En attente</span><?php endif; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Suivis -->
            <div class="detail-section">
                <div class="ds-title" data-i18n="recent_evaluations">Évaluations récentes</div>
                <?php if(empty($e['suivis'])): ?>
                <div class="empty-sm"><i class="fa-solid fa-clipboard"></i><p data-i18n="no_evaluations_yet">Aucune évaluation pour le moment.</p></div>
                <?php else: ?>
                <?php foreach($e['suivis'] as $s):
                    $pres = $s['presence'] ?? 'Présent';
                    $badge = match($pres) {
                        'Présent' => 'badge-present', 'Absent' => 'badge-absent',
                        'Blessé' => 'badge-blesse', 'Retard' => 'badge-retard',
                        'Maladie' => 'badge-maladie',
                        default => 'badge-present',
                    };
                ?>
                <div class="suivi-item">
                    <div class="s-row1">
                        <span class="s-perf"><?php echo htmlspecialchars($s['performance']); ?></span>
                        <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($pres); ?></span>
                    </div>
                    <div class="s-date"><i class="fa-regular fa-calendar" style="margin-right:4px;"></i><?php echo date('d/m/Y', strtotime($s['date_suivi'])); ?></div>
                    <?php if(!empty($s['remarque'])): ?>
                    <div class="s-note"><?php echo htmlspecialchars(mb_strimwidth($s['remarque'],0,120,'...')); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Gallery -->
            <div class="detail-section">
                <div class="ds-title" data-i18n="gallery_title">Galerie</div>
                <div class="gallery-grid">
                    <div class="gallery-item"><img src="stadium.webp" alt="" loading="lazy"><div class="gl-overlay"><i class="fa-solid fa-image"></i> <span data-i18n="training_label">Entraînement</span></div></div>
                    <div class="gallery-item"><img src="academy.jpg" alt=""><div class="gl-overlay"><i class="fa-solid fa-play"></i> <span data-i18n="match_label">Match</span></div></div>
                    <div class="gallery-add"><i class="fa-solid fa-plus"></i><span data-i18n="coming_soon">Plus à venir</span></div>
                </div>
            </div>

        </div><!-- /kid-detail -->
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleKid(idx) {
    const detail = document.getElementById('detail-' + idx);
    const arrow  = document.getElementById('arrow-' + idx);
    const card   = detail.previousElementSibling;
    const isOpen = detail.classList.contains('open');

    document.querySelectorAll('.kid-detail.open').forEach(el => el.classList.remove('open'));
    document.querySelectorAll('.kid-card.open').forEach(el => el.classList.remove('open'));
    document.querySelectorAll('.kid-arrow i').forEach(el => el.className = 'fa-solid fa-chevron-down');

    if (!isOpen) {
        detail.classList.add('open');
        card.classList.add('open');
        arrow.querySelector('i').className = 'fa-solid fa-chevron-up';
    }
}

// ── Theme ────────────────────────────────────────────────────────
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;
const stored = localStorage.getItem('theme') || 'light';
html.setAttribute('data-theme', stored);
themeIcon.className = stored === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
themeToggle.addEventListener('click', () => {
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    themeIcon.className = next === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
});

// ── Init UIManager ───────────────────────────────────────────────
if (typeof uiManager !== 'undefined') {
    uiManager.applyTranslations();
}
</script>
</body>
</html>
