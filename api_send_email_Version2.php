<?php
// api/send_email.php
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

$config = require __DIR__ . '/config.php';
$mailcfg = $config['mail'] ?? [];

$input = getJsonInput();
$to = $input['to'] ?? null;
$subject = $input['subject'] ?? null;
$body = $input['body'] ?? null;

if (!$to || !$subject || !$body) jsonResponse(['success'=>false,'message'=>'to, subject, body required']);

try {
    if (!empty($mailcfg['enabled']) && !empty($mailcfg['smtp'])) {
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            jsonResponse(['success'=>false,'message'=>'PHPMailer not installed. Run composer install.']);
        }
        require __DIR__ . '/../vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $mailcfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailcfg['username'];
        $mail->Password = $mailcfg['password'];
        $mail->SMTPSecure = $mailcfg['secure'] ?? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mailcfg['port'] ?? 587;
        $mail->setFrom($mailcfg['from_email'] ?? 'noreply@example.com', $mailcfg['from_name'] ?? 'Notification');

        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        jsonResponse(['success'=>true,'message'=>'Email 已寄出']);
    } else {
        $headers = "From: " . ($mailcfg['from_email'] ?? 'noreply@example.com') . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $ok = mail($to, $subject, $body, $headers);
        if ($ok) jsonResponse(['success'=>true,'message'=>'Email 已寄出 (mail)']);
        else jsonResponse(['success'=>false,'message'=>'mail() 傳送失敗']);
    }
} catch (Exception $e) {
    jsonResponse(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}