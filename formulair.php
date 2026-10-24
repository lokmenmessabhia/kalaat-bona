<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';
$apiUrl = url_path('/api/inscription_create.php');
$s = get_settings();
require_once __DIR__ . '/lib/db.php';
$pdo = db();
$progs = $pdo->query("SELECT id_programme, nom_programme, icone FROM programme ORDER BY nom_programme ASC")->fetchAll(PDO::FETCH_ASSOC);

$is_admin_mode = isset($_GET['admin']) && $_GET['admin'] == '1';
if ($is_admin_mode) {
    require_once __DIR__ . '/lib/auth.php';
    if (!admin_current()) {
        $is_admin_mode = false;
    }
}

$page_title = htmlspecialchars((string)$s['site_name']) . ' — Inscription';
$hide_nav = $is_admin_mode;
$body_class = $is_admin_mode ? '' : 'page-form';
$extra_styles = '
.page-form .main-content { padding-top: 80px; }

.container-form {
    max-width: 900px;
    margin: 0 auto;
    background: var(--c-white);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-hover);
    overflow: hidden;
}
.form-header {
    background: var(--c-navy);
    color: var(--c-white);
    padding: var(--s8) var(--s5);
    text-align: center;
    position: relative;
    overflow: hidden;
    isolation: isolate;
}
.form-header::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(108,173,223,0.1), transparent);
}
.form-header::after {
    content: "";
    position: absolute;
    top: 50%; right: 0;
    width: 70%; height: 100%;
    background: url("logo_kala.webp") left center / cover no-repeat;
    transform: translateY(-50%);
    opacity: 0.08;
    filter: grayscale(1);
    pointer-events: none;
    mask: linear-gradient(to right, transparent 30%, #000 55%);
    -webkit-mask: linear-gradient(to right, transparent 30%, #000 55%);
}
.form-header h1 { font-size: clamp(1.4rem, 4vw, 2rem); font-weight: 800; margin-bottom: 8px; color: var(--c-white); position: relative; }
.form-header p { opacity: 0.8; font-size: clamp(0.85rem, 2vw, 1rem); position: relative; }
.form-body { padding: var(--s6); }
.form-section { margin-bottom: var(--s7); }
.form-section .section-title { font-size: clamp(1rem, 2.2vw, 1.1rem); font-weight: 700; margin-bottom: var(--s5); color: var(--c-navy); display: flex; align-items: center; gap: var(--s2); }
.form-section .section-title i { color: var(--c-sky); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--s4); }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: clamp(0.8rem, 1.8vw, 0.85rem); font-weight: 600; color: var(--c-text); }
.form-group .required { color: var(--c-error); }
.form-control { padding: 10px 14px; border-radius: var(--r-md); border: 1px solid var(--c-border); background: var(--c-offwhite); font-size: clamp(0.85rem, 1.8vw, 0.9rem); color: var(--c-text); transition: all var(--t-fast); outline: none; }
.date-selector { display: grid; grid-template-columns: 1fr 2fr 1.5fr; gap: 8px; }
.date-selector .form-control { text-align: center; cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2210%22 height=%226%22%3E%3Cpath d=%22M0 0l5 6 5-6z%22 fill=%22%2364748b%22/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 28px; }
.date-selector .form-control:focus { border-color: var(--c-sky); box-shadow: 0 0 0 3px rgba(108,173,223,0.15); }
.form-control:focus { border-color: var(--c-sky); box-shadow: 0 0 0 3px rgba(108,173,223,0.15); }
.form-control::placeholder { color: var(--c-text-muted); }

