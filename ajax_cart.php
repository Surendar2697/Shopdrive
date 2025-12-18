<?php
/**
 * Global Inventory Manager - AJAX Cart Handler
 * Logic: Checks stock, verifies authentication, and prevents duplicate entries.
 */

require 'config/db.php';

// Ensure session is active
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
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid product selected.'
    ]);
    exit();
}

try {
    // 4. VERIFY PRODUCT EXISTS & GET STOCK
    $stmt_prod = $pdo->prepare("SELECT id, stock, status FROM products WHERE id = ? AND deleted_at IS NULL");
    $stmt_prod->execute([$product_id]);
    $product = $stmt_prod->fetch();

    if (!$product || $product['status'] !== 'active') {
        echo json_encode(['status' => 'error', 'message' => 'This product is no longer available.']);
        exit();
    }

    if ($product['stock'] <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Sorry, this item is out of stock.']);
        exit();
    }

    // 5. CHECK IF ITEM IS ALREADY IN THIS USER'S CART
    $stmt_check = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt_check->execute([$user_id, $product_id]);
    $existing_item = $stmt_check->fetch();

    if ($existing_item) {
        // ITEM FOUND: Return "already_in_cart" status for frontend alert
        echo json_encode([
            'status' => 'already_in_cart',
            'message' => 'This item is already in your cart! You can update quantity in the cart page.'
        ]);
        exit();
    } else {
        // NEW ITEM: Insert as a fresh entry
        $stmt_insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt_insert->execute([$user_id, $product_id]);

        // 6. CALCULATE UPDATED CART COUNT FOR THE HEADER BADGE
        $stmt_count = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt_count->execute([$user_id]);
        $new_total = (int)$stmt_count->fetchColumn();

        echo json_encode([
            'status' => 'success',
            'message' => 'Product successfully added to your cart.',
            'cart_count' => $new_total
        ]);
        exit();
    }

} catch (PDOException $e) {
    // LOG ERROR SECURELY AND RETURN GENERIC MESSAGE
    error_log("Cart Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'A server error occurred. Please try again later.'
    ]);
}