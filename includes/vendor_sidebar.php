<?php
// Theme variable from database
$sidebar_theme = $settings['primary_color'] ?? '#00bcd4';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .vendor-sidebar-box {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 10px;
        min-height: 80vh;
    }
    .v-link {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: #555;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        margin-bottom: 5px;
        border-radius: 4px;
        transition: 0.2s;
    }
    .v-link:hover {
        background-color: #f8f9fa;
        color: <?= $sidebar_theme ?>;
    }
    /* Active state uses the Primary Theme Color from settings */
    .v-link.active {
        background-color: <?= $sidebar_theme ?> !important;
        color: #ffffff !important;
    }
    .v-link i { margin-right: 12px; font-size: 16px; }
</style>

<div class="vendor-sidebar-box shadow-sm">
    <div class="small fw-bold text-muted mb-3 px-2 text-uppercase" style="font-size: 10px; letter-spacing: 1px;">
        Vendor Panel
    </div>
    
    <a href="dashboard.php" class="v-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a href="add_product.php" class="v-link <?= ($current_page == 'add_product.php') ? 'active' : '' ?>">
        <i class="bi bi-plus-square"></i> Add Product
    </a>

    <a href="manage_products.php" class="v-link <?= ($current_page == 'manage_products.php') ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i> Manage Products
    </a>

    <a href="orders.php" class="v-link <?= ($current_page == 'orders.php') ? 'active' : '' ?>">
        <i class="bi bi-cart4"></i> Orders
    </a>

    <a href="payments.php" class="v-link <?= ($current_page == 'payments.php') ? 'active' : '' ?>">
        <i class="bi bi-credit-card"></i> Payments
    </a>

    <hr class="my-3">

    <a href="../logout.php" class="v-link text-danger">
        <i class="bi bi-box-arrow-left"></i> Logout
    </a>
</div>