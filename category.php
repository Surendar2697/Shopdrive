<?php 
require 'config/db.php';
require 'includes/header.php';

// 1. VALIDATE & FETCH CATEGORY
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$cat_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? 0; // Get user ID for wishlist check

$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_color = $settings['primary_color'] ?? '#2563eb';

$cat_stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$cat_stmt->execute([$cat_id]);
$current_category = $cat_stmt->fetch();

if (!$current_category) {
    header("Location: index.php");
    exit();
}

// 2. FETCH PRODUCTS
$product_sql = "
    SELECT p.*, pi.image_path, c.name as cat_name
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_cover = 1 
    JOIN categories c ON p.category_id = c.id
    WHERE (p.category_id = ? OR c.parent_id = ?) 
    AND p.status = 'active' AND p.deleted_at IS NULL
    ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($product_sql);
$stmt->execute([$cat_id, $cat_id]);
$products = $stmt->fetchAll();
?>

<style>
    :root { --main-color: <?= $primary_color ?>; }
    body { background-color: #fcfcfc; }

    /* Breadcrumb styling */
    .breadcrumb-box { background: #fff; border-bottom: 1px solid #eee; padding: 15px 0; margin-bottom: 30px; }
    .breadcrumb-item a { color: var(--main-color); text-decoration: none; font-weight: 600; font-size: 13px; }
    .breadcrumb-item.active { font-size: 13px; font-weight: 700; color: #333; text-transform: uppercase; }

    /* Product Card Professional Polish */
    .product-card { 
        transition: all 0.3s ease; 
        border: 1px solid #e2e8f0 !important;
        background: #fff;
        border-radius: 0;
        position: relative;
    }
    .product-card:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 12px 24px rgba(0,0,0,0.06) !important; 
    }
    
    .price-tag { color: var(--main-color); font-weight: 800; font-size: 1.2rem; }
    
    .btn-buy {
        background-color: var(--main-color) !important;
        color: white !important;
        border: none;
        border-radius: 0;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
        transition: 0.3s;
    }
    .btn-buy:hover { opacity: 0.9; }

    .discount-badge {
        background-color: #dc3545;
        color: white;
        font-size: 10px;
        font-weight: 800;
        padding: 4px 8px;
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 2;
    }

    /* WISHLIST HEART DESIGN */
    .wishlist-btn {
        position: absolute;
        right: 15px;
        bottom: 15px; /* Right bottom of the image frame */
        background: white;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        border: 1px solid #eee;
        cursor: pointer;
        z-index: 10;
        transition: 0.2s;
    }
    .wishlist-btn:hover { transform: scale(1.1); }
    .wishlist-btn i { font-size: 1.1rem; transition: transform 0.2s ease; }
</style>

<div class="breadcrumb-box shadow-sm">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">HOME</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($current_category['name']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <div class="col-lg-3 d-none d-lg-block position-relative">
            <?php include 'includes/common_sidebar.php'; ?>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-3">
                <div>
                    <h4 class="fw-bold text-uppercase m-0" style="letter-spacing: 1px;"><?= htmlspecialchars($current_category['name']) ?></h4>
                    <p class="text-muted small mb-0">Browsing all verified inventory in this category</p>
                </div>
                <div class="text-end">
                    <span class="small fw-bold text-muted"><?= count($products) ?> ITEMS FOUND</span>
                </div>
            </div>

            <div class="row g-4">
                <?php if(empty($products)): ?>
                    <div class="col-12 text-center py-5 bg-white border shadow-sm">
                        <i class="bi bi-search display-1 text-muted opacity-25"></i>
                        <p class="mt-3 text-muted">No products found for this category yet.</p>
                        <a href="index.php" class="btn btn-outline-dark rounded-0 px-4 fw-bold">CONTINUE SHOPPING</a>
                    </div>
                <?php else: ?>
                    <?php foreach($products as $p): 
                        $mrp = (float)$p['mrp'];
                        $price = (float)$p['price'];
                        $discount = ($mrp > $price) ? round((($mrp - $price) / $mrp) * 100) : 0;

                        // Check Wishlist Status
                        $is_w = false;
                        if($user_id) {
                            $ck = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                            $ck->execute([$user_id, $p['id']]);
                            if($ck->fetch()) $is_w = true;
                        }
                    ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100 product-card">
                            
                            <?php if($discount > 0): ?>
                                <span class="discount-badge shadow-sm"><?= $discount ?>% OFF</span>
                            <?php endif; ?>

                            <div class="p-3 text-center bg-white position-relative" style="height: 220px;">
                                <div class="wishlist-btn" data-id="<?= $p['id'] ?>">
                                    <i class="bi <?= $is_w ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                                </div>

                                <a href="product_details.php?id=<?= $p['id'] ?>">
                                    <img src="<?= $p['image_path'] ? 'assets/products/'.$p['image_path'] : 'assets/img/no-image.png' ?>" 
                                         class="img-fluid" style="height: 100%; object-fit: contain;" alt="<?= $p['name'] ?>">
                                </a>
                            </div>

                            <div class="card-body border-top bg-white">
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;"><?= htmlspecialchars($p['cat_name']) ?></small>
                                <h6 class="fw-bold text-dark text-truncate mb-2 mt-1">
                                    <?= htmlspecialchars($p['name']) ?>
                                </h6>
                                
                                <div class="mb-3">
                                    <span class="price-tag">₹<?= number_format($price, 2) ?></span>
                                    <?php if($mrp > $price): ?>
                                        <span class="text-muted text-decoration-line-through ms-2 small">₹<?= number_format($mrp, 2) ?></span>
                                    <?php endif; ?>
                                </div>

                                <button class="btn btn-buy w-100 py-2 add-to-cart-btn" data-id="<?= $p['id'] ?>">
                                    <i class="bi bi-cart-plus me-1"></i> ADD TO CART
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // WISHLIST TOGGLE AJAX
    $(document).on('click', '.wishlist-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const pid = btn.data('id');
        const icon = btn.find('i');

        $.ajax({
            url: 'ajax_wishlist.php',
            method: 'POST',
            data: { product_id: pid },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'added') {
                    icon.removeClass('bi-heart').addClass('bi-heart-fill text-danger');
                    icon.css('transform', 'scale(1.3)');
                    setTimeout(() => icon.css('transform', 'scale(1)'), 200);
                } else if (res.status === 'removed') {
                    icon.removeClass('bi-heart-fill text-danger').addClass('bi-heart');
                } else if (res.message === 'login_required') {
                    window.location.href = 'login.php';
                }
            }
        });
    });

    // ADD TO CART AJAX (Already handled by cart_scripts.php, but included if needed)
});
</script>

<?php include 'includes/cart_scripts.php'; ?>
<?php include 'includes/footer.php'; ?>