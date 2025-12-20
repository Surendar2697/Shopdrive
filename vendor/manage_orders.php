<?php
// START OUTPUT BUFFERING TO PREVENT WHITESPACE ERRORS
ob_start();
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. PROTECTION: Only Vendors allowed
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

// ---------------------------------------------------------
// AJAX HANDLER: Update fulfillment for a SPECIFIC item
// ---------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'update_item_fulfillment') {
    ob_clean(); 
    header('Content-Type: application/json');

    try {
        $item_id      = $_POST['item_id'];
        $item_status  = $_POST['item_status']; 
        $courier_name = $_POST['courier_name'] ?? '';
        $tracking_id  = $_POST['tracking_id'] ?? '';
        $tracking_link = $_POST['tracking_link'] ?? '';

        // Fetch Order ID and check Multi-vendor status
        $stmt = $pdo->prepare("SELECT order_id FROM order_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $order_id = $stmt->fetchColumn();

        $mv_check = $pdo->prepare("SELECT COUNT(DISTINCT p.vendor_id) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $mv_check->execute([$order_id]);
        $vendor_count = $mv_check->fetchColumn();

        // ---------------------------------------------------------
        // LOGIC: UPDATE ITEM TABLE ONLY
        // ---------------------------------------------------------
        // Vendors ALWAYS update the order_items table.
        $update_item = $pdo->prepare("UPDATE order_items oi
            JOIN products p ON oi.product_id = p.id
            SET oi.item_status = ?, oi.courier_name = ?, oi.tracking_id = ?, oi.tracking_link = ? 
            WHERE oi.id = ? AND p.vendor_id = ?");
        $update_item->execute([$item_status, $courier_name, $tracking_id, $tracking_link, $item_id, $vendor_id]);

        // ---------------------------------------------------------
        // LOGIC: UPDATE GLOBAL ORDER STATUS (ONLY IF SINGLE VENDOR)
        // ---------------------------------------------------------
        if ($vendor_count == 1) {
            // If there's only ONE vendor, we keep the global status in sync for convenience
            $update_global = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $update_global->execute([$item_status, $order_id]);
        }
        // If $vendor_count > 1, the code skips the global update. 
        // Admin must manually move global status to 'Delivered' when all vendors finish.

        echo json_encode(['status' => 'success', 'is_multi' => ($vendor_count > 1)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit(); 
}

require_once '../includes/header.php';

// Fetch orders that contain items belonging to this vendor
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
        background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 25px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-left: 5px solid #ccc;
    }
    .status-ordered { border-left-color: #ffc107; }
    .status-shipped { border-left-color: var(--primary-theme); }
    .status-delivered { border-left-color: #28a745; }

    .card-label { font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; margin-bottom: 5px; display: block; letter-spacing: 0.5px; }
    .item-fulfillment-row { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-top: 10px; }
    .btn-update-item { background: var(--primary-theme); color: #fff; border: none; padding: 8px 20px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    
    .mv-badge { font-size: 9px; background: #fff5f5; color: #e53e3e; padding: 4px 10px; border: 1px solid #feb2b2; border-radius: 4px; font-weight: 800; }
    .sv-badge { font-size: 9px; background: #f0fff4; color: #2f855a; padding: 4px 10px; border: 1px solid #c6f6d5; border-radius: 4px; font-weight: 800; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10 px-4">
            <h5 class="fw-bold mb-4 text-uppercase">Vendor Dispatch Center</h5>

            <div id="orderList">
                <?php foreach($orders as $o): 
                    $mv_check = $pdo->prepare("SELECT COUNT(DISTINCT p.vendor_id) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                    $mv_check->execute([$o['id']]);
                    $v_count = $mv_check->fetchColumn();
                    $is_multi = ($v_count > 1);
                ?>
                <div class="order-card p-0 status-<?= strtolower($o['order_status']) ?>">
                    <div class="bg-light px-4 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-dark">#ORD-<?= $o['id'] ?></span>
                            <span class="ms-3 badge bg-white text-dark border fw-normal small"><?= date('M d, Y', strtotime($o['created_at'])) ?></span>
                        </div>
                        <div>
                            <span class="card-label d-inline me-2">Order Mode:</span>
                            <?php if($is_multi): ?>
                                <span class="mv-badge"><i class="bi bi-layers-half"></i> MULTI-VENDOR</span>
                            <?php else: ?>
                                <span class="sv-badge"><i class="bi bi-person-check"></i> SINGLE VENDOR</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="row g-4">
                            <div class="col-md-3 border-end">
                                <span class="card-label">Customer Shipping</span>
                                <div class="fw-bold mb-1 text-dark"><?= htmlspecialchars($o['fullname']) ?></div>
                                <div class="small text-muted mb-3"><?= nl2br(htmlspecialchars($o['shipping_address'])) ?></div>
                                
                                <span class="card-label">Global Status</span>
                                <span class="badge bg-dark rounded-0 text-uppercase" style="font-size: 9px;"><?= $o['order_status'] ?></span>
                                <?php if($is_multi): ?>
                                    <p class="text-danger fw-bold mt-2" style="font-size: 8px;">* Admin controls global status for multi-vendor orders.</p>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-9">
                                <span class="card-label">Itemized Fulfillment</span>
                                <?php 
                                    $items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND p.vendor_id = ?");
                                    $items->execute([$o['id'], $vendor_id]);
                                    while($it = $items->fetch()):
                                ?>
                                <div class="item-fulfillment-row mb-3 shadow-sm">
                                    <div class="row align-items-center g-3">
                                        <div class="col-md-5">
                                            <div class="fw-bold small text-truncate"><?= htmlspecialchars($it['name']) ?> (x<?= $it['quantity'] ?>)</div>
                                            <div class="text-muted" style="font-size: 11px;">Current: <span class="text-primary fw-bold"><?= strtoupper($it['item_status']) ?></span></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="card-label">Set Item Status</label>
                                            <select class="form-select form-select-sm fw-bold border-primary" id="status_<?= $it['id'] ?>">
                                                <option value="ordered" <?= $it['item_status'] == 'ordered' ? 'selected' : '' ?>>ORDERED</option>
                                                <option value="shipped" <?= $it['item_status'] == 'shipped' ? 'selected' : '' ?>>SHIPPED</option>
                                                <option value="delivered" <?= $it['item_status'] == 'delivered' ? 'selected' : '' ?>>DELIVERED</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button onclick="updateFulfillment(<?= $it['id'] ?>)" id="btn_<?= $it['id'] ?>" class="btn-update-item">Update Packaging</button>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-3 pt-2 border-top">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm" id="cour_<?= $it['id'] ?>" placeholder="Courier" value="<?= htmlspecialchars($it['courier_name'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm" id="track_<?= $it['id'] ?>" placeholder="Tracking ID" value="<?= htmlspecialchars($it['tracking_id'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control form-control-sm" id="link_<?= $it['id'] ?>" placeholder="Live Link" value="<?= htmlspecialchars($it['tracking_link'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
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
function updateFulfillment(itemId) {
    const btn = $('#btn_' + itemId);
    btn.prop('disabled', true).text('SAVING...');

    $.ajax({
        url: 'manage_orders.php',
        method: 'POST',
        data: {
            action: 'update_item_fulfillment',
            item_id: itemId,
            item_status: $('#status_' + itemId).val(),
            courier_name: $('#cour_' + itemId).val(),
            tracking_id: $('#track_' + itemId).val(),
            tracking_link: $('#link_' + itemId).val()
        },
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).text('Update Packaging');
            if(res.status === 'success') {
                if(res.is_multi) {
                    alert('Item status updated! Global status was NOT changed because this is a multi-vendor order.');
                } else {
                    alert('Item and Global status updated successfully.');
                }
                location.reload();
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>