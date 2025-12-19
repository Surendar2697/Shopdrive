<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

// 1. Fetch Branding & Global Settings
$brand = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_color = $brand['primary_color'] ?? '#2563eb';
$header_bg = $brand['header_color'] ?? '#ffffff';
$app_name = $brand['app_name'] ?? 'Pro-Market';
$app_logo = $brand['app_logo'] ?? '';

$is_folder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/vendor/') !== false || strpos($_SERVER['PHP_SELF'], '/super_admin/') !== false);
$base = $is_folder ? '../' : '';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['fullname'] ?? '';
$userRole = $_SESSION['role'] ?? ''; 

// 2. DYNAMIC COUNTS
$cart_count = 0;
$wishlist_count = 0;

if ($isLoggedIn) {
    $cStmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $cStmt->execute([$_SESSION['user_id']]);
    $cart_count = (int)$cStmt->fetchColumn();

    $wStmt = $pdo->prepare("SELECT COUNT(id) FROM wishlist WHERE user_id = ?");
    $wStmt->execute([$_SESSION['user_id']]);
    $wishlist_count = (int)$wStmt->fetchColumn();
}

// 3. Helper to determine text color for dynamic branding
function getContrastColor($hexColor) {
    $hexColor = str_replace('#', '', $hexColor);
    if(strlen($hexColor) == 6) {
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return ($yiq >= 128) ? '#000000' : '#ffffff';
    }
    return '#000000';
}
$header_text = getContrastColor($header_bg);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    :root { 
        --theme-color: <?= $primary_color ?>; 
        --header-bg: <?= $header_bg ?>;
        --header-text: <?= $header_text ?>;
    }

    .navbar-custom { background-color: var(--header-bg) !important; border-bottom: 1px solid rgba(0,0,0,0.1); padding: 12px 0; }
    .navbar-custom .navbar-brand { color: var(--header-text) !important; font-weight: 800; text-decoration: none; }

    .header-icon-btn {
        color: var(--header-text) !important; padding: 8px; margin-right: 12px;
        position: relative; display: inline-flex; align-items: center; text-decoration: none;
    }

    .header-badge {
        position: absolute; top: 2px; right: -2px; font-size: 9px;
        background-color: var(--theme-color) !important; color: #fff !important; padding: 2px 5px;
        min-width: 18px; text-align: center; border-radius: 50px; border: 2px solid var(--header-bg);
    }

    .user-pill {
        border: 1px solid rgba(128,128,128,0.2); padding: 5px 15px; border-radius: 50px;
        background: rgba(128,128,128,0.05); color: var(--header-text) !important;
        display: flex; align-items: center; cursor: pointer; position: relative;
    }
    .user-pill:hover { border-color: var(--theme-color); }

    .custom-dropdown-menu {
        display: none; position: absolute; top: 100%; right: 0; z-index: 1050;
        min-width: 210px; padding: 10px; margin-top: 15px; background-color: #fff;
        border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); list-style: none;
    }

    .dropdown-item {
        display: block; width: 100%; padding: 10px 15px; font-weight: 500; 
        color: #444 !important; text-decoration: none; border-radius: 8px; transition: 0.2s;
    }
    .dropdown-item:hover { background-color: #f8f9fa; color: var(--theme-color) !important; }
    .dropdown-divider { height: 1px; background: #eee; margin: 8px 0; }

    /* Theme Primary Button */
    .btn-theme-main { background-color: var(--theme-color) !important; color: #ffffff !important; border: none; }
    
    /* FORCE WHITE LOGIN TEXT */
    .nav-login-white { 
        color: #ffffff !important; 
        font-weight: 600 !important; 
        text-decoration: none !important; 
        border-right: 1px solid rgba(255, 255, 255, 0.4);
        padding-right: 1.2rem;
        margin-right: 0.8rem;
        display: inline-block;
    }
    .nav-login-white:hover {
        color: rgba(255, 255, 255, 0.8) !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= $base ?>index.php">
            <?php if($app_logo): ?>
                <img src="<?= $base ?>assets/uploads/<?= $app_logo ?>" style="max-height: 35px;" class="me-2">
            <?php endif; ?>
            <?= htmlspecialchars($app_name) ?>
        </a>

        <div class="ms-auto d-flex align-items-center">
            
            <a href="<?= $base ?>wishlist.php" class="header-icon-btn">
                <i class="bi bi-heart fs-4"></i>
                <span id="wishlist-badge" class="header-badge" style="<?= $wishlist_count > 0 ? '' : 'display:none' ?>">
                    <?= $wishlist_count ?>
                </span>
            </a>

            <a href="<?= $base ?>cart.php" class="header-icon-btn">
                <i class="bi bi-bag fs-4"></i>
                <span id="cart-badge" class="header-badge" style="<?= $cart_count > 0 ? '' : 'display:none' ?>">
                    <?= $cart_count ?>
                </span>
            </a>

            <?php if($isLoggedIn): ?>
                <div class="position-relative">
                    <div class="user-pill" id="profileToggle">
                        <div class="me-2 d-none d-md-block text-end">
                            <div class="small fw-bold mb-0" style="line-height: 1;"><?= htmlspecialchars(explode(' ', $userName)[0]) ?></div>
                            <span style="font-size: 10px; opacity: 0.8;"><?= strtoupper(str_replace('_', ' ', $userRole)) ?></span>
                        </div>
                        <i class="bi bi-person-circle fs-4"></i>
                    </div>
                    
                    <ul class="custom-dropdown-menu" id="profileMenu">
                        <?php if($userRole === 'super_admin'): ?>
                            <li><a class="dropdown-item" href="<?= $base ?>admin/dashboard.php">Admin Panel</a></li>
                            <div class="dropdown-divider"></div>
                        <?php elseif($userRole === 'vendor'): ?>
                            <li><a class="dropdown-item" href="<?= $base ?>vendor/dashboard.php">Vendor Panel</a></li>
                            <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        
                        <li><a class="dropdown-item" href="<?= $base ?>orders.php">My Orders</a></li>
                        <li><a class="dropdown-item" href="<?= $base ?>settings.php">Settings</a></li>
                        <div class="dropdown-divider"></div>
                        <li><a class="dropdown-item text-danger fw-bold" href="<?= $base ?>logout.php">Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= $base ?>login.php" class="nav-login-white">Login</a>
                <a href="<?= $base ?>register.php" class="btn btn-theme-main rounded-pill px-4 fw-bold shadow-sm">Join Now</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function refreshHeaderCounts() {
    $.ajax({
        url: '<?= $base ?>includes/get_counts.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.cart_count > 0) { $('#cart-badge').text(data.cart_count).show(); } else { $('#cart-badge').hide(); }
            if (data.wishlist_count > 0) { $('#wishlist-badge').text(data.wishlist_count).show(); } else { $('#wishlist-badge').hide(); }
        }
    });
}
$(document).ready(function() {
    $('#profileToggle').on('click', function(e) { e.stopPropagation(); $('#profileMenu').fadeToggle(150); });
    $(document).on('click', function(e) { if (!$(e.target).closest('.position-relative').length) { $('#profileMenu').fadeOut(150); } });
});
</script>