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
// AJAX UPDATE HANDLER (Keep your existing logic here)
// ---------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'update_order') {
    ob_clean(); 
    header('Content-Type: application/json');
    try {
        $order_id = $_POST['order_id'];
        $payment_status = $_POST['payment_status'];
        $order_status = $_POST['order_status'];
        $courier_name = $_POST['courier_name'] ?? '';
        $tracking_id = $_POST['tracking_id'] ?? '';
        $tracking_link = $_POST['tracking_link'] ?? '';

        $update = $pdo->prepare("UPDATE orders SET 
            payment_status = ?, order_status = ?, courier_name = ?, 
            tracking_id = ?, tracking_link = ? WHERE id = ?");
        
        $update->execute([$payment_status, $order_status, $courier_name, $tracking_id, $tracking_link, $order_id]);
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
    .search-box { border-radius: 50px; padding: 10px 25px; border: 1px solid #e0e0e0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    
    .order-card { 
        background: #fff; border-radius: 12px; border: none; margin-bottom: 25px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 6px solid #ccc;
    }
    .status-ordered { border-left-color: var(--admin-theme); }
    .status-shipped { border-left-color: #ffc107; }
    .status-delivered { border-left-color: #28a745; }

    .card-label { font-size: 10px; font-weight: 800; color: #999; text-transform: uppercase; margin-bottom: 8px; display: block; letter-spacing: 0.5px; }
    
    /* Product Table Styling */
    .product-details-area { background: #fafafa; border-radius: 8px; padding: 15px; margin-top: 15px; border: 1px solid #eee; }
    .prod-table { width: 100%; font-size: 13px; }
    .prod-table th { color: #888; font-weight: 700; padding-bottom: 10px; border-bottom: 1px solid #eee; text-transform: uppercase; font-size: 10px; }
    .prod-table td { padding: 10px 0; border-bottom: 1px solid #f1f1f1; }
    .prod-table tr:last-child td { border-bottom: none; }
    
    .btn-update-custom { 
        background: var(--admin-theme); color: #fff; border: none; 
        padding: 8px 25px; border-radius: 50px; font-weight: 700; font-size: 12px; transition: 0.3s;
    }
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
                            <span class="small text-muted me-3"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></span>
                            <span class="badge bg-white text-dark border px-3">Total: ₹<?= number_format($o['total_amount'], 2) ?></span>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="row g-4">
                            <div class="col-md-3 border-end">
                                <span class="card-label">Customer & Shipping</span>
                                <div class="fw-bold mb-1" style="color: var(--admin-theme);"><?= htmlspecialchars($o['fullname']) ?></div>
                                <div class="small text-muted lh-sm"><?= nl2br(htmlspecialchars($o['shipping_address'])) ?></div>
                            </div>

                            <div class="col-md-3 border-end">
                                <span class="card-label">Order Management</span>
                                <div class="mb-3">
                                    <label class="small text-muted mb-1">Payment Status</label>
                                    <select class="form-select form-select-sm" id="pay_<?= $o['id'] ?>">
                                        <option value="pending" <?= $o['payment_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="paid" <?= $o['payment_status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="small text-muted mb-1">Logistics Progress</label>
                                    <select class="form-select form-select-sm fw-bold" id="ord_<?= $o['id'] ?>">
                                        <option value="ordered" <?= $o['order_status'] == 'ordered' ? 'selected' : '' ?>>Ordered</option>
                                        <option value="shipped" <?= $o['order_status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $o['order_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <span class="card-label">Courier & Tracking</span>
                                <div class="row g-2 mb-3">
                                    <div class="col-4"><input type="text" class="form-control form-control-sm" id="cour_<?= $o['id'] ?>" placeholder="Courier" value="<?= htmlspecialchars($o['courier_name'] ?? '') ?>"></div>
                                    <div class="col-4"><input type="text" class="form-control form-control-sm" id="track_<?= $o['id'] ?>" placeholder="Tracking ID" value="<?= htmlspecialchars($o['tracking_id'] ?? '') ?>"></div>
                                    <div class="col-4"><input type="text" class="form-control form-control-sm" id="link_<?= $o['id'] ?>" placeholder="Live Link" value="<?= htmlspecialchars($o['tracking_link'] ?? '') ?>"></div>
                                </div>
                                <div class="text-end">
                                    <button onclick="updateOrder(<?= $o['id'] ?>)" id="btn_<?= $o['id'] ?>" class="btn-update-custom shadow-sm">Save Changes</button>
                                </div>
                            </div>
                        </div>

                        <div class="product-details-area">
                            <span class="card-label">Itemized Breakdown</span>
                            <table class="prod-table">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Vendor</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
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
                                        $subtotal = $it['price'] * $it['quantity'];
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($it['prod_name']) ?></td>
                                        <td><span class="badge bg-light text-dark border fw-normal">@<?= htmlspecialchars($it['vendor_name']) ?></span></td>
                                        <td class="text-center"><?= $it['quantity'] ?></td>
                                        <td class="text-end">₹<?= number_format($it['price'], 2) ?></td>
                                        <td class="text-end fw-bold">₹<?= number_format($subtotal, 2) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
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
// (Keep your existing Search and updateOrder AJAX scripts here)
</script>

<?php include '../includes/footer.php'; ?>