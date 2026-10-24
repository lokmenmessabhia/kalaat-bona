<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function get_settings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }

    $pdo = db();
    try {
        $check = $pdo->query("SELECT s_value FROM settings WHERE s_key = 'site_name'")->fetchColumn();
        if ($check === 'TILBI Academy' || $check === 'KALAAT BONA' || $check === false || $check === null) {
            $stmtInsert = $pdo->prepare("INSERT INTO settings (s_key, s_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE s_value = VALUES(s_value)");
            $stmtInsert->execute(['site_name', 'Kallat Bona Football Club']);
            $stmtInsert->execute(['site_logo', 'logo_kala.webp']);
            $stmtInsert->execute(['primary_color', '#0F4B92']);
            $stmtInsert->execute(['secondary_color', '#FFFFFF']);
            
            $pdo->exec("UPDATE programme SET lieu = REPLACE(lieu, 'TILBI Academy', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET lieu = REPLACE(lieu, 'TILBI', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET lieu = REPLACE(lieu, 'Tilbi', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET lieu = REPLACE(lieu, 'KALAAT BONA', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET nom_programme = REPLACE(nom_programme, 'TILBI Academy', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET nom_programme = REPLACE(nom_programme, 'TILBI', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET nom_programme = REPLACE(nom_programme, 'Tilbi', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET nom_programme = REPLACE(nom_programme, 'KALAAT BONA', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET description = REPLACE(description, 'TILBI Academy', 'Kallat Bona Football Club')");
            $pdo->exec("UPDATE programme SET description = REPLACE(description, 'TILBI', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET description = REPLACE(description, 'Tilbi', 'Kallat Bona FC')");
            $pdo->exec("UPDATE programme SET description = REPLACE(description, 'KALAAT BONA', 'Kallat Bona Football Club')");
        }
    } catch (Exception $e) {
        // Safe failover
    }

    $stmt = $pdo->query("SELECT s_key, s_value FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Default values if not set
    $defaults = [
        'site_name' => 'Kalaat Bona Football Club',
        'site_logo' => 'logo_kala.webp',
        'primary_color' => '#0F4B92',
        'secondary_color' => '#C5A059',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_encryption' => 'tls',
        'mail_from' => '',
        'mail_from_name' => 'Kallat Bona FC',
    ];

    foreach ($defaults as $key => $val) {
        if (!isset($settings[$key])) {
            $settings[$key] = $val;
        }
    }

    return $settings;
}

function render_dynamic_styles(): void
{
    $s = get_settings();
    $gold = $s['secondary_color'] ?? '#C5A059';
    echo "
    <style>
        :root {
            --primary-burgundy: {$s['primary_color']};
            --accent-gold: {$gold};
            --site-logo: url('{$s['site_logo']}');
        }
    </style>
    ";
}
