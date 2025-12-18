<?php
// Ensure we have access to the primary theme color
$sidebar_theme = $settings['primary_color'] ?? '#2563eb';
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch Main Categories for the Sidebar
$main_cats = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC")->fetchAll();
?>

<style>
    /* GLOBAL SIDEBAR STYLING */
    .common-sidebar-card {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 0;
        overflow: visible; /* Required for flyout sub-menus */
    }

    .sidebar-header {
        background-color: var(--primary-theme, <?= $sidebar_theme ?>);
        color: #fff;
        padding: 15px;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Category & Link Styling */
    .s-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        color: #444;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        border-bottom: 1px solid #f1f1f1;
        transition: 0.2s;
        position: relative;
    }

    .s-link:hover {
        background-color: #f8f9fa;
        color: var(--primary-theme, <?= $sidebar_theme ?>);
        padding-left: 20px;
    }

    .s-link.active {
        border-left: 4px solid var(--primary-theme, <?= $sidebar_theme ?>);
        background-color: #f1f7ff;
        color: var(--primary-theme, <?= $sidebar_theme ?>);
    }

    /* THE PROFESSIONAL FLYOUT (MEGA MENU) */
    .dropdown-sidebar { position: static; }
    
    .flyout-menu {
        display: none;
        position: absolute;
        left: 100%;
        top: 0;
        width: 260px;
        min-height: 100%;
        background: #fff;
        z-index: 1050;
        border: 1px solid #e0e0e0;
        border-left: 4px solid var(--primary-theme, <?= $sidebar_theme ?>);
        box-shadow: 15px 0 30px rgba(0,0,0,0.08);
    }

    .dropdown-sidebar:hover .flyout-menu {
        display: block;
        animation: sidebarFadeIn 0.2s ease;
    }

    @keyframes sidebarFadeIn {
        from { opacity: 0; transform: translateX(10px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .flyout-item {
        display: block;
        padding: 10px 15px;
        color: #555;
        text-decoration: none;
        font-size: 13px;
        border-bottom: 1px solid #f9f9f9;
    }
    .flyout-item:hover { background: #f8f9fa; color: var(--primary-theme); }
</style>

<div class="common-sidebar-card shadow-sm mb-4">
    <div class="sidebar-header">
        <i class="bi bi-grid-fill me-2"></i> Shop Categories
    </div>
    <div class="list-group list-group-flush position-relative">
        <?php foreach($main_cats as $parent): ?>
            <div class="dropdown-sidebar">
                <a href="category.php?id=<?= $parent['id'] ?>" class="s-link <?= (isset($_GET['id']) && $_GET['id'] == $parent['id']) ? 'active' : '' ?>">
                    <span><?= htmlspecialchars($parent['name']) ?></span>
                    <i class="bi bi-chevron-right small opacity-50"></i>
                </a>
                
                <div class="flyout-menu">
                    <div class="p-3 bg-light border-bottom small fw-bold text-muted text-uppercase">
                        <?= htmlspecialchars($parent['name']) ?> Collection
                    </div>
                    <?php 
                    $sub_stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC");
                    $sub_stmt->execute([$parent['id']]);
                    $subs = $sub_stmt->fetchAll();
                    foreach($subs as $sub): ?>
                        <a href="category.php?id=<?= $sub['id'] ?>" class="flyout-item">
                            <i class="bi bi-arrow-return-right me-2 opacity-50"></i><?= htmlspecialchars($sub['name']) ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if(!$subs): ?>
                        <div class="p-3 small text-muted">No sub-categories</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card border-0 bg-light p-3 text-center border rounded-0">
    <i class="bi bi-shield-check fs-2 text-success"></i>
    <div class="fw-bold small mt-1">SECURE CHECKOUT</div>
    <p class="x-small text-muted mb-0">Trusted across India</p>
</div>