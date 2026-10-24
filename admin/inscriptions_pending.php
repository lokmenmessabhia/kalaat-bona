<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();

if (($admin['role'] ?? '') !== 'coordinateur' && ($admin['role'] ?? '') !== 'main') {
    die('Accès refusé.');
}

$pdo = db();

$tab = $_GET['tab'] ?? 'en_attente';
$allowedTabs = ['en_attente', 'acceptees', 'refusees'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'en_attente';

if ($tab === 'en_attente') {
    $where = "i.statut = 'en_attente'";
    $label = 'En attente';
    $countLabel = 'en attente';
} elseif ($tab === 'acceptees') {
    $where = "i.statut = 'acceptee'";
    $label = 'Acceptées';
    $countLabel = 'acceptées';
} else {
    $where = "i.statut = 'refusee'";
    $label = 'Refusées';
    $countLabel = 'refusées';
}

// Group by player so multi-program signups show as one row
$stmt = $pdo->query("
    SELECT j.id_joueur, j.prenom, j.nom AS joueur_nom, j.date_naissance, j.sexe, j.nationalite, j.adresse, j.telephone, j.email, j.photo, j.taille, j.poids, j.maladie_chronique,
           GROUP_CONCAT(DISTINCT p.nom_programme SEPARATOR ', ') AS programmes,
           GROUP_CONCAT(DISTINCT i.id_inscription ORDER BY i.id_inscription SEPARATOR ',') AS inscription_ids,
           GROUP_CONCAT(DISTINCT i.commentaires SEPARATOR ' | ') AS commentaires,
           MAX(i.date_inscription) AS date_inscription,
           i.statut, i.email_contact, i.nom_contact,
           i.login_contact, i.mot_de_passe_clair,
           pa.nom AS parent_nom, pa.telephone AS parent_telephone, pa.email AS parent_email,
           pf.poste, pf.niveau, pf.experience, pf.club_precedent
    FROM inscription i
    JOIN joueur j ON j.id_joueur = i.id_joueur
    JOIN programme p ON p.id_programme = i.id_programme
    LEFT JOIN parent pa ON pa.id_parent = j.id_parent
    LEFT JOIN profil_football pf ON pf.id_joueur = j.id_joueur
    WHERE $where
    GROUP BY j.id_joueur
    ORDER BY MAX(i.date_inscription) DESC
    LIMIT 100
");
$inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPending = (int)$pdo->query("SELECT COUNT(DISTINCT j.id_joueur) FROM inscription i JOIN joueur j ON j.id_joueur = i.id_joueur WHERE i.statut='en_attente'")->fetchColumn();

$admin_page_title   = 'Inscriptions ' . $label;
$admin_current_page = 'inscriptions_pending';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .page-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
    .page-hd h1 { font-size:1.3rem; font-weight:800; color:var(--c-navy); margin:0; }
    .page-hd .badge-pending { background:#fef3c7; color:#92400e; font-size:0.75rem; font-weight:700; padding:4px 12px; border-radius:20px; margin-left:8px; }

    .tab-nav { display:flex; gap:4px; margin-bottom:24px; }
    .tab-nav a { padding:10px 20px; border-radius:10px 10px 0 0; font-weight:700; font-size:0.85rem; text-decoration:none; color:#64748b; background:#f8fafc; border:1px solid #e2e8f0; border-bottom:none; transition:all .2s; }
    .tab-nav a:hover { color:var(--c-navy); background:#fff; }
    .tab-nav a.active { color:var(--c-navy); background:#fff; position:relative; }
    .tab-nav a.active::after { content:''; position:absolute; bottom:-1px; left:0; right:0; height:2px; background:var(--c-sky); }

    .card { background:#fff; border-radius:16px; box-shadow:0 1px 3px rgba(0,0,0,0.06),0 4px 12px rgba(0,0,0,0.04); border:1px solid #e2e8f0; overflow:hidden; }
    .card-hd { padding:16px 20px; border-bottom:1px solid #e2e8f0; font-weight:700; color:var(--c-navy); font-size:0.95rem; display:flex; align-items:center; justify-content:space-between; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px 16px; border-bottom:1px solid #e2e8f0; text-align:left; font-size:0.88rem; vertical-align:middle; }
    th { background:#f8fafc; color:var(--c-navy); font-weight:700; font-size:0.8rem; text-transform:uppercase; letter-spacing:.3px; }
    td.actions { white-space:nowrap; text-align:right; }
    .empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
    .empty-state i { font-size:2.5rem; margin-bottom:12px; opacity:.5; }
    .player-name { font-weight:700; color:var(--c-navy); }
    .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:700; }
    .badge-pending { background:#fef3c7; color:#92400e; }
    .badge-accepted { background:#d1fae5; color:#065f46; }
    .badge-rejected { background:#fee2e2; color:#991b1b; }

    .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer; border:none; transition:all .2s; text-decoration:none; }
    .btn-accept { background:#d1fae5; color:#065f46; }
    .btn-accept:hover { background:#a7f3d0; }
    .btn-reject { background:#fee2e2; color:#991b1b; }
    .btn-reject:hover { background:#fecaca; }
    .btn-view { background:#eff6ff; color:#1d4ed8; }
    .btn-view:hover { background:#bfdbfe; }
    .btn-sm { padding:5px 10px; font-size:0.78rem; }

    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(4px); padding:20px; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:#fff; border-radius:20px; padding:32px; width:100%; max-width:540px; box-shadow:0 25px 50px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; }
    .modal-box h2 { font-size:1.15rem; font-weight:800; color:var(--c-navy); margin-bottom:12px; }
    .modal-box p { color:#475569; font-size:0.9rem; margin-bottom:20px; line-height:1.5; }
    .modal-actions { display:flex; gap:10px; margin-top:20px; }
    .modal-actions .btn { flex:1; justify-content:center; padding:12px; }
    .btn-cancel { background:#f1f5f9; color:#475569; }
    .btn-cancel:hover { background:#e2e8f0; }
    .btn-danger { background:#dc2626; color:#fff; }
    .btn-danger:hover { background:#b91c1c; }

    .form-control { width:100%; padding:10px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.9rem; font-family:inherit; box-sizing:border-box; }
    .form-control:focus { outline:none; border-color:var(--c-sky); box-shadow:0 0 0 3px rgba(108,173,223,0.1); }
    textarea.form-control { resize:vertical; min-height:80px; }

    .toast { display:none; position:fixed; bottom:30px; right:30px; padding:16px 24px; border-radius:12px; font-weight:700; font-size:0.9rem; z-index:2000; box-shadow:0 8px 32px rgba(0,0,0,0.12); animation: slideUp 0.3s ease; }
    .toast-success { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .toast-error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }

    .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
    .detail-field { }
    .detail-field .df-label { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px; color:#94a3b8; font-weight:700; margin-bottom:2px; }
    .detail-field .df-value { font-size:0.9rem; color:#1e293b; font-weight:600; }
    .detail-field.full { grid-column:1/-1; }
    .detail-divider { border:none; border-top:1px solid #e2e8f0; margin:16px 0; }
    .detail-photo { width:70px; height:70px; border-radius:50%; object-fit:cover; background:#e2e8f0; }
</style>

<div class="page-hd">
    <h1><i class="fa-solid fa-clock" style="color:var(--c-sky);margin-right:8px;"></i> Gestion des Inscriptions</h1>
    <?php if ($totalPending > 0): ?>
        <span class="badge-pending" style="font-size:0.85rem;padding:6px 16px;">
            <i class="fa-solid fa-hourglass-half"></i> <?php echo $totalPending; ?> en attente
        </span>
    <?php endif; ?>
</div>

<div class="tab-nav">
    <a href="?tab=en_attente" class="<?php echo $tab === 'en_attente' ? 'active' : ''; ?>">
        <i class="fa-solid fa-hourglass-half"></i> En attente
        <?php if ($totalPending > 0): ?><span class="badge badge-pending" style="margin-left:6px;"><?php echo $totalPending; ?></span><?php endif; ?>
    </a>
    <a href="?tab=acceptees" class="<?php echo $tab === 'acceptees' ? 'active' : ''; ?>">
        <i class="fa-solid fa-check-circle"></i> Acceptées
    </a>
    <a href="?tab=refusees" class="<?php echo $tab === 'refusees' ? 'active' : ''; ?>">
        <i class="fa-solid fa-times-circle"></i> Refusées
    </a>
</div>

<div class="card">
    <div class="card-hd">
        <span><i class="fa-solid fa-list"></i> Inscriptions <?php echo $countLabel; ?></span>
        <span style="color:#94a3b8;font-size:0.8rem;"><?php echo count($inscriptions); ?> joueur(s)</span>
    </div>

    <?php if (count($inscriptions) === 0): ?>
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>Aucune inscription <?php echo $countLabel; ?>.</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Joueur</th>
                    <th>Âge</th>
                    <th>Programme(s)</th>
                    <th>Contact</th>
                    <th>Date</th>
                    <?php if ($tab !== 'en_attente'): ?><th>Statut</th><?php endif; ?>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inscriptions as $ins):
                    $age = '';
                    if ($ins['date_naissance']) {
                        $birth = new DateTime($ins['date_naissance']);
                        $age = $birth->diff(new DateTime())->y . ' ans';
                    }
                    $dateStr = date('d/m/Y H:i', strtotime($ins['date_inscription']));
                    $progCount = count(array_filter(explode(',', $ins['programmes'] ?? '')));
                ?>
                <tr>
                    <td><span class="player-name"><?php echo htmlspecialchars($ins['prenom'] . ' ' . $ins['joueur_nom']); ?></span></td>
                    <td><?php echo $age; ?></td>
                    <td style="max-width:220px;">
                        <?php echo htmlspecialchars($ins['programmes'] ?? '-'); ?>
                        <?php if ($progCount > 1): ?>
                            <span class="badge badge-pending" style="margin-left:4px;font-size:0.7rem;"><?php echo $progCount; ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size:0.8rem;color:#475569;">
                            <?php echo htmlspecialchars($ins['nom_contact'] ?? ''); ?>
                            <br><?php echo htmlspecialchars($ins['email_contact'] ?? ''); ?>
                        </div>
                    </td>
                    <td style="white-space:nowrap;"><?php echo $dateStr; ?></td>
                    <?php if ($tab !== 'en_attente'): ?>
                        <td>
                            <?php if ($ins['statut'] === 'acceptee'): ?>
                                <span class="badge badge-accepted">Acceptée</span>
                            <?php elseif ($ins['statut'] === 'refusee'): ?>
                                <span class="badge badge-rejected">Refusée</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td class="actions">
                        <button class="btn btn-sm btn-view" onclick="openViewModal(<?php echo htmlspecialchars(json_encode($ins), ENT_QUOTES, 'UTF-8'); ?>)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <?php if ($ins['statut'] === 'en_attente'): ?>
                            <button class="btn btn-sm btn-accept" onclick="openAcceptModal(<?php echo (int)$ins['id_joueur']; ?>, '<?php echo htmlspecialchars($ins['prenom'] . ' ' . $ins['joueur_nom'], ENT_QUOTES); ?>')">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-reject" onclick="openRejectModal(<?php echo (int)$ins['id_joueur']; ?>, '<?php echo htmlspecialchars($ins['prenom'] . ' ' . $ins['joueur_nom'], ENT_QUOTES); ?>')">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        <?php else: ?>
                            <span style="color:#94a3b8;font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- View Details Modal -->
<div id="viewModal" class="modal-overlay">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 style="margin:0;"><i class="fa-solid fa-user" style="color:var(--c-sky);margin-right:6px;"></i> Détails de l'inscription</h2>
            <button onclick="closeModal('viewModal')" style="border:none;background:none;font-size:1.5rem;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <div id="viewContent"></div>
        <div class="modal-actions">
            <button class="btn btn-cancel" onclick="closeModal('viewModal')">Fermer</button>
        </div>
    </div>
</div>

<!-- Accept Modal -->
<div id="acceptModal" class="modal-overlay">
    <div class="modal-box">
        <h2><i class="fa-solid fa-check-circle" style="color:#10b981;margin-right:6px;"></i> Accepter l'inscription</h2>
        <p>Vous allez accepter l'inscription de <strong id="acceptPlayerName"></strong>.</p>
        <p style="font-size:0.85rem;color:#64748b;">Un email de confirmation avec les identifiants de connexion sera envoyé automatiquement.</p>
        <div class="modal-actions">
            <button class="btn btn-accept" id="confirmAcceptBtn" style="flex:1;justify-content:center;padding:12px;">
                <i class="fa-solid fa-check"></i> Confirmer l'acceptation
            </button>
            <button class="btn btn-cancel" onclick="closeModal('acceptModal')">Annuler</button>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-overlay">
    <div class="modal-box">
        <h2><i class="fa-solid fa-times-circle" style="color:#dc2626;margin-right:6px;"></i> Refuser l'inscription</h2>
        <p>Vous allez refuser l'inscription de <strong id="rejectPlayerName"></strong>.</p>
        <div class="form-group" style="margin-bottom:12px;">
            <label style="font-weight:700;font-size:0.85rem;color:#1e293b;display:block;margin-bottom:6px;">Motif (optionnel) :</label>
            <textarea id="rejectMotif" class="form-control" placeholder="Expliquez brièvement le motif du refus..." maxlength="500"></textarea>
        </div>
        <div class="modal-actions">
            <button class="btn btn-danger" id="confirmRejectBtn" style="flex:1;justify-content:center;padding:12px;">
                <i class="fa-solid fa-times"></i> Confirmer le refus
            </button>
            <button class="btn btn-cancel" onclick="closeModal('rejectModal')">Annuler</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
let currentPlayerId = null;
let currentInscriptionIds = [];
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function openViewModal(data) {
    const c = document.getElementById('viewContent');
    const photo = data.photo
        ? '<img src="<?php echo url_path('/uploads'); ?>/' + data.photo + '" class="detail-photo">'
        : '<div class="detail-photo" style="display:flex;align-items:center;justify-content:center;color:#94a3b8;"><i class="fa-solid fa-user fa-xl"></i></div>';
    const sexeLabel = data.sexe === 'masculin' ? 'Masculin' : data.sexe === 'feminin' ? 'Féminin' : '-';
    const age = data.date_naissance ? calcAge(data.date_naissance) : '-';

    const posteLabel = data.poste ? data.poste.charAt(0).toUpperCase() + data.poste.slice(1).replace('_', ' ') : '-';
    const niveauLabel = data.niveau ? data.niveau.charAt(0).toUpperCase() + data.niveau.slice(1) : '-';
    const expLabel = data.experience ? data.experience + (data.experience === '6+' ? ' ans' : ' ans') : '-';

    c.innerHTML = `
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:16px;">
            ${photo}
            <div>
                <div style="font-weight:800; font-size:1.1rem; color:#1e293b;">${escHtml(data.prenom)} ${escHtml(data.joueur_nom)}</div>
                <div style="font-size:0.8rem; color:#64748b;">ID: #${data.id_joueur}</div>
            </div>
        </div>
        <hr class="detail-divider">
        <div class="detail-grid">
            <div class="detail-field">
                <div class="df-label">Âge</div>
                <div class="df-value">${age}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Sexe</div>
                <div class="df-value">${sexeLabel}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Nationalité</div>
                <div class="df-value">${escHtml(data.nationalite || '-')}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Téléphone</div>
                <div class="df-value">${escHtml(data.telephone || '-')}</div>
            </div>
            ${data.email ? `<div class="detail-field full">
                <div class="df-label">Email</div>
                <div class="df-value">${escHtml(data.email)}</div>
            </div>` : ''}
            ${data.adresse ? `<div class="detail-field full">
                <div class="df-label">Adresse</div>
                <div class="df-value">${escHtml(data.adresse)}</div>
            </div>` : ''}
        </div>
        <hr class="detail-divider">
        <div class="detail-grid">
            <div class="detail-field full">
                <div class="df-label">Programmes souhaités</div>
                <div class="df-value">${escHtml(data.programmes || '-')}</div>
            </div>
            ${data.commentaires ? `<div class="detail-field full">
                <div class="df-label">Commentaires / Motivations</div>
                <div class="df-value">${escHtml(data.commentaires)}</div>
            </div>` : ''}
        </div>
        <hr class="detail-divider">
        <div class="detail-grid">
            <div class="detail-field">
                <div class="df-label">Poste souhaité</div>
                <div class="df-value">${posteLabel}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Niveau</div>
                <div class="df-value">${niveauLabel}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Expérience</div>
                <div class="df-value">${expLabel}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Taille</div>
                <div class="df-value">${data.taille ? data.taille + ' cm' : '-'}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Poids</div>
                <div class="df-value">${data.poids ? data.poids + ' kg' : '-'}</div>
            </div>
            <div class="detail-field full">
                <div class="df-label">Club précédent</div>
                <div class="df-value">${escHtml(data.club_precedent || '-')}</div>
            </div>
            ${data.maladie_chronique ? `<div class="detail-field full" style="background:rgba(239,68,68,0.04);border:1px solid rgba(239,68,68,0.15);border-radius:8px;padding:10px;">
                <div class="df-label" style="color:#dc2626;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i>Maladie chronique / Allergies</div>
                <div class="df-value" style="color:#991b1b;">${escHtml(data.maladie_chronique)}</div>
            </div>` : ''}
        </div>
        <hr class="detail-divider">
        <div class="detail-grid">
            <div class="detail-field">
                <div class="df-label">Contact</div>
                <div class="df-value">${escHtml(data.nom_contact || '-')}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Email contact</div>
                <div class="df-value">${escHtml(data.email_contact || '-')}</div>
            </div>
            ${data.parent_nom ? `
            <div class="detail-field full" style="margin-top:8px;border-top:1px dashed #e2e8f0;padding-top:12px;">
                <div style="font-weight:700;color:var(--c-navy);font-size:0.85rem;margin-bottom:8px;">Parent / Tuteur</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Nom du parent</div>
                <div class="df-value">${escHtml(data.parent_nom)}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Téléphone parent</div>
                <div class="df-value">${escHtml(data.parent_telephone || '-')}</div>
            </div>
            <div class="detail-field full">
                <div class="df-label">Email parent</div>
                <div class="df-value">${escHtml(data.parent_email || '-')}</div>
            </div>
            ` : ''}
            <div class="detail-field">
                <div class="df-label">Date d'inscription</div>
                <div class="df-value">${data.date_inscription ? new Date(data.date_inscription).toLocaleDateString('fr-FR', {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-'}</div>
            </div>
            <div class="detail-field">
                <div class="df-label">Statut</div>
                <div class="df-value"><span class="badge ${data.statut === 'acceptee' ? 'badge-accepted' : data.statut === 'refusee' ? 'badge-rejected' : 'badge-pending'}">${data.statut === 'acceptee' ? 'Acceptée' : data.statut === 'refusee' ? 'Refusée' : 'En attente'}</span></div>
            </div>
        </div>
    `;
    document.getElementById('viewModal').classList.add('open');
}

function calcAge(dateStr) {
    const b = new Date(dateStr);
    const t = new Date();
    let age = t.getFullYear() - b.getFullYear();
    const m = t.getMonth() - b.getMonth();
    if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
    return age + ' ans';
}

function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function openAcceptModal(id, name) {
    currentPlayerId = id;
    document.getElementById('acceptPlayerName').textContent = name;
    document.getElementById('acceptModal').classList.add('open');
}

function openRejectModal(id, name) {
    currentPlayerId = id;
    document.getElementById('rejectPlayerName').textContent = name;
    document.getElementById('rejectMotif').value = '';
    document.getElementById('rejectModal').classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    currentPlayerId = null;
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => {
        if (e.target === el) el.classList.remove('open');
    });
});

document.getElementById('confirmAcceptBtn').addEventListener('click', async function() {
    if (!currentPlayerId) return;
    this.disabled = true;
    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';
    try {
        const params = new URLSearchParams();
        params.append('id_joueur', currentPlayerId);
        params.append('csrf_token', csrfToken);
        const res = await fetch('<?php echo url_path('/api/inscription_accept'); ?>', { method: 'POST', body: params });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message || 'Inscription acceptée avec succès.', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(json.error || 'Erreur', 'error');
        }
    } catch (err) {
        showToast('Erreur de connexion.', 'error');
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fa-solid fa-check"></i> Confirmer l\'acceptation';
        closeModal('acceptModal');
    }
});

document.getElementById('confirmRejectBtn').addEventListener('click', async function() {
    if (!currentPlayerId) return;
    this.disabled = true;
    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';
    try {
        const params = new URLSearchParams();
        params.append('id_joueur', currentPlayerId);
        params.append('motif', document.getElementById('rejectMotif').value);
        params.append('csrf_token', csrfToken);
        const res = await fetch('<?php echo url_path('/api/inscription_reject'); ?>', { method: 'POST', body: params });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message || 'Inscription refusée.', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(json.error || 'Erreur', 'error');
        }
    } catch (err) {
        showToast('Erreur de connexion.', 'error');
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fa-solid fa-times"></i> Confirmer le refus';
        closeModal('rejectModal');
    }
});

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast-' + type;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 4000);
}
</script>

<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
