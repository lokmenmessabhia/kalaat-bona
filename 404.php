<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';
$s = get_settings();
$page_title = '404 — Page Non Trouvée | ' . htmlspecialchars((string)$s['site_name']);
$hide_nav = true;
$hide_footer = true;
$body_class = 'page-404';
$extra_styles = '
body.page-404 {
    background: var(--gradient-dark);
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: "Inter", sans-serif;
    text-align: center;
    overflow: hidden;
    margin: 0;
}
.error-container {
    max-width: 600px;
    padding: 40px;
    animation: fadeIn 0.8s ease-out;
    position: relative;
    z-index: 2;
}
.error-container::before {
    content: "";
    position: absolute;
    inset: 0;
    background: url("logo_kala.webp") center / contain no-repeat;
    opacity: 0.04;
    pointer-events: none;
    animation: floatLogo 8s ease-in-out infinite;
}
.error-code {
    font-size: clamp(5rem, 15vw, 10rem);
    font-weight: 900;
    background: var(--gradient-gold);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1;
    margin-bottom: 20px;
    display: inline-block;
    filter: drop-shadow(0 20px 40px rgba(197, 160, 89, 0.2));
}
.error-container h1 {
    font-size: 2rem;
    color: var(--white);
    margin-bottom: 15px;
    font-weight: 800;
}
.error-container p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1.05rem;
    margin-bottom: 35px;
    line-height: 1.7;
}
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 700;
    transition: var(--transition-premium);
    font-family: "Inter", sans-serif;
    font-size: 0.95rem;
}
.back-btn:hover {
    background: var(--gradient-gold);
    color: var(--navy-deeper);
    transform: translateY(-5px);
    box-shadow: var(--shadow-gold);
}
.ball-bg {
    position: absolute;
    font-size: 20rem;
    color: rgba(197, 160, 89, 0.03);
    z-index: 1;
    bottom: -50px;
    right: -50px;
    transform: rotate(-15deg);
    pointer-events: none;
}
.error-particles {
    position: absolute;
    inset: 0;
    overflow: hidden;
    z-index: 1;
}
.page-404 .main-content {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-height: 100vh !important;
    position: relative;
}
.hero-particle {
    position: absolute;
    border-radius: 50%;
    background: rgba(197, 160, 89, 0.25);
    animation: particleFloat 8s ease-in-out infinite;
    pointer-events: none;
}
@keyframes particleFloat {
    0%, 100% { transform: translateY(0) scale(0); opacity: 0; }
    50% { transform: translateY(-80px) scale(1); opacity: 1; }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
';
require_once __DIR__ . '/lib/header.php';
?>

<div class="error-particles"></div>
<div class="error-container">
    <div class="error-code">404</div>
    <h1 data-i18n="page_not_found">Oups ! Hors-jeu.</h1>
    <p data-i18n="page_not_found_desc">La page que vous recherchez semble avoir quitté le terrain ou n'a jamais existé. Pas d'inquiétude, revenons au centre de formation.</p>
    <a href="<?php echo url_path('/index'); ?>" class="back-btn">
        <i class="fa-solid fa-house"></i>
        <span data-i18n="back_to_home">Retour à l'Accueil</span>
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</div>
<div class="ball-bg"><i class="fa-solid fa-futbol"></i></div>

<script>
(function() {
    const container = document.querySelector('.error-particles');
    if (!container) return;
    for (let i = 0; i < 20; i++) {
        const p = document.createElement('div');
        p.className = 'hero-particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.animationDelay = (Math.random() * 8) + 's';
        p.style.animationDuration = (6 + Math.random() * 6) + 's';
        p.style.width = (2 + Math.random() * 3) + 'px';
        p.style.height = p.style.width;
        container.appendChild(p);
    }
})();
</script>

<?php require_once __DIR__ . '/lib/footer.php'; ?>
