<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';
$s = get_settings();
$page_title = htmlspecialchars((string)$s['site_name']) . ' — Club de Football d\'Élite';
require_once __DIR__ . '/lib/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-grid"></div>
    <div class="hero-content">
        <div class="hero-left">
            <div class="hero-tag">
                <i class="fa-solid fa-crown"></i>
                <span data-i18n="est_since">Fondé en 2026</span>
            </div>
            <h1 class="hero-title">
                <span class="hero-gold" data-i18n="hero_title_1">Kalaat Bona</span>
                <span data-i18n="hero_title_2">Football Club</span>
                <span class="hero-arabic">« من قلعتنا يولد الأبطال »</span>
            </h1>
            <p class="hero-sub" data-i18n="hero_subtitle">Le centre d'excellence pour les futurs champions de demain à Annaba. Formation technique, suivi académique et encadrement professionnel.</p>
        </div>
        <div class="hero-right">
            <div class="hero-logo-wrap">
                <picture>
                    <source srcset="logo_kala.webp" type="image/webp">
                    <img src="logo_kala.png" alt="Kalaat Bona FC" class="hero-logo-img" width="420" height="420" loading="eager" fetchpriority="high">
                </picture>
            </div>
        </div>
    </div>
    <div class="hero-scroll">
        <span data-i18n="scroll_down">Découvrir</span>
        <div class="hero-scroll-line"></div>
    </div>
</section>

<!-- Sponsors - Oasis -->
<section class="sponsor-section reveal">
    <div class="sponsor-title" data-i18n="sponsors_title">Nos Sponsors</div>
    <div class="sponsor-track">
        <picture>
            <source srcset="<?php echo url_path('/oasis.webp'); ?>" type="image/webp">
            <img src="<?php echo url_path('/oasis.png'); ?>" alt="Oasis" class="sponsor-logo" width="75" height="35.5" loading="lazy" decoding="async">
        </picture>
    </div>
</section>

<!-- Club Programs -->
<section class="section programs-section">
    <div class="section-header">
        <div class="section-tag"><i class="fa-solid fa-flag-checkered"></i> <span data-i18n="our_programs_home">Nos Programmes</span></div>
        <h2 class="section-title"><span data-i18n="programs_path_title_1">Parcours de</span> <span class="gold" data-i18n="programs_path_title_2">formation</span> <span data-i18n="programs_path_title_3">inspirés du haut niveau</span></h2>
        <p class="section-sub" data-i18n="programs_path_sub">Une offre d'entraînement structurée, moderne et adaptée aux jeunes joueurs de Kalaat Bona, tout en conservant notre identité locale.</p>
    </div>
    <div class="program-grid">
        <article class="program-card reveal">
            <span class="program-badge">U8 - U12</span>
            <h3 data-i18n="card_eveil_title">Éveil technique</h3>
            <p data-i18n="card_eveil_desc">Initiation au football, coordination et plaisir de jouer dans un environnement bienveillant.</p>
        </article>
        <article class="program-card reveal">
            <span class="program-badge">U12 - U16</span>
            <h3 data-i18n="card_formation_title">Formation structurée</h3>
            <p data-i18n="card_formation_desc">Entraînements collectifs, travail tactique et progression individuelle sous la supervision de coachs expérimentés.</p>
        </article>
        <article class="program-card reveal">
            <span class="program-badge">U16 - U19</span>
            <h3 data-i18n="card_comp_title">Préparation compétition</h3>
            <p data-i18n="card_comp_desc">Performance, condition physique et mental de gagnant pour les jeunes ambitieux.</p>
        </article>
        <article class="program-card reveal program-card-vip">
            <span class="program-badge">VIP</span>
            <h3 data-i18n="card_coaching_title">Coaching individuel</h3>
            <p data-i18n="card_coaching_desc">Accompagnement personnalisé pour développer des talents uniques et accélérer le niveau de jeu.</p>
        </article>
    </div>
</section>

<section class="section story-section">
    <div class="story-text reveal" style="max-width:var(--max-w);margin:0 auto;text-align:center;">
        <h2 class="section-title" data-i18n="story_title">Kalaat Bona, une académie <span class="gold">moderne</span> et ambitieuse</h2>
        <p class="section-sub" data-i18n="story_desc">Nous faisons le lien entre l'esprit d'un grand club de football et la culture locale d'Annaba, avec un accent sur l'excellence, la discipline et le plaisir du jeu.</p>
        <div class="hero-actions" style="justify-content:center;">
            <a href="<?php echo url_path('/services'); ?>" class="btn btn-outline" data-i18n="discover_programs">Découvrir nos programmes</a>
            <a href="<?php echo url_path('/formulair'); ?>" class="btn btn-primary" data-i18n="inscription">Inscription</a>
        </div>
    </div>
    <div class="story-stats reveal" style="max-width:var(--max-w);margin:var(--s6) auto 0;">
        <div class="stat-card"><span>120+</span><p data-i18n="stat_players">Joueurs formés</p></div>
        <div class="stat-card"><span>30+</span><p data-i18n="stat_matches">Matchs par saison</p></div>
        <div class="stat-card"><span>15</span><p data-i18n="stat_coachs">Coachs certifiés</p></div>
        <div class="stat-card"><span>5</span><p data-i18n="stat_programs">Programmes dédiés</p></div>
    </div>
</section>

<?php require_once __DIR__ . '/lib/footer.php'; ?>
