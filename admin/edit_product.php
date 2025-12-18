<?php 
require '../config/db.php';
require '../includes/header.php';

if (($_SESSION['role'] ?? '') !== 'super_admin') { header("Location: ../login.php"); exit(); }

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: manage_products.php"); exit(); }

// 1. Fetch Product Basic Info
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

// 2. Fetch All Product Images
$img_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$img_stmt->execute([$id]);
$images = $img_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $name = $_POST['name'];
    $mrp = $_POST['mrp'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $status = $_POST['status'];
    $desc = $_POST['description'];

    $update = $pdo->prepare("UPDATE products SET name = ?, mrp = ?, price = ?, stock = ?, status = ?, description = ? WHERE id = ?");
    $update->execute([$name, $mrp, $price, $stock, $status, $desc, $id]);
    
    // Logic to update Cover Image if selected
    if(isset($_POST['cover_img_id'])){
        $pdo->prepare("UPDATE product_images SET is_cover = 0 WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("UPDATE product_images SET is_cover = 1 WHERE id = ?")->execute([$_POST['cover_img_id']]);
    }

    echo "<script>alert('Product Updated Successfully!'); window.location.href='manage_products.php';</script>";
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2"><?php include '../includes/admin_sidebar.php'; ?></div>
        <div class="col-md-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0">Edit Product: <?= htmlspecialchars($product['name']) ?></h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">Product Title</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $product['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="hidden" <?= $product['status'] == 'hidden' ? 'selected' : '' ?>>Hidden</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">MRP (₹)</label>
                                <input type="number" name="mrp" class="form-control" value="<?= $product['mrp'] ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Selling Price (₹)</label>
                                <input type="number" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Stock</label>
                                <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold small">Manage Images (Select Cover Image)</label>
                                <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded border">
                                    <?php foreach($images as $img): ?>
                                        <div class="text-center">
                                            <img src="../assets/products/<?= $img['image_path'] ?>" class="rounded d-block mb-2 shadow-sm border" style="width: 100px; height: 100px; object-fit: cover;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="cover_img_id" value="<?= $img['id'] ?>" <?= $img['is_cover'] ? 'checked' : '' ?>>
                                                <label class="form-check-label small">Cover</label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Description</label>
                                <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>

                            <div class="col-12">
                                <hr>
                                <button type="submit" name="update" class="btn btn-primary px-5">Update Product</button>
                                <a href="manage_products.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>