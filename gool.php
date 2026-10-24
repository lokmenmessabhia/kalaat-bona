<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';
$s = get_settings();
$id = $_GET['id'] ?? '--';
$page_title = htmlspecialchars((string)$s['site_name']) . ' — Inscription Réussie !';
$hide_nav = true;
$hide_footer = true;
$body_class = 'page-gool';
$extra_styles = '
body.page-gool {
    margin: 0 !important; min-height: 100vh !important; display: flex !important; align-items: center !important; justify-content: center !important;
    background:
        radial-gradient(circle at center, transparent 0%, rgba(6,31,62,0.9) 100%),
        linear-gradient(rgba(15,75,146,0.4), rgba(6,31,62,0.95)),
        url("stadium.webp") !important;
    background-size: cover !important; background-position: center !important; background-attachment: fixed !important;
    font-family: "Inter", sans-serif !important;
    overflow: hidden !important;
    color: white !important;
}
body.page-gool .main-content {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-height: 100vh !important;
}
.gol-box {
    background: transparent;
    border: none;
    padding: 0; max-width: 1000px; width: 90%;
    text-align: center;
    box-shadow: none;
    position: relative;
    transform-style: preserve-3d;
    animation: boxEnter3D 1.4s cubic-bezier(0.165, 0.840, 0.440, 1.000) forwards,
               floatPulse 6s ease-in-out infinite 1.4s;
    z-index: 10;
}
.gol-box::before {
    content: "";
    position: absolute;
    inset: -40px;
    background: url("logo_kala.webp") center / contain no-repeat;
    opacity: 0.05;
    pointer-events: none;
    animation: floatLogo 10s ease-in-out infinite;
}
@keyframes boxEnter3D {
    0% { opacity: 0; transform: translateZ(-800px) translateY(150px) rotateX(30deg) rotateY(-20deg); }
    50% { opacity: 1; transform: translateZ(50px) translateY(-10px) rotateX(-5deg) rotateY(5deg); }
    100% { opacity: 1; transform: translateZ(0) translateY(0) rotateX(0) rotateY(0); }
}
@keyframes floatPulse {
    0%, 100% { transform: translateY(0) scale(1) rotateX(0) rotateY(0); }
    50% { transform: translateY(-12px) scale(1.02) rotateX(2deg) rotateY(-1deg); }
}
.ball-video {
    width: 100%;
    max-width: 960px;
    aspect-ratio: 16 / 9;
    border-radius: 16px;
    object-fit: cover;
    box-shadow: 0 8px 40px rgba(0,0,0,0.3);
    opacity: 0;
    animation: fadeUp 1s ease forwards 0.8s;
}
.goal-email-notice {
    margin-top: 20px;
    padding: 14px 20px;
    background: rgba(255,255,255,0.06);
    border-left: 4px solid rgba(108,173,223,0.6);
    border-radius: 8px;
    font-size: 0.85rem;
    opacity: 0;
    animation: fadeUp 1s ease forwards 2.2s;
    text-align: center;
}
.goal-email-notice i { color: var(--c-sky); margin-right: 6px; }
.goooall-text-local {
    font-size: clamp(3.5rem, 10vw, 6rem);
    font-weight: 900;
    background: linear-gradient(to right, #fcd34d, #f59e0b, #fbbf24, #f59e0b, #fcd34d);
    background-size: 200% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    text-transform: uppercase;
    font-style: italic;
    letter-spacing: -3px;
    margin: 10px 0;
    filter: drop-shadow(0 15px 30px rgba(245, 158, 11, 0.5));
    animation: popGoalLocal 1.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards 0.5s, shine 3s linear infinite 1.7s;
    opacity: 0;
}
@keyframes popGoalLocal {
    0% { transform: scale(0.3) rotate(-15deg); opacity: 0; filter: blur(10px); }
    60% { transform: scale(1.1) rotate(5deg); opacity: 1; filter: blur(0px); }
    100% { transform: scale(1) rotate(0); opacity: 1; filter: blur(0px); }
}
@keyframes shine { to { background-position: 200% center; } }
.goal-subtext {
    color: white; font-weight: 600; font-size: 1.1rem; letter-spacing: 2px;
    opacity: 0; animation: fadeUp 1s ease forwards 1.2s;
}
@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 0.9; transform: translateY(0); } }
.confetti {
    position: absolute;
    width: 10px; height: 10px;
    top: -20px;
    z-index: 9999;
    pointer-events: none;
    animation: fall linear forwards;
}
@keyframes fall {
    0% { transform: translateY(0) rotate(0deg); opacity: 1; }
    80% { opacity: 1; }
    100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
}
.language-container { position: absolute; top: 20px; right: 20px; display: flex; gap: 15px; z-index: 100; }
.goal-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.95);
    color: var(--navy);
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 800;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    transition: var(--transition-premium);
    text-decoration: none;
    font-family: "Inter", sans-serif;
}
.goal-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 35px rgba(197, 160, 89, 0.4);
}
';
require_once __DIR__ . '/lib/header.php';
?>

