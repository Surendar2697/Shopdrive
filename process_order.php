<?php
ob_start();
require 'config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ob_clean();
header('Content-Type: application/json');

function send_error($msg) { echo json_encode(['status' => 'error', 'message' => $msg]); exit(); }

try {
    if (!isset($_SESSION['user_id'])) { send_error('Login required'); }
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    $settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
    $key_id = $settings['razorpay_key_id']; 
    $key_secret = $settings['razorpay_key_secret'];

    if ($action == 'delete_address') {
        $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?")->execute([$_POST['address_id'], $user_id]);
        echo json_encode(['status' => 'success']);
        exit();
    }

    if ($action == 'initiate_order') {
        $pdo->beginTransaction();
        $fullname = trim($_POST['fullname']); $phone = trim($_POST['phone']); $address = trim($_POST['address']);
        $city = trim($_POST['city']); $state = trim($_POST['state']); $pincode = trim($_POST['pincode']);

        if (empty($_POST['is_existing_address'])) {
            $dup = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id=? AND address_line=? AND pincode=?");
            $dup->execute([$user_id, $address, $pincode]);
            if (!$dup->fetch()) {
                $pdo->prepare("INSERT INTO user_addresses (user_id, fullname, phone, address_line, city, state, pincode) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$user_id, $fullname, $phone, $address, $city, $state, $pincode]);
            }
        }

        $stmt = $pdo->prepare("SELECT c.quantity, c.product_id, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll();
        if (empty($items)) { throw new Exception("Cart empty"); }

        $total = 0;
        foreach($items as $item) { $total += ($item['price'] * $item['quantity']); }
        
        // SHIPPING REMOVED (ALWAYS FREE)
        $grand_total = $total; 
        $amount_paise = $grand_total * 100;

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['amount' => $amount_paise, 'currency' => 'INR', 'payment_capture' => 1]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $res = curl_exec($ch);
        $razor_order = json_decode($res);
        curl_close($ch);

        if (isset($razor_order->id)) {
            $full_addr = "$fullname, $address, $city, $state - $pincode";
            $ins = $pdo->prepare("INSERT INTO orders (user_id, total_amount, razorpay_order_id, shipping_address, phone, payment_status, order_status) VALUES (?,?,?,?,?,?,?)");
            $ins->execute([$user_id, $grand_total, $razor_order->id, $full_addr, $phone, 'pending', 'pending']);
            $db_order_id = $pdo->lastInsertId();

            $ins_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
            foreach($items as $i) { $ins_item->execute([$db_order_id, $i['product_id'], $i['quantity'], $i['price']]); }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'razor_order_id' => $razor_order->id, 'db_order_id' => $db_order_id, 'amount' => $amount_paise, 'key' => $key_id]);
        } else { throw new Exception("Gateway Error"); }
    }

    if ($action == 'verify_payment') {
        $pdo->prepare("UPDATE orders SET payment_status = 'paid', razorpay_payment_id = ?, order_status = 'placed' WHERE id = ?")->execute([$_POST['razorpay_payment_id'], $_POST['db_order_id']]);
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } send_error($e->getMessage()); }
?>