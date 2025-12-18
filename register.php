<?php 
require 'config/db.php';
require 'includes/header.php';

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Hardcoded to 'customer' for security
    $role = 'customer'; 

    try {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullname, $email, $password, $role]);
        $msg = "<div class='alert alert-success'>Registration successful! <a href='login.php'>Login here</a></div>";
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger'>Email already exists or error occurred.</div>";
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="text-center fw-bold mb-4">Create Account</h3>
                    <?= $msg ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="fullname" class="form-control" placeholder="John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">Register as Customer</button>
                    </form>
                    <div class="text-center mt-3">
                        <small>Already have an account? <a href="login.php">Login</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>