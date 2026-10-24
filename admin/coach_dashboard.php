<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/settings.php';
require_once __DIR__ . '/../lib/app.php';

require_admin();
$admin = admin_current();
if (($admin['role'] ?? '') !== 'coach') {
    header('Location: ' . url_path('/admin/dashboard.php'));
    exit;
}

$pdo = db();
$settings = get_settings();

// 1. Get Coach's Programs
$stmt = $pdo->prepare('SELECT * FROM programme WHERE id_coach = ?');
$stmt->execute([$admin['id_admin']]);
$programs = $stmt->fetchAll();

$selected_program_id = (int)($_GET['program_id'] ?? ($programs[0]['id_programme'] ?? 0));

// 2. Get Players in Selected Program
$players = [];
if ($selected_program_id > 0) {
    $stmt = $pdo->prepare('
        SELECT j.*, i.date_inscription 
        FROM joueur j
        JOIN inscription i ON j.id_joueur = i.id_joueur
        WHERE i.id_programme = ?
        ORDER BY j.nom, j.prenom
    ');
    $stmt->execute([$selected_program_id]);
    $players = $stmt->fetchAll();
}

// 3. Get Recent Suivis for Coach's players
$recentSuivis = [];
if ($selected_program_id > 0) {
    $stmt = $pdo->prepare('
        SELECT s.*, j.prenom, j.nom 
        FROM suivi s
        JOIN joueur j ON s.id_joueur = j.id_joueur
        JOIN inscription i ON j.id_joueur = i.id_joueur
        WHERE i.id_programme = ?
        GROUP BY s.id_suivi
        ORDER BY s.date_suivi DESC
        LIMIT 15
    ');
    $stmt->execute([$selected_program_id]);
    $recentSuivis = $stmt->fetchAll();
}

$flash_ok = isset($_GET['ok']);
$flash_err = isset($_GET['err']);

$admin_page_title = 'Espace Coach';
$admin_current_page = 'coach_dashboard';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .welcome-msg h1 { font-size: 2rem; color: var(--c-navy); font-weight: 800; }
    .welcome-msg p { color: #64748b; font-size: 1.1rem; }

    /* Program Selector */
    .program-tabs { display: flex; gap: 15px; margin-bottom: 30px; overflow-x: auto; padding-bottom: 10px; }
    .program-tab { padding: 12px 24px; background: white; border-radius: 15px; text-decoration: none; color: #64748b; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; white-space: nowrap; transition: all 0.3s; }
    .program-tab.active { background: var(--c-sky); color: var(--c-navy); border-color: var(--c-sky); }

    /* KPI Cards */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .stat-card { background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .stat-val { font-size: 1.8rem; font-weight: 800; color: var(--c-navy); display: block; }
    .stat-label { font-size: 0.9rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }

    /* Table Card */
    .data-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 40px; }
    .data-header { padding: 25px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .data-header h2 { font-size: 1.3rem; font-weight: 800; color: var(--c-navy); }
    
    table { width: 100%; border-collapse: collapse; }
    th { background: #f8fafc; padding: 15px 25px; text-align: left; font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 700; }
    td { padding: 18px 25px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
    .player-info { display: flex; align-items: center; gap: 15px; }
    .player-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #e2e8f0; }

    .btn-action { padding: 8px 16px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 0.85rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
    .btn-plus { background: var(--c-navy); color: white; }
    .btn-plus:hover { opacity: 0.9; transform: translateY(-2px); }

    /* Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-content { background: white; padding: 40px; border-radius: 24px; width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .modal-header { margin-bottom: 25px; display: flex; justify-content: space-between; }
    .modal-header h3 { font-size: 1.5rem; color: var(--c-navy); font-weight: 800; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 700; margin-bottom: 8px; color: #1e293b; font-size: 0.9rem; }
    .form-control { width: 100%; padding: 12px 18px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; }
</style>
        <?php if($flash_ok): ?>
            <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#059669; padding:15px 20px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fa-solid fa-check-circle"></i> Suivi enregistré avec succès !</div>
        <?php elseif($flash_err): ?>
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:15px 20px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fa-solid fa-exclamation-circle"></i> Erreur lors de l'enregistrement.</div>
        <?php endif; ?>
        <div class="header-wrap">
            <div class="welcome-msg">
                <h1>Bonjour Coach, <?php echo htmlspecialchars($admin['nom']); ?> !</h1>
                <p>Gérez vos programmes et suivez vos joueurs.</p>
            </div>
            <div style="background:white; padding:15px; border-radius:15px; border:1px solid #e2e8f0; text-align:right;">
                <span style="display:block; font-size:0.8rem; color:#64748b;">Aujourd'hui</span>
                <span style="font-weight:800; color:var(--coach-burgundy);"><?php echo date('d F Y'); ?></span>
            </div>
        </div>

        <?php if (empty($programs)): ?>
            <div class="data-card" style="padding: 60px; text-align:center;">
                <i class="fa-solid fa-futbol" style="font-size:4rem; color:#e2e8f0; margin-bottom:20px;"></i>
                <h2>Aucun programme assigné</h2>
                <p>Contactez l'administrateur pour vous assigner à des groupes d'entraînement.</p>
            </div>
        <?php else: ?>
            <div class="program-tabs">
                <?php foreach($programs as $p): ?>
                    <a href="?program_id=<?php echo $p['id_programme']; ?>" class="program-tab <?php echo ($selected_program_id == $p['id_programme']) ? 'active' : ''; ?>">
                        <i class="fa-solid fa-layer-group"></i> <?php echo htmlspecialchars($p['nom_programme']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php 
                $active_p = array_filter($programs, fn($pr) => $pr['id_programme'] == $selected_program_id);
                $active_p = reset($active_p);
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-val"><?php echo count($players); ?></span>
                    <span class="stat-label">Joueurs inscrits</span>
                </div>
                <div class="stat-card">
                    <span class="stat-val" style="font-size:1.1rem;"><?php echo htmlspecialchars($active_p['lieu'] ?: 'Non défini'); ?></span>
                    <span class="stat-label">Terrain / Lieu</span>
                </div>
                <div class="stat-card">
                    <span class="stat-val" style="font-size:1.1rem;"><?php echo htmlspecialchars($active_p['date_horaire'] ?: 'Non défini'); ?></span>
                    <span class="stat-label">Planning</span>
                </div>
            </div>

            <div class="data-card">
                <div class="data-header">
                    <div>
                        <span style="font-size:0.75rem; color:var(--c-navy); font-weight:800; text-transform:uppercase;">Catégorie ACTIVE</span>
                        <h2 style="margin-top:5px;"><?php echo htmlspecialchars($active_p['nom_programme']); ?> - Effectif</h2>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <span style="color:#64748b; font-size:0.9rem;"><?php echo count($players); ?> Joueurs</span>
                        <button class="btn-action" style="background:#0ea5e9; color:white;" onclick="alert('Fonctionnalité: Envoi groupé aux parents de cette catégorie (en cours de développement)')">
                            <i class="fa-solid fa-bullhorn"></i> Message Groupe
                        </button>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Joueur</th>
                            <th>Inscrit le</th>
                            <th>Dernier Suivi</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($players as $plyr): ?>
                            <tr>
                                <td>
                                    <div class="player-info">
                                        <?php if($plyr['photo']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($plyr['photo']); ?>" class="player-img">
                                        <?php else: ?>
                                            <div class="player-img" style="display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-user" style="color:#94a3b8;"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($plyr['prenom'] . ' ' . $plyr['nom']); ?></strong><br>
                                            <span style="font-size:0.8rem; color:#64748b;">ID: #<?php echo $plyr['id_joueur']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($plyr['date_inscription'])); ?></td>
                                <td>
                                    <?php 
                                        $last = $pdo->prepare('SELECT date_suivi FROM suivi WHERE id_joueur = ? ORDER BY date_suivi DESC LIMIT 1');
                                        $last->execute([$plyr['id_joueur']]);
                                        $l = $last->fetch();
                                        echo $l ? date('d/m/Y', strtotime($l['date_suivi'])) : '---';
                                    ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <button class="btn-action btn-plus" onclick="openSuiviModal(<?php echo $plyr['id_joueur']; ?>, '<?php echo addslashes($plyr['prenom'] . ' ' . $plyr['nom']); ?>')">
                                            <i class="fa-solid fa-plus"></i> Suivi
                                        </button>
                                        <?php if($plyr['id_parent']): ?>
                                            <button class="btn-action" style="background:#0ea5e9; color:white;" onclick="openChat(<?php echo $plyr['id_parent']; ?>, '<?php echo addslashes($plyr['prenom'] . ' ' . $plyr['nom']); ?>')">
                                                <i class="fa-solid fa-comment"></i> Parent
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="data-card">
                <div class="data-header">
                    <h2>Récents Suivis du groupe</h2>
                </div>
                <div style="padding: 20px;">
                    <?php if(empty($recentSuivis)): ?>
                        <p style="text-align:center; color:#94a3b8;">Aucun suivi récent pour ce groupe.</p>
                    <?php else: ?>
                        <div style="display:grid; gap:10px;">
                            <?php foreach($recentSuivis as $rs): ?>
                                <div style="display:flex; justify-content:space-between; padding:15px; background:#f8fafc; border-radius:12px; border:1px solid #f1f5f9;">
                                    <div>
                                        <span style="font-weight:800; color:var(--coach-burgundy);"><?php echo htmlspecialchars($rs['prenom'] . ' ' . $rs['nom']); ?></span>
                                        <span style="margin:0 10px; color:#94a3b8;">|</span>
                                        <span style="font-weight:700;"><?php echo htmlspecialchars($rs['performance']); ?></span>
                                        <p style="margin:5px 0 0; font-size:0.85rem; color:#64748b;"><?php echo htmlspecialchars($rs['remarque']); ?></p>
                                    </div>
                                    <div style="text-align:right;">
                                        <span style="font-size:0.8rem; font-weight:700; color:var(--coach-burgundy);"><?php echo date('d/m/Y', strtotime($rs['date_suivi'])); ?></span><br>
                                        <span style="font-size:0.75rem; text-transform:uppercase; color:#059669; font-weight:800;"><?php echo htmlspecialchars($rs['presence']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
</div>

    <!-- Chat Widget (Floating) -->
    <div id="chatWidget" style="display:none; position:fixed; bottom:30px; right:30px; width:350px; height:500px; background:white; border-radius:20px; box-shadow:0 15px 50px rgba(0,0,0,0.2); flex-direction:column; z-index:2000; border:1px solid #e2e8f0; overflow:hidden;">
        <div style="background:var(--c-navy); padding:20px; color:white; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h4 style="margin:0; font-size:1rem;" id="chatTitle">Chat avec Parent</h4>
                <span style="font-size:0.75rem; opacity:0.8;" id="chatSubtitle">Chargement...</span>
            </div>
            <button onclick="closeChat()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div id="chatMessages" style="flex:1; padding:20px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; background:#f8fafc;">
            <!-- Messages load here -->
        </div>
        <div style="padding:15px; border-top:1px solid #f1f5f9; display:flex; gap:10px;">
            <input type="text" id="chatInput"
                style="flex:1; padding:10px 15px; border:1px solid #e2e8f0; border-radius:10px; outline:none;"
                placeholder="Message... (Entrée pour envoyer)"
                onkeydown="if(event.key==='Enter'){event.preventDefault();sendMessage();}">
            <button id="coachSendBtn" onclick="sendMessage()" style="padding:10px 15px; background:var(--c-navy); color:white; border:none; border-radius:10px; cursor:pointer;"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- Modal Suivi -->

    <div id="suiviModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter un Suivi</h3>
                <button onclick="closeSuiviModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form action="api_add_suivi.php" method="POST">
                <input type="hidden" name="id_joueur" id="modal_player_id">
                <input type="hidden" name="from" value="coach">
                <input type="hidden" name="program_id" value="<?php echo $selected_program_id; ?>">
                <div class="form-group">
                    <label>Joueur</label>
                    <input type="text" id="modal_player_name" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label>Performance / Séance</label>
                    <input type="text" name="performance" class="form-control" placeholder="Ex: Entraînement Technique, Match amical..." required>
                </div>
                <div class="form-group">
                    <label>Remarque / Évaluation</label>
                    <textarea name="remarque" class="form-control" rows="3" placeholder="Progression, points à améliorer..."></textarea>
                </div>
                <div class="form-group">
                    <label>Présence</label>
                    <select name="presence" class="form-control">
                        <option value="Présent">Présent</option>
                        <option value="Absent">Absent</option>
                        <option value="Blessé">Blessé</option>
                        <option value="Maladie">Maladie</option>
                        <option value="Retard">Retard</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date de la séance</label>
                    <input type="date" name="date_suivi" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div style="margin-top:30px; display:flex; gap:10px;">
                    <button type="submit" class="btn-action btn-plus" style="width:100%; justify-content:center; padding:15px;">Enregistrer le suivi</button>
                    <button type="button" onclick="closeSuiviModal()" class="btn-action" style="width:100%; justify-content:center; background:#f1f5f9; color:#64748b;">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentChatParentId = null;
        let coachPollTimer = null;

        function openSuiviModal(id, name) {
            document.getElementById('modal_player_id').value = id;
            document.getElementById('modal_player_name').value = name;
            document.getElementById('suiviModal').style.display = 'flex';
        }
        function closeSuiviModal() {
            document.getElementById('suiviModal').style.display = 'none';
        }

        // ── Real-Time Chat (Coach side) ──────────────────────────────────
        function openChat(parentId, playerName) {
            currentChatParentId = parentId;
            document.getElementById('chatTitle').textContent = 'Parent de ' + playerName;
            document.getElementById('chatWidget').style.display = 'flex';
            loadCoachMessages();
            if(coachPollTimer) clearInterval(coachPollTimer);
            coachPollTimer = setInterval(loadCoachMessages, 3000);
            document.getElementById('chatInput').focus();
        }

        function closeChat() {
            document.getElementById('chatWidget').style.display = 'none';
            if(coachPollTimer) { clearInterval(coachPollTimer); coachPollTimer = null; }
        }

        async function loadCoachMessages() {
            if(!currentChatParentId) return;
            try {
                const res = await fetch(`../api/messages.php?id_parent=${currentChatParentId}`);
                if (!res.ok) return;
                const messages = await res.json();
                const container = document.getElementById('chatMessages');
                container.innerHTML = '';
                const count = messages.length;
                document.getElementById('chatSubtitle').textContent = count + ' message' + (count !== 1 ? 's' : '');

                if (!messages.length) {
                    container.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;">Aucun message.</div>';
                    return;
                }

                messages.forEach(msg => {
                    const wrapper = document.createElement('div');
                    const isMe = msg.sender_type === 'admin';
                    wrapper.style.cssText = `display:flex; flex-direction:column; align-items:${isMe ? 'flex-end' : 'flex-start'}; margin-bottom:8px;`;

                    const div = document.createElement('div');
                    div.style.background = isMe ? 'var(--c-navy)' : 'white';
                    div.style.color = isMe ? 'white' : 'var(--c-navy)';
                    div.style.padding = '10px 14px';
                    div.style.borderRadius = isMe ? '15px 15px 0 15px' : '15px 15px 15px 0';
                    div.style.maxWidth = '80%';
                    div.style.fontSize = '0.9rem';
                    div.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
                    div.textContent = msg.message;

                    const time = document.createElement('span');
                    time.style.cssText = 'font-size:0.7rem;color:#94a3b8;margin-top:3px;padding:0 5px;';
                    time.textContent = msg.heure || '';

                    wrapper.appendChild(div);
                    wrapper.appendChild(time);
                    container.appendChild(wrapper);
                });
                container.scrollTop = container.scrollHeight;
            } catch(e) {}
        }

        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const btn = document.getElementById('coachSendBtn');
            const msg = input.value.trim();
            if(!msg || !currentChatParentId) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                await fetch('../api/messages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_parent: currentChatParentId, message: msg })
                });
                input.value = '';
                loadCoachMessages();
            } catch(e) {}
            finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                input.focus();
            }
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('suiviModal')) closeSuiviModal();
        }
    </script>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
