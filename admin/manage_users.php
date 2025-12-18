<?php 
require '../config/db.php';

// 1. SECURITY & LOGIC
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch settings for dynamic theme alignment
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$current_user_id = $_SESSION['user_id'];

// 2. DELETE USER LOGIC
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($delete_id == $current_user_id) {
        header("Location: manage_users.php?error=self_delete");
        exit();
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: manage_users.php?success=deleted");
        exit();
    }
}

// 3. FETCH ALL USERS
$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, fullname ASC")->fetchAll();

require '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $settings['primary_color'] ?? '#00bcd4' ?>; }
    
    body { background-color: #fcfcfc; font-family: 'Segoe UI', Roboto, sans-serif; }

    /* Match the professional card style from Settings */
    .manager-card {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .manager-header {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
    }

    .manager-title { font-size: 1.25rem; font-weight: 700; color: #333; margin: 0; }

    /* Button aligns with the "Save" and "Update" buttons in Settings */
    .btn-add-custom {
        background-color: var(--primary-theme) !important;
        color: white !important;
        border: none;
        border-radius: 0; /* Professional sharp edges */
        font-weight: 600;
        padding: 8px 20px;
        font-size: 14px;
        text-transform: uppercase;
    }

    /* Table styling aligned with Inventory Manager screenshot */
    .custom-table thead th {
        background-color: #f8f9fa;
        color: #000;
        font-weight: 700;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 20px;
        font-size: 13px;
        text-transform: uppercase;
    }

    .custom-table tbody td {
        padding: 15px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
        font-size: 14px;
        color: #444;
    }

    .user-preview {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .badge-status {
        font-size: 10px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        margin-top: 4px;
        display: inline-block;
    }
    .status-active { background-color: #198754; color: white; }

    .action-group { display: flex; gap: 5px; }
    
    .btn-action-outline {
        border: 1px solid #ced4da;
        background: white;
        padding: 4px 10px;
        border-radius: 0;
        color: var(--primary-theme);
        transition: 0.2s;
    }
    .btn-action-outline.delete { color: #dc3545; }
    .btn-action-outline:hover { background: #f8f9fa; border-color: #adb5bd; }

    .role-link { color: var(--primary-theme); font-weight: 700; text-decoration: none; }
    .role-link:hover { text-decoration: underline; }
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 alert-dismissible fade show rounded-0">
                    <i class="bi bi-check2-all me-2"></i> Action completed successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="manager-card">
                <div class="manager-header bg-white">
                    <h1 class="manager-title text-uppercase">Global User Manager</h1>
                    <a href="create_user.php" class="btn btn-add-custom shadow-sm">
                        <i class="bi bi-person-plus me-1"></i> Add New User
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Full Name</th>
                                <th>System Role</th>
                                <th>Email Address</th>
                                <th>Created At</th>
                                <th>Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): 
                                $is_me = ($u['id'] == $current_user_id);
                            ?>
                            <tr>
                                <td>
                                    <div class="user-preview">
                                        <?= strtoupper($u['fullname'][0]) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['fullname']) ?></div>
                                    <span class="badge-status status-active">ACTIVE</span>
                                </td>
                                <td>
                                    <a href="#" class="role-link text-uppercase" style="font-size: 12px;">
                                        <?= str_replace('_', ' ', $u['role']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td class="text-muted small">
                                    <?= date('d M, Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn-action-outline" title="Edit Profile">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if(!$is_me): ?>
                                            <a href="?delete=<?= $u['id'] ?>" class="btn-action-outline delete" onclick="return confirm('Delete this user permanently?')" title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border py-2 px-3 small">Admin Locked</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger border-0 mt-3 small shadow-sm rounded-0">
                    <i class="bi bi-shield-lock me-1"></i> Self-deletion is restricted to maintain system access.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>