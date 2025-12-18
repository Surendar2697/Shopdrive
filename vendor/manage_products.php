<?php 
require '../config/db.php';

// 1. SECURITY & SESSION CHECK
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();

// 2. SOFT DELETE LOGIC
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Verify ownership before action
    $check = $pdo->prepare("SELECT id FROM products WHERE id = ? AND vendor_id = ?");
    $check->execute([$delete_id, $vendor_id]);
    
    if ($check->fetch()) {
        // Mark as deleted without removing from DB
        $stmt = $pdo->prepare("UPDATE products SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: manage_products.php?success=deleted");
        exit();
    }
}

// 3. FETCH PRODUCTS (Filtered to exclude soft-deleted items)
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.vendor_id = ? AND p.deleted_at IS NULL 
        ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$vendor_id]);
$products = $stmt->fetchAll();

require '../includes/header.php'; 
?>

<style>
    /* DYNAMIC THEME ALIGNMENT */
    :root { --primary-theme: <?= $settings['primary_color'] ?? '#00bcd4' ?>; }
    
    body { background-color: #fcfcfc; font-family: 'Segoe UI', Roboto, sans-serif; }

    .manager-card {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-top: 2rem;
    }

    .manager-header {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
        background-color: #fff;
    }

    .manager-title { font-size: 1.25rem; font-weight: 700; color: #333; margin: 0; text-transform: uppercase; }

    .btn-add-custom {
        background-color: var(--primary-theme) !important;
        color: white !important;
        border: none;
        border-radius: 0;
        font-weight: 700;
        padding: 10px 20px;
        font-size: 13px;
        text-transform: uppercase;
        text-decoration: none;
    }

    /* Professional Table Header & Rows */
    .custom-table thead th {
        background-color: #f8f9fa;
        color: #000;
        font-weight: 700;
        border-bottom: 2px solid #dee2e6;
        padding: 15px 20px;
        font-size: 13px;
        text-transform: uppercase;
    }

    .custom-table tbody td {
        padding: 18px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
        font-size: 14px;
        color: #444;
    }

    /* Product Preview Styling */
    .product-preview-box {
        width: 55px;
        height: 55px;
        border-radius: 4px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #f8f9fa;
    }

    .badge-status {
        font-size: 10px;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        display: inline-block;
        margin-top: 5px;
    }
    .status-active { background-color: #198754; color: white; }

    /* Price Formatting */
    .price-group .main-price { font-weight: 700; color: #000; display: block; }
    .price-group .old-price { font-size: 12px; color: #999; text-decoration: line-through; }
    .price-group .discount-tag { font-size: 11px; color: #dc3545; font-weight: 700; margin-left: 5px; }

    /* Action Icon Styling */
    .btn-action-outline {
        border: 1px solid #ced4da;
        background: white;
        padding: 6px 12px;
        border-radius: 0;
        color: var(--primary-theme);
        transition: 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-action-outline.delete { color: #dc3545; margin-left: 5px; }
    .btn-action-outline:hover { background: #f8f9fa; border-color: #adb5bd; }
</style>

<div class="container-fluid px-4 pb-5">
    <div class="row">
        <div class="col-md-2 py-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 rounded-0 small">
                    <i class="bi bi-check-circle-fill me-2"></i> 
                    Operation completed: Product listing updated.
                </div>
            <?php endif; ?>

            <div class="manager-card">
                <div class="manager-header">
                    <h1 class="manager-title">Global Inventory Manager</h1>
                    <a href="add_product.php" class="btn btn-add-custom shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Add New Product
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Product Details</th>
                                <th>Category</th>
                                <th>Pricing (₹)</th>
                                <th>Stock</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $p): 
                                // Fetch Cover Image
                                $img_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_cover = 1 LIMIT 1");
                                $img_stmt->execute([$p['id']]);
                                $cover = $img_stmt->fetchColumn();
                                $image_src = $cover ? "../assets/products/".$cover : "../assets/img/no-image.png";
                                
                                // Calculate Savings Percentage
                                $discount = ($p['mrp'] > 0) ? round((($p['mrp'] - $p['price']) / $p['mrp']) * 100) : 0;
                            ?>
                            <tr>
                                <td width="80">
                                    <img src="<?= $image_src ?>" class="product-preview-box" alt="Product">
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="letter-spacing: -0.2px;"><?= htmlspecialchars($p['name']) ?></div>
                                    <span class="badge-status status-active">Active</span>
                                </td>
                                <td>
                                    <span class="text-muted small fw-bold text-uppercase"><?= htmlspecialchars($p['category_name'] ?? 'General') ?></span>
                                </td>
                                <td>
                                    <div class="price-group">
                                        <span class="main-price">₹<?= number_format($p['price'], 2) ?></span>
                                        <span class="old-price">₹<?= number_format($p['mrp'], 2) ?></span>
                                        <?php if($discount > 0): ?>
                                            <span class="discount-tag"><?= $discount ?>% OFF</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold"><?= $p['stock'] ?></span> <small class="text-muted">pcs</small>
                                </td>
                                <td class="text-end">
                                    <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn-action-outline" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="?delete=<?= $p['id'] ?>" class="btn-action-outline delete" onclick="return confirm('Move this product to trash? Item can be restored by admin.')" title="Trash">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if(empty($products)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-box2 fs-1 text-muted opacity-25 d-block"></i>
                                        <p class="text-muted mt-2">No active products in your inventory.</p>
                                        <a href="add_product.php" class="text-primary fw-bold text-decoration-none small">List your first product →</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>