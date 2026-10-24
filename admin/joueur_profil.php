<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';

require_admin();
$admin = admin_current();
$pdo = db();

$id_joueur = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT j.*, 
           p.id_parent, p.nom AS nom_parent, p.telephone as tel_parent, p.email as email_parent,
           pf.poste, pf.niveau, pf.experience, pf.club_precedent
    FROM joueur j
    LEFT JOIN parent p ON j.id_parent = p.id_parent
    LEFT JOIN profil_football pf ON j.id_joueur = pf.id_joueur
    WHERE j.id_joueur = ?
');
$stmt->execute([$id_joueur]);
$joueur = $stmt->fetch();

if (!$joueur) {
    die("Joueur introuvable.");
}

$suivis = $pdo->prepare('SELECT * FROM suivi WHERE id_joueur = ? ORDER BY date_suivi DESC, id_suivi DESC');
$suivis->execute([$id_joueur]);
$suivis_list = $suivis->fetchAll();

$date = new DateTime($joueur['date_naissance']);
$now = new DateTime();
$age = $now->diff($date)->y;

$admin_page_title = 'Profil Joueur';
$admin_current_page = 'joueurs';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .wrap { max-width: 1200px; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 900px) { .wrap { grid-template-columns: 1fr; } }
    
    .card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); overflow:hidden; margin-bottom: 20px;}
    .card .hd { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display:flex; align-items:center; justify-content: space-between; }
    .card .hd h2 { font-size: 1.15rem; color: var(--c-navy); }
    .card .bd { padding: 20px; }
    
    .profile-hdr { display: flex; align-items: center; gap: 20px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px;}
    .avatar-l { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid var(--c-sky); box-shadow: 0 5px 15px rgba(0,0,0,0.1); background: #e2e8f0; display:flex; align-items:center; justify-content:center;font-size: 2rem; color: #94a3b8;}
    .profile-name { font-size: 1.5rem; font-weight: 900; margin-bottom: 6px; color: var(--c-navy);}
    .tag { display: inline-block; padding: 5px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: bold; background: rgba(108,173,223,0.1); color: var(--c-navy); border: 1px solid rgba(108,173,223,0.2);}
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .info-item { display: flex; flex-direction: column; gap: 4px; }
    .info-lbl { font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
    .info-val { font-size: 0.95rem; font-weight: 600; }

    .pencil-btn { background: none; border: 1px dashed #94a3b8; cursor: pointer; color: #94a3b8; font-size: 0.85rem; padding: 5px 10px; border-radius: 6px; transition: all .2s; display: inline-flex; align-items: center; gap: 5px; font-weight: 700; }
    .pencil-btn:hover { border-color: var(--c-sky); color: var(--c-sky); background: rgba(108,173,223,0.06); }
    #editJoueurForm .ed-fld { display: none; }
    #editJoueurForm.editing .ed-fld { display: block; }
    #editJoueurForm.editing .view-text { display: none; }
    .ed-inp { width: 100%; padding: 7px 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; transition: border .2s; }
    .ed-inp:focus { border-color: var(--c-sky); outline: none; }
    .save-bar { display: none; gap: 8px; align-items: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid #eee; }
    .save-bar.active { display: flex; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-weight: bold; font-size: 0.9rem; margin-bottom: 6px; color: var(--c-navy); }
    .form-control { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem; transition: 0.2s;}
    .form-control:focus { border-color: var(--c-sky); outline: none; }
    textarea.form-control { resize: vertical; min-height: 80px; }
    .btn { background: var(--c-navy); color: #fff; padding: 12px 24px; border: none; border-radius: 50px; font-size: 0.95rem; font-weight: bold; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 10px rgba(0,24,56,0.3); }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,24,56,0.4); }

    .timeline { border-left: 2px solid rgba(108,173,223,0.2); margin-left: 10px; padding-left: 20px;}
    .timeline-item { position: relative; margin-bottom: 24px; }
    .timeline-item::before { content: ''; position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--c-sky); border: 2px solid #fff; box-shadow: 0 0 0 2px var(--c-navy); }
    .tl-date { font-size: 0.8rem; color: #64748b; font-weight: bold; margin-bottom: 4px; }
    .tl-title { font-size: 1rem; font-weight: 800; color: var(--c-text); display:flex; align-items:center; gap: 8px;}
    .tl-desc { font-size: 0.9rem; color: var(--c-text); margin-top: 6px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .pres-badge { padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; }
    .pres-present { background: rgba(16,185,129,0.1); color: #10b981; }
    .pres-absent { background: rgba(239,68,68,0.1); color: #ef4444; }
    .pres-blesse { background: rgba(245,158,11,0.1); color: #f59e0b; }
    .pres-maladie { background: rgba(139,92,246,0.1); color: #8b5cf6; }
</style>
        <div class="column">
            <div class="card">
                <div class="hd">
                    <h2>Informations Générales</h2>
                    <button type="button" class="pencil-btn" id="editToggle" onclick="toggleEdit()"><i class="fa-solid fa-pen"></i> Modifier</button>
                </div>
                <div class="bd">
                    <div class="profile-hdr">
                        <?php if(!empty($joueur['photo'])): ?>
                            <img src="<?php echo htmlspecialchars(url_path('/uploads/'.$joueur['photo']), ENT_QUOTES, 'UTF-8'); ?>" class="avatar-l" alt="Photo">
                        <?php else: ?>
                            <div class="avatar-l">?</div>
                        <?php endif; ?>
                        <div>
                            <div class="profile-name"><?php echo htmlspecialchars($joueur['prenom'].' '.$joueur['nom'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="display:flex; gap:8px; margin-bottom: 8px;">
                                <span class="tag">ID: #<?php echo (int)$joueur['id_joueur']; ?></span>
                                <span class="tag"><?php echo $age; ?> ans</span>
                                <?php if(!empty($joueur['poste'])): ?>
                                    <span class="tag" style="background:rgba(255,215,0,0.2); color:#b29600; border-color:rgba(255,215,0,0.4);"><?php echo htmlspecialchars($joueur['poste'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if(!empty($joueur['maladie_chronique'])): ?>
                                <div style="font-size:0.85rem; color:#991b1b; font-weight:bold; background:rgba(239,68,68,0.1); padding: 5px 10px; border-radius: 6px; display:inline-block; border:1px solid rgba(239,68,68,0.15);"><i class="fa-solid fa-triangle-exclamation"></i> Maladie chronique: <?php echo htmlspecialchars($joueur['maladie_chronique'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form id="editJoueurForm" onsubmit="return false;">
                        <input type="hidden" name="id_joueur" value="<?php echo (int)$joueur['id_joueur']; ?>">

                        <h3 style="font-size:0.95rem;color:var(--c-navy);margin:12px 0;border-top:1px solid #eee;padding-top:16px;">Informations personnelles</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-lbl">Prénom</span>
                                <span class="info-val view-text"><?php echo htmlspecialchars($joueur['prenom'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <input type="text" name="prenom" class="ed-fld ed-inp" value="<?php echo htmlspecialchars($joueur['prenom'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Nom</span>
                                <span class="info-val view-text"><?php echo htmlspecialchars($joueur['nom'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <input type="text" name="nom" class="ed-fld ed-inp" value="<?php echo htmlspecialchars($joueur['nom'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Sexe</span>
                                <span class="info-val view-text"><?php echo htmlspecialchars((string)$joueur['sexe'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <select name="sexe" class="ed-fld ed-inp">
                                    <option value="Homme" <?php if($joueur['sexe']==='Homme') echo 'selected'; ?>>Homme</option>
                                    <option value="Femme" <?php if($joueur['sexe']==='Femme') echo 'selected'; ?>>Femme</option>
                                </select>
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Date de naissance</span>
                                <span class="info-val view-text"><?php echo htmlspecialchars((string)$joueur['date_naissance'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <input type="date" name="date_naissance" class="ed-fld ed-inp" value="<?php echo htmlspecialchars((string)$joueur['date_naissance'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Téléphone</span>
                                <span class="info-val view-text"><?php echo htmlspecialchars((string)($joueur['telephone'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <input type="text" name="telephone" class="ed-fld ed-inp" value="<?php echo htmlspecialchars((string)($joueur['telephone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Email</span>
                                <span class="info-val view-text"><?php echo htmlspecialchars((string)($joueur['email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <input type="email" name="email" class="ed-fld ed-inp" value="<?php echo htmlspecialchars((string)($joueur['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Nationalité</span>
                                <span class="info-val view-text"><?php echo htmlspecialchars((string)($joueur['nationalite'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <input type="text" name="nationalite" class="ed-fld ed-inp" value="<?php echo htmlspecialchars((string)($joueur['nationalite'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Adresse</span>
                                <span class="info-val view-text"><?php echo htmlspecialchars((string)($joueur['adresse'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <input type="text" name="adresse" class="ed-fld ed-inp" value="<?php echo htmlspecialchars((string)($joueur['adresse'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <h3 style="font-size:0.95rem;color:var(--c-navy);margin:16px 0 12px;border-top:1px solid #eee;padding-top:16px;">Physique</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-lbl">Taille</span>
                                <span class="info-val view-text"><?php echo !empty($joueur['taille']) ? htmlspecialchars((string)$joueur['taille'], ENT_QUOTES, 'UTF-8') . ' cm' : '<span style="color:#94a3b8;font-style:italic;">Non renseigné</span>'; ?></span>
                                <input type="number" name="taille" class="ed-fld ed-inp" min="50" max="250" step="0.1" placeholder="cm" value="<?php echo htmlspecialchars((string)($joueur['taille'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Poids</span>
                                <span class="info-val view-text"><?php echo !empty($joueur['poids']) ? htmlspecialchars((string)$joueur['poids'], ENT_QUOTES, 'UTF-8') . ' kg' : '<span style="color:#94a3b8;font-style:italic;">Non renseigné</span>'; ?></span>
                                <input type="number" name="poids" class="ed-fld ed-inp" min="20" max="200" step="0.1" placeholder="kg" value="<?php echo htmlspecialchars((string)($joueur['poids'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <?php if(!empty($joueur['maladie_chronique'])): ?>
                            <div class="info-item" style="grid-column:1/-1;">
                                <div style="font-size:0.85rem;color:#991b1b;background:rgba(239,68,68,0.04);border:1px dashed rgba(239,68,68,0.2);border-radius:6px;padding:4px 10px;">
                                    <i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i><?php echo htmlspecialchars($joueur['maladie_chronique'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="info-item" style="grid-column:1/-1;">
                                <span class="info-lbl">Maladie chronique / Allergies</span>
                                <textarea name="maladie_chronique" class="ed-fld ed-inp" rows="2" placeholder="Maladie chronique / Allergies..."><?php echo htmlspecialchars((string)($joueur['maladie_chronique'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>

                        <h3 style="font-size:0.95rem;color:var(--c-navy);margin:16px 0 12px;border-top:1px solid #eee;padding-top:16px;">Football</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-lbl">Poste <i class="fa-solid fa-lock" style="font-size:0.65rem;color:#94a3b8;margin-left:3px;"></i></span>
                                <span class="info-val"><?php echo !empty($joueur['poste']) ? htmlspecialchars($joueur['poste'], ENT_QUOTES, 'UTF-8') : '<span style="color:#94a3b8;font-style:italic;">-</span>'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Niveau</span>
                                <span class="info-val view-text"><?php echo !empty($joueur['niveau']) ? htmlspecialchars($joueur['niveau'], ENT_QUOTES, 'UTF-8') : '<span style="color:#94a3b8;font-style:italic;">-</span>'; ?></span>
                                <input type="text" name="niveau" class="ed-fld ed-inp" value="<?php echo htmlspecialchars((string)($joueur['niveau'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Expérience</span>
                                <span class="info-val view-text"><?php echo !empty($joueur['experience']) ? htmlspecialchars($joueur['experience'], ENT_QUOTES, 'UTF-8') : '<span style="color:#94a3b8;font-style:italic;">-</span>'; ?></span>
                                <input type="text" name="experience" class="ed-fld ed-inp" value="<?php echo htmlspecialchars((string)($joueur['experience'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Club précédent</span>
                                <span class="info-val view-text"><?php echo !empty($joueur['club_precedent']) ? htmlspecialchars($joueur['club_precedent'], ENT_QUOTES, 'UTF-8') : '<span style="color:#94a3b8;font-style:italic;">-</span>'; ?></span>
                                <input type="text" name="club_precedent" class="ed-fld ed-inp" value="<?php echo htmlspecialchars((string)($joueur['club_precedent'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                        <div style="margin-top:8px;font-size:0.78rem;color:#94a3b8;background:#f8fafc;padding:6px 10px;border-radius:6px;border:1px solid #e2e8f0;"><i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>Le poste ne peut être modifié que sur demande du joueur, validée par l'entraîneur.</div>

                        <div class="save-bar" id="saveBar">
                            <button type="button" class="btn" style="padding:8px 20px;border-radius:8px;font-size:0.85rem;" onclick="saveAllJoueur()"><i class="fa-solid fa-check" style="margin-right:4px;"></i> Enregistrer</button>
                            <button type="button" onclick="toggleEdit()" style="border:none;background:#f1f5f9;color:#475569;padding:8px 14px;border-radius:8px;font-size:0.85rem;cursor:pointer;">Annuler</button>
                            <span id="saveStatus" style="font-size:0.8rem;font-weight:700;"></span>
                        </div>
                    </form>

                    <div style="border-top:1px solid #eee;padding-top:16px;margin-top:16px;">
                        <h3 style="font-size:0.95rem;margin-bottom:12px;color:var(--burgundy);">Contact Parent/Tuteur</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-lbl">Nom</span>
                                <span class="info-val">
                                    <?php if($joueur['id_parent']): ?>
                                        <a href="<?php echo htmlspecialchars(url_path('/admin/parent_profil.php?id='.(int)$joueur['id_parent']), ENT_QUOTES, 'UTF-8'); ?>" style="color:var(--burgundy); text-decoration:underline;">
                                            <?php echo htmlspecialchars((string)$joueur['nom_parent'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-lbl">Téléphone Parent</span>
                                <span class="info-val"><?php echo htmlspecialchars((string)($joueur['tel_parent'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="hd"><h2>Ajouter un Suivi (Évaluation / Présence)</h2></div>
                <div class="bd">
                    <?php if(isset($_GET['ok'])) echo '<div style="color:var(--success); font-weight:bold; margin-bottom:15px; padding:10px; background:rgba(16,185,129,0.1); border-radius:8px;">Suivi enregistré avec succès !</div>'; ?>
                    <?php if(isset($_GET['err'])) echo '<div style="color:var(--danger); font-weight:bold; margin-bottom:15px; padding:10px; background:rgba(239,68,68,0.1); border-radius:8px;">Erreur lors de l\'enregistrement.</div>'; ?>
                    <form action="<?php echo htmlspecialchars(url_path('/admin/api_add_suivi.php'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id_joueur" value="<?php echo (int)$joueur['id_joueur']; ?>">
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                            <div class="form-group">
                                <label>Date du suivi</label>
                                <input type="date" name="date_suivi" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Présence</label>
                                <select name="presence" class="form-control" required>
                                    <option value="Présent">Présent(e)</option>
                                    <option value="Absent">Absent(e)</option>
                                    <option value="Blessé">Blessé(e)</option>
                                    <option value="Maladie">Maladie</option>
                                    <option value="Retard">En Retard</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Performance Globale (Ex: Excellente, Bonne, À améliorer...)</label>
                            <input type="text" name="performance" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Remarques techniques ou comportementales</label>
                            <textarea name="remarque" class="form-control" placeholder="Entrez vos notes..."></textarea>
                        </div>
                        <button type="submit" class="btn">Enregistrer le Suivi</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="column">
            <div class="card">
                <div class="hd"><h2>Historique des Suivis</h2></div>
                <div class="bd">
                    <?php if(count($suivis_list) === 0): ?>
                        <div class="muted" style="text-align:center; padding: 30px 0;">Aucun historique de suivi pour ce joueur.</div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach($suivis_list as $s): 
                                $presClass = 'pres-present';
                                if($s['presence'] === 'Absent') $presClass = 'pres-absent';
                                else if($s['presence'] === 'Blessé') $presClass = 'pres-blesse';
                                else if($s['presence'] === 'Maladie') $presClass = 'pres-maladie';
                            ?>
                                <div class="timeline-item">
                                    <div class="tl-date"><?php echo htmlspecialchars(date('d/m/Y', strtotime((string)$s['date_suivi'])), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="tl-title">
                                        <?php echo htmlspecialchars((string)$s['performance'], ENT_QUOTES, 'UTF-8'); ?>
                                        <span class="pres-badge <?php echo $presClass; ?>"><?php echo htmlspecialchars((string)$s['presence'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <?php if(!empty($s['remarque'])): ?>
                                        <div class="tl-desc"><?php echo nl2br(htmlspecialchars((string)$s['remarque'], ENT_QUOTES, 'UTF-8')); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const playerId = <?php echo (int)$joueur['id_joueur']; ?>;

function toggleEdit() {
    const form = document.getElementById('editJoueurForm');
    const bar = document.getElementById('saveBar');
    const btn = document.getElementById('editToggle');
    const isEditing = form.classList.contains('editing');

    if (isEditing) {
        form.classList.remove('editing');
        bar.classList.remove('active');
        btn.innerHTML = '<i class="fa-solid fa-pen"></i> Modifier';
    } else {
        form.classList.add('editing');
        bar.classList.add('active');
        btn.innerHTML = '<i class="fa-solid fa-xmark"></i> Annuler';
    }
}

async function saveAllJoueur() {
    const status = document.getElementById('saveStatus');
    status.textContent = 'Enregistrement...';
    status.style.color = '#64748b';
    try {
        const form = document.getElementById('editJoueurForm');
        const data = new FormData(form);
        data.append('csrf_token', csrfToken);
        const res = await fetch('<?php echo url_path('/admin/api_update_joueur'); ?>', { method: 'POST', body: data });
        const json = await res.json();
        if (json.ok) {
            status.textContent = '\u2713';
            status.style.color = '#10b981';
            setTimeout(() => location.reload(), 500);
        } else {
            status.textContent = json.error || 'Erreur';
            status.style.color = '#ef4444';
        }
    } catch (e) {
        status.textContent = 'Erreur r\u00e9seau';
        status.style.color = '#ef4444';
    }
}
</script>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
