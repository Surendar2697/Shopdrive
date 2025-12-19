<?php
// 1. SAFETY: Prevent session notices if db.php was already included
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/** * 2. THEME SETUP 
 * We use $primary_theme if already defined in the main page, 
 * otherwise we pull it from $settings. Fallback is #00bcd4.
 */
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$sidebar_color = $settings['primary_color'] ?? '#00bcd4';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* Professional Sidebar Box matching Manager-Card style */
    .vendor-sidebar-box {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 12px;
        min-height: 85vh;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .menu-section-label {
        font-size: 10px;
        font-weight: 800;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        padding: 10px 15px;
        display: block;
    }

    /* Links Styling */
    .v-link {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: #444;
        text-decoration: none !important;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        margin-bottom: 4px;
        transition: 0.2s ease;
        border-radius: 4px;
    }

    /* Icon gets the Primary Color by default */
    .v-link i { 
        margin-right: 12px; 
        font-size: 18px; 
        width: 20px; 
        text-align: center;
        color: <?= $sidebar_color ?>; 
        transition: 0.2s;
    }

    /* Hover State: Text and Icon glow with Primary Color */
    .v-link:hover { 
        background-color: #f8f9fa; 
        color: <?= $sidebar_color ?>; 
    }

    .v-link:hover i {
        color: <?= $sidebar_color ?>;
    }

    /* Active State: Background becomes Primary Color, Text/Icon become White */
    .v-link.active {
        background-color: <?= $sidebar_color ?> !important;
        color: #ffffff !important;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .v-link.active i {
        color: #ffffff !important;
    }

    /* Danger / Logout styling */
    .v-link.text-danger i { color: #dc3545; }
    .v-link.text-danger:hover { background-color: #fff5f5; color: #dc3545; }
    .v-link.text-danger:hover i { color: #dc3545; }

    hr.sidebar-divider {
        margin: 15px 10px;
        border-top: 1px solid #eee;
        opacity: 0.1;
    }
</style>

<div class="vendor-sidebar-box">
    <span class="menu-section-label border-bottom pb-2 mb-3 text-center" style="color:#333">Vendor Panel</span>
    
    <a href="dashboard.php" class="v-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a href="manage_orders.php" class="v-link <?= ($current_page == 'manage_orders.php') ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i> Manage Orders
    </a>

    <span class="menu-section-label mt-4">Inventory</span>

    <a href="upload_product.php" class="v-link <?= ($current_page == 'upload_product.php') ? 'active' : '' ?>">
        <i class="bi bi-plus-square"></i> Add Product
    </a>

    <a href="manage_products.php" class="v-link <?= ($current_page == 'manage_products.php') ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i> My Products
    </a>

    <span class="menu-section-label mt-4">Finance</span>

    <a href="payments.php" class="v-link <?= ($current_page == 'payments.php') ? 'active' : '' ?>">
        <i class="bi bi-credit-card"></i> Payouts
    </a>

    <hr class="sidebar-divider">
    
    <a href="../logout.php" class="v-link text-danger">
        <i class="bi bi-box-arrow-left"></i> Logout
    </a>
</div>