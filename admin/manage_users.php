<?php 
require '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../includes/manual_autoload.php';

$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#00bcd4';
$current_user_id = $_SESSION['user_id'];

// RESEND INVITATION LOGIC
if (isset($_GET['resend_invite'])) {
    $user_id = $_GET['resend_invite'];
    $u = $pdo->prepare("SELECT fullname, email, role FROM users WHERE id = ? AND is_active = 0");
    $u->execute([$user_id]);
    $user_data = $u->fetch();

    if ($user_data) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));
        
        $pdo->prepare("UPDATE users SET verification_token = ?, token_expiry = ? WHERE id = ?")
            ->execute([$token, $expiry, $user_id]);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $settings['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $settings['smtp_user'];
            $mail->Password   = $settings['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $settings['smtp_port'];
            $mail->setFrom($settings['smtp_user'], 'Shopdrive Security');
            $mail->addAddress($user_data['email'], $user_data['fullname']);

            $invite_link = "http://" . $_SERVER['HTTP_HOST'] . str_replace('admin/manage_users.php', '', $_SERVER['PHP_SELF']) . "verify.php?token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = "Action Required: Complete Your Shopdrive Registration";
            
            // UPDATED PROFESSIONAL LIGHT EMAIL TEMPLATE
            $mail->Body = "
            <div style='background: #f4f4f4; padding: 40px 10px; font-family: sans-serif;'>
                <table align='center' width='100%' style='max-width: 550px; background: #ffffff; border: 1px solid #ddd; border-top: 5px solid $primary_theme;'>
                    <tr>
                        <td style='padding: 30px 40px;'>
                            <h2 style='color: #222; margin: 0;'>Finish setting up your account</h2>
                            <p style='color: #555; font-size: 15px; line-height: 1.6; margin-top: 20px;'>
                                Hello {$user_data['fullname']},<br><br>
                                You have been granted access to the Shopdrive portal as a <strong>".strtoupper($user_data['role'])."</strong>. To secure your account and set your password, click the button below.
                            </p>
                            <div style='margin: 35px 0;'>
                                <a href='$invite_link' style='background: $primary_theme; color: #fff; padding: 14px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; display: inline-block;'>ACTIVATE ACCOUNT</a>
                            </div>
                            <hr style='border: none; border-top: 1px solid #eee;'>
                            <p style='color: #999; font-size: 11px; margin-top: 20px;'>
                                Security Note: This link is unique to you and will expire in 30 minutes for your protection.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>";
            $mail->send();

            header("Location: manage_users.php?success=resent");
            exit();
        } catch (Exception $e) { header("Location: manage_users.php?error=mail_failed"); exit(); }
    }
}

// DELETE USER LOGIC
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($delete_id != $current_user_id) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_id]);
        header("Location: manage_users.php?success=deleted");
        exit();
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, fullname ASC")->fetchAll();
require '../includes/header.php'; 
?>

<style>
    :root { --theme: <?= $primary_theme ?>; }
    body { background-color: #f8f9fa; font-family: 'Inter', -apple-system, sans-serif; }
    
    .utility-bar { background: #fff; border-bottom: 1px solid #dee2e6; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
    .table-container { background: #fff; border: 1px solid #dee2e6; border-radius: 0; margin-top: 20px; }
    
    .th-utility { background: #fcfcfc !important; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #666; border-bottom: 1px solid #dee2e6; padding: 12px 20px !important; }
    .td-utility { font-size: 14px; padding: 12px 20px !important; vertical-align: middle !important; border-bottom: 1px solid #f1f1f1; }
    
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    .dot-active { background-color: var(--theme); box-shadow: 0 0 0 3px rgba(<?= hexToRgb($primary_theme) ?>, 0.1); }
    .dot-pending { background-color: #ffc107; }

    .btn-utility { border: 1px solid #ced4da; padding: 5px 12px; font-size: 12px; font-weight: 600; text-decoration: none; color: #444; background: #fff; transition: 0.2s; }
    .btn-utility:hover { background: #f8f9fa; border-color: #adb5bd; color: #000; }
    .btn-primary-util { background: var(--theme) !important; color: #fff !important; border-color: var(--theme) !important; }
    
    .text-danger-util { color: #dc3545 !important; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 p-0"><?php include '../includes/admin_sidebar.php'; ?></div>

        <div class="col-md-10 p-0">
            <div class="utility-bar">
                <h6 class="m-0 fw-bold"><i class="bi bi-people-fill me-2" style="color: var(--theme);"></i> USER DIRECTORY</h6>
                <a href="create_user.php" class="btn-utility btn-primary-util shadow-sm">NEW INVITATION</a>
            </div>

            <div class="px-4 pb-5">
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success border-0 rounded-0 mt-4 small"><i class="bi bi-info-circle me-2"></i> System updated. Actions applied successfully.</div>
                <?php endif; ?>

                <div class="table-container shadow-sm">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="th-utility">Name & Status</th>
                                <th class="th-utility">Access Level</th>
                                <th class="th-utility">Primary Email</th>
                                <th class="th-utility text-end">Management</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): 
                                $is_me = ($u['id'] == $current_user_id);
                                $is_pending = ($u['is_active'] == 0);
                                $is_staff = ($u['role'] === 'super_admin' || $u['role'] === 'vendor');
                            ?>
                            <tr>
                                <td class="td-utility">
                                    <div class="fw-bold"><?= htmlspecialchars($u['fullname']) ?></div>
                                    <div class="small text-muted">
                                        <span class="status-dot <?= $is_pending ? 'dot-pending' : 'dot-active' ?>"></span>
                                        <?= $is_pending ? 'Verification Pending' : 'Account Active' ?>
                                    </div>
                                </td>
                                <td class="td-utility"><span class="badge bg-light text-dark border fw-normal"><?= str_replace('_', ' ', $u['role']) ?></span></td>
                                <td class="td-utility"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="td-utility text-end">
                                    <?php if($is_me): ?>
                                        <span class="badge bg-secondary opacity-50 fw-normal">System Administrator</span>
                                    <?php else: ?>
                                        <?php if($is_pending && $is_staff): ?>
                                            <a href="?resend_invite=<?= $u['id'] ?>" class="btn-utility me-1">RESEND</a>
                                            <a href="?delete=<?= $u['id'] ?>" class="btn-utility text-danger-util" onclick="return confirm('Revoke invitation?')">REVOKE</a>
                                        <?php else: ?>
                                            <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn-utility me-1">EDIT</a>
                                            <a href="?delete=<?= $u['id'] ?>" class="btn-utility text-danger-util" onclick="return confirm('Delete user?')">DELETE</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
function hexToRgb($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "$r, $g, $b";
}
include '../includes/footer.php'; 
?>