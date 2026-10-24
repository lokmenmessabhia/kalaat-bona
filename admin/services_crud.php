<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();
if (($admin['role'] ?? '') !== 'coordinateur' && ($admin['role'] ?? '') !== 'main') {
    die('Accès refusé. Réservé au profil Coordinateur.');
}

$pdo = db();
$error = null;
$success = null;

// ── Handle POST actions ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Helper: handle image upload
    function handle_programme_image(string $field = 'image'): ?string {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = $_FILES[$field]['tmp_name'];
        $name = basename($_FILES[$field]['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) return null;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) return null;
        $newName = 'programme_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = __DIR__ . '/../uploads/programmes/' . $newName;
        if (move_uploaded_file($tmp, $dest)) return $newName;
        return null;
    }

    if ($action === 'add') {
        $nom   = trim((string)($_POST['nom_programme'] ?? ''));
        $nom_en = trim((string)($_POST['nom_programme_en'] ?? ''));
        $nom_ar = trim((string)($_POST['nom_programme_ar'] ?? ''));
        $desc  = trim((string)($_POST['description'] ?? ''));
        $desc_en = trim((string)($_POST['description_en'] ?? ''));
        $desc_ar = trim((string)($_POST['description_ar'] ?? ''));
        $lieu  = trim((string)($_POST['lieu'] ?? ''));
        $lieu_en = trim((string)($_POST['lieu_en'] ?? ''));
        $lieu_ar = trim((string)($_POST['lieu_ar'] ?? ''));
        $horaire = trim((string)($_POST['date_horaire'] ?? ''));
        $horaire_en = trim((string)($_POST['date_horaire_en'] ?? ''));
        $horaire_ar = trim((string)($_POST['date_horaire_ar'] ?? ''));
        $places  = trim((string)($_POST['places'] ?? ''));
        $places_en = trim((string)($_POST['places_en'] ?? ''));
        $places_ar = trim((string)($_POST['places_ar'] ?? ''));
        $age     = trim((string)($_POST['age'] ?? ''));
        $age_en  = trim((string)($_POST['age_en'] ?? ''));
        $age_ar  = trim((string)($_POST['age_ar'] ?? ''));
        $imageDbPath = handle_programme_image();

        if ($nom === '') {
            $error = 'Le nom du programme est requis.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO programme (nom_programme, nom_programme_en, nom_programme_ar, description, description_en, description_ar, image, lieu, lieu_en, lieu_ar, date_horaire, date_horaire_en, date_horaire_ar, places, places_en, places_ar, age, age_en, age_ar)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$nom, $nom_en, $nom_ar, $desc, $desc_en, $desc_ar, $imageDbPath, $lieu, $lieu_en, $lieu_ar, $horaire, $horaire_en, $horaire_ar, $places, $places_en, $places_ar, $age, $age_en, $age_ar]);
            $success = 'Programme ajouté avec succès.';
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id_programme'] ?? 0);
        $nom   = trim((string)($_POST['nom_programme'] ?? ''));
        $nom_en = trim((string)($_POST['nom_programme_en'] ?? ''));
        $nom_ar = trim((string)($_POST['nom_programme_ar'] ?? ''));
        $desc  = trim((string)($_POST['description'] ?? ''));
        $desc_en = trim((string)($_POST['description_en'] ?? ''));
        $desc_ar = trim((string)($_POST['description_ar'] ?? ''));
        $lieu  = trim((string)($_POST['lieu'] ?? ''));
        $lieu_en = trim((string)($_POST['lieu_en'] ?? ''));
        $lieu_ar = trim((string)($_POST['lieu_ar'] ?? ''));
        $horaire = trim((string)($_POST['date_horaire'] ?? ''));
        $horaire_en = trim((string)($_POST['date_horaire_en'] ?? ''));
        $horaire_ar = trim((string)($_POST['date_horaire_ar'] ?? ''));
        $places  = trim((string)($_POST['places'] ?? ''));
        $places_en = trim((string)($_POST['places_en'] ?? ''));
        $places_ar = trim((string)($_POST['places_ar'] ?? ''));
        $age     = trim((string)($_POST['age'] ?? ''));
        $age_en  = trim((string)($_POST['age_en'] ?? ''));
        $age_ar  = trim((string)($_POST['age_ar'] ?? ''));
        $imageDbPath = handle_programme_image();

        if ($id <= 0 || $nom === '') {
            $error = 'Données invalides.';
        } else {
            if ($imageDbPath) {
                $stmt = $pdo->prepare(
                    "UPDATE programme SET nom_programme=?, nom_programme_en=?, nom_programme_ar=?, description=?, description_en=?, description_ar=?, image=?, lieu=?, lieu_en=?, lieu_ar=?, date_horaire=?, date_horaire_en=?, date_horaire_ar=?, places=?, places_en=?, places_ar=?, age=?, age_en=?, age_ar=? WHERE id_programme=?"
                );
                $stmt->execute([$nom, $nom_en, $nom_ar, $desc, $desc_en, $desc_ar, $imageDbPath, $lieu, $lieu_en, $lieu_ar, $horaire, $horaire_en, $horaire_ar, $places, $places_en, $places_ar, $age, $age_en, $age_ar, $id]);
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE programme SET nom_programme=?, nom_programme_en=?, nom_programme_ar=?, description=?, description_en=?, description_ar=?, lieu=?, lieu_en=?, lieu_ar=?, date_horaire=?, date_horaire_en=?, date_horaire_ar=?, places=?, places_en=?, places_ar=?, age=?, age_en=?, age_ar=? WHERE id_programme=?"
                );
                $stmt->execute([$nom, $nom_en, $nom_ar, $desc, $desc_en, $desc_ar, $lieu, $lieu_en, $lieu_ar, $horaire, $horaire_en, $horaire_ar, $places, $places_en, $places_ar, $age, $age_en, $age_ar, $id]);
            }
            $success = 'Programme mis à jour avec succès.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id_programme'] ?? 0);
        if ($id > 0) {
            $old = $pdo->prepare("SELECT image FROM programme WHERE id_programme=?");
            $old->execute([$id]);
            $oldImg = $old->fetchColumn();
            $stmt = $pdo->prepare("DELETE FROM programme WHERE id_programme=?");
            $stmt->execute([$id]);
            if ($oldImg && file_exists(__DIR__ . '/../uploads/programmes/' . $oldImg)) {
                unlink(__DIR__ . '/../uploads/programmes/' . $oldImg);
            }
            $success = 'Programme supprimé.';
        }
    }
}

