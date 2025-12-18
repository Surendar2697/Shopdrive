<?php
require 'config/db.php';
require 'includes/header.php';

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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
?>

<style>
    .wishlist-container { background: #fff; border: 1px solid #e2e8f0; padding: 30px; margin-top: 30px; }
    .product-card { transition: 0.3s; border: 1px solid #eee; height: 100%; position: relative; }
    .product-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .remove-wishlist { 
        position: absolute; top: 10px; right: 10px; 
        color: #ff3e3e; cursor: pointer; background: #fff; 
        width: 30px; height: 30px; border-radius: 50%; 
        display: flex; align-items: center; justify-content: center;
        border: 1px solid #eee; z-index: 5;
    }
    .img-box { height: 180px; padding: 20px; display: flex; align-items: center; justify-content: center; }
    .img-box img { max-height: 100%; max-width: 100%; object-fit: contain; }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-uppercase m-0">My Wishlist <span class="text-muted small">(<?= count($wishlist_items) ?>)</span></h2>
        <a href="all_products.php" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold">CONTINUE SHOPPING</a>
    </div>

    <div class="row g-4" id="wishlist-grid">
        <?php if (empty($wishlist_items)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-heart text-muted opacity-25" style="font-size: 5rem;"></i>
                <h4 class="mt-3 fw-bold">Your wishlist is empty</h4>
                <p class="text-muted">Save items you like to see them here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 wishlist-item-row" id="wish-item-<?= $item['id'] ?>">
                    <div class="card product-card shadow-sm">
                        <div class="remove-wishlist wishlist-btn" data-id="<?= $item['id'] ?>" title="Remove Item">
                            <i class="bi bi-heart-fill"></i>
                        </div>

                        <div class="img-box">
                            <a href="product_details.php?id=<?= $item['id'] ?>">
                                <img src="assets/products/<?= $item['image_path'] ?: 'no-image.png' ?>" alt="<?= $item['name'] ?>">
                            </a>
                        </div>

                        <div class="card-body border-top p-3 text-center">
                            <small class="text-primary fw-bold text-uppercase" style="font-size: 10px;"><?= htmlspecialchars($item['cat_name']) ?></small>
                            <h6 class="fw-bold text-truncate mt-1 mb-2"><?= htmlspecialchars($item['name'] ) ?></h6>
                            <p class="fw-bold text-dark mb-3">₹<?= number_format($item['price'], 2) ?></p>
                            
                            <button class="btn btn-primary w-100 btn-sm fw-bold add-to-cart-btn" data-id="<?= $item['id'] ?>">
                                <i class="bi bi-cart-plus me-1"></i> ADD TO CART
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
    
    // 1. Wishlist Toggle (Removal Logic)
    $(document).on('click', '.wishlist-btn', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const container = $(this).closest('.wishlist-item-row');

        $.ajax({
            url: 'ajax_wishlist.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'removed') {
                    // Smoothly remove the item from the grid
                    container.fadeOut(300, function() { 
                        $(this).remove(); 
                        if ($('.wishlist-item-row').length === 0) {
                            location.reload(); // Show empty state if last item removed
                        }
                    });
                    // Update header badge if function exists in header.php
                    if(typeof updateHeaderWishlist === 'function') updateHeaderWishlist(res.wishlist_count);
                }
            }
        });
    });

    // 2. Add to Cart Logic
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const btn = $(this);
        const originalHtml = btn.html();

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    alert(res.message);
                    if(typeof updateCartCount === 'function') updateCartCount(res.cart_count);
                } else {
                    alert(res.message);
                }
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>