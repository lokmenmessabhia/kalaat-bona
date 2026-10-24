<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();
$s = get_settings();

if (($admin['role'] ?? '') !== 'coordinateur') {
    header('Location: ' . url_path('/admin/dashboard'));
    exit;
}

$pdo = db();
$error = null;
$success = null;

// Handle assign/unassign programmes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_programmes') {
    $id_coach = (int)($_POST['id_coach'] ?? 0);
    $prog_ids = $_POST['programme_ids'] ?? [];
    $pdo->prepare('UPDATE programme SET id_coach = NULL WHERE id_coach = ?')->execute([$id_coach]);
    if (!empty($prog_ids)) {
        $progList = implode(',', array_map('intval', $prog_ids));
        $pdo->exec("UPDATE programme SET id_coach = {$id_coach} WHERE id_programme IN ({$progList})");
    }
    $success = "Programmes mis à jour pour le coach.";
}

// Fetch coaches with their assigned programmes
$coachesList = $pdo->query("
    SELECT a.id_admin, a.nom, a.email, a.role,
           GROUP_CONCAT(p.nom_programme SEPARATOR ', ') AS programmes
    FROM admin a
    LEFT JOIN programme p ON p.id_coach = a.id_admin
    WHERE a.role = 'coach'
    GROUP BY a.id_admin
    ORDER BY a.nom
")->fetchAll();

// Fetch all programmes
$programsList = $pdo->query("SELECT id_programme, nom_programme, id_coach FROM programme ORDER BY nom_programme")->fetchAll();

$admin_page_title = 'Dashboard Coordinateur';
$admin_current_page = 'coordinateur_dashboard';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .card { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .section-header h1 { color: var(--c-navy); font-size: 1.4rem; font-weight: 800; }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    th { font-weight: 700; color: #1e293b; background: #f8fafc; font-size: 0.85rem; text-transform: uppercase; }
    .role-coach { padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

    .prog-assign { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .prog-assign label { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; color: #1e293b; cursor: pointer; background: #f8fafc; padding: 10px 12px; border-radius: 10px; border: 1px solid #e2e8f0; transition: all 0.2s; }
    .prog-assign label:hover { background: #eff6ff; border-color: #bfdbfe; }

    .btn-assign { background: #eff6ff; color: #1d4ed8; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; }
    .btn-assign:hover { background: #1d4ed8; color: white; }
    .msg { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; text-align: center; }
    .msg-success { background: #ecfdf5; color: #059669; }
    .msg-error { background: #fef2f2; color: #dc2626; }
    .header-wrap { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
    .welcome-msg h1 { font-size: 1.8rem; color: var(--c-navy); font-weight: 800; }
    .welcome-msg p { color: #64748b; font-size: 1rem; }
</style>

<div class="header-wrap">
    <div class="welcome-msg">
        <h1><i class="fa-solid fa-people-arrows" style="color:var(--c-sky);"></i> Gestion des Coachs</h1>
        <p>Assignez ou retirez des programmes aux coachs de l'académie.</p>
    </div>
    <div style="background:white; padding:15px 20px; border-radius:15px; border:1px solid #e2e8f0; text-align:right;">
        <span style="display:block; font-size:0.8rem; color:#64748b;">Aujourd'hui</span>
        <span style="font-weight:800; color:var(--c-navy);"><?php echo date('d F Y'); ?></span>
    </div>
</div>

<?php if($success): ?>
    <div class="msg msg-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="msg msg-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="section-header">
        <h1><i class="fa-solid fa-whistle" style="color:#1d4ed8;"></i> Coachs</h1>
        <span style="color:#64748b; font-size:0.9rem;"><?php echo count($coachesList); ?> coach(s)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Coach</th>
                <th>Email</th>
                <th>Programmes Assignés</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($coachesList)): ?>
                <tr><td colspan="4" style="color:#94a3b8; padding:30px; text-align:center;">Aucun coach enregistré. Contactez l'administrateur principal.</td></tr>
            <?php else: ?>
                <?php foreach ($coachesList as $co): ?>
                    <tr>
                        <td style="font-weight:700; color:#1e293b;">
                            <span class="role-coach" style="margin-right:8px;">COACH</span>
                            <?php echo htmlspecialchars($co['nom']); ?>
                        </td>
                        <td style="color:#64748b;"><?php echo htmlspecialchars($co['email']); ?></td>
                        <td style="color:#475569; font-size:0.9rem;"><?php echo htmlspecialchars($co['programmes'] ?: 'Aucun programme assigné'); ?></td>
                        <td>
                            <button class="btn-assign" onclick="openAssignModal(<?php echo $co['id_admin']; ?>, '<?php echo addslashes($co['nom']); ?>')">
                                <i class="fa-solid fa-clipboard-list"></i> Programmes
                            </button>
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
        <h3 style="color:var(--c-navy); margin-bottom:5px;"><i class="fa-solid fa-clipboard-list"></i> Assigner des Programmes</h3>
        <p id="assignCoachName" style="color:#64748b; margin-bottom:20px;"></p>
        <form method="POST">
            <input type="hidden" name="action" value="assign_programmes">
            <input type="hidden" name="id_coach" id="assignCoachId">
            <div class="prog-assign">
                <?php foreach($programsList as $prog): ?>
                    <label>
                        <input type="checkbox" name="programme_ids[]" value="<?php echo $prog['id_programme']; ?>"
                            <?php echo ($prog['id_coach'] == -1) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($prog['nom_programme']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="display:flex; gap:10px; margin-top:25px;">
                <button type="submit" style="flex:1; padding:12px; background:var(--c-navy); color:white; border:none; border-radius:12px; font-weight:700; cursor:pointer;">
                    <i class="fa-solid fa-check"></i> Enregistrer
                </button>
                <button type="button" onclick="closeAssignModal()" style="flex:1; padding:12px; border:1px solid #e2e8f0; border-radius:12px; background:white; cursor:pointer; font-weight:700;">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const programsList = <?php echo json_encode(array_map(fn($p) => ['id' => $p['id_programme'], 'coach' => $p['id_coach']], $programsList)); ?>;

    function openAssignModal(coachId, coachName) {
        document.getElementById('assignCoachId').value = coachId;
        document.getElementById('assignCoachName').textContent = 'Coach: ' + coachName;

        // Pre-check programmes assigned to this coach
        document.querySelectorAll('#assignModal input[type="checkbox"]').forEach(cb => {
            const prog = programsList.find(p => p.id == cb.value);
            cb.checked = prog && prog.coach == coachId;
        });

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
