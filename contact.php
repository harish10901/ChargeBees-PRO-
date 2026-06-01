<?php
// Set content type for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');

// Get form data from POST request
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$form_type = isset($_POST['form_type']) ? trim($_POST['form_type']) : 'General Inquiry';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$company = isset($_POST['company']) ? trim($_POST['company']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';

// Validate required fields
if (empty($first_name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please fill in all required fields']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

// Recipient email
$to = 'db@chargebees.com';
$from_email = 'db@chargebees.com';
$from_name = 'ChargeBees';

// Determine subject based on form type
$subject_map = array(
    'contact' => 'New Contact Form - General Inquiry',
    'solar' => 'Solar Products Inquiry',
    'residential' => 'Residential Solar Inquiry',
    'commercial' => 'Commercial Solar Inquiry',
    'industrial' => 'Industrial Solar Inquiry',
    'ground' => 'Ground Mounted Solar Inquiry',
    'partner' => 'Partnership Inquiry',
    'general' => 'General Inquiry'
);

$subject = isset($subject_map[$form_type]) ? $subject_map[$form_type] : $subject_map['general'];

// Prepare email body with all available data
$email_body = "New Form Submission - " . strtoupper($form_type) . "\n";
$email_body .= str_repeat("=", 60) . "\n\n";
$email_body .= "CONTACT DETAILS:\n";
$email_body .= str_repeat("-", 60) . "\n";
$email_body .= "First Name: " . $first_name . "\n";
if (!empty($last_name)) {
    $email_body .= "Last Name: " . $last_name . "\n";
}
$email_body .= "Email: " . $email . "\n";
if (!empty($phone)) {
    $email_body .= "Phone: " . $phone . "\n";
}
if (!empty($company)) {
    $email_body .= "Company: " . $company . "\n";
}
if (!empty($location)) {
    $email_body .= "Location: " . $location . "\n";
}
$email_body .= "\nFORM TYPE: " . ucfirst($form_type) . "\n";
$email_body .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
$email_body .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
$email_body .= "\n" . str_repeat("=", 60) . "\n";
$email_body .= "MESSAGE:\n";
$email_body .= str_repeat("=", 60) . "\n";
$email_body .= $message . "\n";
$email_body .= str_repeat("=", 60) . "\n";

// Email headers
$headers = array();
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/plain; charset=UTF-8';
$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'Return-Path: ' . $from_email;
$headers[] = 'X-Priority: 3';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$headers_str = implode("\r\n", $headers);

// Send email
$mail_sent = false;

// Try to send email using mail() function
if (function_exists('mail')) {
    $mail_sent = @mail($to, $subject, $email_body, $headers_str);
    error_log("[" . date('Y-m-d H:i:s') . "] Mail sent to $to - Result: " . ($mail_sent ? "SUCCESS" : "FAILED") . " - Type: $form_type");
} else {
    error_log("mail() function not available on server");
}

// Fallback - Save to file
if (!$mail_sent) {
    $log_dir = dirname(__FILE__) . '/contact_submissions';
    
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $form_type_safe = preg_replace('/[^a-zA-Z0-9]/', '_', $form_type);
    $filename = $log_dir . '/' . $form_type_safe . '_' . $timestamp . '_' . uniqid() . '.txt';
    
    $file_content = "FORM SUBMISSION LOG\n";
    $file_content .= "==================\n";
    $file_content .= "Form Type: " . strtoupper($form_type) . "\n";
    $file_content .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $file_content .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $file_content .= "\n" . str_repeat("-", 50) . "\n";
    $file_content .= $email_body;
    
    $file_saved = @file_put_contents($filename, $file_content);
    
    if ($file_saved) {
        $mail_sent = true;
        error_log("Message saved to file: " . $filename);
    }
}

if ($mail_sent) {
    // Send confirmation email to user
    $user_subject = "We received your inquiry - ChargeBees";
    $user_body = "Hi " . $first_name . ",\n\n";
    $user_body .= "Thank you for reaching out to ChargeBees!\n\n";
    $user_body .= "We have received your " . strtolower($form_type) . " inquiry and will get back to you within 24 business hours.\n\n";
    $user_body .= "Your Inquiry Details:\n";
    $user_body .= "- Type: " . ucfirst($form_type) . "\n";
    $user_body .= "- Received: " . date('Y-m-d H:i:s') . "\n\n";
    $user_body .= "Contact Us Directly:\n";
    $user_body .= "Phone: +91 90000 40477\n";
    $user_body .= "Email: db@chargebees.com\n\n";
    $user_body .= "Best regards,\n";
    $user_body .= "ChargeBees Team\n";
    
    $user_headers = array();
    $user_headers[] = 'MIME-Version: 1.0';
    $user_headers[] = 'Content-type: text/plain; charset=UTF-8';
    $user_headers[] = 'From: ChargeBees <' . $from_email . '>';
    $user_headers[] = 'Return-Path: ' . $from_email;
    $user_headers_str = implode("\r\n", $user_headers);
    
    if (function_exists('mail')) {
        @mail($email, $user_subject, $user_body, $user_headers_str);
    }
    
    http_response_code(200);
    echo json_encode(['success' => 'Message sent successfully']);
} else {
    error_log("Form submission failed - Type: $form_type - User: $email");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message. Please try again later.']);
}
?>


