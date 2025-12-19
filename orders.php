<?php
require 'config/db.php';
require 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all orders for this user
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Get primary color from brand settings
$primary_color = $brand['primary_color'] ?? '#2563eb';
?>

<style>
    :root { --main-color: <?= $primary_color ?>; }
    body { background-color: #f8fafc; }
    .page-header { background: #fff; padding: 2rem 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 2rem; }
    .order-card { 
        background: #fff; border-radius: 15px; border: none; 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
        transition: 0.3s; margin-bottom: 1.5rem; overflow: hidden;
    }
    .order-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .order-header { background: #fcfcfd; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
    .status-badge { padding: 0.4rem 1rem; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
    
    /* Dynamic Status Colors */
    .status-placed { background: #dcfce7; color: #166534; }
    .status-pending { background: #fef9c3; color: #854d0e; }
    .status-shipped { background: #e0f2fe; color: #075985; }
    .status-delivered { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    
    .btn-view { color: var(--main-color); border: 1px solid var(--main-color); font-weight: 600; border-radius: 8px; }
    .btn-view:hover { background: var(--main-color); color: #fff; }
</style>

<div class="page-header">
    <div class="container">
        <h2 class="fw-bold mb-0">My Purchase History</h2>
        <p class="text-muted mb-0">Track and manage your orders</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-box-seam display-1 text-muted opacity-25"></i>
                    <h4 class="mt-3 fw-bold">No orders found</h4>
                    <p class="text-muted">You haven't placed any orders yet.</p>
                    <a href="index.php" class="btn btn-primary rounded-pill px-5 mt-2" style="background: var(--main-color); border: none;">Start Shopping</a>
                </div>
            <?php else: ?>
                
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <span class="text-muted small">ORDER ID</span>
                                <div class="fw-bold text-dark">#ORD-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                            </div>
                            <div>
                                <span class="text-muted small">DATE PLACED</span>
                                <div class="fw-bold text-dark"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                            </div>
                            <div>
                                <span class="text-muted small">TOTAL AMOUNT</span>
                                <div class="fw-bold text-primary">₹<?= number_format($order['total_amount'], 2) ?></div>
                            </div>
                            <div>
                                <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                                    <?= $order['order_status'] ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-credit-card-2-front me-3 fs-3 text-muted"></i>
                                <div>
                                    <div class="small text-muted">Payment Status</div>
                                    <div class="fw-bold <?= $order['payment_status'] == 'paid' ? 'text-success' : 'text-danger' ?>">
                                        <?= strtoupper($order['payment_status']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt me-3 fs-3 text-muted"></i>
                                <div style="max-width: 300px;">
                                    <div class="small text-muted">Shipping Address</div>
                                    <div class="small text-truncate"><?= htmlspecialchars($order['shipping_address']) ?></div>
                                </div>
                            </div>

                            <div>
                                <a href="order_success.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-view px-4 py-2">
                                    VIEW DETAILS
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>