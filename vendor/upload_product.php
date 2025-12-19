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

// FETCH PARENT CATEGORIES FOR THE DROPDOWN
$parent_categories = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC")->fetchAll();

require_once '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; font-family: 'Segoe UI', sans-serif; }

    /* Manager Card Style */
    .manager-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .manager-header { padding: 1.25rem 1.5rem; background-color: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .manager-title { font-size: 13px; font-weight: 700; color: #333; margin: 0; text-transform: uppercase; }

    /* Professional Form Styling */
    .form-label { font-weight: 700; font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
    .form-control, .form-select { border-radius: 0; border: 1px solid #ced4da; padding: 12px; font-size: 14px; }
    .form-control:focus, .form-select:focus { border-color: var(--primary-theme); box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.05); }

    .btn-submit {
        background-color: var(--primary-theme) !important; color: white !important;
        border: none; border-radius: 0; font-weight: 700; padding: 12px 35px;
        text-transform: uppercase; font-size: 13px; transition: 0.3s;
    }

    /* Upload Area */
    .upload-container {
        border: 2px dashed #cbd5e1; padding: 40px; text-align: center;
        background: #fcfdfe; cursor: pointer; transition: 0.2s;
    }
    .upload-container:hover { border-color: var(--primary-theme); background: #f1f5f9; }
    
    /* Image Preview Grid */
    .preview-wrapper { position: relative; width: 110px; transition: 0.3s; }
    .preview-img-box { position: relative; cursor: pointer; }
    .preview-img { width: 110px; height: 110px; object-fit: cover; border: 2px solid #ddd; padding: 2px; background: #fff; }
    
    /* Cover Badge Logic */
    .preview-wrapper.is-cover .preview-img { border-color: var(--primary-theme); box-shadow: 0 0 8px rgba(0,0,0,0.15); }
    .cover-badge { 
        position: absolute; top: 5px; left: 5px; background: var(--primary-theme); 
        color: white; font-size: 8px; padding: 2px 5px; font-weight: 800; 
        display: none; text-transform: uppercase; z-index: 10;
    }
    .preview-wrapper.is-cover .cover-badge { display: block; }

    /* Sort Input */
    .sort-input {
        width: 100%; margin-top: 6px; font-size: 10px; padding: 3px;
        border: 1px solid #ddd; text-align: center; font-weight: 800;
        text-transform: uppercase; color: #666;
    }
    .sort-input:focus { border-color: var(--primary-theme); outline: none; }

    .instruction-text { font-size: 11px; color: var(--primary-theme); font-weight: 700; margin-bottom: 10px; display: none; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/vendor_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <h5 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">Product Management</h5>

            <div class="manager-card">
                <div class="manager-header">
                    <h2 class="manager-title">Add New Product</h2>
                </div>

                <div class="p-4">
                    <form action="process_add_product.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="cover_index" id="cover_index" value="0">

                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Choose Category --</option>
                                    <?php foreach($parent_categories as $parent): ?>
                                        <optgroup label="<?= htmlspecialchars($parent['name']) ?>">
                                            <?php 
                                            $sub_stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC");
                                            $sub_stmt->execute([$parent['id']]);
                                            while($sub = $sub_stmt->fetch()): ?>
                                                <option value="<?= $sub['id'] ?>">↳ <?= htmlspecialchars($sub['name']) ?></option>
                                            <?php endwhile; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">MRP (₹)</label>
                                <input type="number" name="mrp" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Selling Price (₹)</label>
                                <input type="number" name="price" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Stock</label>
                                <input type="number" name="stock" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Product Images & Gallery Order</label>
                                <p class="instruction-text" id="instruction">
                                    <i class="bi bi-info-circle-fill"></i> Click an image to set <b>Cover</b>. Change numbers below for <b>Sorting</b>.
                                </p>
                                <div class="upload-container" onclick="document.getElementById('img_input').click();">
                                    <i class="bi bi-cloud-arrow-up fs-2 text-muted"></i>
                                    <p class="mb-0 fw-bold mt-2">Click to select multiple images</p>
                                    <input type="file" name="product_images[]" id="img_input" class="d-none" multiple required onchange="handlePreview(this)">
                                </div>
                                <div id="preview-grid" class="mt-3 d-flex gap-3 flex-wrap"></div>
                            </div>

                            <div class="col-12 border-top pt-4 mt-4 text-end">
                                <a href="manage_products.php" class="text-muted text-decoration-none me-4 small fw-bold">CANCEL</a>
                                <button type="submit" name="submit_product" class="btn btn-submit shadow-sm">Save Product</button>
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
        const instruction = document.getElementById('instruction');
        const coverInput = document.getElementById('cover_index');
        grid.innerHTML = '';
        
        if (input.files.length > 0) {
            instruction.style.display = 'block';
            coverInput.value = 0; // Default cover
            
            Array.from(input.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'preview-wrapper' + (index === 0 ? ' is-cover' : '');
                    wrapper.setAttribute('data-index', index);
                    
                    wrapper.innerHTML = `
                        <div class="preview-img-box" onclick="setCover(${index})">
                            <span class="cover-badge">Cover</span>
                            <img src="${e.target.result}" class="preview-img">
                        </div>
                        <input type="number" name="sort_orders[]" class="sort-input" value="${index + 1}" title="Display Order">
                    `;
                    grid.appendChild(wrapper);
                }
                reader.readAsDataURL(file);
            });
        }
    }

    function setCover(index) {
        document.getElementById('cover_index').value = index;
        document.querySelectorAll('.preview-wrapper').forEach(w => {
            w.classList.remove('is-cover');
            if(w.getAttribute('data-index') == index) w.classList.add('is-cover');
        });
    }
</script>

<?php include '../includes/footer.php'; ?>