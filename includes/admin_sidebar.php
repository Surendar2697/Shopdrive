<div class="card border-0 shadow-sm rounded-0 overflow-hidden">
    <div class="list-group list-group-flush">
        <div class="list-group-item bg-light small fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Main Navigation</div>
        
        <a href="dashboard.php" class="list-group-item list-group-item-action border-0 py-3 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-primary' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        
        <a href="manage_products.php" class="list-group-item list-group-item-action border-0 py-3 <?= basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active bg-primary' : '' ?>">
            <i class="bi bi-box-seam me-2"></i> Manage Products
        </a>

        <a href="upload_product.php" class="list-group-item list-group-item-action border-0 py-3 <?= basename($_SERVER['PHP_SELF']) == 'upload_product.php' ? 'active bg-primary' : '' ?>">
            <i class="bi bi-cloud-upload me-2"></i> Upload Product
        </a>

        <?php if($_SESSION['role'] == 'super_admin'): ?>
            <div class="list-group-item bg-light small fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Administration</div>
            
            <a href="categories.php" class="list-group-item list-group-item-action border-0 py-3 <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-tags me-2"></i> Category Manager
            </a>

            <a href="manage_users.php" class="list-group-item list-group-item-action border-0 py-3 <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-people me-2"></i> Manage Users
            </a>

            <a href="settings.php" class="list-group-item list-group-item-action border-0 py-3 <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-gear me-2"></i> Site Settings
            </a>
        <?php endif; ?>

        <div class="list-group-item bg-light small fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Exit</div>
        
        <a href="../logout.php" class="list-group-item list-group-item-action border-0 py-3 text-danger fw-bold">
            <i class="bi bi-box-arrow-left me-2"></i> Logout Session
        </a>
    </div>
</div>