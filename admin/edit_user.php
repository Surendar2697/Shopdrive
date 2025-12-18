<?php 
require '../config/db.php';

// 1. SECURITY & DATA FETCHING
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$edit_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$edit_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: manage_users.php");
    exit();
}

// 2. UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Security: Prevents the Super Admin from demoting themselves
    if ($edit_id == $current_user_id) {
        $role = 'super_admin'; 
    }

    $update_sql = "UPDATE users SET fullname = ?, email = ?, role = ? WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    
    if ($update_stmt->execute([$fullname, $email, $role, $edit_id])) {
        header("Location: manage_users.php?success=updated");
        exit();
    }
}

require '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $brand['primary_color'] ?? '#00bcd4' ?>; }
    body { background-color: #fcfcfc; }

    .manager-card {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-top: 2rem;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .manager-header {
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
        background-color: #f8f9fa;
    }

    .manager-title { font-size: 1.25rem; font-weight: 700; color: #333; margin: 0; }

    .form-label { font-weight: 700; font-size: 14px; color: #444; }
    
    .form-control, .form-select {
        border-radius: 4px;
        border: 1px solid #ced4da;
        padding: 10px;
        font-size: 14px;
    }

    .form-control:focus {
        border-color: var(--primary-theme);
        box-shadow: 0 0 0 0.2rem rgba(0, 188, 212, 0.15);
    }

    .btn-save {
        background-color: var(--primary-theme) !important;
        color: white !important;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        padding: 10px 25px;
    }

    .btn-cancel {
        background-color: #f8f9fa;
        color: #666;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px 25px;
        text-decoration: none;
        font-weight: 600;
    }
</style>

<div class="container-fluid px-4 pb-5">
    <div class="row">
        <div class="col-md-2 py-4">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <div class="manager-card">
                <div class="manager-header">
                    <h1 class="manager-title">Edit User Profile</h1>
                </div>

                <div class="p-4">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">System Role</label>
                                <?php if($edit_id == $current_user_id): ?>
                                    <select class="form-select" name="role" disabled>
                                        <option value="super_admin" selected>Super Admin</option>
                                    </select>
                                    <input type="hidden" name="role" value="super_admin">
                                    <small class="text-muted">You cannot change your own role.</small>
                                <?php else: ?>
                                    <select class="form-select" name="role" required>
                                        <option value="customer" <?= $user['role'] == 'customer' ? 'selected' : '' ?>>Customer</option>
                                        <option value="vendor" <?= $user['role'] == 'vendor' ? 'selected' : '' ?>>Vendor</option>
                                        <option value="super_admin" <?= $user['role'] == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 border-top pt-4 mt-4 d-flex justify-content-between">
                                <a href="manage_users.php" class="btn-cancel">Cancel</a>
                                <button type="submit" name="update_user" class="btn btn-save">Update User Account</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>