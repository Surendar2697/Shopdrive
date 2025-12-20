<?php
ob_start();
require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// ---------------------------------------------------------
// 1. DATABASE UPDATES
// ---------------------------------------------------------

// Delete Address AJAX
if (isset($_POST['action']) && $_POST['action'] == 'delete_address') {
    ob_clean();
    $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?")
        ->execute([$_POST['address_id'], $user_id]);
    echo json_encode(['status' => 'success']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update Name
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $pdo->prepare("UPDATE users SET fullname = ? WHERE id = ?")->execute([$fullname, $user_id]);
        $_SESSION['fullname'] = $fullname;
        $msg = "Profile updated successfully!";
    }

    // Change Password
    if (isset($_POST['change_password'])) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $u_row = $stmt->fetch();

        if (password_verify($_POST['current_password'], $u_row['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password'] && strlen($_POST['new_password']) >= 6) {
                $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
                $msg = "Password changed successfully!";
            } else { $msg = "Error: Passwords must match and be 6+ chars."; }
        } else { $msg = "Error: Current password incorrect."; }
    }

    // Add Address
    if (isset($_POST['add_new_address'])) {
        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, fullname, phone, address_line, city, state, pincode) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $_POST['f_name'], $_POST['f_phone'], $_POST['f_addr'], $_POST['f_city'], $_POST['f_state'], $_POST['f_pin']]);
        $msg = "New address added!";
    }
}

// Data Fetching
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $user->execute([$user_id]); $u = $user->fetch();
$addresses = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC"); $addresses->execute([$user_id]); $saved_addr = $addresses->fetchAll();
$primary_color = $pdo->query("SELECT primary_color FROM site_settings WHERE id = 1")->fetchColumn() ?? '#2563eb';

require_once 'includes/header.php';
?>

<style>
    :root { --accent: <?= $primary_color ?>; }
    body { background-color: #f3f4f6; color: #1f2937; }
    
    .settings-wrapper { max-width: 1000px; margin: 40px auto; }
    .nav-settings { background: #fff; border-radius: 12px; padding: 10px; border: 1px solid #e5e7eb; }
    .nav-settings .nav-link { 
        color: #6b7280; font-weight: 600; border-radius: 8px; 
        padding: 12px 20px; transition: 0.2s; border: none; width: 100%; text-align: left;
    }
    .nav-settings .nav-link.active { background: #f9fafb; color: var(--accent); }
    
    .content-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .section-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 5px; }
    .section-subtitle { font-size: 0.875rem; color: #6b7280; margin-bottom: 25px; }
    
    .form-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #4b5563; margin-bottom: 6px; }
    .form-control { border-radius: 8px; border: 1px solid #d1d5db; padding: 10px 14px; }
    .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    
    .addr-pill { border: 1px solid #e5e7eb; border-radius: 12px; padding: 15px; background: #f9fafb; position: relative; }
    .btn-action { background: var(--accent); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: 10px 25px; }
    .btn-delete { color: #ef4444; background: none; border: none; font-size: 0.875rem; font-weight: 600; }
</style>

<div class="container settings-wrapper">
    <?php if($msg): ?>
        <div class="alert alert-dark border-0 rounded-4 mb-4 fw-bold shadow-sm"><?= $msg ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="nav nav-settings flex-column" id="settings-tabs" role="tablist">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-profile">Profile Info</button>
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-address">Addresses</button>
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-security">Security</button>
            </div>
        </div>

        <div class="col-md-9">
            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="tab-profile">
                    <div class="content-card">
                        <h3 class="section-title">Personal Information</h3>
                        <p class="section-subtitle">Manage your public display name and account email.</p>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($u['fullname']) ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Email Address</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($u['email']) ?>" readonly>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-action">Update Profile</button>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-address">
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h3 class="section-title">Shipping Addresses</h3>
                            <button class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addModal">+ Add New</button>
                        </div>
                        <p class="section-subtitle">Your saved locations for faster checkout.</p>
                        
                        <div class="row g-3">
                            <?php foreach($saved_addr as $a): ?>
                            <div class="col-12" id="box_<?= $a['id'] ?>">
                                <div class="addr-pill d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($a['fullname']) ?> <span class="text-muted small ms-2"><?= $a['phone'] ?></span></div>
                                        <div class="small text-muted"><?= $a['address_line'] ?>, <?= $a['city'] ?>, <?= $a['state'] ?> - <?= $a['pincode'] ?></div>
                                    </div>
                                    <button onclick="delAddr(<?= $a['id'] ?>)" class="btn-delete">Delete</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-security">
                    <div class="content-card">
                        <h3 class="section-title">Security Settings</h3>
                        <p class="section-subtitle">Change your password to keep your account safe.</p>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-action">Change Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <form method="POST">
                <div class="modal-body p-4">
                    <h5 class="fw-bold mb-4">Add Address</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="f_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="tel" name="f_phone" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Street Address</label><input type="text" name="f_addr" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">City</label><input type="text" name="f_city" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">State</label><input type="text" name="f_state" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Pincode</label><input type="text" name="f_pin" class="form-control" required></div>
                    </div>
                    <button type="submit" name="add_new_address" class="btn btn-action w-100 mt-4">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function delAddr(id) {
    if(confirm('Delete this address?')) {
        $.post('settings.php', { action: 'delete_address', address_id: id }, function() {
            $('#box_' + id).fadeOut();
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>