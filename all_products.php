<?php
require 'config/db.php';
require 'includes/header.php';

// Fetch dynamic theme color
$settings = $pdo->query("SELECT * FROM site_settings WHERE id = 1")->fetch();
$primary_theme = $settings['primary_color'] ?? '#2563eb';
?>

<style>
    :root { --primary-theme: <?= $primary_theme ?>; }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }

    /* Header & Universal Filter UI */
    .catalog-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 40px 0; margin-bottom: 40px; }
    .filter-card { 
        background: #fff; border: 1px solid #e2e8f0; padding: 8px; 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto; 
    }
    .form-control-luxe { border: none; border-radius: 0; padding: 12px 20px; font-weight: 500; height: 45px; }
    .form-control-luxe:focus { box-shadow: none; outline: none; }
    
    #loading-indicator { display: none; position: absolute; right: 20px; top: 12px; z-index: 10; }

    /* Product Grid Styling */
    .inventory-card { 
        background: #fff; border: 1px solid #e2e8f0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        height: 100%; display: flex; flex-direction: column; position: relative;
    }
    .inventory-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); border-color: var(--primary-theme); }
    
    .img-frame { height: 250px; padding: 30px; display: flex; align-items: center; justify-content: center; background: #fff; position: relative; }
    .img-frame img { height: 250px; max-width: 100%; object-fit: contain; }

    /* Wishlist Icon - Circular Glass Style */
    .wishlist-btn {
        position: absolute; right: 15px; bottom: 15px;
        background: white; width: 38px; height: 38px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 1px solid #eee;
        cursor: pointer; z-index: 10; transition: 0.2s;
    }
    .wishlist-btn:hover { transform: scale(1.1); background: #fdfdfd; }
    .wishlist-btn i { font-size: 1.1rem; transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }

    .btn-luxe { 
        background: var(--primary-theme); color: #fff; border-radius: 0; padding: 12px; 
        font-weight: 700; text-transform: uppercase; border:none; width:100%; transition: 0.2s; 
    }
    .btn-luxe:hover { opacity: 0.9; }
</style>

<div class="catalog-header">
    <div class="container text-center">
        <h2 class="fw-bold mb-4 text-dark text-uppercase" style="letter-spacing: -1px;">Global Inventory Catalog</h2>
        
        <div class="filter-card shadow-sm">
            <div class="row g-0 align-items-center">
                <div class="col-md-8 border-end position-relative">
                    <input type="text" id="asyncSearch" class="form-control form-control-luxe" 
                           placeholder="Search products, categories, or sub-categories...">
                    <div id="loading-indicator" class="spinner-border spinner-border-sm text-primary"></div>
                </div>
                <div class="col-md-4">
                    <select id="asyncSort" class="form-select form-control-luxe">
                        <option value="newest">Sort: Newest First</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="name_az">Name: A-Z</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div id="product-grid-target" class="row g-4">
        </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // 1. Initial Load of Products
    fetchProducts();

    // 2. Real-time Search Logic (Debounced to 350ms)
    let typingTimer;
    $('#asyncSearch').on('keyup', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(fetchProducts, 350);
    });

    // 3. Sorting Change Logic
    $('#asyncSort').on('change', fetchProducts);

    function fetchProducts() {
        const query = $('#asyncSearch').val();
        const sortOrder = $('#asyncSort').val();
        
        $('#loading-indicator').show();

        $.ajax({
            url: 'fetch_filtered_products.php',
            method: 'GET',
            data: { search: query, sort: sortOrder },
            success: function(data) {
                $('#product-grid-target').html(data);
            },
            error: function() {
                $('#product-grid-target').html('<div class="col-12 text-center text-danger py-5">Connection Error.</div>');
            },
            complete: function() {
                $('#loading-indicator').hide();
            }
        });
    }

    // 4. DELEGATED EVENT: ADD TO CART
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const btn = $(this);
        const originalText = btn.html();

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    alert(res.message);
                    if(res.cart_count) $('.cart-count-badge').text(res.cart_count);
                } else if (res.status === 'already_in_cart') {
                    alert("Notice: " + res.message);
                } else if (res.message === 'login_required') {
                    window.location.href = 'login.php';
                } else {
                    alert(res.message);
                }
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // 5. DELEGATED EVENT: WISHLIST TOGGLE (Instant Color Change)
    $(document).on('click', '.wishlist-btn', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const btn = $(this);
        const icon = btn.find('i');

        $.ajax({
            url: 'ajax_wishlist.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'added') {
                    // INSTANT UI CHANGE
                    icon.removeClass('bi-heart').addClass('bi-heart-fill text-danger');
                    // Small pulse animation
                    icon.css('transform', 'scale(1.3)');
                    setTimeout(() => icon.css('transform', 'scale(1)'), 200);
                } 
                else if (res.status === 'removed') {
                    // INSTANT UI CHANGE
                    icon.removeClass('bi-heart-fill text-danger').addClass('bi-heart');
                } 
                else if (res.message === 'login_required') {
                    window.location.href = 'login.php';
                }
            },
            error: function() {
                alert('Connection error.');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>