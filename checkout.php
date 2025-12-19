<?php
require 'config/db.php';
require 'includes/header.php';

if (!isset($_SESSION['user_id'])) { 
    echo "<script>window.location.href='login.php';</script>"; 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_color = $settings['primary_color'] ?? '#2563eb';

// Fetch Data
$u_stmt = $pdo->prepare("SELECT fullname, email FROM users WHERE id = ?");
$u_stmt->execute([$user_id]);
$user_info = $u_stmt->fetch();

$addr_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC");
$addr_stmt->execute([$user_id]);
$saved_addresses = $addr_stmt->fetchAll();

$cart_stmt = $pdo->prepare("SELECT c.*, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll();

if (empty($cart_items)) { echo "<script>window.location.href='cart.php';</script>"; exit(); }

$subtotal = 0;
foreach ($cart_items as $item) { $subtotal += ($item['price'] * $item['quantity']); }
// SHIPPING IS NOW 0
$grand_total = $subtotal; 
?>

<style>
    :root { --main-color: <?= $primary_color ?>; }
    body { background-color: #f8fafc; }
    .checkout-card { border-radius: 20px; border: none; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .address-box { border: 2px solid #f1f5f9; border-radius: 12px; padding: 15px; cursor: pointer; position: relative; height: 100%; }
    .address-box.selected { border-color: var(--main-color); background: #f0f7ff; }
    .address-box .check-icon { position: absolute; top: 10px; right: 10px; color: var(--main-color); display: none; }
    .address-box.selected .check-icon { display: block; }
    .delete-addr-btn { position: absolute; bottom: 10px; right: 10px; color: #ef4444; cursor: pointer; z-index: 50; }
    .btn-pay { background: var(--main-color) !important; color: #fff !important; border: none; }
    .btn-outline-theme { border: 2px solid var(--main-color); color: var(--main-color); font-weight: bold; }
    .btn-outline-theme:hover { background-color: var(--main-color) !important; color: #fff !important; }
    .form-control:focus { border-color: var(--main-color); box-shadow: none; }
    .free-badge { color: #10b981; font-weight: bold; }
</style>

<div class="container py-5">
    <form id="checkoutForm">
        <input type="hidden" name="is_existing_address" id="is_existing_address" value="">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="checkout-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0" style="color: var(--main-color);">Shipping Information</h5>
                        <button type="button" class="btn btn-sm btn-outline-theme rounded-pill px-4" id="addNewAddressBtn">New Address</button>
                    </div>

                    <?php if(!empty($saved_addresses)): ?>
                    <div class="row g-3 mb-4" id="addressListContainer">
                        <?php foreach($saved_addresses as $addr): ?>
                        <div class="col-md-6" id="addr_wrapper_<?= $addr['id'] ?>">
                            <div class="address-box" data-name="<?= htmlspecialchars($addr['fullname']) ?>" data-phone="<?= htmlspecialchars($addr['phone']) ?>" data-line="<?= htmlspecialchars($addr['address_line']) ?>" data-city="<?= htmlspecialchars($addr['city']) ?>" data-state="<?= htmlspecialchars($addr['state']) ?>" data-pin="<?= htmlspecialchars($addr['pincode']) ?>">
                                <i class="bi bi-check-circle-fill check-icon"></i>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($addr['fullname']) ?></h6>
                                <p class="small text-muted mb-0"><?= htmlspecialchars($addr['address_line']) ?></p>
                                <span class="delete-addr-btn" onclick="deleteAddress(<?= $addr['id'] ?>, event)"><i class="bi bi-trash3-fill"></i></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div id="formFields" class="row g-3">
                        <div class="col-md-6"><label class="small fw-bold">FULL NAME</label><input type="text" name="fullname" id="f_name" class="form-control" value="<?= htmlspecialchars($user_info['fullname']) ?>" required></div>
                        <div class="col-md-6"><label class="small fw-bold">MOBILE</label><input type="tel" name="phone" id="f_phone" class="form-control" pattern="[0-9]{10}" required></div>
                        <div class="col-12"><label class="small fw-bold">ADDRESS</label><textarea name="address" id="f_addr" class="form-control" rows="2" required></textarea></div>
                        <div class="col-md-4"><label class="small fw-bold">CITY</label><input type="text" name="city" id="f_city" class="form-control" required></div>
                        <div class="col-md-4"><label class="small fw-bold">STATE</label><input type="text" name="state" id="f_state" class="form-control" required></div>
                        <div class="col-md-4"><label class="small fw-bold">PINCODE</label><input type="text" name="pincode" id="f_pin" class="form-control" pattern="[0-9]{6}" required></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="checkout-card p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold mb-4">Summary</h5>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>₹<?= number_format($subtotal, 2) ?></span></div>
                    <div class="d-flex justify-content-between mb-4"><span>Shipping</span><span class="free-badge">FREE</span></div>
                    <div class="d-flex justify-content-between mb-4 fs-4 border-top pt-3"><span class="fw-bold">Total</span><span class="fw-bold" style="color: var(--main-color);">₹<?= number_format($grand_total, 2) ?></span></div>
                    <button type="submit" id="placeOrderBtn" class="btn btn-pay w-100 py-3 fw-bold rounded-pill shadow">CONFIRM & PAY</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function deleteAddress(id, event) {
    event.stopPropagation();
    if(confirm('Delete this address?')) {
        $.ajax({
            url: 'process_order.php',
            method: 'POST',
            data: { action: 'delete_address', address_id: id },
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    $('#addr_wrapper_' + id).fadeOut(300, function() { $(this).remove(); });
                }
            }
        });
    }
}

$(document).ready(function() {
    $('.address-box').click(function() {
        $('.address-box').removeClass('selected');
        $(this).addClass('selected');
        $('#is_existing_address').val('1');
        $('#f_name').val($(this).data('name')); $('#f_phone').val($(this).data('phone'));
        $('#f_addr').val($(this).data('line')); $('#f_city').val($(this).data('city'));
        $('#f_state').val($(this).data('state')); $('#f_pin').val($(this).data('pin'));
    });

    $('#addNewAddressBtn').click(function() {
        $('.address-box').removeClass('selected');
        $('#is_existing_address').val('');
        $('#f_phone, #f_addr, #f_city, #f_state, #f_pin').val('');
        $('#f_name').val('<?= htmlspecialchars($user_info['fullname']) ?>');
    });

    $('#checkoutForm').on('submit', function(e) {
        e.preventDefault();
        $('#placeOrderBtn').prop('disabled', true).text('Processing...');
        $.ajax({
            url: 'process_order.php',
            method: 'POST',
            data: $(this).serialize() + '&action=initiate_order',
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    var options = {
                        "key": res.key, "amount": res.amount, "currency": "INR", "order_id": res.razor_order_id,
                        "handler": function (p){ verify(p, res.db_order_id); },
                        "theme": { "color": "<?= $primary_color ?>" }
                    };
                    new Razorpay(options).open();
                } else { alert(res.message); $('#placeOrderBtn').prop('disabled', false).text('CONFIRM & PAY'); }
            }
        });
    });
});

function verify(p, id) {
    $.ajax({ url: 'process_order.php', method: 'POST', data: { action: 'verify_payment', razorpay_payment_id: p.razorpay_payment_id, db_order_id: id }, success: function() { window.location.href = 'order_success.php?id=' + id; } });
}
</script>
<?php include 'includes/footer.php'; ?>