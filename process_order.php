<?php
ob_start();
require 'config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Clear any previous output and set JSON header
ob_clean();
header('Content-Type: application/json');

function send_error($msg) { 
    echo json_encode(['status' => 'error', 'message' => $msg]); 
    exit(); 
}

try {
    if (!isset($_SESSION['user_id'])) { 
        send_error('Login required'); 
    }
    
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    // Fetch site settings for Razorpay Keys and SMTP
    $settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
    if (!$settings) { throw new Exception("Site settings not found"); }

    $key_id = $settings['razorpay_key_id']; 
    $key_secret = $settings['razorpay_key_secret'];
    $primary_color = $settings['primary_color'] ?? '#2563eb';

    // ---------------------------------------------------------
    // ACTION: DELETE ADDRESS
    // ---------------------------------------------------------
    if ($action == 'delete_address') {
        $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['address_id'], $user_id]);
        echo json_encode(['status' => 'success']);
        exit();
    }

    // ---------------------------------------------------------
    // ACTION: INITIATE ORDER (Create Razorpay Order)
    // ---------------------------------------------------------
    if ($action == 'initiate_order') {
        $pdo->beginTransaction();

        $fullname = trim($_POST['fullname']); 
        $phone = trim($_POST['phone']); 
        $address = trim($_POST['address']);
        $city = trim($_POST['city']); 
        $state = trim($_POST['state']); 
        $pincode = trim($_POST['pincode']);

        // Save address if it's new
        if (empty($_POST['is_existing_address'])) {
            $dup = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id=? AND address_line=? AND pincode=?");
            $dup->execute([$user_id, $address, $pincode]);
            if (!$dup->fetch()) {
                $pdo->prepare("INSERT INTO user_addresses (user_id, fullname, phone, address_line, city, state, pincode) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$user_id, $fullname, $phone, $address, $city, $state, $pincode]);
            }
        }

        // Fetch Cart Items
        $stmt = $pdo->prepare("SELECT c.quantity, c.product_id, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll();
        
        if (empty($items)) { throw new Exception("Cart is empty"); }

        $total = 0;
        foreach($items as $item) { 
            $total += ($item['price'] * $item['quantity']); 
        }
        
        $grand_total = $total; // Free Shipping
        $amount_paise = $grand_total * 100;

        // Create Order in Razorpay via cURL
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount' => $amount_paise, 
            'currency' => 'INR', 
            'payment_capture' => 1
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $res = curl_exec($ch);
        $razor_order = json_decode($res);
        curl_close($ch);

        if (isset($razor_order->id)) {
            $full_addr = "$fullname, $address, $city, $state - $pincode";
            
            // Insert into local Orders table
            $ins = $pdo->prepare("INSERT INTO orders (user_id, total_amount, razorpay_order_id, shipping_address, phone, payment_status, order_status) VALUES (?,?,?,?,?,?,?)");
            $ins->execute([$user_id, $grand_total, $razor_order->id, $full_addr, $phone, 'pending', 'pending']);
            $db_order_id = $pdo->lastInsertId();

            // Insert Items into order_items
            $ins_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
            foreach($items as $i) { 
                $ins_item->execute([$db_order_id, $i['product_id'], $i['quantity'], $i['price']]); 
            }

            $pdo->commit();
            echo json_encode([
                'status' => 'success', 
                'razor_order_id' => $razor_order->id, 
                'db_order_id' => $db_order_id, 
                'amount' => $amount_paise, 
                'key' => $key_id
            ]);
        } else { 
            throw new Exception("Gateway Error: Unable to initiate payment."); 
        }
    }

    // ---------------------------------------------------------
    // ACTION: VERIFY PAYMENT & SEND EMAIL
    // ---------------------------------------------------------
    if ($action == 'verify_payment') {
        $db_order_id = $_POST['db_order_id'];
        $razor_payment_id = $_POST['razorpay_payment_id'];

        // 1. Update Database Status
        $pdo->prepare("UPDATE orders SET payment_status = 'paid', razorpay_payment_id = ?, order_status = 'placed' WHERE id = ?")
            ->execute([$razor_payment_id, $db_order_id]);

        // 2. Clear User Cart
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);

        // 3. Prepare Data for Email
        $order_stmt = $pdo->prepare("SELECT o.*, u.email, u.fullname FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $order_stmt->execute([$db_order_id]);
        $order_data = $order_stmt->fetch();

        $items_stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $items_stmt->execute([$db_order_id]);
        $order_items = $items_stmt->fetchAll();

        // 4. Send Grand Confirmation Email
        require 'includes/manual_autoload.php';
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $settings['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $settings['smtp_user'];
            $mail->Password   = $settings['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $settings['smtp_port'];

            $mail->setFrom($settings['smtp_user'], 'Shopdrive Official');
            $mail->addAddress($order_data['email'], $order_data['fullname']);

            $mail->isHTML(true);
            $mail->Subject = "Payment Received - Order #ORD-$db_order_id";

            // Build Item Table
            $items_rows = "";
            foreach($order_items as $item) {
                $subtotal = number_format($item['price'] * $item['quantity'], 2);
                $items_rows .= "
                <tr>
                    <td style='padding: 12px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                    <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right;'>₹$subtotal</td>
                </tr>";
            }

            $mail->Body = "
            <div style='background-color: #f4f4f4; padding: 30px; font-family: sans-serif;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background: #ffffff; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                    <tr>
                        <td style='background: $primary_color; padding: 30px; text-align: center; color: #fff;'>
                            <h1 style='margin: 0; font-size: 28px;'>Payment Successful</h1>
                            <p style='margin: 5px 0 0;'>Order #ORD-$db_order_id has been placed</p>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 30px;'>
                            <p>Hi <strong>{$order_data['fullname']}</strong>,</p>
                            <p>Great news! We have received your payment. Your order is now being prepared for shipment.</p>
                            
                            <table width='100%' style='margin: 25px 0; border-collapse: collapse;'>
                                <thead style='background: #f8f9fa;'>
                                    <tr>
                                        <th style='padding: 12px; text-align: left;'>Product</th>
                                        <th style='padding: 12px; text-align: center;'>Qty</th>
                                        <th style='padding: 12px; text-align: right;'>Total</th>
                                    </tr>
                                </thead>
                                <tbody>$items_rows</tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan='2' style='padding: 15px 12px; text-align: right; font-weight: bold;'>Amount Paid:</td>
                                        <td style='padding: 15px 12px; text-align: right; font-weight: bold; color: $primary_color; font-size: 18px;'>₹".number_format($order_data['total_amount'], 2)."</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div style='background: #fdfdfd; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
                                <h4 style='margin: 0 0 10px 0;'>Delivery Details:</h4>
                                <p style='margin: 0; font-size: 14px; color: #555; line-height: 1.5;'>{$order_data['shipping_address']}</p>
                            </div>

                            <p style='margin-top: 30px; font-size: 12px; color: #999; text-align: center;'>
                                Transaction ID: $razor_payment_id<br>
                                Thank you for choosing Shopdrive!
                            </p>
                        </td>
                    </tr>
                </table>
            </div>";

            $mail->send();
        } catch (Exception $e) {
            // Log mail errors if necessary
        }

        echo json_encode(['status' => 'success']);
        exit();
    }

} catch (Exception $e) { 
    if ($pdo->inTransaction()) { $pdo->rollBack(); } 
    send_error($e->getMessage()); 
}
?>