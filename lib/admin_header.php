<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/settings.php';

$admin_page_title = $admin_page_title ?? 'Dashboard';
$admin_current_page = $admin_current_page ?? 'dashboard';
$s = get_settings();
$admin = admin_current();
$role = $admin['role'] ?? 'subadmin';

$isMain = $role === 'main';
$isSub  = $role === 'subadmin';
$isCoach = $role === 'coach';
$isFin  = $role === 'financier';

$pendingRequests = 0;
try {
    $prDb = db();
    $prStmt = $prDb->query('SELECT COUNT(*) FROM demande_changement WHERE statut = \'en_attente\'');
    $pendingRequests = (int)$prStmt->fetchColumn();
} catch (\Throwable $e) {}
$isCoord = $role === 'coordinateur';

$roleLabel = match ($role) {
    'main' => 'Super Admin',
    'subadmin' => 'Admin',
    'coach' => 'Coach',
    'financier' => 'Financier',
    'coordinateur' => 'Coordinateur',
    default => ucfirst($role),
};

$hasGeneralAccess = !$isCoach && !$isFin && !$isCoord;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($admin_page_title); ?> | <?php echo htmlspecialchars((string)$s['site_name']); ?></title>
    <link rel="icon" type="image/png" href="<?php echo url_path('/favicon.png'); ?>">
    <link rel="preload" href="<?php echo url_path('/fonts/inter-latin.woff2'); ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?php echo url_path('/fonts/fonts.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo url_path('/style.css'); ?>">
    <?php render_dynamic_styles(); ?>
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: #f0f4f9;
        }

        /* ── Sidebar ── */
        .admin-sidebar {
            width: 270px;
            min-width: 270px;
            background: linear-gradient(180deg, #0a1e3a 0%, #0c2340 40%, #0f2a4a 100%);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            transition: transform 0.35s cubic-bezier(.4,0,.2,1);
            border-right: 1px solid rgba(255,255,255,0.04);
            overflow: hidden;
        }

        .sidebar-brand {
            padding: 22px 24px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-brand img {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: cover;
            background: rgba(255,255,255,0.08);
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .sidebar-brand-info {
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand-name {
            color: #fff;
            font-weight: 800;
            font-size: 0.95rem;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }

        .sidebar-brand-role {
            color: rgba(255,255,255,0.4);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 1px;
        }

        .sidebar-user {
            padding: 16px 24px;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .sidebar-user:hover {
            background: rgba(255,255,255,0.06);
        }

        .sidebar-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--c-sky, #6caddf), #4a8ec4);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(108,173,223,0.25);
            overflow: hidden;
        }

        .sidebar-user-info {
            flex: 1;
            min-width: 0;
        }

        .sidebar-user-name {
            color: #fff;
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-email {
            color: rgba(255,255,255,0.4);
            font-size: 0.72rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.08) transparent;
        }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 4px; }

        .sidebar-nav-label {
            padding: 20px 12px 6px;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,0.2);
            font-weight: 700;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.88rem;
            border-radius: 10px;
            transition: all 0.2s ease;
            margin-bottom: 2px;
            position: relative;
        }

        .sidebar-nav a:hover {
            color: #fff;
            background: rgba(255,255,255,0.07);
        }

        .sidebar-nav a.active {
            color: #fff;
            background: rgba(108,173,223,0.15);
            font-weight: 600;
        }
        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            left: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            border-radius: 0 3px 3px 0;
            background: var(--c-sky, #6caddf);
        }

        .sidebar-nav a i {
            width: 22px;
            text-align: center;
            font-size: 0.95rem;
            flex-shrink: 0;
            color: rgba(255,255,255,0.35);
            transition: color 0.2s;
        }
        .sidebar-nav a:hover i,
        .sidebar-nav a.active i {
            color: var(--c-sky, #6caddf);
        }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid rgba(255,255,255,0.05);
            background: rgba(0,0,0,0.1);
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .sidebar-footer a:hover {
            color: #fff;
            background: rgba(255,255,255,0.06);
        }

        .sidebar-footer a i {
            width: 22px;
            text-align: center;
            font-size: 0.95rem;
        }

        /* ── Main ── */
        .admin-main {
            flex: 1;
            margin-left: 270px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .admin-topbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226,232,240,0.6);
            padding: 0 32px;
            height: 68px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.3rem;
            color: var(--c-navy, #001838);
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .topbar-toggle:hover { background: rgba(0,0,0,0.04); }

        .topbar-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--c-navy, #001838);
            flex: 1;
            letter-spacing: -0.02em;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-date {
            font-size: 0.83rem;
            color: #64748b;
            font-weight: 500;
            padding: 6px 14px;
            background: #f1f5f9;
            border-radius: 8px;
            white-space: nowrap;
        }

        .topbar-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .topbar-badge-role {
            background: rgba(108,173,223,0.1);
            color: var(--c-sky, #6caddf);
            border: 1px solid rgba(108,173,223,0.15);
        }

        .admin-content {
            flex: 1;
            padding: 32px;
            max-width: 1440px;
            width: 100%;
            box-sizing: border-box;
        }

        .admin-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 999;
        }

        /* ── Dark mode ── */
        body.dark .admin-layout {
            background: #0b1120;
        }

        body.dark .admin-topbar {
            background: rgba(15,23,42,0.85);
            backdrop-filter: blur(12px);
            border-color: rgba(255,255,255,0.04);
        }

        body.dark .topbar-title {
            color: #f1f5f9;
        }

        body.dark .topbar-date {
            color: #94a3b8;
            background: rgba(255,255,255,0.04);
        }

        body.dark .topbar-toggle {
            color: #f1f5f9;
        }
        body.dark .topbar-toggle:hover { background: rgba(255,255,255,0.06); }

        body.dark .admin-content {
            color: #e2e8f0;
        }

        body.dark .topbar-badge-role {
            background: rgba(108,173,223,0.08);
            border-color: rgba(108,173,223,0.12);
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                border-right: none;
            }

            .admin-sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 40px rgba(0,0,0,0.3);
            }

            .admin-main {
                margin-left: 0;
            }

            .topbar-toggle {
                display: block;
            }

            .admin-overlay.active {
                display: block;
            }

            .admin-content {
                padding: 20px 16px;
            }

            .topbar-date {
                display: none;
            }

            .admin-topbar {
                padding: 0 16px;
            }
        }
    </style>
    <?php if (isset($extra_styles)): ?>
    <style><?php echo $extra_styles; ?></style>
    <?php endif; ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <div class="admin-overlay" id="adminOverlay"></div>
    <div class="admin-layout">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-brand">
                <img src="<?php echo url_path('/' . ltrim((string)$s['site_logo'], '/')); ?>" alt="<?php echo htmlspecialchars((string)$s['site_name']); ?>">
                <div class="sidebar-brand-info">
                    <span class="sidebar-brand-name"><?php echo htmlspecialchars((string)$s['site_name']); ?></span>
                    <span class="sidebar-brand-role">Espace Administration</span>
                </div>
            </div>

            <?php
            $adminPhoto = '';
            $adminPhotoPath = '';
            try {
                $stmtP = db()->prepare('SELECT photo FROM admin WHERE id_admin = ?');
                $stmtP->execute([$admin['id_admin']]);
                $adminPhoto = (string)($stmtP->fetchColumn() ?: '');
            } catch (\Exception $e) {}
            if ($adminPhoto) {
                $photoFile = __DIR__ . '/../uploads/admins/' . $adminPhoto;
                if (file_exists($photoFile)) {
                    $adminPhotoPath = url_path('/uploads/admins/' . rawurlencode($adminPhoto)) . '?v=' . filemtime($photoFile);
                }
            }
            ?>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?php if ($adminPhotoPath): ?>
                        <img src="<?php echo $adminPhotoPath; ?>" alt="Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($admin['nom'] ?? 'A', 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($admin['nom'] ?? ''); ?></div>
                    <div class="sidebar-user-email"><?php echo htmlspecialchars($admin['email'] ?? ''); ?></div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <?php if ($hasGeneralAccess): ?>
                    <div class="sidebar-nav-label">Général</div>
                    <a href="<?php echo url_path('/admin/dashboard'); ?>" class="<?php echo $admin_current_page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                    <a href="<?php echo url_path('/admin/joueurs'); ?>" class="<?php echo $admin_current_page === 'joueurs' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-users"></i> Joueurs
                    </a>
                    <a href="<?php echo url_path('/admin/parents'); ?>" class="<?php echo $admin_current_page === 'parents' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-people-group"></i> Parents
                    </a>
                    <a href="<?php echo url_path('/formulair?admin=1'); ?>">
                        <i class="fa-solid fa-user-plus"></i> Nouvelle Inscription
                    </a>
                    <a href="<?php echo url_path('/admin/carousel'); ?>" class="<?php echo $admin_current_page === 'carousel' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-images"></i> Carousel
                    </a>
                    <a href="<?php echo url_path('/admin/inscriptions_pending'); ?>" class="<?php echo $admin_current_page === 'inscriptions_pending' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clock"></i> Inscriptions
                    </a>
                    <a href="<?php echo url_path('/admin/player_requests'); ?>" class="<?php echo $admin_current_page === 'player_requests' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-arrows-rotate"></i> Demandes
                        <?php if ($pendingRequests > 0): ?>
                            <span style="margin-left:auto;background:#f59e0b;color:white;font-size:0.65rem;font-weight:800;padding:2px 7px;border-radius:99px;"><?php echo $pendingRequests; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo url_path('/admin/services_crud'); ?>" class="<?php echo $admin_current_page === 'services_crud' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-alt"></i> Programmes
                    </a>
                    <?php if ($isMain): ?>
                        <div class="sidebar-nav-label">Configuration</div>
                        <a href="<?php echo url_path('/admin/settings'); ?>" class="<?php echo $admin_current_page === 'settings' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-gears"></i> Paramètres
                        </a>
                        <a href="<?php echo url_path('/admin/manage_admins'); ?>" class="<?php echo $admin_current_page === 'manage_admins' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-user-shield"></i> Gestion Admins
                        </a>
                    <?php endif; ?>
                <?php elseif ($isCoach): ?>
                    <div class="sidebar-nav-label">Coach</div>
                    <a href="<?php echo url_path('/admin/coach_dashboard'); ?>" class="<?php echo $admin_current_page === 'coach_dashboard' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <a href="<?php echo url_path('/admin/coach_profile'); ?>" class="<?php echo $admin_current_page === 'coach_profile' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-pen"></i> Mon Profil
                    </a>
                    <a href="<?php echo url_path('/admin/player_requests'); ?>" class="<?php echo $admin_current_page === 'player_requests' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-arrows-rotate"></i> Demandes Joueurs
                        <?php if ($pendingRequests > 0): ?>
                            <span style="margin-left:auto;background:#f59e0b;color:white;font-size:0.65rem;font-weight:800;padding:2px 7px;border-radius:99px;"><?php echo $pendingRequests; ?></span>
                        <?php endif; ?>
                    </a>
                <?php elseif ($isFin): ?>
                    <div class="sidebar-nav-label">Finance</div>
                    <a href="<?php echo url_path('/admin/finance_dashboard'); ?>" class="<?php echo $admin_current_page === 'finance_dashboard' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-line"></i> Dashboard Financier
                    </a>
                <?php elseif ($isCoord): ?>
                    <div class="sidebar-nav-label">Coordination</div>
                    <a href="<?php echo url_path('/admin/inscriptions_pending'); ?>" class="<?php echo $admin_current_page === 'inscriptions_pending' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clock"></i> Inscriptions
                    </a>
                    <a href="<?php echo url_path('/admin/coordinateur_dashboard'); ?>" class="<?php echo $admin_current_page === 'coordinateur_dashboard' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-people-arrows"></i> Coachs
                    </a>
                    <a href="<?php echo url_path('/admin/services_crud'); ?>" class="<?php echo $admin_current_page === 'services_crud' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar-alt"></i> Programmes
                    </a>
                <?php endif; ?>
                    <?php if (!$isCoach): ?>
                        <div class="sidebar-nav-label">Compte</div>
                        <a href="<?php echo url_path('/admin/admin_profile'); ?>" class="<?php echo $admin_current_page === 'admin_profile' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-user-pen"></i> Mon Profil
                        </a>
                    <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo url_path('/index'); ?>" title="Voir le site">
                    <i class="fa-solid fa-globe"></i> Voir le site
                </a>
                <a href="<?php echo url_path('/admin/logout'); ?>">
                    <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
                </a>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button class="topbar-toggle" id="sidebarToggle" aria-label="Menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="topbar-title"><?php echo htmlspecialchars($admin_page_title); ?></h1>
                <div class="topbar-right">
                    <span class="topbar-badge topbar-badge-role">
                        <i class="fa-solid fa-shield-halved"></i> <?php echo $roleLabel; ?>
                    </span>
                    <span class="topbar-date">
                        <i class="fa-regular fa-calendar"></i> <?php echo date('d F Y'); ?>
                    </span>
                </div>
            </header>
            <div class="admin-content">
