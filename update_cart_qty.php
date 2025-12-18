<?php
require 'config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_id = (int)$_POST['cart_id'];
    $action = $_POST['action']; // 'inc' or 'dec'

    // Fetch current cart item and stock
    $stmt = $pdo->prepare("SELECT c.quantity, p.stock, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    $item = $stmt->fetch();

    if ($item) {
        $new_qty = $item['quantity'];

        if ($action === 'inc' && $new_qty < $item['stock']) {
            $new_qty++;
        } elseif ($action === 'dec' && $new_qty > 1) {
            $new_qty--;
        }

        $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->execute([$new_qty, $cart_id]);

        echo json_encode([
            'status' => 'success',
            'new_qty' => $new_qty,
            'item_subtotal' => number_format($new_qty * $item['price'], 2)
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    }
}