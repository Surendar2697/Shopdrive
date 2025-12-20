<?php
require_once 'config/db.php';

// Auth Guard
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get Primary Color from site settings
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#2563eb';

// Fetch Wishlist Items
$query = "SELECT p.*, pi.image_path, c.name as cat_name 
          FROM wishlist w 
          JOIN products p ON w.product_id = p.id 
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_cover = 1 
          WHERE w.user_id = ? 
          ORDER BY w.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; }

    /* Modern Minimalist Wishlist Card */
    .wish-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        transition: 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .wish-card:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border-color: var(--primary-theme);
    }

    /* Heart Button Logic */
    .wish-heart-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 10;
        background: #fff;
        color: #ff4d4d;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: none;
    }

    .wish-heart-btn:hover {
        transform: scale(1.15);
        color: #ff4d4d; /* Slight shift to red on hover for warning */
    }

    .wish-heart-btn i {
        font-size: 18px;
    }

    .wish-img-box {
        height: 220px;
        padding: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
    }

    .wish-img-box img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }

    .wish-body {
        padding: 20px;
        text-align: center;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .wish-category {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: #999;
        letter-spacing: 1.2px;
        margin-bottom: 5px;
    }

    .wish-title {
        font-size: 14px;
        font-weight: 700;
        color: #222;
        margin-bottom: 10px;
        height: 40px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .wish-price {
        font-size: 18px;
        font-weight: 800;
        color: #000;
        margin-bottom: 20px;
        margin-top: auto;
    }

    /* Add to Cart with Primary Color */
    .btn-cart-primary {
        background-color: var(--primary-theme);
        color: #fff;
        border: none;
        padding: 12px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        width: 100%;
        border-radius: 6px;
        transition: 0.3s;
    }

    .btn-cart-primary:hover {
        opacity: 0.9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        color: #fff;
    }

    /* Empty State Section */
    .wish-empty {
        padding: 100px 0;
        text-align: center;
    }
    .wish-empty i {
        font-size: 70px;
        color: var(--primary-theme);
        opacity: 0.1;
    }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-5 border-bottom pb-4">
        <div>
            <h2 class="fw-bold m-0">My Favorites</h2>
            <p class="text-muted small m-0">You have <?= count($wishlist_items) ?> items saved</p>
        </div>
        <a href="all_products.php" class="btn btn-outline-dark btn-sm rounded-pill px-4 fw-bold">SHOP ALL</a>
    </div>

    <div class="row g-4" id="wishlist-grid">
        <?php if (empty($wishlist_items)): ?>
            <div class="col-12 wish-empty">
                <i class="bi bi-heart-pulse"></i>
                <h4 class="fw-bold mt-4">Your wishlist is lonely</h4>
                <p class="text-muted">Start adding items you love to your personal collection.</p>
                <a href="all_products.php" class="btn btn-primary rounded-pill px-5 py-2 mt-2" style="background: var(--primary-theme); border:none;">Browse Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-lg-3 col-md-4 col-6 wishlist-item-row" id="wish-item-<?= $item['id'] ?>">
                    <div class="wish-card">
                        <button class="wish-heart-btn wishlist-btn" data-id="<?= $item['id'] ?>" title="Remove from Favorites">
                            <i class="bi bi-heart-fill"></i>
                        </button>

                        <div class="wish-img-box">
                            <a href="product_details.php?id=<?= $item['id'] ?>">
                                <img src="assets/products/<?= $item['image_path'] ?: 'no-image.png' ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            </a>
                        </div>

                        <div class="wish-body">
                            <div class="wish-category"><?= htmlspecialchars($item['cat_name']) ?></div>
                            <div class="wish-title"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="wish-price">₹<?= number_format($item['price'], 0) ?></div>
                            
                            <button class="btn-cart-primary add-to-cart-btn" data-id="<?= $item['id'] ?>">
                                <i class="bi bi-cart-plus me-2"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    // Wishlist Removal Logic (Heart Button)
    $(document).on('click', '.wishlist-btn', function() {
        const productId = $(this).data('id');
        const card = $(this).closest('.wishlist-item-row');
        const icon = $(this).find('i');

        // Visual feedback: change heart to outline before fading
        icon.removeClass('bi-heart-fill').addClass('bi-heart');

        $.ajax({
            url: 'ajax_wishlist.php',
            method: 'POST',
            data: { product_id: productId },
            success: function() {
                card.css('transform', 'scale(0.9)');
                card.fadeOut(350, function() {
                    $(this).remove();
                    if ($('.wishlist-item-row').length === 0) location.reload();
                });
            }
        });
    });

    // Add to Cart Logic
    $(document).on('click', '.add-to-cart-btn', function() {
        const productId = $(this).data('id');
        const btn = $(this);
        const originalContent = btn.html();

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    btn.html('<i class="bi bi-check2"></i> Added');
                    btn.css('background', '#10b981'); // Green success feedback
                    if(typeof updateCartCount === 'function') updateCartCount(res.cart_count);
                } else {
                    alert(res.message);
                    btn.prop('disabled', false).html(originalContent);
                }
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>