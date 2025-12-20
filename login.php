<?php 
ob_start();
require 'config/db.php';
require 'includes/header.php';

$settings = $pdo->query("SELECT * FROM site_settings LIMIT 1")->fetch();
$primary_color = $settings['primary_color'] ?? '#2563eb';

$msg = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim(strtolower($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $msg = "<div class='alert alert-danger'>Please fill in all fields.</div>";
    } else {
        try {
            // Check for user and ensure they are active (verified via email)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_active'] == 0) {
                    $msg = "<div class='alert alert-warning'>Your account is not verified. Please check your email for the activation link.</div>";
                } else {
                    // Start Session and redirect based on role
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] == 'super_admin') {
                        header("Location: admin/dashboard.php");
                    } elseif ($user['role'] == 'vendor') {
                        header("Location: vendor/dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                }
            } else {
                $msg = "<div class='alert alert-danger'>Invalid email or password.</div>";
            }
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-danger'>Database error occurred.</div>";
        }
    }
}
?>

<style>
    :root { --primary: <?= $primary_color ?>; }
    body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }
    
    .glass-card { 
        background: rgba(255, 255, 255, 0.9); 
        backdrop-filter: blur(10px); 
        border-radius: 24px; 
        border: 1px solid rgba(255, 255, 255, 0.3); 
    }
    
    .btn-custom { 
        background: var(--primary); 
        color: #fff; 
        border-radius: 12px; 
        padding: 14px; 
        font-weight: 700; 
        border: none; 
        transition: all 0.3s ease;
        width: 100%;
    }
    .btn-custom:hover { 
        opacity: 0.95; 
        transform: translateY(-2px); 
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        color: #fff;
    }

    .form-control { border-radius: 12px; padding: 12px; border: 1px solid #ddd; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1); border-color: var(--primary); outline: none; }
</style>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <div class="glass-card shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark">Welcome Back</h2>
                    <p class="text-muted small">Enter your credentials to access your account</p>
                </div>

                <?= $msg ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-4">
                        <label class="small fw-bold text-muted uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?= htmlspecialchars($email) ?>" placeholder="name@email.com">
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label class="small fw-bold text-muted uppercase">Password</label>
                            <a href="forgot_password.php" class="small text-decoration-none" style="color: var(--primary);">Forgot?</a>
                        </div>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    
                    <button type="submit" class="btn-custom">Login</button>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">Don't have an account? <a href="register.php" class="fw-bold text-decoration-none" style="color: var(--primary);">Register</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>