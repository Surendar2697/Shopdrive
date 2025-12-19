<?php 
require_once '../config/db.php';

// 1. SECURITY & SESSION CHECK
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security Check: Only Vendors allowed
if (($_SESSION['role'] ?? '') !== 'vendor') {
    header("Location: ../login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

// Fetch settings for dynamic theme alignment
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#00bcd4';

// 2. HANDLE SOFT DELETE
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("UPDATE products SET deleted_at = NOW() WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$del_id, $vendor_id]);
    header("Location: manage_products.php?status=deleted");
    exit();
}

require_once '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; font-family: 'Segoe UI', sans-serif; }

    /* Layout Components */
    .manager-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .manager-header { padding: 1.25rem 1.5rem; background-color: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .manager-title { font-size: 13px; font-weight: 700; color: #333; margin: 0; text-transform: uppercase; }

    /* Table Styling */
    .custom-table thead th { 
        background-color: #fff; color: #888; font-weight: 700; 
        border-bottom: 2px solid #dee2e6; padding: 12px 20px; 
        font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .custom-table tbody td { padding: 15px 20px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; font-size: 14px; }

    /* Product Specific UI */
    .prod-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
    .stock-badge { font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: 2px; text-transform: uppercase; }
    
    .btn-action { 
        font-size: 11px; font-weight: 700; text-transform: uppercase; 
        text-decoration: none; padding: 5px 12px; border: 1px solid #ddd; 
        color: #555; transition: 0.2s; 
    }
    .btn-edit:hover { border-color: var(--primary-theme); color: var(--primary-theme); }
    .btn-delete:hover { border-color: #dc3545; color: #dc3545; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0 text-uppercase" style="letter-spacing: 1px;">Inventory Management</h5>
                <a href="upload_product.php" class="btn btn-sm btn-dark rounded-0 px-3 fw-bold" style="background: var(--primary-theme); border:none;">
                    + ADD NEW ITEM
                </a>
            </div>

            <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                <div class="alert alert-warning rounded-0 small py-2 border-0 shadow-sm mb-4">Product has been moved to archive.</div>
            <?php endif; ?>

            <div class="manager-card">
                <div class="manager-header">
                    <h2 class="manager-title">Active Listings</h2>
                </div>

                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th>Category</th>
                                <th>Price (₹)</th>
                                <th>Stock</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Fetch products with their primary image
                            $stmt = $pdo->prepare("
                                SELECT p.*, c.name as cat_name, 
                                (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_img 
                                FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.id 
                                WHERE p.vendor_id = ? AND p.deleted_at IS NULL 
                                ORDER BY p.created_at DESC
                            ");
                            $stmt->execute([$vendor_id]);
                            
                            while($row = $stmt->fetch()): 
                                $img_path = !empty($row['main_img']) ? "../assets/products/".$row['main_img'] : "../assets/img/placeholder.jpg";
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $img_path ?>" class="prod-thumb me-3 shadow-sm">
                                        <div>
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['name']) ?></span>
                                            <small class="text-muted">#PRD-<?= $row['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="small fw-bold text-muted text-uppercase"><?= htmlspecialchars($row['cat_name'] ?? 'Uncategorized') ?></span></td>
                                <td class="fw-bold">₹<?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <?php if($row['stock'] > 10): ?>
                                        <span class="stock-badge bg-light text-success border border-success">In Stock: <?= $row['stock'] ?></span>
                                    <?php elseif($row['stock'] > 0): ?>
                                        <span class="stock-badge bg-light text-warning border border-warning">Low: <?= $row['stock'] ?></span>
                                    <?php else: ?>
                                        <span class="stock-badge bg-light text-danger border border-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn-action btn-edit me-1">Edit</a>
                                    <a href="manage_products.php?delete_id=<?= $row['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Archive this product?')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>

                            <?php if($stmt->rowCount() == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-box2 display-4 d-block mb-3"></i>
                                        No products found. Start by adding your first item.
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

<?php require_once '../includes/footer.php'; ?>