/* Custom file input */
.file-upload-wrap {
    display: flex;
    align-items: center;
    gap: var(--s3);
}
.file-upload-wrap input[type="file"] {
    display: none;
}
.file-upload-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--s2);
    padding: 10px 20px;
    background: var(--c-navy);
    color: var(--c-sky);
    border: 2px solid var(--c-sky);
    border-radius: var(--r-md);
    font-size: clamp(0.8rem, 1.8vw, 0.85rem);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--t-base);
    font-family: inherit;
    white-space: nowrap;
}
.file-upload-btn:hover { background: var(--c-navy-soft); }
.file-upload-btn i { font-size: 0.9rem; color: var(--c-sky); }
.file-name {
    font-size: clamp(0.75rem, 1.5vw, 0.8rem);
    color: var(--c-text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 180px;
}
body.dark .file-upload-btn { background: var(--c-sky); border-color: var(--c-navy); }
body.dark .file-upload-btn:hover { background: var(--c-sky-light); }
body.dark .file-upload-btn i { color: var(--c-navy); }
.form-body .btn-primary { width: 100%; margin-top: var(--s5); padding: var(--s4) var(--s5); font-size: clamp(0.9rem, 2vw, 1rem); justify-content: center; }

/* Parent section card */
.section-parent {
    background: var(--c-offwhite);
    border: 1px solid var(--c-border);
    border-radius: var(--r-lg);
    padding: var(--s5) var(--s5) var(--s6);
    margin-bottom: var(--s7);
}
.parent-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--s4);
}
.parent-head .section-title {
    margin-bottom: 0;
    gap: var(--s2);
    font-size: clamp(0.95rem, 2vw, 1.05rem);
}
.parent-toggle {
    display: none;
    align-items: center;
    gap: 6px;
    padding: var(--s1) var(--s3);
    border-radius: var(--r-full);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--c-text-muted);
    background: var(--c-border);
    border: none;
    cursor: pointer;
    transition: all var(--t-fast);
    user-select: none;
    font-family: inherit;
}
.parent-toggle:hover {
    background: var(--c-sky);
    color: var(--c-white);
}
.parent-toggle i { font-size: 0.6rem; transition: transform var(--t-fast); }
.parent-toggle.collapsed i { transform: rotate(-90deg); }
.parent-body { transition: all 0.25s ease; }
.parent-body.collapsed { display: none; }
.parent-hint {
    font-size: 0.8rem;
    color: var(--c-text-muted);
    margin-bottom: var(--s4);
    padding: var(--s2) var(--s3);
    border-left: 3px solid var(--c-sky);
    background: var(--c-sky-pale);
    border-radius: 0 var(--r-sm) var(--r-sm) 0;
}
/* Dark mode overrides */
body.dark .form-header { background: #0a1628; color: #ffffff; }
body.dark .form-header h1 { color: #ffffff; }
body.dark .form-header p { color: rgba(255,255,255,0.8); }
body.dark .form-section .section-title { color: var(--c-text); }
body.dark .parent-hint { background: rgba(108,173,223,0.08); }
body.dark .section-parent { border-color: rgba(108,173,223,0.12); }
@media (max-width: 768px) {
    .section-parent { padding: var(--s4); }
    .parent-head { flex-direction: column; align-items: flex-start; gap: var(--s2); }
}
@media (max-width: 768px) {
    .page-form .main-content { padding: var(--s4); }
    .container-form { border-radius: var(--r-md); }
    .form-header { padding: var(--s6) var(--s4); }
    .form-header h1 { font-size: clamp(1.2rem, 5vw, 1.5rem); }
    .form-body { padding: var(--s4); }
    .form-grid { grid-template-columns: 1fr; gap: var(--s3); }
    .form-section .section-title { font-size: clamp(0.9rem, 4vw, 1rem); }
    .date-selector { grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
    .date-selector .form-control { padding: 8px 6px; padding-right: 20px; background-position: right 6px center; background-size: 8px 5px; }
}
@media (max-width: 480px) {
    .form-header { padding: var(--s5) var(--s3); }
    .form-header h1 { font-size: clamp(1rem, 6vw, 1.2rem); }
    .form-header p { font-size: clamp(0.8rem, 4vw, 0.85rem); }
    .form-body { padding: var(--s3); }
    .form-body .btn-primary { padding: var(--s3) var(--s4); }
}
';
if ($is_admin_mode) {
    $extra_styles .= '
.topbar {
    background: var(--c-navy);
    color: var(--c-white);
    padding: 16px 22px;
    display:flex;
    align-items:center;
    justify-content: space-between;
}
.topbar h1 { font-size: 1.2rem; margin: 0; }
.topbar-link { color: var(--c-white); text-decoration:none; font-weight: 600; transition: opacity 0.2s; }
.topbar-link:hover { opacity: 0.8; }
@media (max-width: 768px) {
    .topbar { flex-direction: column; gap: 8px; padding: 12px 16px; text-align: center; }
    .topbar h1 { font-size: 1rem; }
    .topbar div { flex-wrap: wrap; justify-content: center; }
}
';
}
require_once __DIR__ . '/lib/header.php';
?>

<?php if ($is_admin_mode): ?>
<div class="topbar">
    <h1 data-i18n="admin_dashboard_title">Dashboard Admin — Nouveau Joueur</h1>
    <div style="display:flex; gap:16px;">
        <a class="topbar-link" href="<?php echo url_path('/admin/dashboard'); ?>" data-i18n="admin_home">Accueil</a>
        <a class="topbar-link" href="<?php echo url_path('/admin/joueurs'); ?>" data-i18n="admin_players">Joueurs</a>
        <a class="topbar-link" href="<?php echo url_path('/admin/parents'); ?>" data-i18n="admin_parents">Parents</a>
    </div>
</div>
<?php endif; ?>

<div class="<?php echo $is_admin_mode ? '' : 'main-content'; ?>" style="padding: 40px 20px;">
    <div class="container-form">
        <div class="form-header">
            <h1 data-i18n="form_title">Inscription à l'Académie</h1>
            <p data-i18n="form_subtitle">Formulaire officiel du Kalaat Bona FC</p>
        </div>

        <div class="form-body">
            <div id="status" style="display:none; margin-bottom:30px; padding:20px; border-radius:12px;"></div>

            <form id="registrationForm">
                <div class="form-section">
                    <h2 class="section-title"><i class="fa-solid fa-user"></i> <span data-i18n="personal_info">Informations Personnelles</span></h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><span data-i18n="first_name">Prénom</span> <span class="required">*</span></label>
                            <input type="text" name="prenom" class="form-control" placeholder="Entrez votre prénom" data-i18n-placeholder="enter_first_name" required>
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="last_name">Nom</span> <span class="required">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="Entrez votre nom" data-i18n-placeholder="enter_last_name" required>
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="birth_date">Date de naissance</span> <span class="required">*</span></label>
                            <div class="date-selector">
                                <select id="dobDay" class="form-control" required>
                                    <option value="" data-i18n="day_label">Jour</option>
                                </select>
                                <select id="dobMonth" class="form-control" required>
                                    <option value="" data-i18n="month_label">Mois</option>
                                    <option value="1" data-i18n="month_jan">Janvier</option>
                                    <option value="2" data-i18n="month_feb">Février</option>
                                    <option value="3" data-i18n="month_mar">Mars</option>
                                    <option value="4" data-i18n="month_apr">Avril</option>
                                    <option value="5" data-i18n="month_may">Mai</option>
                                    <option value="6" data-i18n="month_jun">Juin</option>
                                    <option value="7" data-i18n="month_jul">Juillet</option>
                                    <option value="8" data-i18n="month_aug">Août</option>
                                    <option value="9" data-i18n="month_sep">Septembre</option>
                                    <option value="10" data-i18n="month_oct">Octobre</option>
                                    <option value="11" data-i18n="month_nov">Novembre</option>
                                    <option value="12" data-i18n="month_dec">Décembre</option>
                                </select>
                                <select id="dobYear" class="form-control" required>
                                    <option value="" data-i18n="year_label">Année</option>
                                </select>
                                <input type="hidden" id="dateNaissance" name="dateNaissance">
                            </div>
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="gender">Sexe</span> <span class="required">*</span></label>
                            <select name="sexe" class="form-control" required>
                                <option value="" data-i18n="select_option">Sélectionner</option>
                                <option value="masculin" data-i18n="male">Masculin</option>
                                <option value="feminin" data-i18n="female">Féminin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label data-i18n="nationality">Nationalité</label>
                            <input type="text" name="nationalite" class="form-control" placeholder="Pays" data-i18n-placeholder="country">
                        </div>
                        <div class="form-group">
                            <label data-i18n="full_address">Adresse complète</label>
                            <input type="text" name="adresse" class="form-control">
                        </div>
                        <div class="form-group">
                            <label data-i18n="profile_photo">Photo de profil</label>
                            <div class="file-upload-wrap">
                                <label class="file-upload-btn" for="photoInput"><i class="fa-solid fa-camera"></i> <span data-i18n="choose_file">Choisir</span></label>
                                <span class="file-name" id="photoFileName" data-i18n="no_file_chosen">Aucun fichier</span>
                                <input type="file" name="photo" id="photoInput" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section section-parent" id="sectionParent">
                    <div class="parent-head">
                        <h2 class="section-title">
                            <i class="fa-solid fa-users"></i>
                            <span data-i18n="parent_info">Informations du Parent / Tuteur</span>
                        </h2>
                        <button id="parentToggle" class="parent-toggle" onclick="toggleParentSection()" type="button">
                            <i class="fa-solid fa-chevron-down"></i>
                            <span data-i18n="optional">Optionnel</span>
                        </button>
                    </div>
                    <div class="parent-body" id="parentBody">
                        <p class="parent-hint" data-i18n="parent_hint">Les informations du parent sont requises pour les joueurs mineurs.</p>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><span data-i18n="parent_name">Nom du Parent</span> <span class="required">*</span></label>
                                <input type="text" id="nomParent" name="nomParent" class="form-control" placeholder="Nom complet" data-i18n-placeholder="full_name">
                            </div>
                            <div class="form-group">
                                <label><span data-i18n="parent_phone">Téléphone Parent</span> <span class="required">*</span></label>
                                <input type="tel" id="telephoneParent" name="telephoneParent" class="form-control" placeholder="05/06/07...">
                            </div>
                            <div class="form-group">
                                <label><span data-i18n="parent_email">Email Parent</span> <span class="required">*</span></label>
                                <input type="email" id="emailParent" name="emailParent" class="form-control" placeholder="email@exemple.com">
                            </div>
                        </div>
                        <div style="margin-top:12px; padding:10px 14px; background:rgba(108,173,223,0.06); border-radius:8px; font-size:0.82rem; color:var(--c-text-muted);">
                            <i class="fa-solid fa-key" style="color:var(--c-sky);margin-right:6px;"></i>
                            <span data-i18n="auto_password">Un mot de passe sera généré automatiquement et envoyé par email.</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fa-solid fa-phone"></i> <span data-i18n="contact_prog">Contact & Programme</span></h2>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column:1/-1;">
                            <label><span data-i18n="player_phone">Téléphone du Joueur</span> <span class="required">*</span></label>
                            <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="Votre numéro" data-i18n-placeholder="your_number" required>
                        </div>
                        <div class="form-group" id="playerEmailGroup" style="display:none; grid-column:1/-1;">
                            <label><span data-i18n="player_email">Email du Joueur</span> <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Requis si +18 ans" data-i18n-placeholder="required_over_18">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fa-solid fa-futbol"></i> <span data-i18n="football_info">Informations Footballistiques</span></h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="poste"><span data-i18n="preferred_position">Position préférée</span> <span class="required">*</span></label>
                            <select id="poste" name="poste" class="form-control" required>
                                <option value="" data-i18n="select_position">Sélectionner une position</option>
                                <option value="gardien" data-i18n="gardien">Gardien de but</option>
                                <option value="defenseur-central" data-i18n="defenseur_central">Défenseur central</option>
                                <option value="defenseur-lateral" data-i18n="defenseur_lateral">Défenseur latéral</option>
                                <option value="milieu-defensif" data-i18n="milieu_defensif">Milieu défensif</option>
                                <option value="milieu-central" data-i18n="milieu_central">Milieu central</option>
                                <option value="milieu-offensif" data-i18n="milieu_offensif">Milieu offensif</option>
                                <option value="ailier" data-i18n="ailier">Ailier</option>
                                <option value="attaquant" data-i18n="attaquant">Attaquant</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="niveau" data-i18n="current_level">Niveau actuel</label>
                            <select id="niveau" name="niveau" class="form-control">
                                <option value="" data-i18n="select_level">Sélectionner le niveau</option>
                                <option value="debutant" data-i18n="beginner">Débutant</option>
                                <option value="intermediaire" data-i18n="intermediate">Intermédiaire</option>
                                <option value="avance" data-i18n="advanced">Avancé</option>
                                <option value="competition" data-i18n="competition_lvl">Compétition</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="experience" data-i18n="years_experience">Années d'expérience</label>
                            <select id="experience" name="experience" class="form-control">
                                <option value="" data-i18n="select_option">Sélectionner</option>
                                <option value="0-1" data-i18n="experience_0_1">0-1 an</option>
                                <option value="2-3" data-i18n="experience_2_3">2-3 ans</option>
                                <option value="4-5" data-i18n="experience_4_5">4-5 ans</option>
                                <option value="6+" data-i18n="experience_6_plus">6+ ans</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="taille" data-i18n="height_label">Taille (cm)</label>
                            <input type="number" id="taille" name="taille" class="form-control" placeholder="170" min="50" max="250" step="0.1">
                        </div>
                        <div class="form-group">
                            <label for="poids" data-i18n="weight_label">Poids (kg)</label>
                            <input type="number" id="poids" name="poids" class="form-control" placeholder="65" min="20" max="200" step="0.1">
                        </div>
                        <div class="form-group">
                            <label for="clubPrecedent" data-i18n="previous_club">Club précédent (si applicable)</label>
                            <input type="text" id="clubPrecedent" name="clubPrecedent" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="maladieChronique" data-i18n="chronic_disease">Maladie chronique / Allergies</label>
                            <textarea id="maladieChronique" name="maladie_chronique" class="form-control" rows="2" placeholder="Ex: Asthme, Diabète, Allergie alimentaire..." data-i18n-placeholder="chronic_disease_placeholder"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fa-solid fa-comment"></i> <span data-i18n="comments_programs">Commentaires & Programme</span></h2>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label data-i18n="desired_programs">Programmes souhaités</label>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <?php foreach ($progs as $p): ?>
                                <label style="display:flex; align-items:center; gap:10px; font-weight:500; cursor:pointer;">
                                    <input type="checkbox" name="programmes[]" value="<?php echo (int)$p['id_programme']; ?>" class="prog-checkbox"> <?php echo htmlspecialchars((string)($p['icone'] ?? '')); ?> <?php echo htmlspecialchars((string)$p['nom_programme']); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label data-i18n="comments_motivation">Commentaires / Motivations</label>
                            <textarea name="commentaires" class="form-control" rows="3" placeholder="Parlez-nous de vous..." data-i18n-placeholder="tell_us_about_you"></textarea>
                        </div>
                    </div>
                </div>

                <div style="margin-top:16px; padding:12px 16px; background:rgba(108,173,223,0.08); border-left:4px solid var(--c-sky); border-radius:8px; font-size:0.85rem; color:var(--c-text-muted);">
                    <i class="fa-solid fa-envelope" style="color:var(--c-sky);margin-right:6px;"></i>
                    <span data-i18n="email_notice">Un email vous sera envoyé dès que votre inscription sera acceptée ou refusée.</span>
                </div>
                <button type="submit" class="btn-primary" id="submitBtn" style="width:100%; margin-top:20px; padding:1.2rem; font-size:1.1rem; justify-content:center;">
                    <span data-i18n="submit_registration">Soumettre l'inscription</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Populate day and year selects
(function() {
    const daySel = document.getElementById('dobDay');
    for (let d = 1; d <= 31; d++) {
        const opt = document.createElement('option');
        opt.value = String(d).padStart(2, '0');
        opt.textContent = d;
        daySel.appendChild(opt);
    }
    const yearSel = document.getElementById('dobYear');
    const curYear = new Date().getFullYear();
    for (let y = curYear; y >= curYear - 80; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        yearSel.appendChild(opt);
    }
})();

function updateDate() {
    const day = document.getElementById('dobDay').value;
    const month = document.getElementById('dobMonth').value;
    const year = document.getElementById('dobYear').value;
    const hidden = document.getElementById('dateNaissance');
    if (day && month && year) {
        hidden.value = year + '-' + String(month).padStart(2, '0') + '-' + day;
    } else {
        hidden.value = '';
    }
    hidden.dispatchEvent(new Event('change'));
}

document.getElementById('dobDay').addEventListener('change', updateDate);
document.getElementById('dobMonth').addEventListener('change', updateDate);
document.getElementById('dobYear').addEventListener('change', updateDate);

const dateInput = document.getElementById('dateNaissance');
const sectionParent = document.getElementById('sectionParent');
const parentToggle = document.getElementById('parentToggle');
const playerEmailGroup = document.getElementById('playerEmailGroup');
const playerEmailInput = document.getElementById('email');
const playerPhoneInput = document.getElementById('telephone');
const playerPhoneMarker = document.querySelector('#telephone').closest('.form-group').querySelector('.required');
const parentRequiredMarkers = document.querySelectorAll('#sectionParent .required');
const parentFields = [
    document.getElementById('nomParent'),
    document.getElementById('telephoneParent'),
    document.getElementById('emailParent')
];

dateInput.addEventListener('change', function() {
    if (!this.value) return;
    const birthDate = new Date(this.value);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) { age--; }
    if (age >= 18) {
        parentToggle.style.display = 'inline-flex';
        parentRequiredMarkers.forEach(el => el.style.display = 'none');
        parentFields.forEach(f => { f.required = false; });
        playerEmailGroup.style.display = 'block';
        playerEmailInput.required = true;
        playerPhoneInput.required = true;
        if (playerPhoneMarker) playerPhoneMarker.style.display = 'inline';
    } else {
        parentToggle.style.display = 'none';
        parentRequiredMarkers.forEach(el => el.style.display = 'inline');
        parentFields.forEach(f => { f.required = true; });
        playerEmailGroup.style.display = 'none';
        playerEmailInput.required = false;
        playerEmailInput.value = '';
        playerPhoneInput.required = false;
        if (playerPhoneMarker) playerPhoneMarker.style.display = 'none';
        if (age < 6) {
            alert(uiManager.t('age_note'));
        }
    }
});

let parentVisible = true;
function toggleParentSection() {
    const body = document.getElementById('parentBody');
    const toggle = document.getElementById('parentToggle');
    parentVisible = !parentVisible;
    body.classList.toggle('collapsed', !parentVisible);
    toggle.classList.toggle('collapsed', !parentVisible);
    toggle.querySelector('i').className = parentVisible ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up';
    toggle.querySelector('span:last-child').textContent = parentVisible ? uiManager.t('toggle_optional') : uiManager.t('toggle_show');
}

window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const programmeQuery = urlParams.get('programme');
    if(programmeQuery) {
        const targetCheckbox = document.querySelector(`input[name="programmes[]"][value="${programmeQuery}"]`);
        if(targetCheckbox) {
            targetCheckbox.checked = true;
        }
    }
});

