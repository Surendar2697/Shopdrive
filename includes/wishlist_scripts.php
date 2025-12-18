$(document).on('click', '.wishlist-btn', function(e) {
    e.preventDefault();
    const productId = $(this).data('id');
    const btn = $(this);

    $.ajax({
        url: 'ajax_wishlist.php',
        method: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'added') {
                btn.find('i').removeClass('bi-heart').addClass('bi-heart-fill text-danger');
                alert(res.message);
            } else if (res.status === 'removed') {
                btn.find('i').removeClass('bi-heart-fill text-danger').addClass('bi-heart');
                alert(res.message);
            } else if (res.message === 'login_required') {
                window.location.href = 'login.php';
            }
        }
    });
});