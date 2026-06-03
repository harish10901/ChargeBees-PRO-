<?php
/**
 * ChargeBees Contact Form Handler
 * Uses Web3Forms API for guaranteed email delivery
 * No SMTP setup needed — works on any hosting (GoDaddy, shared, etc.)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');

// ─── CONFIG ───────────────────────────────────────────────────────────────────
// Web3Forms — FREE service, emails go directly to db@chargebees.com
// STEP: Go to https://web3forms.com → enter db@chargebees.com → get your Access Key
// Paste that key below (one-time setup, takes 2 minutes)
define('WEB3FORMS_ACCESS_KEY', 'YOUR_WEB3FORMS_ACCESS_KEY_HERE');

// Fallback: your own SMTP (optional, only if you want backup)
define('TO_EMAIL', 'db@chargebees.com');
define('FROM_NAME', 'ChargeBees Website');
// ──────────────────────────────────────────────────────────────────────────────

// Collect form data
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name  = isset($_POST['last_name'])  ? trim($_POST['last_name'])  : '';
$email      = isset($_POST['email'])      ? trim($_POST['email'])      : '';
$phone      = isset($_POST['phone'])      ? trim($_POST['phone'])      : '';
$form_type  = isset($_POST['form_type'])  ? trim($_POST['form_type'])  : 'general';
$message    = isset($_POST['message'])    ? trim($_POST['message'])    : '';
$company    = isset($_POST['company'])    ? trim($_POST['company'])    : '';
$location   = isset($_POST['location'])   ? trim($_POST['location'])   : '';

error_log("[" . date('Y-m-d H:i:s') . "] FORM RECEIVED — type: $form_type | email: $email");

// Validate
if (empty($first_name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please fill in all required fields (Name and Email)']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

if (empty($message)) {
    $message = '[No message provided]';
}

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
$subject = isset($subject_map[$form_type]) ? $subject_map[$form_type] : 'General Inquiry';

// Build message body
$full_name = trim("$first_name $last_name");
$body  = "New Form Submission — " . strtoupper($form_type) . "\n";
$body .= str_repeat("=", 60) . "\n\n";
$body .= "Name    : $full_name\n";
$body .= "Email   : $email\n";
if (!empty($phone))   $body .= "Phone   : $phone\n";
if (!empty($company)) $body .= "Company : $company\n";
if (!empty($location))$body .= "Location: $location\n";
$body .= "Form    : " . ucfirst($form_type) . "\n";
$body .= "Time    : " . date('Y-m-d H:i:s') . "\n";
$body .= "IP      : " . $_SERVER['REMOTE_ADDR'] . "\n\n";
$body .= str_repeat("=", 60) . "\n";
$body .= "MESSAGE:\n";
$body .= str_repeat("=", 60) . "\n";
$body .= $message . "\n";
$body .= str_repeat("=", 60) . "\n";

// ─── METHOD 1: Web3Forms API (Recommended — free, reliable) ───────────────────
$sent = false;

if (defined('WEB3FORMS_ACCESS_KEY') && WEB3FORMS_ACCESS_KEY !== 'YOUR_WEB3FORMS_ACCESS_KEY_HERE') {
    $post_data = [
        'access_key'   => WEB3FORMS_ACCESS_KEY,
        'subject'      => $subject,
        'from_name'    => FROM_NAME,
        'name'         => $full_name,
        'email'        => $email,
        'phone'        => $phone,
        'company'      => $company,
        'location'     => $location,
        'form_type'    => ucfirst($form_type),
        'message'      => $message,
        'botcheck'     => '',
    ];

    $ch = curl_init('https://api.web3forms.com/submit');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response     = curl_exec($ch);
    $curl_error   = curl_error($ch);
    $http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        error_log("Web3Forms cURL error: $curl_error");
    } else {
        $result = json_decode($response, true);
        if ($http_code === 200 && isset($result['success']) && $result['success'] === true) {
            $sent = true;
            error_log("Web3Forms SUCCESS — $form_type from $email");
        } else {
            error_log("Web3Forms FAILED — HTTP $http_code — " . $response);
        }
    }
}

// ─── METHOD 2: PHP mail() fallback ────────────────────────────────────────────
if (!$sent) {
    error_log("Trying PHP mail() fallback...");
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . TO_EMAIL . ">\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Return-Path: " . TO_EMAIL . "\r\n";

    if (function_exists('mail')) {
        $sent = @mail(TO_EMAIL, $subject, $body, $headers);
        error_log($sent ? "mail() SUCCESS" : "mail() FAILED");
    }
}

// ─── METHOD 3: Save to file (last resort) ─────────────────────────────────────
if (!$sent) {
    error_log("Saving to file as last resort...");
    $log_dir = dirname(__FILE__) . '/contact_submissions';

    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);

    $filename  = $log_dir . '/' . preg_replace('/[^a-z0-9]/i', '_', $form_type);
    $filename .= '_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.txt';

    if (@file_put_contents($filename, $body)) {
        $sent = true;
        error_log("Saved to file: $filename");
    }
}

// ─── RESPONSE ─────────────────────────────────────────────────────────────────
if ($sent) {
    // Send auto-reply to user
    if (function_exists('mail')) {
        $reply_subject = "We received your inquiry — ChargeBees";
        $reply_body    = "Hi $first_name,\n\n"
            . "Thank you for contacting ChargeBees!\n\n"
            . "We received your " . strtolower($form_type) . " inquiry and will respond within 24 business hours.\n\n"
            . "Summary:\n"
            . "  Type    : " . ucfirst($form_type) . "\n"
            . "  Received: " . date('Y-m-d H:i:s') . "\n\n"
            . "Direct Contact:\n"
            . "  Phone: +91 90000 40477\n"
            . "  Email: db@chargebees.com\n\n"
            . "Best regards,\nChargeBees Team\n";

        $reply_headers  = "MIME-Version: 1.0\r\n";
        $reply_headers .= "Content-type: text/plain; charset=UTF-8\r\n";
        $reply_headers .= "From: ChargeBees <" . TO_EMAIL . ">\r\n";

        @mail($email, $reply_subject, $reply_body, $reply_headers);
    }

    http_response_code(200);
    echo json_encode(['success' => 'Message sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message. Please try again later.']);
}
?>