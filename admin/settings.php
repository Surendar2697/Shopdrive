<?php 
require '../config/db.php';

// 1. SECURITY & LOGIC
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch settings once for the entire page
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();

// 2. UNIFIED PROCESSING LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    
    $logo_name = $settings['app_logo']; 
    if (!empty($_FILES['app_logo']['name'])) {
        $target_dir = "../assets/uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $logo_name = "logo_" . time() . "_" . str_replace(' ', '_', $_FILES['app_logo']['name']);
        move_uploaded_file($_FILES['app_logo']['tmp_name'], $target_dir . $logo_name);
    }

    $sql = "UPDATE site_settings SET 
            app_name = ?, primary_color = ?, header_color = ?, app_logo = ?,
            smtp_host = ?, smtp_user = ?, smtp_pass = ?, smtp_port = ?,
            razorpay_key_id = ?, razorpay_key_secret = ? WHERE id = 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['app_name'], $_POST['primary_color'], $_POST['header_color'], $logo_name,
        $_POST['smtp_host'], $_POST['smtp_user'], $_POST['smtp_pass'], $_POST['smtp_port'],
        $_POST['razorpay_key_id'], $_POST['razorpay_key_secret']
    ]);
    header("Location: settings.php?success=1");
    exit();
}

// 3. MULTIPLE BANNER LOGIC
if (isset($_POST['add_banner'])) {
    $target_dir = "../assets/banners/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    if (!empty($_FILES['banner_images']['name'][0])) {
        foreach ($_FILES['banner_images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['banner_images']['name'][$key];
            $clean_name = time() . "_" . str_replace(' ', '_', $file_name);
            if (move_uploaded_file($tmp_name, $target_dir . $clean_name)) {
                $pdo->prepare("INSERT INTO site_banners (banner_image) VALUES (?)")->execute([$clean_name]);
            }
        }
        header("Location: settings.php?success=1");
        exit();
    }
}

// 4. DELETE BANNER
if (isset($_GET['del_banner'])) {
    $pdo->prepare("DELETE FROM site_banners WHERE id = ?")->execute([$_GET['del_banner']]);
    header("Location: settings.php");
    exit();
}

$banners = $pdo->query("SELECT * FROM site_banners ORDER BY id DESC")->fetchAll();
require '../includes/header.php'; 
?>

<style>
    /* Pulling the Primary Theme Color directly from Database variable */
    :root {
        --dynamic-main: <?= $settings['primary_color'] ?>;
    }

    /* Target Active Tab, Save Button, and Form Focus globally */
    .nav-pills .nav-link.active, 
    .nav-pills .show > .nav-link,
    .btn-primary,
    #global-save-btn button {
        background-color: var(--dynamic-main) !important;
        border-color: var(--dynamic-main) !important;
        color: #ffffff !important;
    }

    /* Ensure text links also follow theme */
    .text-primary {
        color: var(--dynamic-main) !important;
    }

    /* Style for secondary buttons or banner upload */
    .btn-dark {
        background-color: #333;
        border: none;
    }

    .border-dashed { border: 2px dashed var(--dynamic-main); }

    .form-control:focus {
        border-color: var(--dynamic-main);
        box-shadow: 0 0 0 0.25rem rgba(0, 0, 0, 0.05);
    }
