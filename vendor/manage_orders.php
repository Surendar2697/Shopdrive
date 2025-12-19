<?php
// START OUTPUT BUFFERING TO PREVENT WHITESPACE ERRORS
ob_start();
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. PROTECTION: Only Vendors
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

// ---------------------------------------------------------
// AJAX UPDATE HANDLER
// ---------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'update_order_vendor') {
    ob_clean(); 
    header('Content-Type: application/json');

    try {
        $order_id = $_POST['order_id'];
        $order_status = $_POST['order_status']; 
        $courier_name = $_POST['courier_name'] ?? '';
        $tracking_id = $_POST['tracking_id'] ?? '';
        $tracking_link = $_POST['tracking_link'] ?? '';

        // Check for Multi-Vendor Association
        $check_stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.vendor_id) 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $check_stmt->execute([$order_id]);
        $vendor_count = $check_stmt->fetchColumn();

        // If Multi-vendor, block status changes by vendor
        if ($vendor_count > 1) {
            $current_status_stmt = $pdo->prepare("SELECT order_status FROM orders WHERE id = ?");
            $current_status_stmt->execute([$order_id]);
            $current_status = $current_status_stmt->fetchColumn();

            if ($order_status !== $current_status) {
                echo json_encode(['status' => 'error', 'message' => 'Multi-vendor order: Status can only be changed by Admin.']);
                exit();
            }
        }

        // Update tracking and status
        $update = $pdo->prepare("UPDATE orders SET 
            order_status = ?, courier_name = ?, tracking_id = ?, tracking_link = ? 
            WHERE id = ? AND id IN (
                SELECT order_id FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = ?
            )");
        
        $update->execute([$order_status, $courier_name, $tracking_id, $tracking_link, $order_id, $vendor_id]);
        
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit(); 
}

require_once '../includes/header.php';

// Fetch Vendor-Specific Orders
$stmt = $pdo->prepare("
    SELECT DISTINCT o.*, u.fullname 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE p.vendor_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$vendor_id]);
$orders = $stmt->fetchAll();