// ── Fetch all services ───────────────────────────────────
$services = $pdo->query("SELECT * FROM programme ORDER BY id_programme DESC")->fetchAll(PDO::FETCH_ASSOC);

$admin_page_title   = 'Gestion des Programmes';
$admin_current_page = 'services_crud';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .page-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
    .page-hd h1 { font-size:1.3rem; font-weight:800; color:var(--c-navy); }

    .msg { padding:14px; border-radius:10px; margin-bottom:20px; font-weight:600; text-align:center; font-size:0.9rem; }
    .msg-ok { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .msg-err { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

    .card { background:#fff; border-radius:16px; box-shadow:0 1px 3px rgba(0,0,0,0.06),0 4px 12px rgba(0,0,0,0.04); padding:24px; border:1px solid #e2e8f0; margin-bottom:24px; }
    .card-hd { font-size:1rem; font-weight:800; color:var(--c-navy); margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid #f1f5f9; }

    .grid-form { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .grid-form .full { grid-column:1/-1; }
    @media (max-width:640px) { .grid-form { grid-template-columns:1fr; } }
    .form-group { }
    .form-group label { display:block; font-weight:700; color:#1e293b; margin-bottom:6px; font-size:0.82rem; }
    .form-control { width:100%; padding:10px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.92rem; transition:all .2s; background:#fff; }
    .form-control:focus { outline:none; border-color:var(--c-sky); box-shadow:0 0 0 3px rgba(108,173,223,0.1); }
    textarea.form-control { resize:vertical; min-height:80px; }

    .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:10px; font-weight:700; font-size:0.88rem; cursor:pointer; border:none; transition:all .2s; text-decoration:none; }
    .btn-primary { background:var(--c-navy); color:#fff; }
    .btn-primary:hover { background:var(--c-navy-soft,#0a2a4a); transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,24,56,0.2); }
    .btn-sm { padding:6px 14px; font-size:0.82rem; }
    .btn-edit { background:#eff6ff; color:#1d4ed8; }
    .btn-edit:hover { background:#dbeafe; }
    .btn-del { background:#fef2f2; color:#dc2626; }
    .btn-del:hover { background:#fee2e2; }

    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px; border-bottom:1px solid #e2e8f0; text-align:left; font-size:0.9rem; vertical-align:middle; }
    th { background:#f8fafc; color:var(--c-navy); font-weight:700; font-size:0.82rem; text-transform:uppercase; letter-spacing:.3px; }
    td.actions { white-space:nowrap; }
    .icon-preview { font-size:1.3rem; }

    .empty-state { text-align:center; padding:50px 20px; color:#94a3b8; }
    .empty-state i { font-size:2.5rem; margin-bottom:12px; opacity:.5; }

    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(4px); padding:20px; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:#fff; border-radius:20px; padding:32px; width:100%; max-width:580px; box-shadow:0 25px 50px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; }
    .modal-box h2 { font-size:1.15rem; font-weight:800; color:var(--c-navy); margin-bottom:20px; }
    .modal-actions { display:flex; gap:10px; margin-top:20px; }
    .modal-actions .btn { flex:1; justify-content:center; }
    .btn-cancel { background:#f1f5f9; color:#475569; }
    .btn-cancel:hover { background:#e2e8f0; }
</style>

<?php if ($success): ?><div class="msg msg-ok"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="msg msg-err"><?php echo $error; ?></div><?php endif; ?>

<div class="page-hd">
    <h1><i class="fa-solid fa-concierge-bell" style="color:var(--c-sky);margin-right:8px;"></i> Gestion des Programmes</h1>
    <button class="btn btn-primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Nouveau Programme</button>
</div>

<div class="card">
    <div class="card-hd">Tous les programmes</div>
    <?php if (count($services) === 0): ?>
        <div class="empty-state">
            <i class="fa-solid fa-store-slash"></i>
            <p>Aucun programme pour le moment. Cliquez sur « Nouveau Programme » pour en créer un.</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Lieu</th>
                    <th>Horaire</th>
                    <th>Places</th>
                    <th>Âge</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                <tr>
                    <td>
                        <?php if (!empty($s['image'])): ?>
                            <img src="../uploads/programmes/<?php echo htmlspecialchars($s['image']); ?>" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover;">
                        <?php else: ?>
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:8px;background:#f1f5f9;color:#94a3b8;font-size:1.2rem;"><i class="fa-solid fa-image"></i></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:700;color:var(--c-navy);"><?php echo htmlspecialchars((string)$s['nom_programme']); ?></td>
                    <td class="muted"><?php echo htmlspecialchars((string)($s['lieu'] ?? '-')); ?></td>
                    <td class="muted"><?php echo htmlspecialchars((string)($s['date_horaire'] ?? '-')); ?></td>
                    <td class="muted"><?php echo htmlspecialchars((string)($s['places'] ?? '-')); ?></td>
                    <td class="muted"><?php echo htmlspecialchars((string)($s['age'] ?? '-')); ?></td>
                    <td class="actions" style="text-align:right;">
                        <button class="btn btn-sm btn-edit js-edit-btn" data-id="<?php echo (int)$s['id_programme']; ?>"><i class="fa-solid fa-pen"></i></button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce programme ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_programme" value="<?php echo (int)$s['id_programme']; ?>">
                            <button type="submit" class="btn btn-sm btn-del"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── ADD Modal ────────────────────────────────────────── -->
<div id="addModal" class="modal-overlay">
    <div class="modal-box">
        <h2><i class="fa-solid fa-plus-circle" style="color:var(--c-sky);margin-right:6px;"></i> Nouveau Programme</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="grid-form">
                <div class="form-group full">
                    <label>Nom du programme (FR) *</label>
                    <input type="text" name="nom_programme" class="form-control" required placeholder="Ex: Baby-Foot">
                </div>
                <div class="form-group full">
                    <label>Nom du programme (EN)</label>
                    <input type="text" name="nom_programme_en" class="form-control" placeholder="Ex: Baby-Foot">
                </div>
                <div class="form-group full" dir="rtl">
                    <label>اسم البرنامج (AR)</label>
                    <input type="text" name="nom_programme_ar" class="form-control" placeholder="مثال: بيبي فوت">
                </div>
                <div class="form-group full">
                    <label>Description (FR)</label>
                    <textarea name="description" class="form-control" placeholder="Détails du programme…"></textarea>
                </div>
                <div class="form-group full">
                    <label>Description (EN)</label>
                    <textarea name="description_en" class="form-control" placeholder="Program details…"></textarea>
                </div>
                <div class="form-group full" dir="rtl">
                    <label>الوصف (AR)</label>
                    <textarea name="description_ar" class="form-control" placeholder="تفاصيل البرنامج…"></textarea>
                </div>
                <div class="form-group full">
                    <label>Image du programme</label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <small style="color:#64748b;font-size:0.78rem;">JPG, PNG ou WebP — Max 5 Mo</small>
                </div>
                <div class="form-group">
                    <label>Lieu (FR)</label>
                    <input type="text" name="lieu" class="form-control" placeholder="Stade Central, Annaba">
                </div>
                <div class="form-group">
                    <label>Lieu (EN)</label>
                    <input type="text" name="lieu_en" class="form-control" placeholder="Central Stadium, Annaba">
                </div>
                <div class="form-group" dir="rtl">
                    <label>المكان (AR)</label>
                    <input type="text" name="lieu_ar" class="form-control" placeholder="الملعب المركزي، عنابة">
                </div>
                <div class="form-group">
                    <label>Horaire (FR)</label>
                    <input type="text" name="date_horaire" class="form-control" placeholder="Samedi matin">
                </div>
                <div class="form-group">
                    <label>Horaire (EN)</label>
                    <input type="text" name="date_horaire_en" class="form-control" placeholder="Saturday morning">
                </div>
                <div class="form-group" dir="rtl">
                    <label>المواعيد (AR)</label>
                    <input type="text" name="date_horaire_ar" class="form-control" placeholder="صباح السبت">
                </div>
                <div class="form-group">
                    <label>Places (FR)</label>
                    <input type="text" name="places" class="form-control" placeholder="20 places">
                </div>
                <div class="form-group">
                    <label>Places (EN)</label>
                    <input type="text" name="places_en" class="form-control" placeholder="20 spots">
                </div>
                <div class="form-group" dir="rtl">
                    <label>الأماكن (AR)</label>
                    <input type="text" name="places_ar" class="form-control" placeholder="20 مقعداً">
                </div>
                <div class="form-group">
                    <label>Âge (FR)</label>
                    <input type="text" name="age" class="form-control" placeholder="4-6 ans">
                </div>
                <div class="form-group">
                    <label>Âge (EN)</label>
                    <input type="text" name="age_en" class="form-control" placeholder="4-6 years">
                </div>
                <div class="form-group" dir="rtl">
                    <label>العمر (AR)</label>
                    <input type="text" name="age_ar" class="form-control" placeholder="4-6 سنوات">
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Ajouter</button>
                <button type="button" class="btn btn-cancel" onclick="closeAddModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- ── EDIT Modal ───────────────────────────────────────── -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <h2><i class="fa-solid fa-pen-to-square" style="color:var(--c-sky);margin-right:6px;"></i> Modifier le Programme</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_programme" id="edit_id">
            <div class="grid-form">
                <div class="form-group full">
                    <label>Nom du programme (FR) *</label>
                    <input type="text" name="nom_programme" id="edit_nom" class="form-control" required>
                </div>
                <div class="form-group full">
                    <label>Nom du programme (EN)</label>
                    <input type="text" name="nom_programme_en" id="edit_nom_en" class="form-control">
                </div>
                <div class="form-group full" dir="rtl">
                    <label>اسم البرنامج (AR)</label>
                    <input type="text" name="nom_programme_ar" id="edit_nom_ar" class="form-control">
                </div>
                <div class="form-group full">
                    <label>Description (FR)</label>
                    <textarea name="description" id="edit_desc" class="form-control" placeholder="Détails du programme…"></textarea>
                </div>
                <div class="form-group full">
                    <label>Description (EN)</label>
                    <textarea name="description_en" id="edit_desc_en" class="form-control" placeholder="Program details…"></textarea>
                </div>
                <div class="form-group full" dir="rtl">
                    <label>الوصف (AR)</label>
                    <textarea name="description_ar" id="edit_desc_ar" class="form-control" placeholder="تفاصيل البرنامج…"></textarea>
                </div>
                <div class="form-group full">
                    <label>Image du programme</label>
                    <div id="edit_image_preview" style="margin-bottom:8px;"></div>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <small style="color:#64748b;font-size:0.78rem;">Laisser vide pour garder l'image actuelle</small>
                </div>
                <div class="form-group">
                    <label>Lieu (FR)</label>
                    <input type="text" name="lieu" id="edit_lieu" class="form-control" placeholder="Stade Central, Annaba">
                </div>
                <div class="form-group">
                    <label>Lieu (EN)</label>
                    <input type="text" name="lieu_en" id="edit_lieu_en" class="form-control">
                </div>
                <div class="form-group" dir="rtl">
                    <label>المكان (AR)</label>
                    <input type="text" name="lieu_ar" id="edit_lieu_ar" class="form-control">
                </div>
                <div class="form-group">
                    <label>Horaire (FR)</label>
                    <input type="text" name="date_horaire" id="edit_horaire" class="form-control" placeholder="Samedi matin">
                </div>
                <div class="form-group">
                    <label>Horaire (EN)</label>
                    <input type="text" name="date_horaire_en" id="edit_horaire_en" class="form-control">
                </div>
                <div class="form-group" dir="rtl">
                    <label>المواعيد (AR)</label>
                    <input type="text" name="date_horaire_ar" id="edit_horaire_ar" class="form-control">
                </div>
                <div class="form-group">
                    <label>Places (FR)</label>
                    <input type="text" name="places" id="edit_places" class="form-control" placeholder="20 places">
                </div>
                <div class="form-group">
                    <label>Places (EN)</label>
                    <input type="text" name="places_en" id="edit_places_en" class="form-control">
                </div>
                <div class="form-group" dir="rtl">
                    <label>الأماكن (AR)</label>
                    <input type="text" name="places_ar" id="edit_places_ar" class="form-control">
                </div>
                <div class="form-group">
                    <label>Âge (FR)</label>
                    <input type="text" name="age" id="edit_age" class="form-control" placeholder="4-6 ans">
                </div>
                <div class="form-group">
                    <label>Âge (EN)</label>
                    <input type="text" name="age_en" id="edit_age_en" class="form-control">
                </div>
                <div class="form-group" dir="rtl">
                    <label>العمر (AR)</label>
                    <input type="text" name="age_ar" id="edit_age_ar" class="form-control">
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
                <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
const servicesData = <?php echo json_encode($services, JSON_HEX_APOS); ?>;

function openAddModal() {
    document.getElementById('addModal').classList.add('open');
}
function closeAddModal() {
    document.getElementById('addModal').classList.remove('open');
}
function openEditModal(id) {
    const s = servicesData.find(p => String(p.id_programme) === String(id));
    if (!s) return;
    document.getElementById('edit_id').value        = s.id_programme;
    document.getElementById('edit_nom').value       = s.nom_programme || '';
    document.getElementById('edit_nom_en').value    = s.nom_programme_en || '';
    document.getElementById('edit_nom_ar').value    = s.nom_programme_ar || '';
    document.getElementById('edit_desc').value      = s.description || '';
    document.getElementById('edit_desc_en').value   = s.description_en || '';
    document.getElementById('edit_desc_ar').value   = s.description_ar || '';
    document.getElementById('edit_lieu').value      = s.lieu || '';
    document.getElementById('edit_lieu_en').value   = s.lieu_en || '';
    document.getElementById('edit_lieu_ar').value   = s.lieu_ar || '';
    document.getElementById('edit_horaire').value   = s.date_horaire || '';
    document.getElementById('edit_horaire_en').value= s.date_horaire_en || '';
    document.getElementById('edit_horaire_ar').value= s.date_horaire_ar || '';
    document.getElementById('edit_places').value    = s.places || '';
    document.getElementById('edit_places_en').value = s.places_en || '';
    document.getElementById('edit_places_ar').value = s.places_ar || '';
    document.getElementById('edit_age').value       = s.age || '';
    document.getElementById('edit_age_en').value    = s.age_en || '';
    document.getElementById('edit_age_ar').value    = s.age_ar || '';
    const preview = document.getElementById('edit_image_preview');
    if (s.image) {
        preview.innerHTML = '<img src="../uploads/programmes/' + s.image + '" style="width:80px;height:80px;border-radius:8px;object-fit:cover;">';
    } else {
        preview.innerHTML = '';
    }
    document.getElementById('editModal').classList.add('open');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('open');
}

document.querySelectorAll('.js-edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        openEditModal(this.dataset.id);
    });
});

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => {
        if (e.target === el) el.classList.remove('open');
    });
});
</script>

<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
