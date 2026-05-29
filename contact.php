<?php
// Set content type for JSON response
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get form data from POST request
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$solution = isset($_POST['solution']) ? trim($_POST['solution']) : 'General Inquiry';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email) || empty($message)) {
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

// Email subject
$subject = "New Contact Form Submission - " . htmlspecialchars($solution);

// Prepare email body
$email_body = "New Contact Form Submission\n";
$email_body .= "=================================\n\n";
$email_body .= "First Name: " . htmlspecialchars($first_name) . "\n";
$email_body .= "Last Name: " . htmlspecialchars($last_name) . "\n";
$email_body .= "Email: " . htmlspecialchars($email) . "\n";

if (!empty($phone)) {
    $email_body .= "Phone: " . htmlspecialchars($phone) . "\n";
}

$email_body .= "Subject: " . htmlspecialchars($solution) . "\n";
$email_body .= "\nMessage:\n";
$email_body .= "----------\n";
$email_body .= htmlspecialchars($message) . "\n";
$email_body .= "----------\n\n";
$email_body .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
$email_body .= "Visitor IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

// Email headers
$headers = "From: " . htmlspecialchars($email) . "\r\n";
$headers .= "Reply-To: " . htmlspecialchars($email) . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email to db@chargebees.com
$mail_sent = mail($to, $subject, $email_body, $headers);

if ($mail_sent) {
    // Optional: Send confirmation email to user
    $user_subject = "We received your message - ChargeBees";
    $user_body = "Hi " . htmlspecialchars($first_name) . ",\n\n";
    $user_body .= "Thank you for reaching out to ChargeBees!\n\n";
    $user_body .= "We have received your message and will get back to you within 24 business hours.\n\n";
    $user_body .= "Your Message Details:\n";
    $user_body .= "- Subject: " . htmlspecialchars($solution) . "\n";
    $user_body .= "- Received on: " . date('Y-m-d H:i:s') . "\n\n";
    $user_body .= "In the meantime, if you have any urgent queries, feel free to contact us:\n";
    $user_body .= "Phone: +91 90000 40477\n";
    $user_body .= "Email: db@chargebees.com\n\n";
    $user_body .= "Best regards,\n";
    $user_body .= "ChargeBees Team\n";
    
    $user_headers = "From: db@chargebees.com\r\n";
    $user_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    mail($email, $user_subject, $user_body, $user_headers);
    
    http_response_code(200);
    echo json_encode(['success' => 'Message sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message. Please try again later.']);
}
?>
