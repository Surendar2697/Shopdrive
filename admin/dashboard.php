<?php 
require '../config/db.php';

// 1. SECURITY & SESSION CHECK
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch settings for dynamic theme alignment
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#00bcd4';

// 2. FETCH LIVE DYNAMIC STATS
// User & Vendor Counts
$userCount   = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$vendorCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'vendor'")->fetchColumn();
$prodCount   = $pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();

// Sales Calculation (Only Successful/Paid Payments)
$totalSales  = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?? 0;

// Order Fulfillment Stats
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'ordered'")->fetchColumn();
$shippedOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'shipped'")->fetchColumn();

require '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; font-family: 'Segoe UI', sans-serif; }

    /* Stat Card Style */
    .stat-card {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        height: 100%;
    }
    .stat-label { font-size: 10px; font-weight: 800; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .stat-value { font-size: 22px; font-weight: 700; color: #000; }
    .stat-icon { font-size: 24px; color: var(--primary-theme); opacity: 0.7; }

    /* Manager Card Style */
    .manager-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; }
    .manager-header { padding: 1.25rem 1.5rem; background-color: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .manager-title { font-size: 13px; font-weight: 700; color: #333; margin: 0; text-transform: uppercase; }

    .custom-table thead th { background-color: #fff; color: #000; font-weight: 700; border-bottom: 2px solid #dee2e6; padding: 12px 20px; font-size: 11px; text-transform: uppercase; }
    .custom-table tbody td { padding: 12px 20px; border-bottom: 1px solid #f1f1f1; font-size: 14px; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <h5 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">Admin Command Center</h5>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="stat-card border-start border-4" style="border-left-color: var(--primary-theme) !important;">
                        <div>
                            <div class="stat-label">System Customers</div>
                            <div class="stat-value"><?= number_format($userCount) ?></div>
                        </div>
                        <i class="bi bi-people stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4" style="border-left-color: #6f42c1 !important;">
                        <div>
                            <div class="stat-label">Active Vendors</div>
                            <div class="stat-value"><?= number_format($vendorCount) ?></div>
                        </div>
                        <i class="bi bi-shop stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4" style="border-left-color: #0dcaf0 !important;">
                        <div>
                            <div class="stat-label">Live Inventory</div>
                            <div class="stat-value"><?= number_format($prodCount) ?></div>
                        </div>
                        <i class="bi bi-box-seam stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4" style="border-left-color: #198754 !important;">
                        <div>
                            <div class="stat-label">Revenue (Paid)</div>
                            <div class="stat-value">₹<?= number_format($totalSales, 2) ?></div>
                        </div>
                        <i class="bi bi-currency-rupee stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="stat-card border-start border-4" style="border-left-color: #ffc107 !important;">
                        <div>
                            <div class="stat-label">Pending Orders (New)</div>
                            <div class="stat-value"><?= number_format($pendingOrders) ?></div>
                        </div>
                        <div class="text-end">
                            <a href="manage_orders.php" class="btn btn-sm btn-outline-warning fw-bold" style="font-size: 10px;">PROCESS NOW</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card border-start border-4" style="border-left-color: #0d6efd !important;">
                        <div>
                            <div class="stat-label">Orders in Transit</div>
                            <div class="stat-value"><?= number_format($shippedOrders) ?></div>
                        </div>
                        <div class="text-end">
                            <a href="manage_orders.php" class="btn btn-sm btn-outline-primary fw-bold" style="font-size: 10px;">VIEW TRACKING</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="manager-card">
                <div class="manager-header">
                    <h2 class="manager-title">Global Product Activity</h2>
                    <a href="manage_products.php" class="btn-view-all">Full Inventory</a>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Vendor</th>
                                <th>Price</th>
                                <th class="text-end">Stock Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->query("SELECT p.*, u.fullname FROM products p LEFT JOIN users u ON p.vendor_id = u.id WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC LIMIT 5");
                            while($row = $stmt->fetch()): 
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['name']) ?></span>
                                    <small class="text-muted">#PRD-<?= $row['id'] ?></small>
                                </td>
                                <td><span class="small fw-bold"><?= htmlspecialchars($row['fullname'] ?? 'Admin') ?></span></td>
                                <td class="fw-bold text-dark">₹<?= number_format($row['price'], 2) ?></td>
                                <td class="text-end">
                                    <?php if($row['stock'] > 0): ?>
                                        <span class="badge rounded-pill bg-success" style="font-size: 9px;">IN STOCK</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-danger" style="font-size: 9px;">OUT OF STOCK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>