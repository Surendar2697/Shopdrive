<?php 
require '../config/db.php';
require '../includes/header.php';

if (($_SESSION['role'] ?? '') !== 'super_admin') { header("Location: ../login.php"); exit(); }

// Handle Delete
if(isset($_GET['delete'])){
    // The database FK 'ON DELETE CASCADE' will automatically remove entries in product_images
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage_products.php?msg=deleted");
}

// Fetch all products with Vendor Name, Category, and ONLY the Cover Image
$sql = "SELECT p.*, u.fullname as vendor_name, c.name as cat_name, pi.image_path 
        FROM products p 
        JOIN users u ON p.vendor_id = u.id 
        JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_cover = 1
        ORDER BY p.created_at DESC";
$products = $pdo->query($sql)->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2"><?php include '../includes/admin_sidebar.php'; ?></div>
        <div class="col-md-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">Global Inventory Manager</h5>
                    <a href="upload_product.php" class="btn btn-primary btn-sm">+ Add New Product</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Preview</th>
                                <th>Product Name</th>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Pricing (₹)</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $p): 
                                $discount = ($p['mrp'] > $p['price']) ? round((($p['mrp'] - $p['price']) / $p['mrp']) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <?php if($p['image_path']): ?>
                                        <img src="../assets/products/<?= $p['image_path'] ?>" width="55" height="55" class="rounded border shadow-sm object-fit-cover">
                                    <?php else: ?>
                                        <div class="bg-light rounded border d-flex align-items-center justify-content-center" style="width:55px; height:55px;"><i class="bi bi-image text-muted"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                                    <span class="badge bg-<?= $p['status'] == 'active' ? 'success' : 'secondary' ?> x-small"><?= strtoupper($p['status']) ?></span>
                                </td>
                                <td><small class="text-primary fw-bold"><?= htmlspecialchars($p['vendor_name']) ?></small></td>
                                <td><small class="text-muted"><?= htmlspecialchars($p['cat_name']) ?></small></td>
                                <td>
                                    <div class="fw-bold text-dark">₹<?= number_format($p['price'], 2) ?></div>
                                    <?php if($discount > 0): ?>
                                        <small class="text-muted text-decoration-line-through">₹<?= number_format($p['mrp'], 2) ?></small>
                                        <small class="text-danger ms-1"><?= $discount ?>% off</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $p['stock'] ?> pcs</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this product?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>