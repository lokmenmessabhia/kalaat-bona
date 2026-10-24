<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/settings.php';

$s = get_settings();
$page_title = $page_title ?? htmlspecialchars((string)$s['site_name']);
$extra_styles = $extra_styles ?? '';
$extra_head = $extra_head ?? '';
$hide_nav = $hide_nav ?? false;
$hide_footer = $hide_footer ?? false;
$body_class = $body_class ?? '';
$logo_path = url_path('/' . ltrim((string)$s['site_logo'], '/'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/png" href="<?php echo url_path('/favicon.png'); ?>">
    <link rel="preload" href="<?php echo url_path('/fonts/inter-latin.woff2'); ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php echo url_path('/fonts/noto-naskh-arabic-arabic.woff2'); ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php echo url_path('/services_bg.webp'); ?>" as="image">
    <link rel="stylesheet" href="<?php echo url_path('/fonts/fonts.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo url_path('/style.css'); ?>">
    <?php render_dynamic_styles(); ?>
    <?php if ($extra_styles): ?><style><?php echo $extra_styles; ?></style><?php endif; ?>
    <?php echo $extra_head; ?>
</head>
<body<?php echo $body_class ? ' class="' . $body_class . '"' : ''; ?>>
    <div id="page-transition" class="page-transition">
        <img class="pt-logo" src="<?php echo $logo_path; ?>" alt="">
    </div>
    <div id="scroll-progress"></div>

    <?php if (!$hide_nav): ?>
    <header class="main-nav" id="mainNav">
        <div class="main-nav-container">
            <!-- Left Nav -->
            <nav class="nav-menu nav-menu-left" aria-label="Navigation principale">
                <ul>
                    <li><a href="<?php echo url_path('/index'); ?>"><span data-i18n="home">Accueil</span></a></li>
                    <li><a href="<?php echo url_path('/services'); ?>"><span data-i18n="services">Programmes</span></a></li>
                    <li><a href="<?php echo url_path('/formulair'); ?>"><span data-i18n="inscription">Inscription</span></a></li>
                </ul>
            </nav>

            <!-- Center Logo (true center) -->
            <a href="<?php echo url_path('/index'); ?>" class="nav-brand-center">
                <img src="<?php echo $logo_path; ?>" alt="<?php echo htmlspecialchars((string)$s['site_name']); ?>">
            </a>

            <!-- Sponsor (100px right of center, absolute) -->
            <a href="#" class="nav-sponsor-center" aria-label="Sponsor officiel Viva Mall">
                    <img src="<?php echo url_path('/viva.webp'); ?>" alt="Viva Mall" class="sponsor-logo-center" width="60" height="60" loading="lazy">
                <span class="sponsor-sep-center"></span>
                <span class="sponsor-label-center" data-i18n="official_sponsor">Official Sponsor</span>
            </a>

            <!-- Right Nav -->
            <nav class="nav-menu nav-menu-right" aria-label="Actions">
                <ul>
                    <li>
                        <!-- Theme Toggle -->
                        <button class="nav-icon-btn" id="themeToggle" aria-label="Changer le thème" title="Theme">
                            <i class="fa-solid fa-moon" id="themeIcon"></i>
                        </button>
                    </li>
                    <li>
                        <!-- Language Switcher -->
                        <div class="lang-switcher" id="langSwitcher">
                            <button class="nav-icon-btn lang-btn" id="langToggle" aria-label="Changer la langue" title="Langue">
                                <i class="fa-solid fa-language"></i>
                            </button>
                            <div class="lang-dropdown" id="langDropdown">
                                <button class="lang-option" data-lang="fr">Français</button>
                                <button class="lang-option" data-lang="en">English</button>
                                <button class="lang-option" data-lang="ar">العربية</button>
                            </div>
                        </div>
                    </li>
                    <li class="nav-login">
                        <?php
                        $loggedInLink = '';
                        $loggedInLabel = '';
                        if (session_status() === PHP_SESSION_NONE) @session_start();
                        if (isset($_SESSION['joueur_id'])) {
                            $loggedInLink = url_path('/player_dashboard');
                            $loggedInLabel = 'Mon Espace';
                        } elseif (isset($_SESSION['parent_id'])) {
                            $loggedInLink = url_path('/parent_dashboard');
                            $loggedInLabel = 'Espace Famille';
                        }
                        ?>
                        <?php if ($loggedInLink): ?>
                            <a href="<?php echo $loggedInLink; ?>" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-user"></i> <span data-i18n="<?php echo isset($_SESSION['joueur_id']) ? 'nav_my_space' : 'nav_family_space'; ?>"><?php echo $loggedInLabel; ?></span>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo url_path('/portal_login'); ?>" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-user"></i> <span data-i18n="nav_login">Connexion</span>
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </nav>

            <!-- Hamburger -->
            <button class="nav-hamburger" id="navHamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>

        <!-- Mobile Overlay -->
        <div class="nav-overlay" id="navOverlay"></div>

        <!-- Mobile Panel -->
        <div class="nav-right" id="navRight">
            <div class="nav-right-header">
                <img src="<?php echo $logo_path; ?>" alt="<?php echo htmlspecialchars((string)$s['site_name']); ?>" class="nav-right-logo">
                <button class="nav-close-btn" id="navCloseBtn" aria-label="Fermer le menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="nav-sponsor-mobile">
                <a href="#" aria-label="Sponsor officiel Viva Mall">
                    <img src="<?php echo url_path('/viva.webp'); ?>" alt="Viva Mall" class="sponsor-logo-mobile" width="40" height="40" loading="lazy">
                    <span class="sponsor-sep-mobile"></span>
                    <span class="sponsor-label-mobile" data-i18n="official_sponsor">Official Sponsor</span>
                </a>
            </div>
            <div class="nav-links">
                <a href="<?php echo url_path('/index'); ?>" class="nav-link" data-i18n="home">Accueil</a>
                <a href="<?php echo url_path('/services'); ?>" class="nav-link" data-i18n="services">Programmes</a>
                <a href="<?php echo url_path('/formulair'); ?>" class="nav-link" data-i18n="inscription">Inscription</a>
            </div>
            <div class="nav-right-divider"></div>
            <?php $mobileLink = $loggedInLink ?: url_path('/portal_login'); ?>
            <a href="<?php echo $mobileLink; ?>" class="nav-cta-mobile"><i class="fa-solid fa-user"></i> <span data-i18n="<?php echo $loggedInLink ? (isset($_SESSION['joueur_id']) ? 'nav_my_space' : 'nav_family_space') : 'nav_login'; ?>"><?php echo $loggedInLabel ?: 'Connexion'; ?></span></a>
            <div class="mobile-nav-extras">
                <button class="mobile-theme-btn" id="mobileThemeToggle">
                    <i class="fa-solid fa-moon" id="mobileThemeIcon"></i> <span data-i18n="theme_toggle_title">Thème</span>
                </button>
                <div class="mobile-lang-btns">
                    <button class="mobile-lang-btn active" data-lang="fr">FR</button>
                    <button class="mobile-lang-btn" data-lang="en">EN</button>
                    <button class="mobile-lang-btn" data-lang="ar">عربي</button>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <main id="mainContent">
