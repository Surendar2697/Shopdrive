<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

// 1. Fetch Branding & Global Settings
$brand = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_color = $brand['primary_color'] ?? '#2563eb';
$header_bg = $brand['header_color'] ?? '#ffffff';
$app_name = $brand['app_name'] ?? 'Pro-Market';
$app_logo = $brand['app_logo'] ?? '';

$is_folder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/vendor/') !== false);
$base = $is_folder ? '../' : '';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['fullname'] ?? '';
$userRole = $_SESSION['role'] ?? '';

// 2. DYNAMIC COUNTS (Database-Driven)
$cart_count = 0;
$wishlist_count = 0;

if ($isLoggedIn) {
    // Cart Count
    $cStmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $cStmt->execute([$_SESSION['user_id']]);
    $cart_count = (int)$cStmt->fetchColumn();

    // Wishlist Count
    $wStmt = $pdo->prepare("SELECT COUNT(id) FROM wishlist WHERE user_id = ?");
    $wStmt->execute([$_SESSION['user_id']]);
    $wishlist_count = (int)$wStmt->fetchColumn();
}

// 3. Helper to determine text color
function getContrastColor($hexColor) {
    $hexColor = str_replace('#', '', $hexColor);
    if(strlen($hexColor) == 3) {
        $hexColor = $hexColor[0].$hexColor[0].$hexColor[1].$hexColor[1].$hexColor[2].$hexColor[2];
    }
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? '#000000' : '#ffffff';
}
$header_text = getContrastColor($header_bg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root { 
            --theme-color: <?= $primary_color ?>; 
            --header-bg: <?= $header_bg ?>;
            --header-text: <?= $header_text ?>;
        }
        
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }

        .navbar-custom { 
            background-color: var(--header-bg) !important; 
            border-bottom: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .navbar-custom .nav-link, .navbar-custom .navbar-brand { 
            color: var(--header-text) !important; 
        }

        .btn-primary { background-color: var(--theme-color) !important; border-color: var(--theme-color) !important; }

        /* Icon Container Styling */
        .header-icon-btn {
            color: var(--header-text) !important;
            padding: 8px;
            margin-right: 10px;
            position: relative;
            transition: 0.3s;
            display: inline-block;
        }
        
        /* Universal Badge Styling */
        .header-badge {
            position: absolute;
            top: 2px;
            right: -2px;
            font-size: 9px;
            font-weight: 800;
            background-color: #ff3e3e;
            color: #ffffff;
            border: 2px solid var(--header-bg);
            padding: 2px 5px;
            min-width: 18px;
            text-align: center;
        }

        .user-pill {
            border: 1px solid rgba(128,128,128,0.2);
            padding: 5px 15px;
            border-radius: 50px;
            background: rgba(128,128,128,0.05);
            color: var(--header-text) !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom py-3 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= $base ?>index.php">
            <?php if($app_logo): ?>
                <img src="<?= $base ?>assets/uploads/<?= $app_logo ?>" style="max-height: 35px;" class="me-2">
            <?php endif; ?>
            <?= htmlspecialchars($app_name) ?>
        </a>

        <div class="ms-auto d-flex align-items-center">
            
            <a href="<?= $base ?>wishlist.php" class="header-icon-btn text-decoration-none">
                <i class="bi bi-heart fs-4"></i>
                <?php if($wishlist_count > 0): ?>
                    <span class="badge rounded-pill header-badge wishlist-count-badge">
                        <?= $wishlist_count ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="<?= $base ?>cart.php" class="header-icon-btn text-decoration-none">
                <i class="bi bi-bag fs-4"></i>
                <?php if($cart_count > 0): ?>
                    <span class="badge rounded-pill header-badge cart-count-badge">
                        <?= $cart_count ?>
                    </span>
                <?php endif; ?>
            </a>

            <?php if($isLoggedIn): ?>
                <div class="dropdown">
                    <a href="#" class="user-pill d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="me-2 d-none d-md-block text-end">
                            <div class="small fw-bold mb-0" style="line-height: 1;"><?= htmlspecialchars(explode(' ', $userName)[0]) ?></div>
                            <span style="font-size: 10px; opacity: 0.8;"><?= strtoupper($userRole) ?></span>
                        </div>
                        <i class="bi bi-person-circle fs-4"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 rounded-0">
                        <li><a class="dropdown-item py-2" href="<?= $base ?><?= $userRole ?>/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                        <li><a class="dropdown-item py-2" href="<?= $base ?>wishlist.php"><i class="bi bi-heart me-2"></i> My Wishlist</a></li>
                        <li><a class="dropdown-item py-2" href="<?= $base ?>orders.php"><i class="bi bi-receipt me-2"></i> My Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger py-2 fw-bold" href="<?= $base ?>logout.php"><i class="bi bi-power me-2"></i> Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= $base ?>login.php" class="nav-link px-3 fw-bold border-end me-2" style="font-size: 14px;">Login</a>
                <a href="<?= $base ?>register.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="font-size: 14px;">Join Now</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
/**
 * Update the header wishlist badge instantly after AJAX toggle
 */
function updateHeaderWishlist(count) {
    let badge = $('.wishlist-count-badge');
    if(count > 0) {
        if(badge.length) {
            badge.text(count);
        } else {
            $('.bi-heart').after('<span class="badge rounded-pill header-badge wishlist-count-badge">'+count+'</span>');
        }
    } else {
        badge.remove();
    }
}
</script>