document.getElementById('registrationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const statusEl = document.getElementById('status');
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + uiManager.t('sending');
    const formData = new FormData(this);
    try {
        const res = await fetch('<?php echo $apiUrl; ?>', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.ok) {
            this.reset();
            statusEl.style.display = 'block';
            statusEl.style.background = 'rgba(108,173,223,0.08)';
            statusEl.style.color = 'var(--c-text)';
            statusEl.style.border = '1px solid rgba(108,173,223,0.2)';
            statusEl.innerHTML = '<div style="text-align:center;"><i class="fa-solid fa-check-circle" style="color:#10b981;font-size:2rem;margin-bottom:10px;"></i><p style="font-weight:700;font-size:1.1rem;margin-bottom:6px;">' + uiManager.t('form_submitted_success') + '</p><p style="font-size:0.9rem;color:#64748b;"><i class="fa-solid fa-envelope" style="color:var(--c-sky);margin-right:6px;"></i> ' + uiManager.t('form_success_msg_2') + '</p></div>';
            setTimeout(() => {
                window.location.href = '<?php echo url_path("/gool"); ?>?id=' + json.id_joueur;
            }, 3000);
        } else {
            throw new Error(json.error || uiManager.t('unknown_error'));
        }
    } catch (err) {
        statusEl.style.display = 'block';
        statusEl.style.background = 'rgba(239, 68, 68, 0.08)';
        statusEl.style.color = '#ef4444';
        statusEl.style.border = '1px solid rgba(239, 68, 68, 0.15)';
            statusEl.innerHTML = '<strong>' + uiManager.t('form_error_label') + '</strong> ' + err.message;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span data-i18n="submit_registration">Soumettre l\'inscription</span> <i class="fa-solid fa-arrow-right"></i>';
    }
});

// File input display with size validation
document.getElementById('photoInput')?.addEventListener('change', function() {
    const file = this.files[0];
    const label = document.getElementById('photoFileName');
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            label.textContent = uiManager.t('file_too_heavy');
            label.style.color = '#ef4444';
            this.value = '';
        } else {
            label.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(1) + ' ' + uiManager.t('file_size_unit_short') + ')';
            label.style.color = '';
        }
    } else {
        label.textContent = uiManager.t('no_file_chosen');
        label.style.color = '';
    }
});
</script>

<?php
$hide_footer = $is_admin_mode;
require_once __DIR__ . '/lib/footer.php';
?>
