<?php
/**
 * ChargeBees — Email Configuration
 *
 * HOW TO GET YOUR WEB3FORMS KEY (FREE, 5 minutes):
 * ─────────────────────────────────────────────────
 * 1. Go to https://web3forms.com
 * 2. Enter:  db@chargebees.com
 * 3. Check your inbox → click the confirmation link
 * 4. Copy the Access Key you receive
 * 5. Paste it in contact.php at:
 *       define('WEB3FORMS_ACCESS_KEY', 'PASTE_HERE');
 *
 * That's it! Forms will start delivering to db@chargebees.com instantly.
 * Free plan = 250 submissions/month. No credit card needed.
 * ─────────────────────────────────────────────────
 */

// Destination email — all form submissions go here
define('TO_EMAIL', 'db@chargebees.com');
define('FROM_NAME', 'ChargeBees Website');

// Web3Forms key (set in contact.php directly)
// Get it free at: https://web3forms.com

/**
 * OPTIONAL SMTP (only if you want direct SMTP instead of Web3Forms)
 * ─────────────────────────────────────────────────
 * If your GoDaddy / Titan email supports SMTP:
 *
 * Host   : smtp.titan.email  (or smtp.secureserver.net for GoDaddy)
 * Port   : 465 (SSL) or 587 (TLS)
 * User   : db@chargebees.com
 * Pass   : your email password
 *
 * To enable: install PHPMailer in same folder, then update contact.php
 */
define('SMTP_HOST',     'smtp.titan.email');
define('SMTP_PORT',     465);
define('SMTP_USER',     'db@chargebees.com');
define('SMTP_PASSWORD', 'your-password-here'); // Update if using SMTP
define('SMTP_SECURE',   'ssl');
?>