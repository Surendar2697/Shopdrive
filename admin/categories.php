<?php 
require '../config/db.php';
require '../includes/header.php';

// PROTECTION: Only Super Admin allowed
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// 1. ADD CATEGORY/SUB-CATEGORY LOGIC
if (isset($_POST['save_category'])) {
    $name = trim($_POST['cat_name']);
    $parent_id = $_POST['parent_id']; 

    $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
    $stmt->execute([$name, $parent_id]);
    $success_msg = "Category successfully added to the system.";
}

// 2. DELETE LOGIC
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Cascading delete: Removes the category and any sub-categories attached to it
    $pdo->prepare("DELETE FROM categories WHERE id = ? OR parent_id = ?")->execute([$id, $id]);
    header("Location: categories.php?deleted=1");
    exit();
}

// Fetch Main Categories for the dropdown and display
$main_categories = $pdo->query("SELECT * FROM categories WHERE parent_id = 0 ORDER BY name ASC")->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Category & Sub-Category Manager</h2>
                <span class="text-muted small">Manage your store's taxonomy</span>
            </div>

            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold">Create New Category</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Category Name</label>
                                    <input type="text" name="cat_name" class="form-control" placeholder="e.g., Electronics, Shirts, etc." required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Parent Group</label>
                                    <select name="parent_id" class="form-select bg-light border-0">
                                        <option value="0">None (Create as Main Category)</option>
                                        <?php foreach($main_categories as $m): ?>
                                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text x-small">Select a parent if this is a sub-item (e.g., 'Pant' belongs to 'Male').</div>
                                </div>
                                <button type="submit" name="save_category" class="btn btn-primary w-100 fw-bold shadow-sm">
                                    Save Category
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold">Structure Preview</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if(empty($main_categories)): ?>
                                    <div class="p-5 text-center text-muted">
                                        <i class="bi bi-folder2-open display-4"></i>
                                        <p class="mt-2">No categories found in database.</p>
                                    </div>
                                <?php endif; ?>

                                <?php foreach($main_categories as $parent): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 bg-light">
                                        <span class="fw-bold text-dark"><i class="bi bi-folder-fill text-warning me-2"></i> <?= htmlspecialchars($parent['name']) ?></span>
                                        <a href="?delete=<?= $parent['id'] ?>" class="btn btn-xs btn-outline-danger px-2 py-0" onclick="return confirm('Deleting a Main category will remove ALL nested sub-categories. Proceed?')">Delete</a>
                                    </div>

                                    <?php 
                                    $sub_stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC");
                                    $sub_stmt->execute([$parent['id']]);
                                    while($sub = $sub_stmt->fetch()):
                                    ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 ps-5 border-start border-primary border-4">
                                            <span class="text-secondary small">
                                                <i class="bi bi-arrow-return-right me-2"></i> <?= htmlspecialchars($sub['name']) ?>
                                            </span>
                                            <a href="?delete=<?= $sub['id'] ?>" class="text-danger x-small text-decoration-none" onclick="return confirm('Remove this sub-category?')">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        </div>
                                    <?php endwhile; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>