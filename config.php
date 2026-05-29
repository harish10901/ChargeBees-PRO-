<?php
/**
 * Email Configuration for ChargeBees Contact Form
 * For GoDaddy/Titan Email Hosting
 */

// SMTP Configuration (Optional - use if mail() function doesn't work)
// Contact GoDaddy support for your specific SMTP settings
define('USE_SMTP', false); // Set to true if you need to use SMTP instead of mail()

// SMTP Settings for Titan/GoDaddy
define('SMTP_HOST', 'smtp.titan.email'); // Or your GoDaddy SMTP server
define('SMTP_PORT', 465); // 465 for SSL, 587 for TLS
define('SMTP_USER', 'db@chargebees.com'); // Your email address
define('SMTP_PASSWORD', 'your-password-here'); // Your email password
define('SMTP_SECURE', 'ssl'); // 'ssl' or 'tls'

// Email Settings
define('FROM_EMAIL', 'db@chargebees.com');
define('FROM_NAME', 'ChargeBees');
define('REPLY_TO_EMAIL', 'db@chargebees.com');

/**
 * Note: If you want to enable SMTP:
 * 1. Set USE_SMTP to true
 * 2. Add your email credentials above
 * 3. Make sure PHPMailer library is available in the same directory
 * 
 * To get SMTP credentials:
 * - Log in to your GoDaddy cPanel
 * - Go to Email > Email Accounts
 * - Click on your email account
 * - Find Mail Server settings
 */
?>
