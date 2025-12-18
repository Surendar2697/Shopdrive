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

// 2. FETCH GLOBAL STATS
$userCount   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$vendorCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'vendor'")->fetchColumn();
$prodCount   = $pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
$totalSales  = 145800; // Placeholder for actual sales logic later

require '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; font-family: 'Segoe UI', Roboto, sans-serif; }

    /* Professional Stat Cards */
    .stat-card {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        height: 100%;
    }
    .stat-label { font-size: 11px; font-weight: 800; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .stat-value { font-size: 24px; font-weight: 700; color: #000; }
    .stat-icon { font-size: 28px; color: var(--primary-theme); opacity: 0.8; }

    /* Manager Card Style */
    .manager-card {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .manager-header {
        padding: 1.25rem 1.5rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .manager-title { font-size: 14px; font-weight: 700; color: #333; margin: 0; text-transform: uppercase; }

    /* Table Styling */
    .custom-table thead th {
        background-color: #fff;
        color: #000;
        font-weight: 700;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 20px;
        font-size: 12px;
        text-transform: uppercase;
    }
    .custom-table tbody td {
        padding: 15px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
        font-size: 14px;
    }
    .btn-view-all {
        background: transparent;
        border: 1px solid #ddd;
        color: #555;
        font-size: 11px;
        font-weight: 700;
        padding: 5px 12px;
        text-transform: uppercase;
        text-decoration: none;
        transition: 0.2s;
    }
    .btn-view-all:hover { background: #000; color: #fff; border-color: #000; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <h5 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">Admin Command Center</h5>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card border-start border-4" style="border-left-color: var(--primary-theme) !important;">
                        <div>
                            <div class="stat-label">System Users</div>
                            <div class="stat-value"><?= number_format($userCount) ?></div>
                        </div>
                        <i class="bi bi-people stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4" style="border-left-color: #198754 !important;">
                        <div>
                            <div class="stat-label">Total Vendors</div>
                            <div class="stat-value"><?= number_format($vendorCount) ?></div>
                        </div>
                        <i class="bi bi-shop stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4" style="border-left-color: #ffc107 !important;">
                        <div>
                            <div class="stat-label">Active Products</div>
                            <div class="stat-value"><?= number_format($prodCount) ?></div>
                        </div>
                        <i class="bi bi-box-seam stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-4" style="border-left-color: #dc3545 !important;">
                        <div>
                            <div class="stat-label">Platform Sales</div>
                            <div class="stat-value">₹<?= number_format($totalSales) ?></div>
                        </div>
                        <i class="bi bi-currency-rupee stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="manager-card">
                <div class="manager-header">
                    <h2 class="manager-title">Recent Product Listings (Global)</h2>
                    <a href="manage_products.php" class="btn-view-all">Manage Inventory</a>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Assigned Vendor</th>
                                <th>Pricing (₹)</th>
                                <th>Date Added</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stmt = $pdo->query("SELECT p.*, u.fullname FROM products p JOIN users u ON p.vendor_id = u.id WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC LIMIT 6");
                            while($row = $stmt->fetch()): 
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['name']) ?></span>
                                    <small class="text-muted">UID: #PRD-<?= $row['id'] ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 11px; font-weight: 600;">
                                        <?= htmlspecialchars($row['fullname']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">₹<?= number_format($row['price'], 2) ?></td>
                                <td class="text-muted small"><?= date('d M, Y', strtotime($row['created_at'])) ?></td>
                                <td class="text-end">
                                    <span class="badge rounded-pill" style="background: rgba(25, 135, 84, 0.1); color: #198754; font-size: 10px; font-weight: 800; border: 1px solid #198754;">LIVE</span>
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