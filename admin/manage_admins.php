<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();
$s = get_settings();

// Only main admins can manage other admins
if (($admin['role'] ?? 'subadmin') !== 'main') {
    header('Location: ' . url_path('/admin/dashboard'));
    exit;
}

$pdo = db();
$error = null;
$success = null;

// Handle Add / Delete logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $nom = trim((string)($_POST['nom'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'subadmin');

        if ($nom === '' || $email === '' || $pass === '') {
            $error = "Tous les champs sont requis.";
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO admin (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $email, $hash, $role]);
                $newId = (int)$pdo->lastInsertId();
                // If coach, assign selected programmes
                if ($role === 'coach' && !empty($_POST['programme_ids'])) {
                    $progIds = array_map('intval', $_POST['programme_ids']);
                    $progList = implode(',', $progIds);
                    $pdo->exec("UPDATE programme SET id_coach = {$newId} WHERE id_programme IN ({$progList})");
                }
                $success = ($role === 'coach') ? "Coach créé et programmes assignés avec succès." : "Administrateur ajouté avec succès.";
            } catch (Exception $e) {
                $error = "Erreur : L'email existe peut-être déjà.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'assign_programmes') {
        $id_coach = (int)($_POST['id_coach'] ?? 0);
        $prog_ids = $_POST['programme_ids'] ?? [];
        // First clear any programmes currently assigned to this coach
        $pdo->prepare('UPDATE programme SET id_coach = NULL WHERE id_coach = ?')->execute([$id_coach]);
        // Assign selected programmes
        if (!empty($prog_ids)) {
            $progList = implode(',', array_map('intval', $prog_ids));
            $pdo->exec("UPDATE programme SET id_coach = {$id_coach} WHERE id_programme IN ({$progList})");
        }
        $success = "Programmes mis à jour pour ce coach.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)($_POST['id_admin'] ?? 0);
        // Can't delete self
        if ($id === (int)$admin['id_admin']) {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM admin WHERE id_admin = ?");
            $stmt->execute([$id]);
            $success = "Administrateur supprimé.";
        }
    }
}

