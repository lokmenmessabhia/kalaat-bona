<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();
$s = get_settings();

$carousel_dir = __DIR__ . '/../uploads/carousel/';
if (!is_dir($carousel_dir)) {
    mkdir($carousel_dir, 0777, true);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['filename'])) {
        $file = basename($_POST['filename']);
        $filepath = $carousel_dir . $file;
        if (is_file($filepath)) {
            unlink($filepath);
            $message = "<div style='color: green; margin-bottom: 15px;'>Image supprimée avec succès.</div>";
        }
    } elseif (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Size limit: 10MB
            if ($file['size'] > 10 * 1024 * 1024) {
                $message = "<div style='color: red; margin-bottom: 15px;'>Image trop volumineuse (max 10 Mo).</div>";
                return;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowedExts)) {
                $message = "<div style='color: red; margin-bottom: 15px;'>Format non supporté (jpg, png, webp).</div>";
                return;
            }
            // Validate MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowedMimes)) {
                $message = "<div style='color: red; margin-bottom: 15px;'>Le fichier n'est pas une image valide.</div>";
                return;
            }
            $new_name = 'img_' . bin2hex(random_bytes(12)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $carousel_dir . $new_name)) {
                $message = "<div style='color: green; margin-bottom: 15px;'>Image uploadée avec succès.</div>";
            } else {
                $message = "<div style='color: red; margin-bottom: 15px;'>Erreur lors de l'upload.</div>";
            }
        }
    }
}

$images = array_diff(scandir($carousel_dir), array('..', '.'));

$admin_page_title = 'Gestion du Carousel';
$admin_current_page = 'carousel';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 25px; margin-bottom: 20px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
    .img-card { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; position: relative; }
    .img-card img { width: 100%; height: 150px; object-fit: cover; display: block; }
    .img-card form { position: absolute; top: 5px; right: 5px; }
    .btn-delete { background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    .btn-upload { background: var(--c-navy); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    .btn-upload:hover { background: var(--c-navy-soft); }
    input[type="file"] { padding: 8px; border: 2px solid #e2e8f0; border-radius: 8px; }
</style>
        <?php echo $message; ?>
        
        <div class="card">
            <h2 style="margin-top:0;">Ajouter une image</h2>
            <form method="post" enctype="multipart/form-data" style="margin-top: 15px;">
                <input type="file" name="image" required accept="image/*" style="margin-right: 10px;">
                <button type="submit" class="btn-upload"><i class="fa-solid fa-upload"></i> Uploader</button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Images Actuelles</h2>
            <div class="grid">
                <?php if (empty($images)): ?>
                    <p style="color: #666;">Aucune image dans le carousel pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($images as $img): ?>
                        <div class="img-card">
                            <img src="<?php echo url_path('/uploads/carousel/' . htmlspecialchars($img)); ?>" alt="Carousel">
                            <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette image ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($img); ?>">
                                <button type="submit" class="btn-delete" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
