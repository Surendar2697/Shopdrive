<?php 
require 'config/db.php';
require 'includes/header.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        // Role-Based Redirect logic
        if ($user['role'] == 'super_admin') {
            header("Location: admin/dashboard.php");
        } elseif ($user['role'] == 'vendor') {
            header("Location: vendor/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Incorrect email address or password.";
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="bg-dark p-4 text-center">
                    <h4 class="text-white fw-bold mb-0">Welcome Back</h4>
                    <p class="text-white-50 small">Log in to your Pro-Market account</p>
                </div>
                <div class="card-body p-4">
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger py-2 small"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg border-0 bg-light" placeholder="email@example.com" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg border-0 bg-light" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold shadow-sm mb-3">Sign In</button>
                    </form>

                    <div class="text-center">
                        <p class="text-muted small mb-2">New to the platform?</p>
                        <a href="register.php" class="btn btn-outline-secondary w-100 fw-bold">Create New Account</a>
                    </div>
                </div>
            </div>
            
            <p class="text-center mt-4 small text-muted">
                Forgot your password? <a href="#" class="text-decoration-none">Contact Support</a>
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>