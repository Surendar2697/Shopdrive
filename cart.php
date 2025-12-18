<?php
require 'config/db.php';
require 'includes/header.php';

// 1. SECURITY: Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

$user_id = $_SESSION['user_id'];
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#2563eb';

// 2. FETCH CART ITEMS FROM DATABASE
$cart_query = "
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.mrp, p.stock, pi.image_path 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_cover = 1 
    WHERE c.user_id = ?
";
$stmt = $pdo->prepare($cart_query);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$total_mrp = 0;
$total_selling_price = 0;
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; font-family: 'Inter', sans-serif; }

    .cart-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0; }
    .cart-header { background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; }
    
    .product-img { width: 90px; height: 90px; object-fit: contain; background: #fff; border: 1px solid #f1f5f9; padding: 5px; }
    .item-row { border-bottom: 1px solid #f1f5f9; padding: 25px 20px; transition: 0.2s; }
    .item-row:last-child { border-bottom: none; }
    
    .price-main { font-weight: 800; color: #0f172a; font-size: 18px; }
    .price-old { font-size: 14px; color: #94a3b8; text-decoration: line-through; margin-left: 8px; }
    
    /* QTY Controls */
    .qty-wrapper { width: 120px; }
    .btn-qty { border: 1px solid #cbd5e1; background: #fff; font-weight: bold; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
    .btn-qty:hover { background: #f1f5f9; border-color: var(--primary-theme); color: var(--primary-theme); }
    .qty-input { width: 45px; height: 32px; border: 1px solid #cbd5e1; border-left: none; border-right: none; text-align: center; font-weight: 700; font-size: 14px; }

    .summary-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0; padding: 30px; position: sticky; top: 100px; }
    .summary-title { font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #1e293b; margin-bottom: 25px; display: block; }
    
    .btn-checkout { background: var(--primary-theme) !important; color: #fff !important; border-radius: 0; font-weight: 700; text-transform: uppercase; padding: 16px; width: 100%; border: none; letter-spacing: 1px; font-size: 14px; }
    .btn-checkout:hover { opacity: 0.9; transform: translateY(-1px); }
    
    .remove-btn { color: #ef4444; font-size: 11px; font-weight: 800; text-decoration: none; text-transform: uppercase; margin-top: 10px; display: inline-block; letter-spacing: 0.5px; }
    .remove-btn:hover { color: #dc2626; }
</style>

<div class="container mt-5 pb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="cart-card shadow-sm">
                <div class="cart-header">
                    Shopping Cart (<?= count($cart_items) ?> Products)
                </div>

                <?php if (empty($cart_items)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bag-x-fill display-1 text-muted opacity-25"></i>
                        <p class="mt-3 fw-bold text-muted">Your inventory bag is empty.</p>
                        <a href="index.php" class="btn btn-outline-dark rounded-0 px-4 mt-2 fw-bold text-uppercase">Back to Shop</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): 
                        $sub_mrp = (float)$item['mrp'] * (int)$item['quantity'];
                        $sub_total = (float)$item['price'] * (int)$item['quantity'];
                        $total_mrp += $sub_mrp;
                        $total_selling_price += $sub_total;
                    ?>
                    <div class="item-row d-flex flex-wrap align-items-center">
                        <img src="<?= $item['image_path'] ? 'assets/products/'.$item['image_path'] : 'assets/img/no-image.png' ?>" class="product-img me-4">
                        
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 15px;"><?= htmlspecialchars($item['name']) ?></h6>
                            <span class="badge bg-light text-dark border rounded-0 mb-3" style="font-size: 10px;">ID: #PRD-<?= $item['product_id'] ?></span>
                            
                            <div class="d-flex align-items-center">
                                <span class="price-main">₹<?= number_format($item['price'], 2) ?></span>
                                <?php if($item['mrp'] > $item['price']): ?>
                                    <span class="price-old">₹<?= number_format($item['mrp'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="qty-wrapper me-lg-5 my-3 my-lg-0">
                            <label class="d-block mb-1 text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">ADJUST QUANTITY</label>
                            <div class="d-flex align-items-center">
                                <button class="btn-qty qty-action" data-action="dec" data-id="<?= $item['cart_id'] ?>">-</button>
                                <input type="text" class="qty-input qty-display-<?= $item['cart_id'] ?>" value="<?= $item['quantity'] ?>" readonly>
                                <button class="btn-qty qty-action" data-action="inc" data-id="<?= $item['cart_id'] ?>">+</button>
                            </div>
                        </div>

                        <div class="text-end" style="min-width: 130px;">
                            <div class="fw-bold fs-5 text-dark">₹<?= number_format($sub_total, 2) ?></div>
                            <a href="remove_cart_item.php?id=<?= $item['cart_id'] ?>" class="remove-btn" onclick="return confirm('Remove item from cart?')">
                                <i class="bi bi-trash3 me-1"></i> Remove
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="summary-card shadow-sm">
                <span class="summary-title">Payment Details</span>
                
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Total MRP (Base Price)</span>
                    <span class="text-muted small">₹<?= number_format($total_mrp, 2) ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Discount Savings</span>
                    <span class="text-success fw-bold small">- ₹<?= number_format($total_mrp - $total_selling_price, 2) ?></span>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Shipping & Handling</span>
                    <span class="text-success fw-bold small">FREE</span>
                </div>

                <hr class="my-4 border-dashed">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="fw-bold text-dark h6 mb-0">Payable Amount</span>
                    <span class="h4 fw-bold mb-0" style="color: #000;">₹<?= number_format($total_selling_price, 2) ?></span>
                </div>

                <a href="checkout.php" class="btn btn-checkout shadow-sm <?= empty($cart_items) ? 'disabled' : '' ?>">
                    Proceed to Checkout <i class="bi bi-shield-lock ms-2"></i>
                </a>

                <div class="mt-4 pt-3 border-top text-center">
                    <div class="small text-muted fw-bold">
                        <i class="bi bi-check-circle-fill text-success me-1"></i> Razorpay Secure Payments
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).on('click', '.qty-action', function() {
    const cartId = $(this).data('id');
    const action = $(this).data('action');
    const btn = $(this);

    // Visual feedback
    btn.css('opacity', '0.5');

    $.ajax({
        url: 'update_cart_qty.php',
        method: 'POST',
        data: { cart_id: cartId, action: action },
        success: function(res) {
            if (res.status === 'success') {
                // To keep the professional "Global Manager" experience accurate,
                // we refresh the page to update all totals, savings, and badges.
                location.reload(); 
            } else {
                alert(res.message);
                btn.css('opacity', '1');
            }
        },
        error: function() {
            alert('Server error while updating quantity.');
            btn.css('opacity', '1');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>