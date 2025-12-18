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

// 2. FETCH NESTED CATEGORIES (Parents and their Children)
$parent_categories = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC")->fetchAll();

require '../includes/header.php'; 
?>

<style>
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
        background-color: #f8f9fa;
    }

    .manager-title { font-size: 1.25rem; font-weight: 700; color: #333; margin: 0; text-transform: uppercase; }

    /* Professional Form Styling */
    .form-label { font-weight: 700; font-size: 13px; color: #444; text-transform: uppercase; margin-bottom: 8px; }
    .form-control, .form-select {
        border-radius: 0;
        border: 1px solid #ced4da;
        padding: 12px;
        font-size: 14px;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-theme);
        box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.05);
    }

    /* Category Dropdown Styling */
    .optgroup-header { font-weight: 800; color: #000; background: #f8f9fa; }

    .btn-submit {
        background-color: var(--primary-theme) !important;
        color: white !important;
        border: none;
        border-radius: 0;
        font-weight: 700;
        padding: 12px 35px;
        text-transform: uppercase;
        font-size: 14px;
        transition: 0.3s;
    }
    .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

    .upload-container {
        border: 2px dashed #cbd5e1;
        padding: 40px;
        text-align: center;
        background: #fcfdfe;
        cursor: pointer;
        transition: 0.2s;
    }
    .upload-container:hover { border-color: var(--primary-theme); background: #f1f5f9; }
</style>

<div class="container-fluid px-4 pb-5">
    <div class="row">
        <div class="col-md-2 py-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <div class="manager-card">
                <div class="manager-header">
                    <h1 class="manager-title">Add New Product</h1>
                </div>

                <div class="p-4">
                    <form action="process_add_product.php" method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label">Product Name / Title</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter formal product name" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Category Assignment</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Choose Category --</option>
                                    <?php foreach($parent_categories as $parent): ?>
                                        <optgroup label="<?= htmlspecialchars($parent['name']) ?>" class="optgroup-header">
                                            <?php 
                                            $sub_stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC");
                                            $sub_stmt->execute([$parent['id']]);
                                            $subs = $sub_stmt->fetchAll();
                                            foreach($subs as $sub): ?>
                                                <option value="<?= $sub['id'] ?>">↳ <?= htmlspecialchars($sub['name']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">MRP (Maximum Retail Price)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">₹</span>
                                    <input type="number" name="mrp" class="form-control border-start-0" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Selling Price</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">₹</span>
                                    <input type="number" name="price" class="form-control border-start-0" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Initial Stock Level</label>
                                <input type="number" name="stock" class="form-control" placeholder="Quantity in pcs" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Product Specifications</label>
                                <textarea name="description" class="form-control" rows="5" placeholder="Enter detailed description, size guides, or technical specs..."></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Media Assets (Images)</label>
                                <div class="upload-container" onclick="document.getElementById('img_input').click();">
                                    <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                    <p class="mb-0 fw-bold mt-2">Click to browse multiple images</p>
                                    <small class="text-muted">High resolution JPEG/PNG recommended</small>
                                    <input type="file" name="product_images[]" id="img_input" class="d-none" multiple required onchange="handlePreview(this)">
                                </div>
                                <div id="preview-grid" class="mt-3 d-flex gap-3 flex-wrap"></div>
                            </div>

                            <div class="col-12 border-top pt-4 mt-5 text-end">
                                <a href="manage_products.php" class="text-muted text-decoration-none me-4 small fw-bold">DISCARD</a>
                                <button type="submit" name="submit_product" class="btn btn-submit shadow-sm">
                                    Submit to Inventory
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function handlePreview(input) {
        const grid = document.getElementById('preview-grid');
        grid.innerHTML = '';
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const wrap = document.createElement('div');
                    wrap.className = "position-relative";
                    wrap.innerHTML = `<img src="${e.target.result}" style="width:100px; height:100px; object-fit:cover;" class="border p-1 bg-white">`;
                    grid.appendChild(wrap);
                }
                reader.readAsDataURL(file);
            });
        }
    }
</script>

<?php include '../includes/footer.php'; ?>