<?php
require 'config/db.php';
require 'includes/header.php';

// 1. VALIDATE PRODUCT ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? 0;
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#2563eb';

// 2. FETCH PRODUCT & VENDOR
$sql = "SELECT p.*, c.name as category_name, u.fullname as vendor_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.vendor_id = u.id
        WHERE p.id = ? AND p.status = 'active' AND p.deleted_at IS NULL";
$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);
$p = $stmt->fetch();

if (!$p) {
    echo "<div class='container py-5 text-center'><h3>Inventory Item Not Found</h3><a href='index.php'>Return to Dashboard</a></div>";
    include 'includes/footer.php';
    exit();
}

// 3. FETCH IMAGES & WISHLIST STATUS
$img_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_cover DESC");
$img_stmt->execute([$product_id]);
$images = $img_stmt->fetchAll();

$is_wishlisted = false;
if ($user_id) {
    $w_check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $w_check->execute([$user_id, $product_id]);
    if ($w_check->fetch()) $is_wishlisted = true;
}

$mrp = (float)$p['mrp'];
$price = (float)$p['price'];
$discount = ($mrp > 0) ? round((($mrp - $price) / $mrp) * 100) : 0;
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fdfdfd; font-family: 'Inter', sans-serif; }

    /* --- GALLERY & ZOOM --- */
    .product-gallery-sticky { position: sticky; top: 100px; }
    .main-stage {
        background: #fff; border: 1px solid #f1f5f9; position: relative;
        height: 550px; display: flex; align-items: center; justify-content: center;
        overflow: hidden; cursor: none;
    }
    .main-stage img { max-height: 90%; max-width: 90%; object-fit: contain; }
    .zoom-lens {
        position: absolute; width: 180px; height: 180px; border: 3px solid #fff;
        border-radius: 50%; background-repeat: no-repeat; visibility: hidden;
        pointer-events: none; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .thumb-strip { gap: 12px; margin-top: 20px; overflow-x: auto; padding-bottom: 10px; }
    .thumb-item { width: 80px; height: 80px; border: 1.5px solid #e2e8f0; padding: 5px; cursor: pointer; transition: 0.3s; background: #fff; }
    .thumb-item.active { border-color: var(--primary-theme); transform: scale(1.05); }

    /* --- ACTION BUTTONS STYLE --- */
    .product-actions { display: flex; align-items: center; gap: 12px; margin-top: 35px; }

    .btn-add-cart {
        background: #4a0404 !important; /* Deep Maroon/Brown from image */
        color: #fff !important; border: none; flex: 1; padding: 16px;
        font-weight: 700; text-transform: uppercase; font-size: 14px;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        transition: 0.3s;
    }

    .wishlist-group {
        display: flex; align-items: center; border: 1px solid #a3a3a3;
        background: #fdf2f2; cursor: pointer; transition: 0.3s; height: 55px;
    }
    .wishlist-icon-box {
        padding: 0 18px; border-right: 1px solid #a3a3a3; height: 100%;
        display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .wishlist-text-box { padding: 0 20px; font-weight: 700; color: #4a0404; font-size: 14px; }

    .wishlist-group.active { background: #fee2e2; border-color: #ef4444; }
    .wishlist-group.active .wishlist-icon-box { border-color: #ef4444; }

    /* --- INFO PANEL --- */
    .brand-tag { font-weight: 800; font-size: 11px; color: var(--primary-theme); text-transform: uppercase; letter-spacing: 2px; }
    .product-title { font-weight: 700; color: #1e293b; font-size: 32px; letter-spacing: -0.5px; }
    .price-box { background: #fff; border: 1px solid rgba(0,0,0,0.05); padding: 25px; margin: 25px 0; position: relative;}
    .current-price { font-size: 38px; font-weight: 800; color: #000; }
    .old-price { font-size: 18px; color: #94a3b8; text-decoration: line-through; margin-left: 10px; }
    .discount-label { font-size: 12px; background: #fee2e2; color: #ef4444; padding: 4px 10px; font-weight: 700; position: absolute; right: 25px; top: 35%;}
</style>

<div class="container mt-5 pb-5">
    <div class="row g-5">
        <div class="col-lg-6">
            <div class="product-gallery-sticky">
                <div class="main-stage shadow-sm" id="zoomContainer">
                    <div id="lens" class="zoom-lens"></div>
                    <img id="mainDisplay" src="assets/products/<?= $images[0]['image_path'] ?? 'no-image.png' ?>">
                </div>
                <div class="d-flex thumb-strip">
                    <?php foreach($images as $index => $img): ?>
                        <div class="thumb-item <?= ($index == 0) ? 'active' : '' ?>" onclick="updateGallery(this, 'assets/products/<?= $img['image_path'] ?>')">
                            <img src="assets/products/<?= $img['image_path'] ?>" class="img-fluid h-100 w-100 object-fit-contain">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2 fw-bold small">
                    <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">STORE</a> / <?= strtoupper($p['category_name']) ?></li>
                </ol>
            </nav>

            <span class="brand-tag">Verified Inventory</span>
            <h1 class="product-title"><?= htmlspecialchars($p['name']) ?></h1>
            
            <div class="d-flex align-items-center mb-4 text-warning small">
                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                <span class="ms-2 text-muted fw-bold">(4.9/5 Quality Score)</span>
            </div>

            <div class="price-box shadow-sm">
                <span class="current-price">₹<?= number_format($price, 2) ?></span>
                <?php if($mrp > $price): ?>
                    <span class="old-price">₹<?= number_format($mrp, 2) ?></span>
                    <span class="discount-label"><?= $discount ?>% OFF</span>
                <?php endif; ?>
                <div class="mt-2 small text-success fw-bold">
                    <i class="bi bi-check-circle-fill me-1"></i> Quality Inspected & Secure Shipping
                </div>
            </div>

            <h6 class="fw-bold small text-uppercase mb-3">Item Description</h6>
            <p class="text-muted"><?= nl2br(htmlspecialchars($p['description'])) ?></p>

            <div class="product-actions">
                <button class="btn btn-add-cart add-to-cart-btn" data-id="<?= $p['id'] ?>">
                    <i class="bi bi-cart-fill"></i> ADD TO CART
                </button>

                <div class="wishlist-group wishlist-btn <?= $is_wishlisted ? 'active' : '' ?>" data-id="<?= $p['id'] ?>">
                    <div class="wishlist-icon-box">
                        <i class="bi <?= $is_wishlisted ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                    </div>
                    <div class="wishlist-text-box">Wishlist</div>
                </div>
            </div>

            <div class="row mt-5 g-3">
                <div class="col-6 border-start ps-3"><span class="brand-tag d-block">Vendor</span><b><?= htmlspecialchars($p['vendor_name']) ?></b></div>
                <div class="col-6 border-start ps-3"><span class="brand-tag d-block">Inventory</span><b class="text-success"><?= $p['stock'] ?> Units Available</b></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// GALLERY LOGIC
function initZoom(containerID, lensID) {
    const container = document.getElementById(containerID);
    const lens = document.getElementById(lensID);
    const img = container.querySelector('img');
    container.addEventListener("mousemove", (e) => {
        lens.style.backgroundImage = `url('${img.src}')`;
        const zoom = 2.5;
        lens.style.backgroundSize = (img.width * zoom) + "px " + (img.height * zoom) + "px";
        const rect = img.getBoundingClientRect();
        let x = e.clientX - rect.left, y = e.clientY - rect.top;
        lens.style.left = (e.clientX - container.getBoundingClientRect().left - 90) + "px";
        lens.style.top = (e.clientY - container.getBoundingClientRect().top - 90) + "px";
        lens.style.backgroundPosition = `-${(x * zoom) - 90}px -${(y * zoom) - 90}px`;
    });
    container.addEventListener("mouseenter", () => lens.style.visibility = "visible");
    container.addEventListener("mouseleave", () => lens.style.visibility = "hidden");
}

function updateGallery(element, path) {
    document.getElementById('mainDisplay').src = path;
    document.querySelectorAll('.thumb-item').forEach(item => item.classList.remove('active'));
    element.classList.add('active');
}

$(document).ready(function() {
    initZoom("zoomContainer", "lens");

    // WISHLIST TOGGLE AJAX
    $(document).on('click', '.wishlist-btn', function() {
        const btn = $(this);
        const icon = btn.find('i');
        $.ajax({
            url: 'ajax_wishlist.php',
            method: 'POST',
            data: { product_id: btn.data('id') },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'added') {
                    btn.addClass('active');
                    icon.removeClass('bi-heart').addClass('bi-heart-fill text-danger');
                } else if (res.status === 'removed') {
                    btn.removeClass('active');
                    icon.removeClass('bi-heart-fill text-danger').addClass('bi-heart');
                } else if (res.message === 'login_required') {
                    window.location.href = 'login.php';
                }
                if(typeof updateHeaderWishlist === 'function') updateHeaderWishlist(res.wishlist_count);
            }
        });
    });
});
</script>

<?php 
include 'includes/cart_scripts.php';
include 'includes/footer.php'; 
?>