<div class="language-container nav-buttons"></div>
<div class="gol-box">
    <video autoplay loop muted playsinline class="ball-video">
        <source src="<?php echo url_path('/ball.webm'); ?>" type="video/webm">
    </video>
    <h1 class="goooall-text-local" data-i18n="goooall">GOOOLL !!!</h1>
    <div class="goal-subtext" data-i18n="goal_success_msg">VOUS ÊTES MAINTENANT INSCRIT DANS VOTRE ACADÉMIE PRÉFÉRÉE</div>
    <div class="goal-email-notice">
        <i class="fa-solid fa-envelope"></i>
        <span data-i18n="goal_email_notice">Un email vous sera envoyé dès que votre inscription sera acceptée ou refusée.</span>
    </div>
    <div class="goal-pid" style="margin-top: 30px; background:rgba(255,255,255,0.06); padding:10px 25px; border-radius:50px; border:1px solid rgba(255,255,255,0.08); opacity:0; animation:fadeUp 1s ease forwards 1.6s;">
        <span data-i18n="goal_player_id">VOTRE ID JOUEUR EST</span> <strong style="color:var(--gold);"><?php echo htmlspecialchars($id); ?></strong>
    </div>
    <div class="goal-action" style="margin-top: 40px; opacity:0; animation:fadeUp 1s ease forwards 2s;">
        <a href="<?php echo url_path('/portal_login'); ?>" class="goal-btn" id="accBtn">
            <span data-i18n="go_to_portal">Accéder au Portail</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</div>

<div id="gool-ball-anim" style="display:none; position:fixed; top:50%; left:-100px; z-index:99999; font-size:5rem; color:#000; text-shadow: 0 10px 20px rgba(0,0,0,0.3); transform:translateY(-50%); pointer-events:none;">
    <i class="fa-solid fa-futbol" style="background: #fff; border-radius: 50%; width: 1em; height: 1em; line-height: 1em; display: inline-block; text-align: center; box-shadow: inset 0 0 0 2px #000;"></i>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const colors = ['#fbbf24', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#ec4899', '#a855f7'];
    const container = document.body;
    for(let i = 0; i < 150; i++) {
        let conf = document.createElement('div');
        conf.className = 'confetti';
        conf.style.left = Math.random() * 100 + 'vw';
        conf.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        if (Math.random() > 0.5) { conf.style.borderRadius = '50%'; }
        conf.style.animationDuration = (Math.random() * 3 + 2) + 's';
        conf.style.animationDelay = (Math.random() * 2) + 's';
        container.appendChild(conf);
    }

    const accBtn = document.getElementById('accBtn');
    if(accBtn) {
        accBtn.addEventListener('click', function(e) {
            e.preventDefault();
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + uiManager.t('goal_redirect');
            this.style.pointerEvents = 'none';
            this.style.opacity = '0.8';
            const ball = document.getElementById('gool-ball-anim');
            ball.style.display = 'block';
            ball.animate([
                { left: '-100px', transform: 'translateY(-50%) rotate(0deg) scale(0.5)' },
                { left: '40%', transform: 'translateY(-200px) rotate(360deg) scale(1.2)' },
                { left: '110vw', transform: 'translateY(-50%) rotate(720deg) scale(1)' }
            ], { duration: 1200, easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)' });
            setTimeout(() => { window.location.href = this.href; }, 1000);
        });
    }
});
</script>
</body>
</html>
