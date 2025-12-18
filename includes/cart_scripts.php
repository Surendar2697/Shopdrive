<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {

    /**
     * Use Event Delegation: $(document).on('click', ...)
     * This ensures the button works even if it was loaded via AJAX 
     * (like in your all_products.php search results).
     */
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();

        // 1. Identify Product and Button
        const productId = $(this).data('id');
        const btn = $(this);
        const originalContent = btn.html();

        // 2. Visual Feedback: Disable button to prevent double-clicks
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Adding...');

        // 3. AJAX Request to Backend
        $.ajax({
            url: 'ajax_cart.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // Item added successfully
                    alert(res.message);
                    
                    // Update Header Cart Badge if it exists
                    updateHeaderCartCount(res.cart_count);
                } 
                else if (res.status === 'already_in_cart') {
                    // THE SPECIFIC ALERT YOU REQUESTED
                    alert("Notice: " + res.message);
                } 
                else if (res.message === 'login_required') {
                    // Redirect to login if user session expired or not logged in
                    window.location.href = 'login.php?msg=auth_required';
                } 
                else {
                    // Handle other errors (out of stock, invalid ID, etc.)
                    alert('Error: ' + res.message);
                }
            },
            error: function() {
                alert('Connection error. Could not communicate with the inventory server.');
            },
            complete: function() {
                // Restore button state
                btn.prop('disabled', false).html(originalContent);
            }
        });
    });

    /**
     * Function to update the red cart count badge in the header 
     * without refreshing the page.
     */
    function updateHeaderCartCount(count) {
        // Target the badge in your header.php (class used in our previous header)
        const badge = $('.cart-badge, .cart-count-badge');
        
        if (badge.length) {
            badge.text(count).fadeIn();
        } else {
            // If badge doesn't exist (e.g. was 0), you might need to reload or create it
            // For simplicity in this build, we reload to sync all UI elements
            location.reload(); 
        }
    }
});
</script>