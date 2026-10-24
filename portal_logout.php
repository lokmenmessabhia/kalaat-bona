<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/app.php';

session_start();
session_destroy();
header('Location: ' . url_path('/portal_login.php'));
exit;
