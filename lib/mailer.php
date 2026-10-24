<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/app.php';

use PHPMailer\PHPMailer\PHPMailer;

function mailer_build(): PHPMailer
{
    $s = get_settings();
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $s['smtp_host'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $s['smtp_user'] ?? '';
    $mail->Password   = $s['smtp_pass'] ?? '';
    $encryption = strtolower(trim((string)($s['smtp_encryption'] ?? 'tls')));
    $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)($s['smtp_port'] ?? ($encryption === 'ssl' ? 465 : 587));
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = 0;
    $mail->setFrom(
        $s['mail_from'] ?? '',
        $s['mail_from_name'] ?? 'Kallat Bona FC'
    );
    return $mail;
}

function mailer_send(?string $to, string $subject, string $htmlBody): bool
{
    $s = get_settings();
    if (trim((string)($s['smtp_user'] ?? '')) === '' || trim((string)($s['smtp_host'] ?? '')) === '') {
        error_log('Mailer error: SMTP not configured. Set smtp_host and smtp_user in admin settings.');
        return false;
    }
    if ($to === null || trim($to) === '') {
        error_log('Mailer error: email_contact is empty');
        return false;
    }
    $mail = null;
    try {
        $mail = mailer_build();
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</h1>', '</h2>', '</h3>', '</li>'], "\n", $htmlBody));
        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log('Mailer error: ' . ($mail !== null ? $mail->ErrorInfo : $e->getMessage()));
        return false;
    }
}

function mailer_acceptance_html(?string $nomContact, ?string $loginId, ?string $password, string $playerName, string $playerId, string $directUrl = ''): string
{
    $s = get_settings();
    $siteName = htmlspecialchars($s['site_name'] ?? 'Kallat Bona Football Club');
    $logoUrl  = absolute_url('/' . ltrim($s['site_logo'] ?? 'logo_kala.webp', '/'));
    $portalUrl = $directUrl ?: absolute_url('/portal_login');

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:'Segoe UI',Arial,sans-serif;">
<table role="presentation" style="width:100%;max-width:600px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
<tr><td style="background:#001838;padding:30px 24px;text-align:center;">
<img src="{$logoUrl}" alt="{$siteName}" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid rgba(108,173,223,0.3);">
<h1 style="color:#fff;margin:16px 0 4px;font-size:1.4rem;">Félicitations !</h1>
<p style="color:rgba(255,255,255,0.7);margin:0;font-size:0.9rem;">Votre inscription a été acceptée</p>
</td></tr>
<tr><td style="padding:32px 24px;">
<p style="color:#1e293b;font-size:1rem;line-height:1.6;">Bonjour <strong style="color:#001838;">{$nomContact}</strong>,</p>
<p style="color:#475569;font-size:0.95rem;line-height:1.6;">Nous avons le plaisir de vous informer que l'inscription de <strong>{$playerName}</strong> à <strong>{$siteName}</strong> a été acceptée.</p>
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin:24px 0;">
<h2 style="color:#001838;font-size:1rem;margin:0 0 16px;text-align:center;">Vos identifiants de connexion</h2>
<table style="width:100%;font-size:0.9rem;">
<tr><td style="padding:8px 0;color:#64748b;">Identifiant :</td><td style="padding:8px 0;font-weight:700;color:#001838;">{$loginId}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Mot de passe :</td><td style="padding:8px 0;font-weight:700;color:#001838;">{$password}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">ID Joueur :</td><td style="padding:8px 0;font-weight:700;color:#001838;">{$playerId}</td></tr>
</table>
</div>
<div style="text-align:center;margin:28px 0;">
<a href="{$portalUrl}" style="display:inline-block;background:#001838;color:#fff;padding:14px 36px;border-radius:10px;font-weight:700;font-size:1rem;text-decoration:none;">Accéder au Portail</a>
</div>
<p style="color:#94a3b8;font-size:0.85rem;text-align:center;margin-top:20px;">Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
</td></tr>
</table>
</body>
</html>
HTML;
}

function mailer_rejection_html(string $nomContact, string $playerName, string $motif = ''): string
{
    $s = get_settings();
    $siteName = htmlspecialchars($s['site_name'] ?? 'Kallat Bona Football Club');
    $logoUrl  = absolute_url('/' . ltrim($s['site_logo'] ?? 'logo_kala.webp', '/'));

    $motifBlock = $motif !== '' ? "<p style=\"background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;color:#991b1b;font-size:0.9rem;margin:16px 0;\"><strong>Motif :</strong> " . htmlspecialchars($motif) . "</p>" : '';

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:'Segoe UI',Arial,sans-serif;">
<table role="presentation" style="width:100%;max-width:600px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
<tr><td style="background:#001838;padding:30px 24px;text-align:center;">
<img src="{$logoUrl}" alt="{$siteName}" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid rgba(108,173,223,0.3);">
<h1 style="color:#fff;margin:16px 0 4px;font-size:1.4rem;">Inscription non retenue</h1>
<p style="color:rgba(255,255,255,0.7);margin:0;font-size:0.9rem;">Nous sommes désolés</p>
</td></tr>
<tr><td style="padding:32px 24px;">
<p style="color:#1e293b;font-size:1rem;line-height:1.6;">Bonjour <strong style="color:#001838;">{$nomContact}</strong>,</p>
<p style="color:#475569;font-size:0.95rem;line-height:1.6;">Nous vous remercions d'avoir inscrit <strong>{$playerName}</strong> à <strong>{$siteName}</strong>.</p>
<p style="color:#475569;font-size:0.95rem;line-height:1.6;">Après étude de votre dossier, nous ne sommes malheureusement pas en mesure de donner suite à votre candidature pour le moment.</p>
{$motifBlock}
<p style="color:#475569;font-size:0.95rem;line-height:1.6;">Nous vous encourageons à renouveler votre candidature lors d'une prochaine session. L'équipe reste à votre disposition pour toute information complémentaire.</p>
<p style="color:#1e293b;font-size:1rem;line-height:1.6;margin-top:24px;">Cordialement,<br><strong>{$siteName}</strong></p>
<p style="color:#94a3b8;font-size:0.85rem;text-align:center;margin-top:20px;">Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
</td></tr>
</table>
</body>
</html>
HTML;
}
