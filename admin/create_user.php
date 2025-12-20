<?php 
ob_start(); 
require '../config/db.php';

// 1. SECURITY & SESSION CHECK
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../includes/manual_autoload.php';

// Fetch settings for dynamic theme alignment
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#00bcd4';

$msg = "";
$success_mode = false;

// 2. CHECK FOR REDIRECT SUCCESS
if (isset($_SESSION['invite_msg'])) {
    $msg = $_SESSION['invite_msg'];
    $success_mode = true;
    unset($_SESSION['invite_msg']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim(strtolower($_POST['email']));
    $role = $_POST['role'];
    
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    try {
        $pdo->beginTransaction();

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) { throw new Exception("Action Denied: This email address is already on file."); }

        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, role, verification_token, token_expiry, is_active) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$fullname, $email, $role, $token, $expiry]);

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_user'];
        $mail->Password   = $settings['smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $settings['smtp_port'];

        $mail->setFrom($settings['smtp_user'], 'Shopdrive Administration');
        $mail->addAddress($email, $fullname);

        $invite_link = "http://" . $_SERVER['HTTP_HOST'] . str_replace('admin/create_user.php', '', $_SERVER['PHP_SELF']) . "verify.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Invitation: Administrative Provisioning";
        $mail->Body = "
            <div style='background: #f8f9fa; padding: 50px 20px; font-family: Arial, sans-serif; text-align: center;'>
                <div style='max-width: 500px; margin: auto; background: #fff; border-top: 4px solid $primary_theme; padding: 40px; border-radius: 8px;'>
                    <h1 style='color: $primary_theme; font-size: 24px; margin: 0;'>SHOPDRIVE</h1>
                    <p style='color: #333; font-size: 16px; margin-top: 20px;'>Hi $fullname, you have been invited to join the network as a <strong>$role</strong>.</p>
                    <div style='margin: 30px 0;'><a href='$invite_link' style='background: $primary_theme; color: #fff; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold;'>COMPLETE ACTIVATION</a></div>
                </div>
            </div>";

        $mail->send();
        $pdo->commit();

        $_SESSION['invite_msg'] = "Invitation successfully dispatched to $email";
        header("Location: create_user.php");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $msg = "<div class='alert alert-danger border-0 rounded-0 small p-3'><strong>Error:</strong> " . $e->getMessage() . "</div>";
    }
}

require '../includes/header.php'; 
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #fcfcfc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }

    /* Layout Wrapper */
    .manager-surface { background: #fff; border: 1px solid #eef0f2; border-radius: 4px; }
    .manager-surface-header { padding: 1.5rem; background: #fdfdfd; border-bottom: 1px solid #f1f1f1; display: flex; justify-content: space-between; align-items: center; }
    
    /* Clean Label & Input */
    .field-label { font-size: 12px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: block; }
    .field-input { width: 100%; border: 1px solid #d1d9e0; border-radius: 4px; padding: 12px 16px; font-size: 15px; background: #fff; transition: border-color 0.2s; }
    .field-input:focus { border-color: var(--primary-theme); outline: none; box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1); }
    
    .btn-submit { background: var(--primary-theme); color: #fff; border: none; padding: 12px 35px; font-size: 13px; font-weight: 700; border-radius: 4px; text-transform: uppercase; transition: 0.2s; }
    .btn-submit:hover { opacity: 0.9; cursor: pointer; color: #fff; }

    /* Success Result Banner */
    .result-banner { background: var(--primary-theme); color: #fff; padding: 18px 25px; border-radius: 4px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; font-size: 14px; font-weight: 600; }
</style>

<div class="container-fluid mt-4 pb-5">
    <div class="row">
        <div class="col-md-2 mb-4">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-10 px-lg-4">
            <h5 class="fw-bold mb-4 text-uppercase text-muted" style="letter-spacing: 1.5px; font-size: 13px;">Onboarding Management</h5>

            <div class="row">
                <div class="col-xl-6">
                    
                    <?php if($success_mode): ?>
                        <div class="result-banner shadow-sm">
                            <i class="bi bi-send-check-fill fs-4"></i>
                            <span><?= $msg ?></span>
                        </div>
                    <?php elseif($msg): ?>
                        <div class="mb-4"><?= $msg ?></div>
                    <?php endif; ?>

                    <div class="manager-surface shadow-sm">
                        <div class="manager-surface-header">
                            <h2 class="m-0" style="font-size: 14px; font-weight: 700; color: #333; text-transform: uppercase;">Personnel Invitation</h2>
                            <i class="bi bi-envelope-plus-fill text-muted"></i>
                        </div>
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" autocomplete="off">
                                <div class="mb-4">
                                    <label class="field-label">Full Name</label>
                                    <input type="text" name="fullname" class="field-input" required>
                                </div>

                                <div class="mb-4">
                                    <label class="field-label">Email Address</label>
                                    <input type="email" name="email" class="field-input" required>
                                </div>

                                <div class="mb-5">
                                    <label class="field-label">System Role</label>
                                    <select name="role" class="field-input form-select">
                                        <option value="vendor">Vendor (Selling Privileges)</option>
                                        <option value="super_admin">Super Admin (Root Privileges)</option>
                                    </select>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn-submit">
                                        Confirm & Send Link
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 offset-xl-1 mt-5 mt-xl-0">
                    <div class="p-4 bg-light border-start border-4" style="border-left-color: var(--primary-theme) !important;">
                        <h6 class="fw-bold mb-3 small text-uppercase" style="letter-spacing: 1px;">Security Protocol</h6>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2"><i class="bi bi-shield-check text-success me-2"></i> Zero-Knowledge: Passwords are set by the user.</li>
                            <li><i class="bi bi-clock-history text-danger me-2"></i> Expiry: Activation tokens expire in 30 minutes.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>