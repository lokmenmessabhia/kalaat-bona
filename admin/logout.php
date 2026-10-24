<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/app.php';

admin_logout();
header('Location: ' . url_path('/admin/login.php'));
exit;

