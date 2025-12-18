<?php
/**
 * Global Inventory Manager - AJAX Wishlist Toggle
 * Logic: Toggles item in DB and returns the updated total count for the header badge.
 */

require 'config/db.php';

// Safe Session Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SET HEADER FOR JSON COMMUNICATION
header('Content-Type: application/json');

// 2. AUTHENTICATION GUARD
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'login_required'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

// 3. INPUT VALIDATION
if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product reference.']);
    exit();
}

try {
    // 4. TOGGLE LOGIC (Check if already in wishlist)
    $stmt_check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt_check->execute([$user_id, $product_id]);
    $existing = $stmt_check->fetch();

    if ($existing) {
        // ACTION: REMOVE
        $stmt_del = $pdo->prepare("DELETE FROM wishlist WHERE id = ?");
        $stmt_del->execute([$existing['id']]);
        $status = 'removed';
        $msg = 'Removed from wishlist';
    } else {
        // ACTION: ADD
        $stmt_ins = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt_ins->execute([$user_id, $product_id]);
        $status = 'added';
        $msg = 'Added to wishlist';
    }

    // 5. FETCH UPDATED TOTAL WISHLIST COUNT FOR HEADER BADGE
    $stmt_count = $pdo->prepare("SELECT COUNT(id) FROM wishlist WHERE user_id = ?");
    $stmt_count->execute([$user_id]);
    $new_count = (int)$stmt_count->fetchColumn();

    // 6. RETURN COMPLETE RESPONSE
    echo json_encode([
        'status' => $status,
        'message' => $msg,
        'wishlist_count' => $new_count
    ]);

} catch (PDOException $e) {
    error_log("Wishlist Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error.']);
}