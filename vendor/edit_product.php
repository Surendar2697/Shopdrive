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

// 2. FETCH PRODUCT DATA
if (!isset($_GET['id'])) { header("Location: manage_products.php"); exit(); }
$product_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
$stmt->execute([$product_id, $vendor_id]);
$product = $stmt->fetch();

if (!$product) { die("Product not found or access denied."); }

// 3. FETCH CATEGORIES & IMAGES
$parent_categories = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC")->fetchAll();
$current_images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$current_images->execute([$product_id]);
$images = $current_images->fetchAll();

// 4. UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $mrp = $_POST['mrp'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];

    $update_sql = "UPDATE products SET name = ?, category_id = ?, mrp = ?, price = ?, stock = ?, description = ? WHERE id = ? AND vendor_id = ?";
    $pdo->prepare($update_sql)->execute([$name, $category_id, $mrp, $price, $stock, $description, $product_id, $vendor_id]);

    // Handle New Image Uploads
    if (!empty($_FILES['product_images']['name'][0])) {
        $target_dir = "../assets/products/";
        foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
            $file_name = time() . "_" . $key . "_" . str_replace(' ', '_', $_FILES['product_images']['name'][$key]);
            if (move_uploaded_file($tmp_name, $target_dir . $file_name)) {
                $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_cover) VALUES (?, ?, 0)")->execute([$product_id, $file_name]);
            }
        }
    }
    header("Location: manage_products.php?success=updated");
    exit();
}

require '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $settings['primary_color'] ?? '#00bcd4' ?>; }
    body { background-color: #fcfcfc; font-family: 'Segoe UI', Roboto, sans-serif; }

    .manager-card { 
        background: #fff; 
        border: 1px solid #e0e0e0; 
        border-radius: 4px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
        margin-top: 2rem; 
    }

    .manager-header { 
        padding: 1.5rem; 
        border-bottom: 1px solid #eee; 
        background-color: #f8f9fa; 
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .form-label { font-weight: 700; font-size: 13px; color: #444; text-transform: uppercase; margin-bottom: 8px; }
    
    .form-control, .form-select { 
        border-radius: 0; 
        border: 1px solid #ced4da; 
        padding: 12px; 
        font-size: 14px; 
    }

    /* Primary Save Button */
    .btn-update { 
        background-color: var(--primary-theme) !important; 
        color: white !important; 
        border: none; 
        font-weight: 700; 
        padding: 12px 35px; 
        text-transform: uppercase;
        border-radius: 0;
    }

    /* Secondary Back Button */
    .btn-back-outline {
        border: 1px solid #ced4da;
        background: white;
        color: #444;
        font-weight: 700;
        padding: 12px 35px;
        text-transform: uppercase;
        text-decoration: none;
        font-size: 14px;
        display: inline-block;
        transition: 0.2s;
        border-radius: 0;
    }
    .btn-back-outline:hover {
        background: #f8f9fa;
        color: #000;
    }

    .current-img-wrap { position: relative; width: 100px; height: 100px; }
    .current-img-wrap img { width: 100%; height: 100%; object-fit: cover; border: 1px solid #ddd; padding: 2px; background: #fff; }
</style>

<div class="container-fluid px-4 pb-5">
    <div class="row">
        <div class="col-md-2 py-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <div class="manager-card">
                <div class="manager-header">
                    <h1 class="h5 fw-bold m-0 text-uppercase">Edit Product</h1>
                    <a href="manage_products.php" class="text-decoration-none text-muted small fw-bold">
                        <i class="bi bi-arrow-left me-1"></i> BACK TO LIST
                    </a>
                </div>

                <div class="p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Category Assignment</label>
                                <select name="category_id" class="form-select" required>
                                    <?php foreach($parent_categories as $parent): ?>
                                        <optgroup label="<?= htmlspecialchars($parent['name']) ?>">
                                            <?php 
                                            $sub_stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ?");
                                            $sub_stmt->execute([$parent['id']]);
                                            while($sub = $sub_stmt->fetch()): ?>
                                                <option value="<?= $sub['id'] ?>" <?= $product['category_id'] == $sub['id'] ? 'selected' : '' ?>>↳ <?= htmlspecialchars($sub['name']) ?></option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">MRP (₹)</label>
                                <input type="number" name="mrp" class="form-control" value="<?= $product['mrp'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Selling Price (₹)</label>
                                <input type="number" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Level</label>
                                <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label d-block">Existing Assets</label>
                                <div class="d-flex gap-2 flex-wrap mb-3">
                                    <?php foreach($images as $img): ?>
                                        <div class="current-img-wrap shadow-sm">
                                            <img src="../assets/products/<?= $img['image_path'] ?>">
                                            <?php if($img['is_cover']): ?>
                                                <span class="badge bg-dark position-absolute top-0 start-0 m-1" style="font-size: 8px;">COVER</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <label class="form-label">Add New Media</label>
                                <input type="file" name="product_images[]" class="form-control" multiple>
                            </div>

                            <div class="col-12 text-end border-top pt-4 mt-5">
                                <a href="manage_products.php" class="btn-back-outline me-2">Cancel</a>
                                <button type="submit" name="update_product" class="btn btn-update shadow-sm">Update Product Record</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>