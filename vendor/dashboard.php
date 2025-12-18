<?php 
require '../config/db.php';

// PROFESSIONAL SESSION CHECK
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Only Vendors allowed
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

// Fetch settings for dynamic theme alignment
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$vendor_id = $_SESSION['user_id'];

require '../includes/header.php'; 
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <div class="manager-card border shadow-sm bg-white">
                <div class="manager-header p-4 border-bottom">
                    <h1 class="manager-title fw-bold text-uppercase">Vendor Overview</h1>
                </div>
                
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted fw-bold text-uppercase">Inventory</small>
                                <h3 class="fw-bold mb-0">Manage Products</h3>
                                <a href="manage_products.php" class="small text-decoration-none" style="color:var(--primary-theme)">View List →</a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted fw-bold text-uppercase">Sales</small>
                                <h3 class="fw-bold mb-0">Orders</h3>
                                <a href="orders.php" class="small text-decoration-none" style="color:var(--primary-theme)">View Details →</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>