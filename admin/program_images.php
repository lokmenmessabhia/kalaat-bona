<?php
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';

require_admin();

$pdo = db();
$message = '';
$error = '';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['programme_image'])) {
    $id_programme = (int)$_POST['id_programme'] ?? 0;
    $file = $_FILES['programme_image'];
    
    if ($id_programme === 0) {
        $error = 'Veuillez sélectionner un programme.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Erreur lors du téléchargement du fichier.';
    } else {
        // Validate file type
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            $error = 'Format de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Le fichier est trop volumineux. Maximum 5 Mo.';
        } else {
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'programme_' . $id_programme . '_' . time() . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/programmes/';
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update database
                $stmt = $pdo->prepare('UPDATE programme SET image = ? WHERE id_programme = ?');
                $stmt->execute([$filename, $id_programme]);
                $message = 'Image téléchargée avec succès!';
            } else {
                $error = 'Erreur lors de la sauvegarde du fichier.';
            }
        }
    }
}

// Get all programmes
$programmes = $pdo->query('SELECT id_programme, nom_programme, image FROM programme WHERE nom_programme != "" ORDER BY nom_programme')->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestion des Images - Programmes';
$body_class = 'admin-programmes';
$extra_styles = '
.programmes-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: var(--s6) var(--s5);
}
.programmes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: var(--s5);
    margin-top: var(--s6);
}
.programme-card {
    background: var(--c-white);
    border: 1px solid var(--c-border);
    border-radius: var(--r-lg);
    overflow: hidden;
    box-shadow: var(--shadow-card);
    transition: all var(--t-base);
}
.programme-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}
.programme-image-preview {
    width: 100%;
    height: 160px;
    object-fit: cover;
    display: block;
    background: var(--c-offwhite);
}
.programme-card-content {
    padding: var(--s5);
}
.programme-name {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: var(--s3);
    color: var(--c-navy);
}
.image-status {
    font-size: 0.85rem;
    color: var(--c-text-muted);
    margin-bottom: var(--s3);
}
.image-status.has {
    color: var(--c-sky);
}
.upload-btn {
    width: 100%;
    padding: var(--s3);
    background: var(--c-sky);
    color: var(--c-white);
    border: none;
    border-radius: var(--r-md);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--t-base);
    font-size: 0.9rem;
}
.upload-btn:hover {
    background: var(--c-sky-light);
    transform: translateY(-2px);
}
.alert {
    padding: var(--s4);
    border-radius: var(--r-md);
    margin-bottom: var(--s5);
    font-weight: 500;
}
.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-left: 4px solid #10b981;
}
.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-left: 4px solid #ef4444;
}
.upload-form {
    background: var(--c-offwhite);
    padding: var(--s6);
    border-radius: var(--r-lg);
    margin-bottom: var(--s6);
}
.form-group {
    margin-bottom: var(--s4);
}
.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: var(--s2);
    color: var(--c-navy);
}
.form-group select,
.form-group input[type="file"] {
    width: 100%;
    padding: var(--s3);
    border: 1px solid var(--c-border);
    border-radius: var(--r-md);
    font-size: 0.9rem;
}
.form-actions {
    display: flex;
    gap: var(--s3);
}
.btn-submit {
    flex: 1;
    padding: var(--s3) var(--s5);
    background: var(--c-navy);
    color: var(--c-white);
    border: none;
    border-radius: var(--r-full);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--t-base);
}
.btn-submit:hover {
    background: var(--c-navy-soft);
}
@media (max-width: 768px) {
    .programmes-grid {
        grid-template-columns: 1fr;
    }
    .programmes-container {
        padding: var(--s4) var(--s3);
    }
}
';

require_once __DIR__ . '/../lib/admin_header.php';
?>

<div class="programmes-container">
    <h1>📸 Gestion des Images - Programmes</h1>
    <p style="color: var(--c-text-muted); margin-bottom: var(--s5);">Téléchargez des images pour chaque programme. Les images s\'afficheront sur la page des services.</p>

    <?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="upload-form">
        <h3 style="margin-bottom: var(--s4); color: var(--c-navy);">Télécharger une Image</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="id_programme">Sélectionner un programme:</label>
                <select name="id_programme" id="id_programme" required>
                    <option value="">-- Choisir un programme --</option>
                    <?php foreach ($programmes as $prog): ?>
                    <option value="<?php echo (int)$prog['id_programme']; ?>">
                        <?php echo htmlspecialchars((string)$prog['nom_programme']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="programme_image">Sélectionner une image:</label>
                <input type="file" name="programme_image" id="programme_image" accept="image/*" required>
                <small style="color: var(--c-text-muted); display: block; margin-top: var(--s2);">Max: 5 Mo. Formats: JPG, PNG, GIF, WebP</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">📤 Télécharger l'image</button>
            </div>
        </form>
    </div>

    <!-- Programmes Grid -->
    <h3 style="margin-bottom: var(--s4); color: var(--c-navy);">Programmes</h3>
    <div class="programmes-grid">
        <?php foreach ($programmes as $prog): ?>
        <div class="programme-card">
            <img src="<?php echo $prog['image'] ? url_path('/uploads/programmes/' . htmlspecialchars($prog['image'])) : url_path('/logo_kala.webp'); ?>" alt="<?php echo htmlspecialchars((string)$prog['nom_programme']); ?>" class="programme-image-preview">
            <div class="programme-card-content">
                <p class="programme-name"><?php echo htmlspecialchars((string)$prog['nom_programme']); ?></p>
                <p class="image-status <?php echo $prog['image'] ? 'has' : ''; ?>">
                    <?php echo $prog['image'] ? '✓ Image définie' : '✗ Pas d\'image'; ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
