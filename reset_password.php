<?php 
ob_start();
require 'config/db.php';
require 'includes/header.php';

$settings = $pdo->query("SELECT * FROM site_settings LIMIT 1")->fetch();
$primary_color = $settings['primary_color'] ?? '#2563eb';

$msg = "";
$valid_token = false;
$user_id = null;

// 1. Validate Token from URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $now = date("Y-m-d H:i:s");

    $stmt = $pdo->prepare("SELECT id, reset_expiry FROM users WHERE reset_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($now > $user['reset_expiry']) {
            $msg = "<div class='alert alert-danger'>This reset link has expired (30-minute limit). Please request a new one.</div>";
        } else {
            $valid_token = true;
            $user_id = $user['id'];
        }
    } else {
        $msg = "<div class='alert alert-danger'>Invalid or broken reset link.</div>";
    }
} else {
    header("Location: login.php");
    exit();
}

// 2. Handle Password Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $password_raw = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $password_regex = "/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    if ($password_raw !== $confirm_password) {
        $msg = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } elseif (!preg_match($password_regex, $password_raw)) {
        $msg = "<div class='alert alert-danger'>Password must be at least 8 characters, include one uppercase letter, one number, and one special character.</div>";
    } else {
        $new_password = password_hash($password_raw, PASSWORD_DEFAULT);

        try {
            // Update password and clear reset token fields
            $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
            $update->execute([$new_password, $user_id]);

            $_SESSION['login_msg'] = "<div class='alert alert-success text-center'>Password updated successfully! Please login with your new password.</div>";
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-danger'>Database error occurred.</div>";
        }
    }
}
?>

<style>
    :root { --primary: <?= $primary_color ?>; }
    body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }
    .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.3); }
    .btn-custom { background: var(--primary); color: #fff; border-radius: 12px; padding: 14px; font-weight: 700; border: none; transition: 0.3s; width: 100%; cursor: pointer; }
    .btn-custom:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); color: #fff; }
    .form-control { border-radius: 12px; padding: 12px; border: 1px solid #ddd; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1); border-color: var(--primary); outline: none; }
    .strength-meter { height: 6px; border-radius: 3px; background: #eee; margin-top: 8px; overflow: hidden; }
    .strength-bar { height: 100%; width: 0; transition: 0.3s; }
    .btn-error { animation: shake 0.4s; background-color: #dc3545 !important; }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
</style>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <div class="glass-card shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark">Set New Password</h2>
                    <p class="text-muted small">Secure your account with a strong password</p>
                </div>

                <?= $msg ?>

                <?php if($valid_token): ?>
                <form method="POST" id="resetForm" autocomplete="off">
                    <div class="mb-3 position-relative">
                        <label class="small fw-bold text-muted uppercase">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••" autocomplete="new-password">
                        <div class="strength-meter"><div id="strength-bar" class="strength-bar"></div></div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted uppercase">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="••••••••">
                        <span id="match-text" class="small fw-bold mt-1 d-block"></span>
                    </div>
                    <button type="submit" id="submitBtn" class="btn-custom mt-3 text-uppercase">Update Password</button>
                </form>
                <?php else: ?>
                    <div class="text-center">
                        <a href="forgot_password.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">Request New Link</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwd = document.getElementById('password');
    const conf = document.getElementById('confirm_password');
    const bar = document.getElementById('strength-bar');
    const matchText = document.getElementById('match-text');
    const btn = document.getElementById('submitBtn');
    const form = document.getElementById('resetForm');

    if(!form) return;

    pwd.addEventListener('input', function() {
        let val = pwd.value;
        let strength = 0;
        if (val.length >= 8) strength += 25;
        if (/[A-Z]/.test(val)) strength += 25;
        if (/[0-9]/.test(val)) strength += 25;
        if (/[\W_]/.test(val)) strength += 25;

        bar.style.width = strength + '%';
        if (strength <= 50) bar.style.backgroundColor = '#ff4d4d';
        else if (strength <= 75) bar.style.backgroundColor = '#ffa500';
        else bar.style.backgroundColor = '#2ecc71';
    });

    function checkMatch() {
        if (!conf.value) { matchText.textContent = ""; return true; }
        if (pwd.value === conf.value) {
            matchText.textContent = "✓ Passwords Match";
            matchText.style.color = "#2ecc71";
            btn.classList.remove('btn-error');
            return true;
        } else {
            matchText.textContent = "✗ Passwords do not match";
            matchText.style.color = "#ff4d4d";
            return false;
        }
    }

    pwd.addEventListener('input', checkMatch);
    conf.addEventListener('input', checkMatch);

    form.addEventListener('submit', function(e) {
        if (!checkMatch()) {
            e.preventDefault();
            btn.classList.add('btn-error');
            setTimeout(() => btn.classList.remove('btn-error'), 400);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>