<?php
/**
 * Manual Autoloader for PHPMailer
 * Place this file in includes/
 */

// Define the path to the PHPMailer source files
$base_path = __DIR__ . '/phpmailer/src/';

require_once $base_path . 'Exception.php';
require_once $base_path . 'PHPMailer.php';
require_once $base_path . 'SMTP.php';