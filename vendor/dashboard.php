<?php 
require '../config/db.php';

// 1. SECURITY & SESSION CHECK
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security Check: Only Vendors allowed
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

// Fetch settings for dynamic theme alignment
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#00bcd4';

// 2. FETCH LIVE DYNAMIC STATS (Filtered by Vendor ID)

// Total Products listed by this vendor
$prodCount = $pdo->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = ? AND deleted_at IS NULL");
$prodCount->execute([$vendor_id]);
$prodCount = $prodCount->fetchColumn();

// Total Revenue for this vendor (Sum of their items in paid orders)
$totalSales = $pdo->prepare("
    SELECT SUM(oi.price * oi.quantity) 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    JOIN products p ON oi.product_id = p.id 
    WHERE p.vendor_id = ? AND o.payment_status = 'paid'
");
$totalSales->execute([$vendor_id]);
$totalSales = $totalSales->fetchColumn() ?? 0;

// Pending Orders for this vendor
$pendingOrders = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id) 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE p.vendor_id = ? AND o.order_status = 'ordered'
");
$pendingOrders->execute([$vendor_id]);
$pendingOrders = $pendingOrders->fetchColumn();

// Shipped Orders for this vendor
$shippedOrders = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id) 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE p.vendor_id = ? AND o.order_status = 'shipped'
");
$shippedOrders->execute([$vendor_id]);
$shippedOrders = $shippedOrders->fetchColumn();

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
    
    .btn-view-all { 
        font-size: 11px; font-weight: 700; text-transform: uppercase; text-decoration: none; color: var(--primary-theme); 
    }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <h5 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">Vendor Command Center</h5>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="stat-card border-start border-4" style="border-left-color: var(--primary-theme) !important;">
                        <div>
                            <div class="stat-label">Your Inventory</div>
                            <div class="stat-value"><?= number_format($prodCount) ?> Items</div>
                        </div>
                        <i class="bi bi-box-seam stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card border-start border-4" style="border-left-color: #198754 !important;">
                        <div>
                            <div class="stat-label">Your Earnings (Paid)</div>
                            <div class="stat-value">₹<?= number_format($totalSales, 2) ?></div>
                        </div>
                        <i class="bi bi-currency-rupee stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card border-start border-4" style="border-left-color: #6f42c1 !important;">
                        <div>
                            <div class="stat-label">Store Status</div>
                            <div class="stat-value">Active</div>
                        </div>
                        <i class="bi bi-shop stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="stat-card border-start border-4" style="border-left-color: #ffc107 !important;">
                        <div>
                            <div class="stat-label">Orders to Process</div>
                            <div class="stat-value"><?= number_format($pendingOrders) ?></div>
                        </div>
                        <div class="text-end">
                            <a href="manage_orders.php" class="btn btn-sm btn-outline-warning fw-bold" style="font-size: 10px;">MANAGE ORDERS</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card border-start border-4" style="border-left-color: #0d6efd !important;">
                        <div>
                            <div class="stat-label">Items in Transit</div>
                            <div class="stat-value"><?= number_format($shippedOrders) ?></div>
                        </div>
                        <div class="text-end">
                            <a href="manage_orders.php" class="btn btn-sm btn-outline-primary fw-bold" style="font-size: 10px;">TRACK SHIPMENTS</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="manager-card">
                <div class="manager-header">
                    <h2 class="manager-title">Your Recent Listings</h2>
                    <a href="manage_products.php" class="btn-view-all">Manage All Products</a>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Price</th>
                                <th>Date Added</th>
                                <th class="text-end">Stock Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
                            $stmt->execute([$vendor_id]);
                            while($row = $stmt->fetch()): 
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['name']) ?></span>
                                    <small class="text-muted">#PRD-<?= $row['id'] ?></small>
                                </td>
                                <td class="fw-bold text-dark">₹<?= number_format($row['price'], 2) ?></td>
                                <td class="text-muted small"><?= date('d M, Y', strtotime($row['created_at'])) ?></td>
                                <td class="text-end">
                                    <?php if($row['stock'] > 0): ?>
                                        <span class="badge rounded-pill" style="background: rgba(25, 135, 84, 0.1); color: #198754; font-size: 10px; font-weight: 800; border: 1px solid #198754;">IN STOCK (<?= $row['stock'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; font-size: 10px; font-weight: 800; border: 1px solid #dc3545;">OUT OF STOCK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($stmt->rowCount() == 0): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted small">No products listed yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>