// Fetch all admins
$adminsList = $pdo->query("SELECT id_admin, nom, email, role FROM admin WHERE role != 'coach' ORDER BY role, nom")->fetchAll();
$coachesList = $pdo->query("
    SELECT a.id_admin, a.nom, a.email, a.role,
           GROUP_CONCAT(p.nom_programme SEPARATOR ', ') AS programmes
    FROM admin a
    LEFT JOIN programme p ON p.id_coach = a.id_admin
    WHERE a.role = 'coach'
    GROUP BY a.id_admin
    ORDER BY a.nom
")->fetchAll();
$programsList = $pdo->query("SELECT id_programme, nom_programme, id_coach FROM programme ORDER BY nom_programme")->fetchAll();

$admin_page_title = 'Gestion des Admins';
$admin_current_page = 'manage_admins';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .card { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .section-header h1 { color: var(--c-navy); font-size: 1.4rem; font-weight: 800; }
    
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    th { font-weight: 700; color: #1e293b; background: #f8fafc; font-size: 0.85rem; text-transform: uppercase; }
    .role-badge { padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .role-main { background: #fffcf0; color: #b45309; border: 1px solid #fef3c7; }
    .role-sub { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
    .role-coach { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .prog-assign { display: flex; flex-wrap: wrap; gap: 5px; }
    .prog-assign label { display: flex; align-items: center; gap: 5px; font-size: 0.8rem; font-weight: 600; color: #1e293b; cursor: pointer; background: #f8fafc; padding: 4px 10px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .prog-assign label:hover { background: #eff6ff; border-color: #bfdbfe; }
    #coach-fields { display: none; }
    
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
    .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; transition: all 0.3s ease; }
    .form-control:focus { outline: none; border-color: var(--c-sky); }
    .btn-add { background: var(--c-navy); color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; height: 100%; box-shadow: 0 4px 12px rgba(0,24,56,0.15); transition: all 0.3s ease; }
    .btn-add:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,24,56,0.25); }
    
    .btn-del { border: none; background: #fef2f2; color: #dc2626; padding: 8px 12px; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
    .btn-del:hover { background: #dc2626; color: white; }
    .msg { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; text-align: center; }
    .msg-success { background: #ecfdf5; color: #059669; }
    .msg-error { background: #fef2f2; color: #dc2626; }
</style>
        
        <?php if($success): ?>
            <div class="msg msg-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="msg msg-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="section-header"><h1><i class="fa-solid fa-user-plus" style="color:var(--primary-burgundy);"></i> Ajouter un Compte</h1></div>
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <input type="text" name="nom" class="form-control" placeholder="Nom Complet" required>
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                    <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                    <select name="role" class="form-control" required onchange="toggleCoachFields(this.value)">
                        <option value="subadmin">Sub-Admin</option>
                        <option value="main">Main Admin</option>
                        <option value="coach">Coach</option>
                    </select>
                    <button type="submit" class="btn-add"><i class="fa-solid fa-plus"></i> Ajouter</button>
                </div>
                <div id="coach-fields" style="margin-top: 20px; padding: 20px; background: #eff6ff; border-radius: 12px; border: 1px solid #bfdbfe;">
                    <h3 style="color: #1d4ed8; margin-bottom: 15px; font-size:1rem;"><i class="fa-solid fa-clipboard-list"></i> Assigner des Programmes au Coach</h3>
                    <div class="prog-assign">
                        <?php foreach($programsList as $prog): ?>
                            <label>
                                <input type="checkbox" name="programme_ids[]" value="<?php echo $prog['id_programme']; ?>">
                                <?php echo htmlspecialchars($prog['nom_programme']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="section-header"><h1><i class="fa-solid fa-user-shield" style="color:var(--primary-burgundy);"></i> Administrateurs</h1></div>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($adminsList as $a): ?>
                        <tr>
                            <td style="font-weight:700; color:#1e293b;"><?php echo htmlspecialchars($a['nom']); ?></td>
                            <td style="color:#64748b;"><?php echo htmlspecialchars($a['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo ($a['role'] === 'main' ? 'main' : 'sub'); ?>">
                                    <?php echo htmlspecialchars($a['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($a['id_admin'] != $admin['id_admin']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet administrateur ?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_admin" value="<?php echo $a['id_admin']; ?>">
                                        <button type="submit" class="btn-del"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#cbd5e1; font-size:0.8rem;">(Vous)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="section-header"><h1><i class="fa-solid fa-whistle" style="color:#1d4ed8;"></i> Coachs</h1></div>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Programmes Assignés</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($coachesList)): ?>
                        <tr><td colspan="4" style="color:#94a3b8; padding:20px; text-align:center;">Aucun coach enregistré.</td></tr>
                    <?php else: ?>
                        <?php foreach ($coachesList as $co): ?>
                            <tr>
                                <td style="font-weight:700; color:#1e293b;">
                                    <span class="role-badge role-coach" style="margin-right:8px;">COACH</span>
                                    <?php echo htmlspecialchars($co['nom']); ?>
                                </td>
                                <td style="color:#64748b;"><?php echo htmlspecialchars($co['email']); ?></td>
                                <td style="color:#475569; font-size:0.9rem;"><?php echo htmlspecialchars($co['programmes'] ?: 'Aucun programme assigné'); ?></td>
                                <td>
                                    <div style="display:flex; gap:8px;">
                                        <button class="btn-del" style="background:#eff6ff; color:#1d4ed8;" onclick="openAssignModal(<?php echo $co['id_admin']; ?>, '<?php echo addslashes($co['nom']); ?>')"><i class="fa-solid fa-clipboard-list"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce coach ?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_admin" value="<?php echo $co['id_admin']; ?>">
                                            <button type="submit" class="btn-del"><i class="fa-solid fa-trash-can"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Assign Programme Modal -->
        <div id="assignModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
            <div style="background:white; border-radius:20px; padding:35px; width:100%; max-width:500px; box-shadow:0 25px 50px rgba(0,0,0,0.2);">
                <h3 style="color:var(--primary-burgundy); margin-bottom:5px;"><i class="fa-solid fa-clipboard-list"></i> Assigner des Programmes</h3>
                <p id="assignCoachName" style="color:#64748b; margin-bottom:20px;"></p>
                <form method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="assign_programmes">
                    <input type="hidden" name="id_coach" id="assignCoachId">
                    <div class="prog-assign" style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        <?php foreach($programsList as $prog): ?>
                            <label style="border:1px solid #e2e8f0; padding:10px 12px; border-radius:10px; display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" name="programme_ids[]" value="<?php echo $prog['id_programme']; ?>" <?php echo ($prog['id_coach'] == -1) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($prog['nom_programme']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:25px;">
                        <button type="submit" class="btn-add" style="flex:1;"><i class="fa-solid fa-check"></i> Enregistrer</button>
                        <button type="button" onclick="closeAssignModal()" style="flex:1; padding:12px; border:1px solid #e2e8f0; border-radius:12px; background:white; cursor:pointer; font-weight:700;">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleCoachFields(role) {
            document.getElementById('coach-fields').style.display = (role === 'coach') ? 'block' : 'none';
        }
        function openAssignModal(coachId, coachName) {
            document.getElementById('assignCoachId').value = coachId;
            document.getElementById('assignCoachName').textContent = 'Coach: ' + coachName;
            document.getElementById('assignModal').style.display = 'flex';
        }
        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
        }
        window.addEventListener('click', e => {
            if (e.target === document.getElementById('assignModal')) closeAssignModal();
        });
    </script>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
