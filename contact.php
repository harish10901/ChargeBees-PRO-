<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');

// ─── SMTP CONFIG ──────────────────────────────────────────────────────────────
$smtp_user     = 'chaargebee@gmail.com';
$smtp_password = '$Vzdas070'; // replace with actual password or use environment variable for security
$to_email      = 'chaargebee@gmail.com';
$from_name     = 'ChargeBees Website';

// Gmail SMTP
$smtp_configs = [
    ['host' => 'smtp.gmail.com', 'port' => 465, 'secure' => 'ssl'],
    ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => 'tls'],
];
// ──────────────────────────────────────────────────────────────────────────────

// Collect form data
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name  = isset($_POST['last_name'])  ? trim($_POST['last_name'])  : '';
$email      = isset($_POST['email'])      ? trim($_POST['email'])      : '';
$phone      = isset($_POST['phone'])      ? trim($_POST['phone'])      : '';
$form_type  = isset($_POST['form_type'])  ? trim($_POST['form_type'])  : 'general';
$message    = isset($_POST['message'])    ? trim($_POST['message'])    : '';
$company    = isset($_POST['company'])    ? trim($_POST['company'])    : '';
$location   = isset($_POST['location'])  ? trim($_POST['location'])   : '';

error_log("[" . date('Y-m-d H:i:s') . "] FORM RECEIVED — type: $form_type | email: $email");

// Validate
if (empty($first_name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please fill in all required fields']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}
if (empty($message)) $message = '[No message provided]';

// Subject map
$subject_map = [
    'contact'     => 'New Contact Form — General Inquiry',
    'solar'       => 'Solar Products Inquiry',
    'residential' => 'Residential Solar Inquiry',
    'commercial'  => 'Commercial Solar Inquiry',
    'industrial'  => 'Industrial Solar Inquiry',
    'ground'      => 'Ground Mounted Solar Inquiry',
    'partner'     => 'Partnership Inquiry',
    'general'     => 'General Inquiry',
];
$subject   = isset($subject_map[$form_type]) ? $subject_map[$form_type] : 'General Inquiry';
$full_name = trim("$first_name $last_name");

// Email body
$body  = "New Form Submission — " . strtoupper($form_type) . "\n";
$body .= str_repeat("=", 60) . "\n\n";
$body .= "Name    : $full_name\n";
$body .= "Email   : $email\n";
if (!empty($phone))    $body .= "Phone   : $phone\n";
if (!empty($company))  $body .= "Company : $company\n";
if (!empty($location)) $body .= "Location: $location\n";
$body .= "Form    : " . ucfirst($form_type) . "\n";
$body .= "Time    : " . date('Y-m-d H:i:s') . "\n";
$body .= "IP      : " . $_SERVER['REMOTE_ADDR'] . "\n\n";
$body .= str_repeat("=", 60) . "\n";
$body .= "MESSAGE:\n" . str_repeat("=", 60) . "\n";
$body .= $message . "\n";
$body .= str_repeat("=", 60) . "\n";

// ─── PHPMailer ────────────────────────────────────────────────────────────────
$sent      = false;
$mail_obj  = null;
$phpmailer_src = dirname(__FILE__) . '/PHPMailer/src/';

if (!file_exists($phpmailer_src . 'PHPMailer.php')) {
    error_log("CRITICAL: PHPMailer not found at $phpmailer_src");
} else {
    require_once $phpmailer_src . 'PHPMailer.php';
    require_once $phpmailer_src . 'SMTP.php';
    require_once $phpmailer_src . 'Exception.php';

    foreach ($smtp_configs as $cfg) {
        if ($sent) break;
        error_log("Trying SMTP: {$cfg['host']}:{$cfg['port']} ({$cfg['secure']})");
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = ($cfg['secure'] === 'ssl')
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $cfg['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 10;

            $mail->setFrom($smtp_user, $from_name);
            $mail->addAddress($to_email);
            $mail->addReplyTo($email, $full_name);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            $sent     = true;
            $mail_obj = $mail;
            error_log("PHPMailer SUCCESS via {$cfg['host']}:{$cfg['port']}");
        } catch (Exception $e) {
            error_log("PHPMailer FAILED {$cfg['host']}:{$cfg['port']} — " . $mail->ErrorInfo);
        }
    }
}

// Fallback: mail()
if (!$sent && function_exists('mail')) {
    error_log("Trying PHP mail() fallback...");
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: $from_name <$smtp_user>\r\n";
    $headers .= "Reply-To: $email\r\n";
    $sent = @mail($to_email, $subject, $body, $headers);
    error_log($sent ? "mail() SUCCESS" : "mail() FAILED");
}

// Last resort: save to file
if (!$sent) {
    $log_dir  = dirname(__FILE__) . '/contact_submissions';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    $filename = $log_dir . '/' . preg_replace('/[^a-z0-9]/i','_',$form_type)
              . '_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.txt';
    if (@file_put_contents($filename, $body)) {
        $sent = true;
        error_log("Saved to file: $filename");
    }
}

// ─── RESPONSE ─────────────────────────────────────────────────────────────────
if ($sent) {
    // Auto-reply to user
    if ($mail_obj !== null) {
        try {
            $mail_obj->clearAddresses();
            $mail_obj->clearReplyTos();
            $mail_obj->addAddress($email, $full_name);
            $mail_obj->Subject = 'We received your inquiry — ChargeBees';
            $mail_obj->Body    = "Hi $first_name,\n\nThank you for contacting ChargeBees!\n\n"
                . "We received your " . strtolower($form_type) . " inquiry and will respond within 24 business hours.\n\n"
                . "Direct Contact:\n  Phone: +91 90000 40477\n  Email: db@chargebees.com\n\n"
                . "Best regards,\nChargeBees Team\n";
            $mail_obj->send();
            error_log("Auto-reply sent to $email");
        } catch (Exception $e) {
            error_log("Auto-reply failed: " . $mail_obj->ErrorInfo);
        }
    }
    http_response_code(200);
    echo json_encode(['success' => 'Message sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message. Please try again later.']);
}
?>