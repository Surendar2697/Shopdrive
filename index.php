<?php 
require 'config/db.php';
require 'includes/header.php';

// 1. Fetch Global Settings
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_color = $settings['primary_color'] ?? '#2563eb';
$user_id = $_SESSION['user_id'] ?? 0;

// 2. Fetch Active Slide Banners
$banners = $pdo->query("SELECT * FROM site_banners ORDER BY sort_order ASC")->fetchAll();

// 3. Fetch Latest Products
$product_sql = "
    SELECT p.*, c.name as cat_name, pi.image_path 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_cover = 1 
    WHERE p.status = 'active' AND p.deleted_at IS NULL
    ORDER BY p.created_at DESC 
    LIMIT 12";
$products = $pdo->query($product_sql)->fetchAll();
?>

<style>
    :root { --main-color: <?= $primary_color ?>; }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }

    /* --- CREATIVE SLIDESHOW (HERO) --- */
    .hero-wrapper { border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    
    .carousel-item { height: 480px; background: #000; position: relative; }
    .carousel-item img { 
        height: 100%; object-fit: cover; opacity: 0.75; 
        transition: transform 6s ease-in-out; 
    }
    .carousel-item.active img { transform: scale(1.15); }

    .glass-caption {
        position: absolute; left: 6%; top: 50%; transform: translateY(-50%);
        max-width: 460px; background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 35px; border-radius: 28px; color: #fff; z-index: 10;
        animation: fadeInSlide 0.8s ease-out forwards;
    }
    @keyframes fadeInSlide { from { opacity: 0; transform: translateY(-40%); } to { opacity: 1; transform: translateY(-50%); } }

    .caption-tag { 
        background: #fff; color: var(--main-color); font-size: 11px; 
        font-weight: 800; padding: 4px 12px; border-radius: 50px; 
        display: inline-block; margin-bottom: 15px; text-transform: uppercase;
    }
    .caption-title { font-size: 42px; font-weight: 900; line-height: 1.1; margin-bottom: 15px; }
    .btn-hero { 
        background: var(--main-color); color: #fff; border: none; 
        padding: 12px 30px; border-radius: 50px; font-weight: 700; 
        transition: 0.3s; text-decoration: none; display: inline-block;
    }
    .btn-hero:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); color: #fff; }

    /* --- PRODUCT CARD STYLE --- */
    .product-card { 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        border: 1px solid #e2e8f0 !important; background: #fff;
        border-radius: 16px; position: relative; height: 100%;
        display: flex; flex-direction: column; overflow: hidden;
    }
    .product-card:hover { transform: translateY(-10px); box-shadow: 0 20px 30px rgba(0,0,0,0.06) !important; border-color: var(--main-color); }
    
    .price-tag { color: var(--main-color); font-weight: 800; font-size: 1.25rem; }
    .btn-cart-luxe { 
        background: var(--main-color); color: white; border: none; 
        border-radius: 12px; font-weight: 700; padding: 10px; width: 100%; transition: 0.3s;
    }
    .btn-cart-luxe:hover { opacity: 0.9; }

    /* --- WISHLIST HEART --- */
    .wishlist-btn {
        position: absolute; right: 15px; top: 15px; 
        background: #fff; width: 38px; height: 38px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: none;
        cursor: pointer; z-index: 10; transition: 0.3s;
    }
    .wishlist-btn:hover { transform: scale(1.15); background: #fdf2f2; }
    .wishlist-btn i { transition: 0.2s; font-size: 1.1rem; }
</style>

<div class="container mt-4 pb-5">
    <div class="row g-4">
        <div class="col-lg-3 d-none d-lg-block">
            <?php include 'includes/common_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <?php if(!empty($banners)): ?>
            <div class="hero-wrapper mb-5">
                <div id="homeCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach($banners as $index => $b): ?>
                            <div class="carousel-item <?= ($index == 0) ? 'active' : '' ?>">
                                <img src="assets/banners/<?= $b['banner_image'] ?>" class="d-block w-100">
                                <div class="glass-caption d-none d-md-block">
                                    <span class="caption-tag">Global Selection</span>
                                    <h2 class="caption-title">Modern Gear. <br>Minimal Effort.</h2>
                                    <p class="opacity-75 mb-4">Curated products from verified global vendors, delivered directly to your door with end-to-end security.</p>
                                    <a href="all_products.php" class="btn btn-hero">START EXPLORING</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                <div>
                    <h4 class="fw-bold m-0 text-dark">Trending Inventory</h4>
                    <small class="text-muted">Discover what's hot right now</small>
                </div>
                <a href="all_products.php" class="fw-bold text-decoration-none small" style="color: var(--main-color);">VIEW CATALOG <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="row g-4">
                <?php foreach($products as $p): 
                    $mrp = (float)$p['mrp'];
                    $price = (float)$p['price'];
                    $is_w = false;
                    if($user_id) {
                        $ck = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                        $ck->execute([$user_id, $p['id']]);
                        if($ck->fetch()) $is_w = true;
                    }
                ?>
                <div class="col-md-4 col-sm-6">
                    <div class="product-card">
                        <div class="wishlist-btn" data-id="<?= $p['id'] ?>">
                            <i class="bi <?= $is_w ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                        </div>

                        <div class="p-4 text-center bg-white" style="height: 240px;">
                            <a href="product_details.php?id=<?= $p['id'] ?>">
                                <img src="<?= $p['image_path'] ? 'assets/products/'.$p['image_path'] : 'assets/img/no-image.png' ?>" 
                                     class="img-fluid" style="height: 100%; object-fit: contain;">
                            </a>
                        </div>

                        <div class="card-body p-4 pt-0">
                            <span class="text-uppercase fw-bold opacity-50 mb-1 d-block" style="font-size: 10px; letter-spacing: 1px;"><?= htmlspecialchars($p['cat_name']) ?></span>
                            <h6 class="fw-bold text-dark text-truncate mb-3"><?= htmlspecialchars($p['name']) ?></h6>
                            
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="price-tag">₹<?= number_format($price, 2) ?></span>
                                <?php if($mrp > $price): ?>
                                    <span class="text-muted text-decoration-line-through small">₹<?= number_format($mrp, 2) ?></span>
                                <?php endif; ?>
                            </div>

                            <button class="btn btn-cart-luxe add-to-cart-btn" data-id="<?= $p['id'] ?>">
                                <i class="bi bi-cart-plus me-2"></i> ADD TO CART
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    // 1. ADD TO CART AJAX
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { product_id: btn.data('id') },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    alert(res.message);
                    if(typeof updateCartCount === 'function') updateCartCount(res.cart_count);
                } else if (res.message === 'login_required') {
                    window.location.href = 'login.php';
                }
            },
            complete: function() { btn.prop('disabled', false).html(originalHtml); }
        });
    });

    // 2. WISHLIST TOGGLE AJAX
    $(document).on('click', '.wishlist-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const icon = btn.find('i');

        $.ajax({
            url: 'ajax_wishlist.php',
            method: 'POST',
            data: { product_id: btn.data('id') },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'added') {
                    icon.removeClass('bi-heart').addClass('bi-heart-fill text-danger');
                    icon.css('transform', 'scale(1.4)');
                    setTimeout(() => icon.css('transform', 'scale(1)'), 200);
                } else if (res.status === 'removed') {
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

<?php include 'includes/footer.php'; ?>