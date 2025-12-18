<?php 
require '../config/db.php';
require '../includes/header.php';

// PROTECTION: Only Super Admin allowed
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullname, $email, $password, $role]);
        $msg = "<div class='alert alert-success border-0 shadow-sm'><i class='bi bi-check-circle me-2'></i> Account for $fullname created as $role!</div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger border-0 shadow-sm'>Error: Email might already be in use.</div>";
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Onboard New Personnel</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Admin</a></li>
                        <li class="breadcrumb-item"><a href="manage_users.php">Users</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </nav>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-dark text-white py-3">
                            <h5 class="mb-0 small fw-bold text-uppercase tracking-wider">Account Provisioning Form</h5>
                        </div>
                        <div class="card-body p-4">
                            <?= $msg ?>
                            <form method="POST">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Full Legal Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="bi bi-person"></i></span>
                                            <input type="text" name="fullname" class="form-control bg-light border-0" placeholder="e.g. Michael Scott" required>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Professional Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="email" class="form-control bg-light border-0" placeholder="name@pro-market.com" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">System Role</label>
                                        <select name="role" class="form-select bg-light border-0" required>
                                            <option value="vendor">Vendor (Seller Access)</option>
                                            <option value="super_admin">Super Admin (Full Access)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Temporary Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="bi bi-key"></i></span>
                                            <input type="password" name="password" class="form-control bg-light border-0" required>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <div class="p-3 bg-light rounded-3 mb-4">
                                            <p class="small text-muted mb-0">
                                                <i class="bi bi-info-circle me-2"></i> 
                                                New vendors will be able to upload products immediately after login. Super admins will have access to global site settings and user management.
                                            </p>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow">
                                            Confirm and Create Account
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>