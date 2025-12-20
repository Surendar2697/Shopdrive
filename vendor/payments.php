<?php
ob_start();
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Protection: Vendor Only
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

require_once '../includes/header.php';

// 1. Fetch Totals for the Vendor
// Total Earned (All items sold)
$total_earned = $pdo->prepare("SELECT SUM(oi.price * oi.quantity) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.vendor_id = ? AND payout_status = 'released'");
$total_earned->execute([$vendor_id]);
$earned_val = $total_earned->fetchColumn() ?: 0;

// Pending Payout (Items sold but admin hasn't released payment)
$pending_payout = $pdo->prepare("SELECT SUM(oi.price * oi.quantity) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.vendor_id = ? AND payout_status = 'pending'");
$pending_payout->execute([$vendor_id]);
$pending_val = $pending_payout->fetchColumn() ?: 0;

// 2. Fetch Detailed Transaction History
$query = "SELECT 
            oi.order_id,
            oi.price as unit_price,
            oi.quantity,
            oi.payout_status,
            oi.payout_date,
            o.created_at as order_date,
            p.name as product_name
          FROM order_items oi
          JOIN orders o ON oi.order_id = o.id
          JOIN products p ON oi.product_id = p.id
          WHERE p.vendor_id = ?
          ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$vendor_id]);
$transactions = $stmt->fetchAll();

$primary_theme = $brand['primary_color'] ?? '#2563eb';
?>

<style>
    :root { --vendor-theme: <?= $primary_theme ?>; }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    
    .wallet-card { background: #fff; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; height: 100%; }
    .wallet-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
    .wallet-balance { font-size: 28px; font-weight: 800; color: #1e293b; margin-top: 5px; }
    
    .history-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-top: 30px; }
    .table thead th { 
        background: #f8fafc; font-size: 10px; text-transform: uppercase; 
        color: #64748b; padding: 15px; border-bottom: 2px solid #edf2f7;
    }
    .table td { vertical-align: middle; padding: 15px; font-size: 13px; color: #334155; }
    
    .payout-status { font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 50px; }
    .status-pending { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }
    .status-released { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10 px-4">
            <h4 class="fw-bold mb-4">Earnings & Payouts</h4>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="wallet-card border-start border-4 border-success">
                        <span class="wallet-label">Total Withdrawn</span>
                        <div class="wallet-balance">₹<?= number_format($earned_val, 2) ?></div>
                        <p class="small text-muted mb-0 mt-2">Payments already released to your account.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="wallet-card border-start border-4 border-warning">
                        <span class="wallet-label">Pending Settlement</span>
                        <div class="wallet-balance text-warning">₹<?= number_format($pending_val, 2) ?></div>
                        <p class="small text-muted mb-0 mt-2">Funds held until admin approval.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="wallet-card bg-dark text-white">
                        <span class="wallet-label text-white-50">Total Revenue</span>
                        <div class="wallet-balance text-white">₹<?= number_format($earned_val + $pending_val, 2) ?></div>
                        <p class="small text-white-50 mb-0 mt-2">Your lifetime sales on this platform.</p>
                    </div>
                </div>
            </div>

            <div class="history-card shadow-sm">
                <div class="p-3 border-bottom bg-light">
                    <h6 class="m-0 fw-bold">Recent Transactions</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Item Details</th>
                                <th>Net Amount</th>
                                <th>Payout Status</th>
                                <th>Release Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No transactions found yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($transactions as $t): 
                                    $subtotal = $t['unit_price'] * $t['quantity'];
                                    $released = ($t['payout_status'] == 'released');
                                ?>
                                <tr>
                                    <td class="fw-bold">#ORD-<?= $t['order_id'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($t['product_name']) ?></div>
                                        <div class="text-muted small">Qty: <?= $t['quantity'] ?> @ ₹<?= number_format($t['unit_price'], 2) ?></div>
                                    </td>
                                    <td class="fw-bold text-dark">₹<?= number_format($subtotal, 2) ?></td>
                                    <td>
                                        <span class="payout-status <?= $released ? 'status-released' : 'status-pending' ?>">
                                            <i class="bi <?= $released ? 'bi-check-circle-fill' : 'bi-clock-history' ?> me-1"></i>
                                            <?= $released ? 'RELEASED' : 'PENDING' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">
                                            <?= $t['payout_date'] ? date('d M Y', strtotime($t['payout_date'])) : 'Waiting for Admin...' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>