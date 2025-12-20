<?php
ob_start();
require 'config/db.php';
require 'includes/header.php';

$message = "";
$status_type = "danger"; 
$show_login = false;
$show_password_form = false;
$user_id = null;

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $now = date("Y-m-d H:i:s");

    $stmt = $pdo->prepare("SELECT id, fullname, is_active, token_expiry, password FROM users WHERE verification_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_active'] == 1) {
            $message = "Your account is already verified. You can proceed to login.";
            $status_type = "info";
            $show_login = true;
        } elseif ($now > $user['token_expiry']) {
            $message = "This verification link has expired (30-minute limit). Please contact support or register again.";
            $status_type = "warning";
        } else {
            // Check if the user was invited (has no password)
            if (empty($user['password'])) {
                $show_password_form = true;
                $user_id = $user['id'];
                $message = "Welcome " . htmlspecialchars($user['fullname']) . "! Please set your account password to continue.";
                $status_type = "success";
            } else {
                // Standard user activation
                $update = $pdo->prepare("UPDATE users SET is_active = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?");
                if ($update->execute([$user['id']])) {
                    $message = "Thank you, " . htmlspecialchars($user['fullname']) . "! Your account has been successfully verified.";
                    $status_type = "success";
                    $show_login = true;
                }
            }
        }
    } else {
        $message = "Invalid or broken verification link.";
    }
}

// Handle Password Setting for Invited Users
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_invitation'])) {
    $pwd = $_POST['password'];
    $conf = $_POST['confirm_password'];
    $u_id = $_POST['u_id'];
    $password_regex = "/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    if ($pwd !== $conf) {
        $message = "Passwords do not match.";
        $show_password_form = true;
    } elseif (!preg_match($password_regex, $pwd)) {
        $message = "Password must be at least 8 characters, include one uppercase, one number, and one special character.";
        $show_password_form = true;
    } else {
        $hashed = password_hash($pwd, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ?, is_active = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?");
        $update->execute([$hashed, $u_id]);
        
        $_SESSION['reg_success'] = "<div class='alert alert-success'>Account activated! You can now login.</div>";
        header("Location: login.php");
        exit();
    }
}

$settings = $pdo->query("SELECT primary_color FROM site_settings LIMIT 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#2563eb';
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    .verify-card { border-radius: 24px; border: none; background: #fff; box-shadow: 0 20px 40px rgba(0,0,0,0.05); padding: 40px; }
    .icon-box { width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 30px; }
    .btn-verify { background-color: var(--primary-theme) !important; color: #fff !important; border-radius: 12px; font-weight: bold; padding: 12px 30px; text-decoration: none; border: none; transition: 0.3s; }
    .btn-verify:hover { opacity: 0.9; transform: translateY(-2px); }
    .form-control { border-radius: 10px; padding: 12px; border: 1px solid #eee; background: #fdfdfd; }
</style>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="verify-card text-center">
                <div class="icon-box bg-<?= $status_type ?> bg-opacity-10 text-<?= $status_type ?>">
                    <i class="bi <?= $status_type == 'success' ? 'bi-patch-check-fill' : ($status_type == 'info' ? 'bi-info-circle-fill' : 'bi-exclamation-octagon-fill') ?>"></i>
                </div>

                <h4 class="fw-bold mb-2">Account Verification</h4>
                <p class="text-muted small mb-4"><?= $message ?></p>

                <?php if ($show_password_form): ?>
                    <form method="POST" class="text-start">
                        <input type="hidden" name="u_id" value="<?= $user_id ?>">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted uppercase">Create Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                        <div class="mb-4">
                            <label class="small fw-bold text-muted uppercase">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                        </div>
                        <button type="submit" name="complete_invitation" class="btn-verify w-100">Set Password & Activate</button>
                    </form>
                <?php elseif ($show_login): ?>
                    <a href="login.php" class="btn-verify d-block">Login Now</a>
                <?php else: ?>
                    <a href="register.php" class="btn-verify d-block">Back to Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>