<?php
require 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$search = $_GET['search'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$user_id = $_SESSION['user_id'] ?? 0;

$params = [];
$query = "SELECT p.*, c.name as cat_name, pi.image_path 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN categories pc ON c.parent_id = pc.id
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_cover = 1 
          WHERE p.status = 'active' AND p.deleted_at IS NULL";

if ($search) {
    $query .= " AND (p.name LIKE ? OR c.name LIKE ? OR pc.name LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term];
}

switch ($sort) {
    case 'price_low': $query .= " ORDER BY p.price ASC"; break;
    case 'price_high': $query .= " ORDER BY p.price DESC"; break;
    case 'name_az': $query .= " ORDER BY p.name ASC"; break;
    default: $query .= " ORDER BY p.id DESC"; break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

foreach ($products as $p): 
    $price = (float)$p['price']; $mrp = (float)$p['mrp'];
    $discount = ($mrp > $price) ? round((($mrp - $price) / $mrp) * 100) : 0;
    
    // Check Wishlist Status
    $is_w = false;
    if($user_id) {
        $ck = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $ck->execute([$user_id, $p['id']]);
        if($ck->fetch()) $is_w = true;
    }
?>
    <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="inventory-card">
            <div class="img-frame">
                <div class="wishlist-btn" data-id="<?= $p['id'] ?>">
                    <i class="bi <?= $is_w ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                </div>
                
                <?php if($discount > 0): ?>
                    <span class="badge bg-danger position-absolute top-0 start-0 m-3 rounded-0"><?= $discount ?>% OFF</span>
                <?php endif; ?>
                <a href="product_details.php?id=<?= $p['id'] ?>">
                    <img src="assets/products/<?= $p['image_path'] ?: 'no-image.png' ?>">
                </a>
            </div>
            <div class="p-4 border-top bg-white flex-grow-1">
                <span class="small fw-bold text-uppercase text-primary"><?= htmlspecialchars($p['cat_name']) ?></span>
                <h6 class="fw-bold text-dark text-truncate mt-1 mb-3"><?= htmlspecialchars($p['name']) ?></h6>
                <div class="d-flex align-items-center mb-4">
                    <span class="fw-bold fs-5">₹<?= number_format($price, 2) ?></span>
                    <?php if($mrp > $price): ?><span class="text-muted text-decoration-line-through small ms-2">₹<?= number_format($mrp, 2) ?></span><?php endif; ?>
                </div>
                <button class="btn btn-luxe add-to-cart-btn" data-id="<?= $p['id'] ?>">ADD TO CART</button>
            </div>
        </div>
    </div>
<?php endforeach; ?>