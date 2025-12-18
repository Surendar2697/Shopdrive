<?php 
require '../config/db.php';
require '../includes/header.php';

// Protection
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

$msg = "";

// Only process if the form was actually submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    
    // Using null coalescing (??) to prevent "Undefined array key" warnings
    $vendor_id = $_SESSION['user_id'];
    $cat_id    = $_POST['category_id'] ?? 0;
    $name      = $_POST['name'] ?? 'No Name';
    $mrp       = $_POST['mrp'] ?? 0;
    $price     = $_POST['price'] ?? 0; // Selling Price
    $stock     = $_POST['stock'] ?? 0;
    $desc      = $_POST['description'] ?? '';
    
    // 1. ENSURE DIRECTORY EXISTS (Fixes your "No such file or directory" error)
    $target_dir = "../assets/products/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    try {
        $pdo->beginTransaction();

        // 2. INSERT PRODUCT DATA
        $stmt = $pdo->prepare("INSERT INTO products (vendor_id, category_id, name, mrp, price, stock, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vendor_id, $cat_id, $name, $mrp, $price, $stock, $desc]);
        $product_id = $pdo->lastInsertId();

        // 3. HANDLE MULTIPLE IMAGE UPLOAD
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                // Sanitize file name: remove spaces and add timestamp
                $original_name = basename($_FILES['images']['name'][$key]);
                $clean_name = time() . "_" . str_replace(' ', '_', $original_name);
                $target_file = $target_dir . $clean_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    // Logic: First image in array is the Cover (is_cover = 1)
                    $is_cover = ($key == 0) ? 1 : 0;
                    $sort_order = $key;

                    $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_cover, sort_order) VALUES (?, ?, ?, ?)");
                    $imgStmt->execute([$product_id, $clean_name, $is_cover, $sort_order]);
                }
            }
        }

        $pdo->commit();
        $msg = "<div class='alert alert-success fw-bold'>₹ Product published successfully with multiple images!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch categories for dropdown
$main_cats = $pdo->query("SELECT * FROM categories WHERE parent_id = 0")->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Add New Product (Indian Marketplace)</h5>
                    <a href="manage_products.php" class="btn btn-sm btn-outline-light">Back to List</a>
                </div>
                <div class="card-body p-4">
                    <?= $msg ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Product Full Title</label>
                                <input type="text" name="name" class="form-control border-0 bg-light py-2" placeholder="Enter product name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Select Category</label>
                                <select name="category_id" class="form-select border-0 bg-light py-2" required>
                                    <option value="">Choose Sub-category...</option>
                                    <?php foreach($main_cats as $m): ?>
                                        <optgroup label="<?= htmlspecialchars($m['name']) ?>">
                                            <?php 
                                            $subs = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ?");
                                            $subs->execute([$m['id']]);
                                            while($s = $subs->fetch()):
                                            ?>
                                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold text-danger">MRP (Market Price)</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-light">₹</span>
                                    <input type="number" name="mrp" class="form-control border-0 bg-light" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-success">Selling Price (Offer)</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-light">₹</span>
                                    <input type="number" name="price" class="form-control border-0 bg-light" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Stock available</label>
                                <input type="number" name="stock" class="form-control border-0 bg-light" placeholder="Quantity" required>
                            </div>

                            <div class="col-md-12">
                                <div class="p-3 border-dashed rounded-3 bg-light text-center">
                                    <label class="form-label fw-bold d-block">Upload Product Images</label>
                                    <input type="file" name="images[]" class="form-control" multiple required>
                                    <small class="text-muted d-block mt-2">Note: The 1st image selected will be your <strong>Cover Image</strong>.</small>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Detailed Description</label>
                                <textarea name="description" class="form-control border-0 bg-light" rows="5" placeholder="Describe your product..."></textarea>
                            </div>

                            <div class="col-12 text-end">
                                <hr>
                                <button type="submit" name="upload" class="btn btn-primary px-5 py-2 fw-bold shadow">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>Publish Product
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-dashed { border: 2px dashed #cbd5e1; }
    .bg-light { background-color: #f8fafc !important; }
</style>

<?php include '../includes/footer.php'; ?>