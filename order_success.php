<?php
require 'config/db.php';
require 'includes/header.php';

// 1. Validate Order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { 
    echo "<script>window.location.href='index.php';</script>"; 
    exit(); 
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? 0;

// 2. Fetch Order Details with Logistics Info
$stmt = $pdo->prepare("SELECT o.*, u.fullname FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) { 
    echo "<div class='container py-5 text-center'><h3>Order Not Found</h3></div>"; 
    include 'includes/footer.php';
    exit(); 
}

// 3. Fetch Items
$item_stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$item_stmt->execute([$order_id]);
$items = $item_stmt->fetchAll();

$primary_color = $brand['primary_color'] ?? '#2563eb';
$status = strtolower($order['order_status']);
?>

<style>
    :root { --main-color: <?= $primary_color ?>; }
    body { background-color: #f8fafc; }
    .status-card { border-radius: 20px; border: none; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 25px; }
    
    /* Tracking Timeline Style */
    .track-step { position: relative; padding-bottom: 25px; padding-left: 35px; border-left: 2px solid #e2e8f0; }
    .track-step:last-child { border-left-color: transparent; }
    .track-step::before { 
        content: ''; position: absolute; left: -9px; top: 0; 
        width: 16px; height: 16px; border-radius: 50%; background: #e2e8f0; 
        border: 3px solid #fff; box-shadow: 0 0 0 2px #e2e8f0;
    }
    .track-step.active::before { background: var(--main-color); box-shadow: 0 0 0 2px var(--main-color); }
    .track-step.active { color: var(--main-color); font-weight: bold; }

    .tracking-box { background: #f8fafc; border-radius: 12px; padding: 18px; border: 1px solid #e2e8f0; }
    .btn-theme { background: var(--main-color) !important; color: #fff !important; border-radius: 50px; font-weight: bold; border: none; }
</style>

<div class="container py-5">
    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="status-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Tracking Timeline</h4>
                    <span class="badge px-3 py-2 rounded-pill" style="background: var(--main-color); color:#fff">
                        #ORD-<?= $order['id'] ?>
                    </span>
                </div>

                <div class="tracking-timeline mt-4">
                    <div class="track-step <?= in_array($status, ['ordered', 'shipped', 'delivered']) ? 'active' : '' ?>">
                        <i class="bi bi-patch-check-fill me-2"></i> Order Placed
                        <p class="small text-muted fw-normal mb-0">Your order has been received and is being processed.</p>
                    </div>

                    <div class="track-step <?= in_array($status, ['shipped', 'delivered']) ? 'active' : '' ?>">
                        <i class="bi bi-truck me-2"></i> Shipped
                        <p class="small text-muted fw-normal mb-0">Your package is out for delivery.</p>
                    </div>

                    <div class="track-step <?= $status == 'delivered' ? 'active' : '' ?>">
                        <i class="bi bi-house-heart-fill me-2"></i> Delivered
                        <p class="small text-muted fw-normal mb-0">Successfully handed over to the customer.</p>
                    </div>
                </div>

                <?php if(!empty($order['courier_name'])): ?>
                <div class="tracking-box mt-4 shadow-sm">
                    <h6 class="fw-bold mb-3"><i class="bi bi-box-seam-fill me-2 text-primary"></i>Logistics Partner</h6>
                    <div class="row align-items-center">
                        <div class="col-md-5 mb-2 mb-md-0">
                            <span class="text-muted small">Partner:</span> <br>
                            <span class="fw-bold"><?= htmlspecialchars($order['courier_name']) ?></span>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <span class="text-muted small">Tracking ID:</span> <br>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($order['tracking_id']) ?></span>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <?php if(!empty($order['tracking_link'])): ?>
                                <a href="<?= htmlspecialchars($order['tracking_link']) ?>" target="_blank" class="btn btn-sm btn-dark px-3 rounded-pill">Track Live</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="status-card shadow-sm">
                <h5 class="fw-bold mb-4">Item Details</h5>
                <?php foreach($items as $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom text-start">
                    <span class="text-dark"><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                    <span class="fw-bold">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between fs-4 fw-bold mt-4">
                    <span>Grand Total Paid</span>
                    <span style="color: var(--main-color);">₹<?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="status-card shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Shipping Address</h6>
                <div class="p-3 bg-light rounded-3 small text-muted lh-base">
                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                </div>
            </div>

            <div class="status-card shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-credit-card-fill me-2 text-primary"></i>Payment Summary</h6>
                <div class="d-flex justify-content-between small mb-2">
                    <span>Payment Status:</span>
                    <span class="fw-bold text-uppercase <?= $order['payment_status'] == 'paid' ? 'text-success' : 'text-warning' ?>">
                        <?= htmlspecialchars($order['payment_status']) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between small mb-3">
                    <span>Payment Mode:</span>
                    <span class="fw-bold">Razorpay Online</span>
                </div>
                <hr>
                <div class="d-grid gap-2 mt-2">
                    <a href="orders.php" class="btn btn-theme py-2 shadow-sm">View Order History</a>
                    <a href="index.php" class="btn btn-light border btn-sm py-2">Continue Shopping</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    $(document).ready(function() {
        if (typeof refreshHeaderCounts === "function") { refreshHeaderCounts(); }
    });
</script>

<?php include 'includes/footer.php'; ?>