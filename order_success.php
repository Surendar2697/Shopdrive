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

// 2. Fetch Order Details (Basic Info)
$stmt = $pdo->prepare("SELECT o.*, u.fullname FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) { 
    echo "<div class='container py-5 text-center'><h3>Order Not Found</h3></div>"; 
    include 'includes/footer.php';
    exit(); 
}

// 3. Fetch Items with individual tracking info
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

    /* Multi-vendor Item Tracking Box */
    .item-tracking-card { 
        background: #fdfdfd; border: 1px solid #e2e8f0; border-radius: 15px; 
        padding: 15px; margin-bottom: 15px; border-left: 5px solid var(--main-color);
    }
    .tracking-badge { font-size: 10px; font-weight: bold; text-transform: uppercase; background: #eef2ff; color: var(--main-color); padding: 4px 10px; border-radius: 50px; }
    .btn-theme { background: var(--main-color) !important; color: #fff !important; border-radius: 50px; font-weight: bold; border: none; }
</style>

<div class="container py-5">
    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="status-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0 text-dark">Order Status</h4>
                    <span class="badge px-3 py-2 rounded-pill" style="background: var(--main-color); color:#fff">
                        #ORD-<?= $order['id'] ?>
                    </span>
                </div>

                <div class="tracking-timeline mt-4">
                    <div class="track-step <?= in_array($status, ['ordered', 'shipped', 'delivered']) ? 'active' : '' ?>">
                        <i class="bi bi-patch-check-fill me-2"></i> Order Received
                        <p class="small text-muted fw-normal mb-0">We have received your payment and notified the sellers.</p>
                    </div>

                    <div class="track-step <?= $status == 'delivered' ? 'active' : '' ?>">
                        <i class="bi bi-house-heart-fill me-2"></i> Order Completed
                        <p class="small text-muted fw-normal mb-0">Items have been delivered to your doorstep.</p>
                    </div>
                </div>
            </div>

            <div class="status-card shadow-sm">
                <h5 class="fw-bold mb-4">Shipment Details</h5>
                <p class="text-muted small">Your order contains items from different sellers. Each may ship separately.</p>

                <?php 
                $shipment_found = false;
                foreach($items as $it): 
                    if(!empty($it['tracking_id'])): 
                        $shipment_found = true;
                ?>
                <div class="item-tracking-card shadow-sm">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <span class="tracking-badge mb-2 d-inline-block">Package for <?= htmlspecialchars($it['name']) ?></span>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($it['courier_name']) ?></div>
                            <div class="small text-muted">Tracking ID: <span class="text-dark fw-bold"><?= htmlspecialchars($it['tracking_id']) ?></span></div>
                        </div>
                        <div class="col-md-3">
                            <span class="badge rounded-pill <?= $it['item_status'] == 'shipped' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                <?= strtoupper($it['item_status']) ?>
                            </span>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <?php if(!empty($it['tracking_link'])): ?>
                                <a href="<?= htmlspecialchars($it['tracking_link']) ?>" target="_blank" class="btn btn-sm btn-dark px-3 rounded-pill mt-2">Track Item</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; endforeach; ?>

                <?php if(!$shipment_found): ?>
                    <div class="text-center py-4 bg-light rounded-4">
                        <i class="bi bi-clock-history fs-2 text-muted"></i>
                        <p class="text-muted small mt-2">Sellers are currently preparing your items. Tracking info will appear here once shipped.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="status-card shadow-sm">
                <h5 class="fw-bold mb-4">Purchased Items</h5>
                <?php foreach($items as $item): ?>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom text-start">
                    <div>
                        <span class="text-dark fw-bold"><?= htmlspecialchars($item['name']) ?></span>
                        <br><small class="text-muted">Quantity: <?= $item['quantity'] ?></small>
                    </div>
                    <span class="fw-bold">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between fs-4 fw-bold mt-4">
                    <span>Amount Paid</span>
                    <span style="color: var(--main-color);">₹<?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="status-card shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Delivery Address</h6>
                <div class="p-3 bg-light rounded-3 small text-muted lh-base">
                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                </div>
            </div>

            <div class="status-card shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-credit-card-fill me-2 text-primary"></i>Payment Info</h6>
                <div class="d-flex justify-content-between small mb-2">
                    <span>Status:</span>
                    <span class="fw-bold text-uppercase <?= $order['payment_status'] == 'paid' ? 'text-success' : 'text-warning' ?>">
                        <?= htmlspecialchars($order['payment_status']) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between small mb-3">
                    <span>Gateway:</span>
                    <span class="fw-bold">Razorpay Online</span>
                </div>
                <hr>
                <div class="d-grid gap-2 mt-2">
                    <a href="orders.php" class="btn btn-theme py-2 shadow-sm">My Orders</a>
                    <a href="index.php" class="btn btn-light border btn-sm py-2">Continue Shopping</a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>