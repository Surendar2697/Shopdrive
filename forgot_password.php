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
$email = "";

// 1. Check for success message in Session (to show after redirect)
if (isset($_SESSION['reset_success'])) {
    $msg = $_SESSION['reset_success'];
    unset($_SESSION['reset_success']);
    // Input field will remain empty because $email is initialized as ""
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim(strtolower($_POST['email']));

    if (empty($email)) {
        $msg = "<div class='alert alert-danger'>Please enter your email address.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

                $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
                $update->execute([$token, $expiry, $user['id']]);

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = $settings['smtp_host'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $settings['smtp_user'];
                    $mail->Password   = $settings['smtp_pass'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = $settings['smtp_port'];

                    $mail->setFrom($settings['smtp_user'], 'Shopdrive Support');
                    $mail->addAddress($email, $user['fullname']);

                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Your Password | Shopdrive';
                    $mail->Body    = "
                        <div style='background: #f3f4f6; padding: 40px 10px; font-family: sans-serif;'>
                            <table align='center' width='100%' style='max-width: 500px; background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-collapse: collapse; overflow: hidden;'>
                                <tr><td style='padding: 30px; background: $primary_color; text-align: center;'><h1 style='color: #fff; margin: 0;'>SHOPDRIVE</h1></td></tr>
                                <tr><td style='padding: 40px 30px; text-align: center;'>
                                    <h2 style='color: #111; margin-top: 0;'>Password Reset</h2>
                                    <p style='color: #555;'>Hello {$user['fullname']}, click the button below to reset your password. Valid for 30 minutes.</p>
                                    <div style='margin: 30px 0;'><a href='$reset_link' style='background: $primary_color; color: #fff; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>Reset Password</a></div>
                                </td></tr>
                            </table>
                        </div>";

                    $mail->send();

                    // 2. SET SESSION AND REDIRECT (Stops resending on refresh)
                    $_SESSION['reset_success'] = "<div class='alert alert-success text-center border-0 py-3'><strong>Reset Link Sent!</strong> Please check your email.</div>";
                    header("Location: forgot_password.php");
                    exit();

                } catch (Exception $e) {
                    $msg = "<div class='alert alert-danger'>Email failed. Check SMTP settings.</div>";
                }
            } else {
                $msg = "<div class='alert alert-danger'>No active account found with that email.</div>";
            }
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-danger'>Database error.</div>";
        }
    }
}
?>

<style>
    :root { --primary: <?= $primary_color ?>; }
    body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }
    .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.3); }
    .btn-custom { background: var(--primary); color: #fff; border-radius: 12px; padding: 14px; font-weight: 700; border: none; transition: all 0.3s ease; width: 100%; }
    .btn-custom:hover { opacity: 0.95; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); color: #fff; }
    .form-control { border-radius: 12px; padding: 12px; border: 1px solid #ddd; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1); border-color: var(--primary); outline: none; }
</style>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8 text-center">
            <div class="glass-card shadow-lg p-4 p-md-5">
                <h2 class="fw-bold text-dark mb-3">Reset Password</h2>
                <p class="text-muted small mb-4">Provide your email to receive a secure link.</p>

                <?= $msg ?>

                <?php if(!isset($_SESSION['reset_success'])): ?>
                <form method="POST">
                    <div class="mb-4 text-start">
                        <label class="small fw-bold text-muted uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>" placeholder="Enter your email">
                    </div>
                    <button type="submit" class="btn-custom">SEND RESET LINK</button>
                </form>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="login.php" class="small fw-bold text-decoration-none" style="color: var(--primary);">← Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>