<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';
require_once __DIR__ . '/lib/db.php';
$s = get_settings();
$pdo = db();

$services = $pdo->query("
    SELECT * FROM programme
    WHERE nom_programme != ''
      AND (lieu != '' OR age != '' OR places != '' OR description != '')
    ORDER BY id_programme ASC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Nos Programmes — ' . htmlspecialchars((string)$s['site_name']);
$body_class = 'page-services';
$extra_styles = '
.hero-services {
    position: relative;
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: calc(var(--nav-h) + var(--s8)) var(--s5) var(--s8);
    background: url("services_bg.webp") center/cover no-repeat;
    overflow: hidden;
}
.hero-services::before {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(10, 20, 50, 0.75);
    pointer-events: none;
}
.hero-services-container {
    max-width: var(--max-w);
    width: 100%;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--s8);
    align-items: center;
    position: relative;
    z-index: 2;
}
.hero-services-left {
    display: flex;
    flex-direction: column;
    gap: var(--s5);
}
.hero-services-right {
    display: flex;
    justify-content: center;
    align-items: center;
}
.hero-services .hero-title {
    font-size: clamp(2.4rem, 5.5vw, 4.2rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.08;
    color: var(--c-white);
}
.gold-text { color: var(--c-sky); }
.hero-services .hero-subtitle {
    font-size: clamp(0.9rem, 2.2vw, 1rem);
    max-width: 560px;
    color: rgba(255,255,255,0.7);
    line-height: 1.75;
}
.btn-gold {
    display: inline-flex;
    align-items: center;
    gap: var(--s2);
    padding: var(--s3) var(--s5);
    border-radius: var(--r-full);
    font-weight: 600;
    font-size: clamp(0.85rem, 1.8vw, 0.9rem);
    background: var(--c-sky);
    color: var(--c-navy);
    text-decoration: none;
    transition: all var(--t-base);
    border: none;
    cursor: pointer;
}
.btn-gold:hover { background: var(--c-sky-light); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(108,173,223,0.4); }
.hero-services-logo {
    width: min(380px, 100%);
    height: auto;
    border-radius: var(--r-lg);
    animation: floatLogo 6s ease-in-out infinite;
}
.features-section {
    background: var(--c-offwhite);
    padding: var(--s9) var(--s5);
}
.features-grid {
    max-width: var(--max-w);
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--s5);
    align-items: stretch;
}
.feature-card {
    background: var(--c-white);
    border: 1px solid var(--c-border);
    border-radius: var(--r-lg);
    overflow: hidden;
    transition: all var(--t-base);
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-card);
    height: 100%;
}
.feature-card-image {
    width: 100%;
    height: auto;
    display: block;
    object-fit: contain;
    background: var(--c-sky-pale);
    transition: transform var(--t-base);
}
.feature-card:hover .feature-card-image {
    transform: scale(1.05);
}
.feature-card-content {
    padding: var(--s6) var(--s5);
    display: flex;
    flex-direction: column;
    flex: 1;
}
.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    border-color: var(--c-sky);
    overflow: hidden;
}
.feature-title {
    font-size: clamp(1.1rem, 2.8vw, 1.3rem);
    font-weight: 700;
    margin-bottom: var(--s3);
    color: var(--c-text);
}
.feature-details {
    list-style: none;
    padding: 0;
    margin: 0 0 var(--s5);
    font-size: clamp(0.85rem, 1.8vw, 0.9rem);
    color: var(--c-text-muted);
    flex: 1;
}
.feature-details li i { width: 16px; color: var(--c-sky); font-size: clamp(0.75rem, 1.5vw, 0.8rem); }
.feature-details li { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
.btn-register {
    background: transparent;
    color: var(--c-text);
    text-decoration: none;
    padding: 12px;
    border-radius: var(--r-full);
    font-weight: 600;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: block;
    font-size: clamp(0.85rem, 1.8vw, 0.9rem);
    border: 1.5px solid var(--c-border);
    cursor: pointer;
    margin-top: auto;
}
.btn-register:hover { background: var(--c-navy); color: #fff; border-color: var(--c-navy); transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,24,56,0.25); }
.feature-card:hover .btn-register { background: var(--c-navy); color: #fff; border-color: var(--c-navy); }
.reveal { opacity: 0; transform: translateY(30px); transition: all 0.6s ease; }
.reveal.revealed { opacity: 1; transform: translateY(0); }

/* Dark mode overrides */
body.dark .btn-register { background: transparent; color: var(--c-text); border-color: var(--c-border); }
body.dark .btn-register:hover { background: var(--c-sky); color: var(--c-navy); border-color: var(--c-sky); }
body.dark .feature-card:hover .btn-register { background: var(--c-sky); color: var(--c-navy); border-color: var(--c-sky); }

@media (max-width: 992px) {
    .hero-services-container {
        grid-template-columns: 1fr;
        text-align: center;
        gap: var(--s6);
    }
    .hero-services-left { align-items: center; }
    .hero-services .hero-subtitle { max-width: 100%; }
    .hero-services-right { order: -1; }
    .hero-services-logo { max-width: 200px; }
}
@media (max-width: 768px) {
    .hero-services .hero-title { font-size: clamp(1.8rem, 5vw, 2.8rem); }
    .features-section { padding: var(--s7) var(--s4); }
    .features-grid { gap: var(--s4); grid-template-columns: 1fr; }
    .hero-services-logo { max-width: 140px; }
    .feature-card-content { padding: var(--s5) var(--s4); }
    .feature-card-image { height: auto; }
}
@media (max-width: 480px) {
    .hero-services { min-height: 50vh; padding: calc(var(--nav-h) + var(--s6)) var(--s4) var(--s6); }
    .hero-services .hero-title { font-size: clamp(1.5rem, 10vw, 2rem); }
    .hero-services-logo { max-width: 100px; }
    .features-section { padding: var(--s6) var(--s3); }
    .features-grid { gap: var(--s3); }
    .feature-card-content { padding: var(--s4) var(--s3); }
    .feature-card-image { height: auto; }
    .feature-icon { width: 44px; height: 44px; font-size: clamp(0.9rem, 4vw, 1rem); }
}
';
require_once __DIR__ . '/lib/header.php';
?>

<!-- Services Hero -->
<section class="hero-services">
    <div class="hero-services-container">
        <div class="hero-services-left">
            <h1 class="hero-title" data-i18n="programs_hero_title">Programmes <span class="gold-text">d'Élite</span></h1>
            <p class="hero-subtitle" data-i18n="programs_hero_subtitle">Découvrez nos méthodes de formation conçues pour transformer le potentiel en excellence. Kalaat Bona FC offre un environnement professionnel pour chaque étape de votre progression.</p>
        </div>
        <div class="hero-services-right">
            <picture>
                <source srcset="logo_kala.webp" type="image/webp">
                <img src="logo_kala.png" alt="Kalaat Bona FC" class="hero-services-logo" width="420" height="420" loading="eager">
            </picture>
        </div>
    </div>
</section>

<!-- Programs Grid -->
<section class="features-section" id="programe-list">
    <div class="features-grid">
        <?php if (count($services) === 0): ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fa-solid fa-store-slash" style="font-size:2.5rem;margin-bottom:12px;opacity:.5;"></i>
            <p data-i18n="no_services">Aucun service disponible pour le moment.</p>
        </div>
        <?php else: ?>
        <?php foreach ($services as $svc):
            $imgFile = trim((string)($svc['image'] ?? ''));
            $imgPath = 'uploads/programmes/' . $imgFile;
            $hasImage = $imgFile !== '' && file_exists($imgPath);
            $webpPath = '';
            if ($hasImage) {
                $webpFile = pathinfo($imgFile, PATHINFO_FILENAME) . '.webp';
                if (file_exists('uploads/programmes/' . $webpFile)) {
                    $webpPath = 'uploads/programmes/' . $webpFile;
                }
            }
            $dims = $hasImage ? @getimagesize($imgPath) : false;
            $whAttr = $dims ? ' width="' . $dims[0] . '" height="' . $dims[1] . '"' : '';
        ?>
        <div class="feature-card reveal" data-programme-id="<?php echo (int)$svc['id_programme']; ?>">
            <?php if ($hasImage): ?>
            <picture>
                <?php if ($webpPath !== ''): ?>
                <source srcset="<?php echo htmlspecialchars($webpPath); ?>" type="image/webp">
                <?php endif; ?>
                <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars((string)$svc['nom_programme']); ?>" class="feature-card-image"<?php echo $whAttr; ?> loading="lazy" decoding="async">
            </picture>
            <?php else: ?>
            <img src="logo_kala.webp" alt="<?php echo htmlspecialchars((string)$svc['nom_programme']); ?>" class="feature-card-image" width="400" height="400" style="object-fit:contain;padding:20px;background:var(--c-sky-pale);">
            <?php endif; ?>
            <div class="feature-card-content">
                <h3 class="feature-title" data-programme-name><?php echo htmlspecialchars((string)$svc['nom_programme']); ?></h3>
                <?php if (!empty($svc['description']) || !empty($svc['description_en']) || !empty($svc['description_ar'])): ?>
                <p data-programme-desc style="font-size:0.85rem;color:#64748b;margin-bottom:12px;line-height:1.6;"><?php echo nl2br(htmlspecialchars((string)($svc['description'] ?? ''))); ?></p>
                <?php endif; ?>
                <ul class="feature-details">
                    <li data-detail="lieu" style="<?php echo empty($svc['lieu']) && empty($svc['lieu_en']) && empty($svc['lieu_ar']) ? 'display:none;' : ''; ?>"><i class="fa-solid fa-map-pin"></i> <span><?php echo htmlspecialchars((string)($svc['lieu'] ?? '')); ?></span></li>
                    <li data-detail="date_horaire" style="<?php echo empty($svc['date_horaire']) && empty($svc['date_horaire_en']) && empty($svc['date_horaire_ar']) ? 'display:none;' : ''; ?>"><i class="fa-solid fa-calendar-week"></i> <span><?php echo htmlspecialchars((string)($svc['date_horaire'] ?? '')); ?></span></li>
                    <li data-detail="places" style="<?php echo empty($svc['places']) && empty($svc['places_en']) && empty($svc['places_ar']) ? 'display:none;' : ''; ?>"><i class="fa-solid fa-user-group"></i> <span><?php echo htmlspecialchars((string)($svc['places'] ?? '')); ?></span></li>
                    <li data-detail="age" style="<?php echo empty($svc['age']) && empty($svc['age_en']) && empty($svc['age_ar']) ? 'display:none;' : ''; ?>"><i class="fa-solid fa-star"></i> <span><span data-i18n="category_label">Catégorie</span> : <span data-detail-text><?php echo htmlspecialchars((string)($svc['age'] ?? '')); ?></span></span></li>
                </ul>
            </div>
            <a href="<?php echo url_path('/formulair?programme=' . (int)$svc['id_programme']); ?>" class="btn-register" data-i18n="register_session">S'inscrire</a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<script>
function reveal() {
    var reveals = document.querySelectorAll(".reveal");
    for (var i = 0; i < reveals.length; i++) {
        var windowHeight = window.innerHeight;
        var elementTop = reveals[i].getBoundingClientRect().top;
        var elementVisible = 150;
        if (elementTop < windowHeight - elementVisible) {
            reveals[i].classList.add("revealed");
        }
    }
}
window.addEventListener("scroll", reveal);
reveal();

var programmesData = <?php echo json_encode($services, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>;

function applyProgrammeLanguage(lang) {
    programmesData.forEach(function(svc) {
        var card = document.querySelector('[data-programme-id="' + svc.id_programme + '"]');
        if (!card) return;
        var nameEl = card.querySelector('[data-programme-name]');
        var descEl = card.querySelector('[data-programme-desc]');
        if (nameEl) {
            if (lang === 'en' && svc.nom_programme_en) nameEl.textContent = svc.nom_programme_en;
            else if (lang === 'ar' && svc.nom_programme_ar) nameEl.textContent = svc.nom_programme_ar;
            else nameEl.textContent = svc.nom_programme || '';
        }
        if (descEl) {
            if (lang === 'en' && svc.description_en) descEl.innerHTML = svc.description_en.replace(/\n/g, '<br>');
            else if (lang === 'ar' && svc.description_ar) descEl.innerHTML = svc.description_ar.replace(/\n/g, '<br>');
            else descEl.innerHTML = (svc.description || '').replace(/\n/g, '<br>');
            descEl.style.display = descEl.textContent.trim() ? '' : 'none';
        }
        var fields = ['lieu', 'date_horaire', 'places', 'age'];
        fields.forEach(function(f) {
            var li = card.querySelector('[data-detail="' + f + '"]');
            if (!li) return;
            var val = '';
            if (lang === 'en') val = svc[f + '_en'] || svc[f] || '';
            else if (lang === 'ar') val = svc[f + '_ar'] || svc[f] || '';
            else val = svc[f] || '';
            if (f === 'age') {
                var ageText = li.querySelector('[data-detail-text]');
                if (ageText) ageText.textContent = val;
            } else {
                var span = li.querySelector('span');
                if (span) span.textContent = val;
            }
            li.style.display = val ? '' : 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var savedLang = localStorage.getItem('lang') || 'fr';
    applyProgrammeLanguage(savedLang);
});

window.addEventListener('languageChanged', function(e) {
    applyProgrammeLanguage(e.detail.lang);
});
</script>

<?php require_once __DIR__ . '/lib/footer.php'; ?>
