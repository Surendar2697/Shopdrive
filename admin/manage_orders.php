<?php
// START OUTPUT BUFFERING TO PREVENT WHITESPACE ERRORS
ob_start();
require '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// PROTECTION
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// ---------------------------------------------------------
// AJAX UPDATE HANDLER: UPDATED FOR PER-ITEM TRACKING
// ---------------------------------------------------------
if (isset($_POST['action'])) {
    ob_clean(); 
    header('Content-Type: application/json');
    try {
        if ($_POST['action'] == 'update_global_status') {
            // Update Global Order Status & Payment
            $update = $pdo->prepare("UPDATE orders SET payment_status = ?, order_status = ? WHERE id = ?");
            $update->execute([$_POST['payment_status'], $_POST['order_status'], $_POST['order_id']]);
        } 
        elseif ($_POST['action'] == 'update_item_tracking') {
            // Update Tracking for a specific item
            $update = $pdo->prepare("UPDATE order_items SET courier_name = ?, tracking_id = ?, tracking_link = ?, item_status = ? WHERE id = ?");
            $update->execute([
                $_POST['courier_name'], 
                $_POST['tracking_id'], 
                $_POST['tracking_link'], 
                $_POST['item_status'], 
                $_POST['item_id']
            ]);
        }
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit(); 
}

require '../includes/header.php';

// Fetch Orders
$orders = $pdo->query("SELECT o.*, u.fullname FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
$theme_color = $brand['primary_color'] ?? '#2563eb';
?>

<style>
    :root { --admin-theme: <?= $theme_color ?>; }
    body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
    .search-box { border-radius: 50px; padding: 10px 25px; border: 1px solid #e0e0e0; }
    
    .order-card { background: #fff; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 6px solid #ccc; }
    .status-ordered { border-left-color: var(--admin-theme); }
    .status-shipped { border-left-color: #ffc107; }
    .status-delivered { border-left-color: #28a745; }

    .card-label { font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; margin-bottom: 8px; display: block; letter-spacing: 0.5px; }
    
    /* Itemized Tracking Table */
    .tracking-area { background: #fff; border: 1px solid #edf2f7; border-radius: 8px; padding: 15px; margin-top: 15px; }
    .item-tracking-row { border-bottom: 1px solid #f1f1f1; padding: 15px 0; }
    .item-tracking-row:last-child { border-bottom: none; }
    
    .btn-save-sm { font-size: 10px; font-weight: 700; padding: 5px 15px; border-radius: 4px; background: var(--admin-theme); color: white; border: none; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2"><?php include '../includes/admin_sidebar.php'; ?></div>

        <div class="col-md-10 px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Master Order Logs</h2>
                <input type="text" id="orderSearch" class="form-control w-25 search-box" placeholder="Search Customer or ID...">
            </div>

            <div id="orderList">
                <?php foreach($orders as $o): ?>
                <div class="order-card p-0 status-<?= strtolower($o['order_status']) ?> searchable-item" data-id="<?= $o['id'] ?>" data-customer="<?= strtolower($o['fullname']) ?>">
                    <div class="bg-light px-4 py-2 border-bottom d-flex justify-content-between align-items-center rounded-top">
                        <span class="fw-bold">#ORD-<?= $o['id'] ?></span>
                        <div>
                            <span class="small text-muted me-3"><?= date('d M Y', strtotime($o['created_at'])) ?></span>
                            <span class="badge bg-white text-dark border px-3">Total: ₹<?= number_format($o['total_amount'], 2) ?></span>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="row g-4 mb-4">
                            <div class="col-md-4 border-end">
                                <span class="card-label">Customer & Shipping</span>
                                <div class="fw-bold mb-1" style="color: var(--admin-theme);"><?= htmlspecialchars($o['fullname']) ?></div>
                                <div class="small text-muted lh-sm"><?= nl2br(htmlspecialchars($o['shipping_address'])) ?></div>
                            </div>

                            <div class="col-md-4 border-end">
                                <span class="card-label">Global Status (System Wide)</span>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <select class="form-select form-select-sm" id="pay_<?= $o['id'] ?>">
                                            <option value="pending" <?= $o['payment_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="paid" <?= $o['payment_status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select class="form-select form-select-sm fw-bold" id="ord_<?= $o['id'] ?>">
                                            <option value="ordered" <?= $o['order_status'] == 'ordered' ? 'selected' : '' ?>>Ordered</option>
                                            <option value="shipped" <?= $o['order_status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="delivered" <?= $o['order_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <button onclick="updateGlobal(<?= $o['id'] ?>)" class="btn btn-dark btn-sm w-100 fw-bold" style="font-size: 11px;">Update Global Status</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 text-center">
                                <span class="card-label">Order Type</span>
                                <?php
                                    $v_check = $pdo->prepare("SELECT COUNT(DISTINCT p.vendor_id) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                    $v_check->execute([$o['id']]);
                                    $v_count = $v_check->fetchColumn();
                                ?>
                                <span class="badge <?= $v_count > 1 ? 'bg-danger' : 'bg-primary' ?> px-4 py-2 mt-2">
                                    <?= $v_count > 1 ? 'MULTI-VENDOR ('.$v_count.')' : 'SINGLE VENDOR' ?>
                                </span>
                            </div>
                        </div>

                        <div class="tracking-area">
                            <span class="card-label">Individual Item Tracking & Fulfillment</span>
                            <?php 
                            $items = $pdo->prepare("
                                SELECT oi.*, p.name as prod_name, v.fullname as vendor_name 
                                FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                JOIN users v ON p.vendor_id = v.id 
                                WHERE oi.order_id = ?
                            ");
                            $items->execute([$o['id']]);
                            while($it = $items->fetch()):
                            ?>
                            <div class="item-tracking-row" id="item_row_<?= $it['id'] ?>">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-3">
                                        <div class="fw-bold small"><?= htmlspecialchars($it['prod_name']) ?></div>
                                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 10px;">@<?= htmlspecialchars($it['vendor_name']) ?></span>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select form-select-sm fw-bold" id="item_status_<?= $it['id'] ?>">
                                            <option value="ordered" <?= $it['item_status'] == 'ordered' ? 'selected' : '' ?>>Ordered</option>
                                            <option value="shipped" <?= $it['item_status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="delivered" <?= $it['item_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control form-control-sm" id="it_cour_<?= $it['id'] ?>" placeholder="Courier" value="<?= htmlspecialchars($it['courier_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control form-control-sm" id="it_track_<?= $it['id'] ?>" placeholder="Tracking ID" value="<?= htmlspecialchars($it['tracking_id'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control form-control-sm" id="it_link_<?= $it['id'] ?>" placeholder="Link" value="<?= htmlspecialchars($it['tracking_link'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-1">
                                        <button onclick="updateItemTracking(<?= $it['id'] ?>)" class="btn-save-sm">Save</button>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
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
// Search Functionality
$('#orderSearch').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $(".searchable-item").filter(function() {
        $(this).toggle($(this).data('customer').indexOf(value) > -1 || $(this).data('id').toString().indexOf(value) > -1)
    });
});

// Update Global Order Status
function updateGlobal(id) {
    $.ajax({
        url: 'manage_orders.php',
        method: 'POST',
        data: {
            action: 'update_global_status',
            order_id: id,
            payment_status: $('#pay_' + id).val(),
            order_status: $('#ord_' + id).val()
        },
        success: function(res) {
            if(res.status === 'success') {
                alert('Global status updated!');
                location.reload();
            }
        }
    });
}

// Update Individual Item Tracking
function updateItemTracking(itemId) {
    $.ajax({
        url: 'manage_orders.php',
        method: 'POST',
        data: {
            action: 'update_item_tracking',
            item_id: itemId,
            item_status: $('#item_status_' + itemId).val(),
            courier_name: $('#it_cour_' + itemId).val(),
            tracking_id: $('#it_track_' + itemId).val(),
            tracking_link: $('#it_link_' + itemId).val()
        },
        success: function(res) {
            if(res.status === 'success') {
                alert('Item tracking updated!');
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>