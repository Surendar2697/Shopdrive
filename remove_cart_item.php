<?php
require 'config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. SECURITY CHECK: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. VALIDATION: Check if ID is provided and numeric
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $cart_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    // 3. DATABASE ACTION: Delete only if it belongs to the logged-in user
    // This prevents "ID Browsing" security vulnerabilities
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$cart_id, $user_id]);

    if ($result) {
        // Redirect back to cart with success message
        header("Location: cart.php?msg=item_removed");
    } else {
        // Redirect back with error message
        header("Location: cart.php?msg=error");
    }
} else {
    // No ID provided, go back to cart
    header("Location: cart.php");
}
exit();