$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#00bcd4';
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; font-family: 'Segoe UI', sans-serif; }
    
    .order-card { 
        background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; margin-bottom: 25px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.04); border-left: 5px solid #ccc;
    }
    .status-ordered { border-left-color: #ffc107; }
    .status-shipped { border-left-color: var(--primary-theme); }
    .status-delivered { border-left-color: #28a745; }

    .card-label { font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; margin-bottom: 5px; display: block; letter-spacing: 0.5px; }
    .status-select { font-size: 12px; font-weight: 700; border-radius: 0; padding: 5px; }
    .btn-update-shipment { background: var(--primary-theme); color: #fff; border: none; padding: 10px; border-radius: 2px; font-size: 11px; font-weight: 700; text-transform: uppercase; width: 100%; }

    .product-box { background: #fdfdfd; border: 1px solid #f1f1f1; padding: 12px; border-radius: 4px; }
    .product-header { display: grid; grid-template-columns: 2fr 0.5fr 1fr 1fr; font-size: 10px; font-weight: 800; color: #aaa; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 5px; }
    .product-item { display: grid; grid-template-columns: 2fr 0.5fr 1fr 1fr; align-items: center; padding: 6px 0; border-bottom: 1px dashed #eee; font-size: 12px; }
    .product-item:last-child { border-bottom: none; }
    
    .vendor-total-row { border-top: 2px solid #eee; margin-top: 8px; padding-top: 8px; font-weight: 800; display: flex; justify-content: space-between; font-size: 13px; color: var(--primary-theme); }
    .lock-notice { font-size: 9px; font-weight: 800; color: #dc3545; background: #fff5f5; padding: 4px; border: 1px solid #feb2b2; display: block; margin-top: 5px; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10 px-4">
            <h5 class="fw-bold mb-4 text-uppercase">Vendor Order Management</h5>

            <div id="orderList">
                <?php foreach($orders as $o): 
                    // Check if Multi-Vendor
                    $check_mv = $pdo->prepare("SELECT COUNT(DISTINCT p.vendor_id) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                    $check_mv->execute([$o['id']]);
                    $is_multi_vendor = ($check_mv->fetchColumn() > 1);
                ?>
                <div class="order-card p-0 status-<?= strtolower($o['order_status']) ?>">
                    <div class="bg-light px-4 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">#ORD-<?= $o['id'] ?></span>
                        <span class="badge bg-white text-muted border fw-normal"><?= date('M d, Y', strtotime($o['created_at'])) ?></span>
                    </div>
                    
                    <div class="p-4">
                        <div class="row g-4">
                            <div class="col-md-3 border-end">
                                <span class="card-label">Shipping Address</span>
                                <div class="fw-bold mb-1 text-dark"><?= htmlspecialchars($o['fullname']) ?></div>
                                <div class="small text-muted mb-3"><?= nl2br(htmlspecialchars($o['shipping_address'])) ?></div>
                                <span class="card-label">Payment</span>
                                <span class="badge <?= $o['payment_status'] == 'paid' ? 'bg-success' : 'bg-warning text-dark' ?> rounded-0 text-uppercase" style="font-size: 9px;">
                                    <?= $o['payment_status'] ?>
                                </span>
                            </div>

                            <div class="col-md-5 border-end">
                                <span class="card-label">Your Items & Earnings</span>
                                <div class="product-box">
                                    <div class="product-header">
                                        <span>Product</span><span>Qty</span><span class="text-end">Rate</span><span class="text-end">Total</span>
                                    </div>
                                    <?php 
                                        $v_total = 0;
                                        $items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND p.vendor_id = ?");
                                        $items->execute([$o['id'], $vendor_id]);
                                        while($item = $items->fetch()):
                                            $sub = $item['price'] * $item['quantity'];
                                            $v_total += $sub;
                                    ?>
                                        <div class="product-item">
                                            <span class="text-truncate" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></span>
                                            <span class="ps-2">x<?= $item['quantity'] ?></span>
                                            <span class="text-end">₹<?= number_format($item['price'], 1) ?></span>
                                            <span class="text-end fw-bold">₹<?= number_format($sub, 1) ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                    <div class="vendor-total-row">
                                        <span>Subtotal:</span><span>₹<?= number_format($v_total, 2) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <span class="card-label">Order Status</span>
                                <?php if($is_multi_vendor): ?>
                                    <select class="form-select status-select mb-1" disabled style="background-color: #f8f9fa;">
                                        <option><?= strtoupper($o['order_status']) ?></option>
                                    </select>
                                    <span class="lock-notice"><i class="bi bi-lock-fill"></i> ADMIN CONTROL ONLY (MULTI-VENDOR)</span>
                                    <input type="hidden" id="status_<?= $o['id'] ?>" value="<?= $o['order_status'] ?>">
                                <?php else: ?>
                                    <select class="form-select status-select mb-3" id="status_<?= $o['id'] ?>">
                                        <option value="ordered" <?= $o['order_status'] == 'ordered' ? 'selected' : '' ?>>ORDERED</option>
                                        <option value="shipped" <?= $o['order_status'] == 'shipped' ? 'selected' : '' ?>>SHIPPED</option>
                                        <option value="delivered" <?= $o['order_status'] == 'delivered' ? 'selected' : '' ?>>DELIVERED</option>
                                    </select>
                                <?php endif; ?>

                                <span class="card-label mt-2">Logistics</span>
                                <input type="text" class="form-control form-control-sm mb-2" id="cour_<?= $o['id'] ?>" placeholder="Courier" value="<?= htmlspecialchars($o['courier_name'] ?? '') ?>">
                                <div class="row g-2">
                                    <div class="col-6"><input type="text" class="form-control form-control-sm" id="track_<?= $o['id'] ?>" placeholder="Track ID" value="<?= htmlspecialchars($o['tracking_id'] ?? '') ?>"></div>
                                    <div class="col-6"><input type="text" class="form-control form-control-sm" id="link_<?= $o['id'] ?>" placeholder="Link" value="<?= htmlspecialchars($o['tracking_link'] ?? '') ?>"></div>
                                </div>
                                <button onclick="updateVendorOrder(<?= $o['id'] ?>)" id="btn_<?= $o['id'] ?>" class="btn-update-shipment mt-3 shadow-sm">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateVendorOrder(id) {
    const btn = $('#btn_' + id);
    btn.prop('disabled', true).text('PROCESSING...');
    $.ajax({
        url: 'manage_orders.php',
        method: 'POST',
        data: {
            action: 'update_order_vendor',
            order_id: id,
            order_status: $('#status_' + id).val(),
            courier_name: $('#cour_' + id).val(),
            tracking_id: $('#track_' + id).val(),
            tracking_link: $('#link_' + id).val()
        },
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).text('SAVE CHANGES');
            if(res.status === 'success') { location.reload(); }
            else { alert(res.message); }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>