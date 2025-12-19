<?php 
require_once '../config/db.php';

// 1. SECURITY & SESSION CHECK
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

// Fetch settings for dynamic theme alignment
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#00bcd4';

// 2. FINANCIAL CALCULATIONS (Vendor specific)

// Total Earned (Items sold in 'paid' orders)
$total_earned = $pdo->prepare("SELECT SUM(oi.price * oi.quantity) FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE p.vendor_id = ? AND o.payment_status = 'paid'");
$total_earned->execute([$vendor_id]);
$total_earned = $total_earned->fetchColumn() ?? 0;

// Total already processed by Admin
$total_paid_out = $pdo->prepare("SELECT SUM(amount) FROM vendor_payouts WHERE vendor_id = ? AND status = 'completed'");
$total_paid_out->execute([$vendor_id]);
$total_paid_out = $total_paid_out->fetchColumn() ?? 0;

$current_balance = $total_earned - $total_paid_out;

require_once '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; font-family: 'Segoe UI', sans-serif; }

    /* Manager & Stat Cards */
    .manager-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .manager-header { padding: 1.25rem 1.5rem; background-color: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .manager-title { font-size: 13px; font-weight: 700; color: #333; margin: 0; text-transform: uppercase; }

    .stat-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; padding: 20px; display: flex; align-items: center; justify-content: space-between; height: 100%; border-bottom: 3px solid #eee; }
    .stat-label { font-size: 10px; font-weight: 800; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-size: 22px; font-weight: 700; color: #000; margin-top: 5px; }
    
    /* Table Styling */
    .custom-table thead th { background-color: #fff; color: #888; font-weight: 700; border-bottom: 2px solid #dee2e6; padding: 12px 20px; font-size: 11px; text-transform: uppercase; }
    .custom-table tbody td { padding: 15px 20px; border-bottom: 1px solid #f1f1f1; font-size: 14px; }
    
    .status-badge { font-size: 9px; font-weight: 800; padding: 4px 10px; border-radius: 50px; text-transform: uppercase; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <h5 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">Payment & Earnings Report</h5>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card" style="border-bottom-color: var(--primary-theme);">
                        <div>
                            <div class="stat-label">Lifetime Revenue</div>
                            <div class="stat-value">₹<?= number_format($total_earned, 2) ?></div>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-3 text-muted opacity-25"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="border-bottom-color: #198754;">
                        <div>
                            <div class="stat-label">Outstanding Balance</div>
                            <div class="stat-value">₹<?= number_format($current_balance, 2) ?></div>
                        </div>
                        <i class="bi bi-wallet2 fs-3 text-muted opacity-25"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="border-bottom-color: #6f42c1;">
                        <div>
                            <div class="stat-label">Total Payouts Received</div>
                            <div class="stat-value">₹<?= number_format($total_paid_out, 2) ?></div>
                        </div>
                        <i class="bi bi-bank fs-3 text-muted opacity-25"></i>
                    </div>
                </div>
            </div>

            <div class="manager-card">
                <div class="manager-header">
                    <h2 class="manager-title">Payout Settlement History</h2>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Ref ID</th>
                                <th>Settlement Date</th>
                                <th>Amount</th>
                                <th>Transfer Method</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->prepare("SELECT * FROM vendor_payouts WHERE vendor_id = ? ORDER BY created_at DESC");
                            $stmt->execute([$vendor_id]);
                            while($row = $stmt->fetch()): 
                            ?>
                            <tr>
                                <td class="fw-bold text-dark">#TXN-<?= $row['id'] ?></td>
                                <td class="text-muted small"><?= date('d M, Y', strtotime($row['created_at'])) ?></td>
                                <td class="fw-bold text-success">+ ₹<?= number_format($row['amount'], 2) ?></td>
                                <td class="text-muted small text-uppercase"><?= htmlspecialchars($row['payment_method']) ?></td>
                                <td class="text-end">
                                    <?php if($row['status'] == 'completed'): ?>
                                        <span class="status-badge bg-success text-white">Settled</span>
                                    <?php else: ?>
                                        <span class="status-badge bg-secondary text-white">In Process</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if($stmt->rowCount() == 0): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted small">No settlement history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>