</style>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 mb-4"><?php include '../includes/admin_sidebar.php'; ?></div>
        <div class="col-md-10">
            <h2 class="fw-bold mb-4">Site Customization</h2>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 alert-dismissible fade show">
                    <i class="bi bi-check2-all me-2"></i>Branding and Configuration updated!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-0">
                <div class="card-header bg-white border-bottom py-3">
                    <ul class="nav nav-pills card-header-pills" id="settingsTab" role="tablist">
                        <li class="nav-item"><a class="nav-link active fw-bold" data-bs-toggle="tab" href="#branding">Branding</a></li>
                        <li class="nav-item"><a class="nav-link fw-bold" data-bs-toggle="tab" href="#banners">Slide Banners</a></li>
                        <li class="nav-item"><a class="nav-link fw-bold" data-bs-toggle="tab" href="#smtp">SMTP (Email)</a></li>
                        <li class="nav-item"><a class="nav-link fw-bold" data-bs-toggle="tab" href="#payment">Payment (Razorpay)</a></li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="tab-content">
                            
                            <div class="tab-pane fade show active" id="branding">
                                <div class="row g-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold small text-uppercase">Application Name</label>
                                        <input type="text" name="app_name" class="form-control rounded-0" value="<?= htmlspecialchars($settings['app_name']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-uppercase">Primary Theme Color</label>
                                        <input type="color" name="primary_color" class="form-control form-control-color w-100 border-0" value="<?= $settings['primary_color'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-uppercase">Header Background Color</label>
                                        <input type="color" name="header_color" class="form-control form-control-color w-100 border-0" value="<?= $settings['header_color'] ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold small text-uppercase">Update Logo</label>
                                        <input type="file" name="app_logo" class="form-control rounded-0">
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="banners">
                                <div class="p-5 border-dashed rounded-0 bg-light text-center mb-4">
                                    <i class="bi bi-images fs-1"></i>
                                    <label class="form-label d-block fw-bold mt-2 text-uppercase">Upload Multiple Banners</label>
                                    <input type="file" name="banner_images[]" class="form-control w-50 mx-auto rounded-0" multiple>
                                    <button name="add_banner" type="submit" class="btn btn-dark px-5 mt-3 rounded-0 text-uppercase fw-bold">Upload Images</button>
                                </div>
                                <div class="row g-3">
                                    <?php foreach($banners as $b): ?>
                                        <div class="col-md-3">
                                            <div class="card border shadow-sm position-relative overflow-hidden">
                                                <img src="../assets/banners/<?= $b['banner_image'] ?>" class="card-img-top" style="height: 120px; object-fit: cover;">
                                                <a href="?del_banner=<?= $b['id'] ?>" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 rounded-0"><i class="bi bi-x"></i></a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="smtp">
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="small fw-bold text-uppercase">Host</label><input type="text" name="smtp_host" class="form-control rounded-0" value="<?= htmlspecialchars($settings['smtp_host']) ?>"></div>
                                    <div class="col-md-6"><label class="small fw-bold text-uppercase">Port</label><input type="text" name="smtp_port" class="form-control rounded-0" value="<?= htmlspecialchars($settings['smtp_port']) ?>"></div>
                                    <div class="col-md-6"><label class="small fw-bold text-uppercase">User</label><input type="text" name="smtp_user" class="form-control rounded-0" value="<?= htmlspecialchars($settings['smtp_user']) ?>"></div>
                                    <div class="col-md-6"><label class="small fw-bold text-uppercase">Pass</label><input type="password" name="smtp_pass" class="form-control rounded-0" value="<?= htmlspecialchars($settings['smtp_pass']) ?>"></div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="payment">
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="small fw-bold text-uppercase">Razorpay Key ID</label><input type="text" name="razorpay_key_id" class="form-control rounded-0" value="<?= htmlspecialchars($settings['razorpay_key_id']) ?>"></div>
                                    <div class="col-md-6"><label class="small fw-bold text-uppercase">Razorpay Secret</label><input type="password" name="razorpay_key_secret" class="form-control rounded-0" value="<?= htmlspecialchars($settings['razorpay_key_secret']) ?>"></div>
                                </div>
                            </div>

                        </div>

                        <div class="mt-5 text-end border-top pt-4" id="global-save-btn">
                            <button type="submit" name="update_settings" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold rounded-0 text-uppercase">Update All Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const saveBtn = document.getElementById('global-save-btn');
        let activeTab = localStorage.getItem('activeSettingsTab');
        if (activeTab) {
            let tabEl = document.querySelector('a[href="' + activeTab + '"]');
            if (tabEl) { 
                new bootstrap.Tab(tabEl).show(); 
                toggleButton(activeTab);
            }
        }
        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(link => {
            link.addEventListener('shown.bs.tab', e => {
                const target = e.target.getAttribute("href");
                localStorage.setItem('activeSettingsTab', target);
                toggleButton(target);
            });
        });
        function toggleButton(tabId) {
            saveBtn.style.display = (tabId === '#banners') ? 'none' : 'block';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>