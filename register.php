<?php 
ob_start();
require 'config/db.php';
require 'includes/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'includes/manual_autoload.php';

$settings = $pdo->query("SELECT * FROM site_settings LIMIT 1")->fetch();
$primary_color = $settings['primary_color'] ?? '#2563eb';

$msg = "";
$fullname = "";
$email = "";

if (isset($_SESSION['reg_success'])) {
    $msg = $_SESSION['reg_success'];
    unset($_SESSION['reg_success']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim(strtolower($_POST['email']));
    $password_raw = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $password_regex = "/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    if (empty($fullname) || empty($email)) {
        $msg = "<div class='alert alert-danger'>Please fill in all fields.</div>";
    } elseif ($password_raw !== $confirm_password) {
        $msg = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } elseif (!preg_match($password_regex, $password_raw)) {
        $msg = "<div class='alert alert-danger'>Password security requirements not met.</div>";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        try {
            $check_user = $pdo->prepare("SELECT id, is_active FROM users WHERE email = ?");
            $check_user->execute([$email]);
            $existing_user = $check_user->fetch();

            $proceed = false;
            if ($existing_user) {
                if ($existing_user['is_active'] == 1) {
                    $msg = "<div class='alert alert-danger'>This email is already verified. Please login.</div>";
                } else {
                    $update = $pdo->prepare("UPDATE users SET fullname = ?, password = ?, verification_token = ?, token_expiry = ? WHERE id = ?");
                    $update->execute([$fullname, $password, $token, $expiry, $existing_user['id']]);
                    $proceed = true;
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role, verification_token, token_expiry, is_active) VALUES (?, ?, ?, 'customer', ?, ?, 0)");
                $stmt->execute([$fullname, $email, $password, $token, $expiry]);
                $proceed = true;
            }

            if ($proceed) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = $settings['smtp_host']; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $settings['smtp_user']; 
                    $mail->Password   = $settings['smtp_pass']; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = $settings['smtp_port']; 

                    $mail->setFrom($settings['smtp_user'], 'Shopdrive');
                    $mail->addAddress($email, $fullname);

                    $verify_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=" . $token;

                    $mail->isHTML(true);
                    $mail->Subject = 'Activate Your Account | Shopdrive';
                    $mail->Body    = "
                        <div style='background: #f3f4f6; padding: 40px 10px; font-family: sans-serif;'>
                            <table align='center' width='100%' style='max-width: 500px; background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-collapse: collapse; overflow: hidden;'>
                                <tr><td style='padding: 30px; background: $primary_color; text-align: center;'><h1 style='color: #fff; margin: 0; letter-spacing: 2px;'>SHOPDRIVE</h1></td></tr>
                                <tr><td style='padding: 40px 30px; text-align: center;'>
                                    <h2 style='color: #111; margin-top: 0;'>One Step Closer...</h2>
                                    <p style='color: #555; font-size: 16px; line-height: 1.6;'>Hi $fullname, thank you for joining us. Click the button below to verify your email and activate your account.</p>
                                    <div style='margin: 30px 0;'><a href='$verify_link' style='background: $primary_color; color: #fff; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>Verify My Email</a></div>
                                    <p style='color: #999; font-size: 12px;'>This link is valid for 30 minutes. If you did not sign up, please ignore this email.</p>
                                </td></tr>
                            </table>
                        </div>";

                    $mail->send();
                    $_SESSION['reg_success'] = "<div class='alert alert-info text-center'><strong>Link Sent!</strong> Check your email to verify.</div>";
                    header("Location: register.php");
                    exit();
                } catch (Exception $e) { $msg = "<div class='alert alert-danger'>Email failed. Check SMTP settings.</div>"; }
            }
        } catch (PDOException $e) { $msg = "<div class='alert alert-danger'>Database error occurred.</div>"; }
    }
}
?>

<style>
    :root { --primary: <?= $primary_color ?>; }
    body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }
    .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.3); }
    
    /* Fixed Button Hover State */
    .btn-custom { 
        background: var(--primary); 
        color: #fff; 
        border-radius: 12px; 
        padding: 14px; 
        font-weight: 700; 
        border: none; 
        transition: all 0.3s ease;
        display: block;
        width: 100%;
        cursor: pointer;
    }
    .btn-custom:hover { 
        opacity: 0.95; 
        transform: translateY(-2px); 
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    /* Style for invalid state via JS */
    .btn-error {
        animation: shake 0.4s;
        background-color: #dc3545 !important;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .form-control { border-radius: 12px; padding: 12px; border: 1px solid #ddd; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1); border-color: var(--primary); outline: none; }
    .strength-meter { height: 6px; border-radius: 3px; background: #eee; margin-top: 8px; overflow: hidden; transition: 0.3s; }
    .strength-bar { height: 100%; width: 0; transition: 0.3s; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <div class="glass-card shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark">Create Account</h2>
                    <p class="text-muted small">Join Shopdrive for a premium experience</p>
                </div>

                <?= $msg ?>

                <?php if(!isset($_SESSION['reg_success'])): ?>
                <form method="POST" id="regForm" autocomplete="off">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted uppercase">Full Name</label>
                        <input type="text" name="fullname" class="form-control" required value="<?= htmlspecialchars($fullname) ?>" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>" autocomplete="off">
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="small fw-bold text-muted uppercase">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password">
                        <div class="strength-meter"><div id="strength-bar" class="strength-bar"></div></div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted uppercase">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        <span id="match-text" class="small fw-bold mt-1 d-block"></span>
                    </div>
                    <button type="submit" id="submitBtn" class="btn-custom mt-3">Register</button>
                </form>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <small class="text-muted">Already have an account? <a href="login.php" class="fw-bold text-decoration-none" style="color: var(--primary);">Login</a></small>
                </div>
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
    const form = document.getElementById('regForm');

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
        if (!conf.value) { 
            matchText.textContent = ""; 
            return true; 
        }
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

    // Form submission validation
    form.addEventListener('submit', function(e) {
        if (!checkMatch()) {
            e.preventDefault(); // Stop form submission
            btn.classList.add('btn-error');
            setTimeout(() => btn.classList.remove('btn-error'), 400); // Remove shake class
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>