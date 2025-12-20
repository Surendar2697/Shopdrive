<?php
ob_start();
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Protection: Super Admin Only
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// ---------------------------------------------------------
// AJAX HANDLER: Release Payment to Vendor
// ---------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'release_payment') {
    ob_clean();
    header('Content-Type: application/json');
    try {
        $item_id = $_POST['item_id'];
        $status = $_POST['status']; 
        $date = ($status == 'released') ? date('Y-m-d H:i:s') : null;

        $stmt = $pdo->prepare("UPDATE order_items SET payout_status = ?, payout_date = ? WHERE id = ?");
        $stmt->execute([$status, $date, $item_id]);
        
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

require_once '../includes/header.php';

// Fetch Totals for Dashboard
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?: 0;
$pending_payout = $pdo->query("SELECT SUM(price * quantity) FROM order_items WHERE payout_status = 'pending'")->fetchColumn() ?: 0;

// ---------------------------------------------------------
// FIXED QUERY: Using v.id instead of v.user_id
// ---------------------------------------------------------
$query = "SELECT 
            oi.id as item_row_id,
            oi.order_id,
            oi.price as sold_price,
            oi.quantity,
            oi.payout_status,
            oi.payout_date,
            o.payment_status as user_payment_status,
            o.created_at as order_date,
            p.name as product_name,
            v.fullname as vendor_name,
            v.id as vendor_id
          FROM order_items oi
          JOIN orders o ON oi.order_id = o.id
          JOIN products p ON oi.product_id = p.id
          JOIN users v ON p.vendor_id = v.id
          ORDER BY o.created_at DESC";

$transactions = $pdo->query($query)->fetchAll();

$primary_theme = $brand['primary_color'] ?? '#2563eb';
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    
    .stats-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; border-left: 4px solid var(--primary-theme); }
    .fin-table-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    
    .table thead th { 
        background: #f8fafc; font-size: 11px; text-transform: uppercase; 
        color: #64748b; padding: 15px; border-bottom: 2px solid #edf2f7;
    }
    .table td { vertical-align: middle; padding: 15px; font-size: 13px; }
    
    .status-badge { font-size: 10px; font-weight: 700; padding: 5px 12px; border-radius: 50px; }
    .bg-pending { background: #fef3c7; color: #92400e; }
    .bg-released { background: #dcfce7; color: #166534; }
    
    .btn-release { background: var(--primary-theme); color: #fff; border: none; font-size: 11px; font-weight: 700; padding: 8px 15px; border-radius: 6px; }
    .btn-release:hover { opacity: 0.9; color: #fff; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10 px-4">
            <h3 class="fw-bold mb-4 text-dark">Payment & Payout Manager</h3>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <span class="text-muted small fw-bold text-uppercase">Total Revenue Collected</span>
                        <h2 class="fw-bold mb-0">₹<?= number_format($total_revenue, 2) ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card" style="border-left-color: #f59e0b;">
                        <span class="text-muted small fw-bold text-uppercase">Owed to Vendors</span>
                        <h2 class="fw-bold mb-0 text-warning">₹<?= number_format($pending_payout, 2) ?></h2>
                    </div>
                </div>
            </div>

            <div class="fin-table-card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order Details</th>
                                <th>Product & Vendor</th>
                                <th>Payable Amount</th>
                                <th>Release Status</th>
                                <th class="text-end">Management</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transactions as $t): 
                                $amt = $t['sold_price'] * $t['quantity'];
                                $released = ($t['payout_status'] == 'released');
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold">#ORD-<?= $t['order_id'] ?></div>
                                    <div class="text-muted small"><?= date('d M Y', strtotime($t['order_date'])) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['product_name']) ?></div>
                                    <div class="small text-primary">Vendor: <?= htmlspecialchars($t['vendor_name']) ?> (ID: <?= $t['vendor_id'] ?>)</div>
                                </td>
                                <td>
                                    <div class="fw-bold">₹<?= number_format($amt, 2) ?></div>
                                    <span class="badge <?= $t['user_payment_status'] == 'paid' ? 'bg-success' : 'bg-danger' ?>" style="font-size: 9px;">
                                        USER <?= strtoupper($t['user_payment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $released ? 'bg-released' : 'bg-pending' ?>">
                                        <?= $released ? 'RELEASED' : 'PENDING' ?>
                                    </span>
                                    <?php if($released): ?>
                                        <div class="text-muted small mt-1" style="font-size: 10px;">on <?= date('d M Y', strtotime($t['payout_date'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if(!$released): ?>
                                        <button onclick="updatePayout(<?= $t['item_row_id'] ?>, 'released')" class="btn-release shadow-sm">Release Payout</button>
                                    <?php else: ?>
                                        <button onclick="updatePayout(<?= $t['item_row_id'] ?>, 'pending')" class="btn btn-sm btn-link text-danger text-decoration-none small">Undo Release</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updatePayout(id, status) {
    const actionText = status === 'released' ? 'release this payment to the vendor' : 'mark this payment as pending again';
    if(!confirm("Are you sure you want to " + actionText + "?")) return;
    
    $.post('payments.php', { action: 'release_payment', item_id: id, status: status }, function(res) {
        if(res.status === 'success') {
            location.reload();
        } else {
            alert("Error: " + res.message);
        }
    }, 'json');
}
</script>

<?php include '../includes/footer.php'; ?>