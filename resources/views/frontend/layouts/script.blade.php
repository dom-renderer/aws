<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
<script>
$(function () {

    const products = [{
        price: "$299",
        images: [
            "images/food-img2.png",
            "images/food-img3.png",
            "images/food-img4.png",
            "images/food-img4.png"
        ]
    }];

    const $productsGrid = $('#productsGrid');
    const slideshowIntervals = {};

    $.each(products, function (index, product) {

        const $productCard = $('<div>', {
            class: 'product-card',
            'data-product-index': index
        });

        const $imageContainer = $('<div>', { class: 'product-image-container' });

        $.each(product.images, function (imgIndex, imgSrc) {
            $('<img>', {
                src: imgSrc,
                class: 'product-image ' + (imgIndex === 0 ? 'active' : ''),
                alt: product.name || ''
            }).appendTo($imageContainer);
        });

        const $indicatorContainer = $('<div>', { class: 'image-indicator' });

        $.each(product.images, function (imgIndex) {
            $('<div>', {
                class: 'indicator-dot ' + (imgIndex === 0 ? 'active' : '')
            }).appendTo($indicatorContainer);
        });

        $imageContainer.append($indicatorContainer);

        const $productInfo = $(`
            <div class="product-info">
                <div class="wish-list">
                    <button class="btn">
                        <img src="images/menuicon-3.svg" alt="">
                        Wishlist
                    </button>
                </div>
            </div>
        `);

        $productCard.append($imageContainer, $productInfo);
        $productsGrid.append($productCard);

        let currentImageIndex = 0;
        let hoverTimeout = null;

        $productCard.on('mouseenter', function () {
            const $images = $imageContainer.find('.product-image');
            const $dots = $indicatorContainer.find('.indicator-dot');

            function changeImage() {
                $images.eq(currentImageIndex).removeClass('active');
                $dots.eq(currentImageIndex).removeClass('active');

                currentImageIndex = (currentImageIndex + 1) % $images.length;

                $images.eq(currentImageIndex).addClass('active');
                $dots.eq(currentImageIndex).addClass('active');
            }

            hoverTimeout = setTimeout(function () {
                changeImage();
                slideshowIntervals[index] = setInterval(changeImage, 1000);
            }, 400);
        });

        $productCard.on('mouseleave', function () {
            clearTimeout(hoverTimeout);
            clearInterval(slideshowIntervals[index]);

            const $images = $imageContainer.find('.product-image');
            const $dots = $indicatorContainer.find('.indicator-dot');

            $images.eq(currentImageIndex).removeClass('active');
            $dots.eq(currentImageIndex).removeClass('active');

            currentImageIndex = 0;

            $images.eq(0).addClass('active');
            $dots.eq(0).addClass('active');
        });
    });

    $('.user-admn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $('.menu-account').toggleClass('show');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.menu-account, .user-admn').length) {
            $('.menu-account').removeClass('show');
        }
    });

    $(document).on('mouseenter', '.product-card', function () {
        const $wishlist = $(this).find('.wish-list');
        $wishlist.addClass('show');
        setTimeout(() => $wishlist.addClass('animate'), 10);
    });

    $(document).on('mouseleave', '.product-card', function () {
        const $wishlist = $(this).find('.wish-list');
        $wishlist.removeClass('animate')
            .one('transitionend', function () {
                $wishlist.removeClass('show');
            });
    });

    $('.notification-clk').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $('.notification').toggleClass('show');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.notification, .notification-clk').length) {
            $('.notification').removeClass('show');
        }
    });

    const $cartPanel = $('#cartPanel');
    const $closeCart = $('#closeCart');

    if (!$cartPanel.hasClass('hidden') && !$cartPanel.hasClass('active')) {
        $cartPanel.addClass('hidden');
    }

    function showPanel() {
        $cartPanel.removeClass('hidden')[0].offsetWidth;
        $cartPanel.addClass('active');
    }

    function hidePanel() {
        if ($cartPanel.hasClass('hidden') || !$cartPanel.hasClass('active')) return;

        $cartPanel.removeClass('active')
            .one('transitionend', function (e) {
                if (e.target === this) {
                    $cartPanel.addClass('hidden');
                }
            });
    }

    $(document).on('click', '.cart-btn', function (e) {
        e.preventDefault();
        showPanel();
    });

    $closeCart.on('click', function (e) {
        e.preventDefault();
        hidePanel();
    });

    $(document).on('click', function (e) {
        if ($cartPanel.hasClass('hidden')) return;
        if (!$(e.target).closest('#cartPanel, .cart-btn').length) {
            hidePanel();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !$cartPanel.hasClass('hidden')) {
            hidePanel();
        }
    });

    $('.cart-toggle-wrapper').each(function () {
        const $wrapper = $(this);
        const $addBtn = $wrapper.find('.cart-btn');
        const $cartHome = $wrapper.find('.cart-home');
        const $deleteBtn = $wrapper.find('.cart-delete');
        const $btnPlus = $wrapper.find('.btn-plus');
        const $btnMinus = $wrapper.find('.btn-minus');
        const $qtyInput = $wrapper.find('.quantity-value');

        if (!$addBtn.length || !$cartHome.length) return;

        $addBtn.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $wrapper.addClass('active');
            $cartHome.attr('aria-hidden', 'false');
            $btnPlus.trigger('focus');
        });

        $deleteBtn.on('click', function (e) {
            e.stopPropagation();
            $wrapper.removeClass('active');
            $cartHome.attr('aria-hidden', 'true');
        });

        $(document).on('click', function (e) {
            if (!$wrapper.hasClass('active')) return;
            if (!$(e.target).closest($wrapper).length) {
                $wrapper.removeClass('active');
                $cartHome.attr('aria-hidden', 'true');
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $wrapper.hasClass('active')) {
                $wrapper.removeClass('active');
                $cartHome.attr('aria-hidden', 'true');
            }
        });

        $btnPlus.on('click', function (e) {
            e.stopPropagation();
            let v = parseInt($qtyInput.val() || '1', 10);
            $qtyInput.val(v + 1);
        });

        $btnMinus.on('click', function (e) {
            e.stopPropagation();
            let v = parseInt($qtyInput.val() || '1', 10);
            if (v > 1) $qtyInput.val(v - 1);
        });
    });

});
</script>