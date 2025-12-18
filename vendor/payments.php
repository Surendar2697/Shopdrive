<?php 
require '../config/db.php';

// PROFESSIONAL SESSION CHECK
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
require '../includes/header.php'; 
?>