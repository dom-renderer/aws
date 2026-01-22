@extends('front.layout')

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.1.6/assets/owl.carousel.min.css">
@endpush

@section('content')
    @foreach ($sections as $section)

    @if($section?->key == 'banner_carousel' && $section?->value?->visible && !empty($section?->value?->slides ?? []))
    <!-- Carousel Section -->
    <section class="hero">
        <div class="hero-block">
            <div class="hero-box">
                <!-- change "carousel slide" to "carousel slide carousel-fade" -->
                <div id="carouselExampleControls" class="carousel slide carousel-fade" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#carouselExampleControls" data-bs-slide-to="0"
                            class="active" aria-current="true" aria-label="Slide 1"></button>
                        <button type="button" data-bs-target="#carouselExampleControls" data-bs-slide-to="1"
                            aria-label="Slide 2"></button>
                        <button type="button" data-bs-target="#carouselExampleControls" data-bs-slide-to="2"
                            aria-label="Slide 3"></button>
                    </div>
                    <div class="carousel-inner">

                        @foreach ($section?->value?->slides as $slide)
                        <div class="carousel-item @if($loop->first) active @endif">
                            <div class="hero-caro firt-hr">
                                <img src="{{ asset('storage/' . $slide?->image) }}" class="d-block w-100" alt="Banner">
                                <div class="hero-content">
                                    <h2>{{ $slide?->heading }}</h2>
                                    <p>{{ $slide?->description }}</p>
                                    @if($slide?->has_button)
                                    <a href="{{ $slide?->redirect }}" class="btn hero-btn">{{ $slide?->button_title }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach

                    </div>

                    <!-- Controls -->
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleControls"
                        data-bs-slide="prev">
                        <span class="arrow-bg">
                            <img src="{{ asset('front-theme/images/arro-right.svg') }}" alt="">
                        </span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleControls"
                        data-bs-slide="next">
                        <span class="arrow-bg">
                            <img src="{{ asset('front-theme/images/arrow-left.svg') }}" alt="">
                        </span>
                    </button>
                </div>
            </div>
            <div class="hero-bottom">
                <div class="container">
                    <div class="hero-bttom-bx">
                        <div class="her-bx-left">
                            <h3>Top Categories</h3>
                        </div>
                        <div class="her-bx-right">
                            <a href="">
                                View All
                                <img src="{{ asset('front-theme/images/right-arrow-view.png') }}" alt="">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Carousel Section -->
    @endif

    @if(($section?->key == 'top_categories_grid' && $section?->value?->visible && !empty($section?->value?->categories ?? [])) || ($section?->key == 'top_categories_linear' && $section?->value?->visible && !empty($section?->value?->categories ?? [])))
    <!-- Food Content Start -->
    <section class="food">
        <div class="food-block">
            @if(($section?->key == 'top_categories_grid' && $section?->value?->visible && !empty($section?->value?->categories ?? [])))
            <div class="food-bevrage">
                <div class="container">
                    <div class="row">
                        @foreach ($section?->value?->categories as $category)
                        <div class="col-lg-12 col-xl-6 col-xxl-3 col-md-12 col-sm-12">
                            <div class="food-box">
                                <h3 class="h-30">
                                    <span>{{ $category?->title }}</span>
                                    @if(count($category?->items ?? []) > 4)
                                    <a href="{{ $category?->redirect }}">View All</a>
                                    @endif
                                </h3>
                                <div class="row">
                                    @foreach (Product::with(['primaryImage', 'images'])->select('id', 'name', 'slug', 'short_url')->whereIn('id', array_slice($category->items, 0, 4))->get() as $item)
                                    <a class="col-lg-6 col-xl-6 col-md-6 col-sm-6" href="{{ route('product.index', ['product_slug' => $item->slug, 'short_url' => $item->short_url]) }}">
                                        <div class="f-inbx">
                                            <!-- <img src="{{ asset('storage/' . $item?->primaryImage?->file) }}" alt="{{ $item->name }}"> -->
                                            <div class="hover-slider hover-slider-2">
                                                <div class="products-grid" id="productsGrid-Product-{{ $item->short_url }}" data-info="{{ json_encode(['slug' => $item->slug, 'short_url' => $item->short_url]) }}" data-images="{{ $item->images }}"></div>
                                                <p class="slow-mn text-truncate" title="{{ $item->name }}">{{ $item->name }}</p>
                                            </div>
                                        </div>
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if(($section?->key == 'top_categories_linear' && $section?->value?->visible && !empty($section?->value?->categories ?? [])))
            @foreach ($section->value->categories as $category)
            <div class="food-rum">
                <div class="container">
                    <h3 class="h-30">{{ $category->title }}</h3>
                    <div class="rum-block">
                        @foreach (Product::with('primaryImage')->select('id', 'name', 'slug', 'short_url')->whereIn('id', array_slice($category->items, 0, 5))->get() as $item)
                        <div class="rum-box">
                        <a href="{{ route('product.index', ['product_slug' => $item->slug, 'short_url' => $item->short_url]) }}">
                                <img src="{{ asset('storage/' . $item?->primaryImage?->file) }}" alt="{{ $item->name }}">
                                <p class="p-12">{{ $item->name }}</p>
                            </a>
                        </div>
                        @endforeach
                        @if(count($category->items) > 5)
                        <div class="rum-box view-rm">
                            <a href="">
                                View All
                                <img src="{{ asset('front-theme/images/arrow-blue-vew.png') }}" alt="">
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </section>
    <!-- Food Content End -->
    @endif

    @if($section->key == 'top_selling_products')
    <!-- Seling-product Content Start -->
    <section class="product-section">
        <div class="product-block pd-y50 margin-heg">
            <div class="container">
                <h2 class="h-30">
                    Top Selling Products
                    @if($topSellingProductCount > 4)
                    <a href="" class="view-white">
                        View All
                        <img src="{{ asset('front-theme/images/view-all-white.png') }}" alt="">
                    </a>
                    @endif
                </h2>
                <div class="row">

                @forelse($topSellingProduct as $tsp)
                    <div class="col-lg-6 col-xl-6 col-xxl-3 col-md-6 col-sm-6">
                        <div class="product-box">
                            <a href="{{ route('product.index', ['product_slug' => $tsp['slug'], 'short_url' => $tsp['short_url']]) }}">
                                <img src="{{ asset('storage/' . $tsp['primary_image']) }}" class="w-100 ofc-h250" alt="{{ $tsp['name'] }}">
                            </a>
                            <h3 class="h-20 mt-3 mb-3">
                                <a href="{{ route('product.index', ['product_slug' => $tsp['slug'], 'short_url' => $tsp['short_url']]) }}" class="text-decoration-none text-dark">
                                    {{ $tsp['name'] }}
                                </a>
                            </h3>
                            <div class="price-bxm">
                                @if($tsp['min_order_qty'] > 1)
                                <p class="p-18 mt-2 mb-3">Min Order: {{ $tsp['min_order_qty'] }}</p>
                                @endif
                            </div>

                            <!-- Cart button with data attributes -->
                            <div class="cart-toggle-wrapper" 
                                data-product-short-url="{{ $tsp['short_url'] }}"
                                data-variant-short-url="{{ $tsp['variant_short_url'] ?? '' }}"
                                data-unit-type="{{ $tsp['default_unit_type'] }}"
                                data-unit-id="{{ $tsp['default_unit_id'] }}"
                                data-product-type="{{ $tsp['type'] }}"
                                data-price="{{ $tsp['starting_price'] }}">
                                
                                <a href="{{ route('product.index', ['product_slug' => $tsp['slug'], 'short_url' => $tsp['short_url']]) }}" class="btn cart-btn-css d-block">
                                    <span class="btn-text">View</span>
                                </a>

                            </div>
                        </div>
                    </div>
                @empty
                @endforelse

                </div>
            </div>
        </div>
    </section>
    <!-- Seling-product Content End -->
    @endif


    @if($section->key == 'recently_viewed' && $recentlyViewedProducts->isNotEmpty())
    <!-- Recent Section Start -->
    <section class="recent-ml">
        <div class="recent-block pd-y50">
            <div class="container">
                <h2 class="h-30">
                    Recently Viewed
                    @if($recentlyViewedProducts->count() > 4)
                    <a href="" class="view-black">
                        View All
                        <img src="{{ asset('front-theme/images/view-blue-arrow.svg') }}" alt="">
                    </a>
                    @endif
                </h2>
                <div class="row">
                    @foreach($recentlyViewedProducts as $rvp)
                    <div class="col-lg-6 col-xl-6 col-xxl-3 col-md-6 col-sm-6">
                        <div class="recent-box">
                            <a href="{{ route('product.index', ['product_slug' => $rvp['slug'], 'short_url' => $rvp['short_url']]) }}">
                                <img class="ofc-h250" src="{{ asset('storage/' . $rvp['primary_image']) }}" alt="{{ $rvp['name'] }}">
                            </a>
                            <div class="rc-bx-in">
                                <h3 class="h-20 mb-3">
                                    <a href="{{ route('product.index', ['product_slug' => $rvp['slug'], 'short_url' => $rvp['short_url']]) }}" class="text-decoration-none text-dark">
                                        {{ $rvp['name'] }}
                                    </a>
                                </h3>
                                @if($rvp['has_discount'])
                                <p class="pr-bold mb-1">${{ number_format($rvp['starting_price'], 2) }}</p>
                                <p class="text-muted text-decoration-line-through mb-3" style="font-size: 0.85rem;">${{ number_format($rvp['original_price'], 2) }}</p>
                                @else
                                <p class="pr-bold mb-3">${{ number_format($rvp['starting_price'], 2) }}</p>
                                @endif
                                
                                <!-- Cart button with data attributes -->
                                <div class="cart-toggle-wrapper" 
                                    data-product-short-url="{{ $rvp['short_url'] }}"
                                    data-variant-short-url="{{ $rvp['variant_short_url'] ?? '' }}"
                                    data-unit-type="{{ $rvp['default_unit_type'] }}"
                                    data-unit-id="{{ $rvp['default_unit_id'] }}"
                                    data-product-type="{{ $rvp['type'] }}"
                                    data-price="{{ $rvp['starting_price'] }}">
                                    
                                    <a href="{{ route('product.index', ['product_slug' => $rvp['slug'], 'short_url' => $rvp['short_url']]) }}" class="btn cart-btn-css d-block">
                                        <span class="btn-text">View</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    <!-- Recent Section End -->
    @endif

    @if($section->key == 'newsletter_subscription')
    <!-- Subscribe Section Start -->
    <section class="sub-section">
        <div class="sub-block">
            <div class="container">
                <div class="sub-box text-center">
                    <h2 class="h-30">Stay Updated</h2>
                    <p class="p-20 py-3">Sign up for updates and exclusive wholesale offers</p>
                    <div class="sub-serch-box">
                        <input type="text" placeholder="Your email address">
                        <button class="btn-sub btn">Subscribe</button>
                    </div>
                    <p class="p-18">By subscribing , you agree to receive marketing communications from Anjo Wholesale
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!-- Subscribe Section End -->
    @endif

    @endforeach
@endsection

@push('js')
    <script src="{{ asset('front-theme/js/Jquery-min.js') }}"></script>
    <script src="{{ asset('front-theme/js/bootstrap-min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    </script>
    <script>
        const productImageUrl = "{{ asset('storage') }}";
        const productsGrids = document.querySelectorAll('.products-grid');
        productsGrids.forEach((element, index) => {
            let allImages = JSON.parse(element?.dataset?.images);
            let allInfo = JSON.parse(element?.dataset?.info);
            let productImage = [];
            const slideshowIntervals = {};

            if (allImages != null && typeof allImages[Symbol.iterator] === "function") {
                Object.values(allImages).forEach(image => {
                    productImage.push(`${productImageUrl}/${image.file}`);
                });
            }

            let productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.dataset.productIndex = index;

            let imageContainer = document.createElement('div');
            imageContainer.className = 'product-image-container';

            productImage.forEach((imgSrc, imgIndex) => {
                const img = document.createElement('img');
                img.src = imgSrc;
                img.className = `product-image ${imgIndex === 0 ? 'active' : ''}`;
                imageContainer.appendChild(img);
            });

            let indicatorContainer = document.createElement('div');
            indicatorContainer.className = 'image-indicator';
            productImage.forEach((_, imgIndex) => {
                const dot = document.createElement('div');
                dot.className = `indicator-dot ${imgIndex === 0 ? 'active' : ''}`;
                indicatorContainer.appendChild(dot);
            });

            imageContainer.appendChild(indicatorContainer);

            const productInfo = document.createElement('a');
            productInfo.className = 'product-info';
            productInfo.dataset.info = JSON.stringify(allInfo);
            productInfo.innerHTML = `
                
                <div class="wish-list">
                    <button class="btn">
                        <img src="{{ asset('front-theme/images/menuicon-3.svg') }}" alt="">
                        Wishlist
                    </button>
                </div>
            `;

            productCard.appendChild(imageContainer);
            productCard.appendChild(productInfo);

            element.appendChild(productCard);

            let currentImageIndex = 0;
            let hoverTimeout = null;

            productCard.addEventListener('mouseenter', () => {
                const images = imageContainer.querySelectorAll('.product-image');
                const dots = indicatorContainer.querySelectorAll('.indicator-dot');

                const changeImage = () => {
                    images[currentImageIndex].classList.remove('active');
                    dots[currentImageIndex].classList.remove('active');

                    currentImageIndex = (currentImageIndex + 1) % images.length;

                    images[currentImageIndex].classList.add('active');
                    dots[currentImageIndex].classList.add('active');
                };

                hoverTimeout = setTimeout(() => {
                    changeImage();
                    slideshowIntervals[index] = setInterval(changeImage, 1000);
                }, 400);
            });

            productCard.addEventListener('mouseleave', () => {
                clearTimeout(hoverTimeout);
                clearInterval(slideshowIntervals[index]);

                const images = imageContainer.querySelectorAll('.product-image');
                const dots = indicatorContainer.querySelectorAll('.indicator-dot');

                images[currentImageIndex].classList.remove('active');
                dots[currentImageIndex].classList.remove('active');

                currentImageIndex = 0;

                images[0].classList.add('active');
                dots[0].classList.add('active');
            });
        });
    </script>

    <style>
        .cart-toggle-wrapper.active .cart-btn {
            pointer-events: none !important;
            z-index: 0 !important;
        }
        .cart-toggle-wrapper.active .cart-home {
            pointer-events: auto !important;
            z-index: 10 !important;
        }
    </style>
    <script>
        $(document).ready(function() {
            const cartItemsState = {};
            let updateTimeout = null;
            
            // Create unique key for cart state
            function getCartKey(productShortUrl, variantShortUrl) {
                return productShortUrl + (variantShortUrl ? '_' + variantShortUrl : '');
            }
            
            // Add to cart button click
            $(document).on('click', '.home-add-to-cart-btn', function(e) {
                e.preventDefault();
                console.log('Add to cart clicked');
                
                const wrapper = $(this).closest('.cart-toggle-wrapper');
                const productShortUrl = wrapper.data('product-short-url');
                const variantShortUrl = wrapper.data('variant-short-url') || null;
                const unitType = parseInt(wrapper.data('unit-type')) || 0;
                const unitId = parseInt(wrapper.data('unit-id')) || 0;
                const productType = wrapper.data('product-type');
                const price = parseFloat(wrapper.data('price')) || 0;
                
                console.log('Product:', productShortUrl, 'Variant:', variantShortUrl, 'UnitType:', unitType, 'UnitId:', unitId, 'Price:', price);
                
                const cartKey = getCartKey(productShortUrl, variantShortUrl);
                
                const btn = $(this);
                const btnText = btn.find('.btn-text');
                const btnLoading = btn.find('.btn-loading');
                
                btn.prop('disabled', true);
                btnText.addClass('d-none');
                btnLoading.removeClass('d-none');
                
                $.ajax({
                    url: '{{ route("cart.add") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product_short_url: productShortUrl,
                        product_variant_short_url: variantShortUrl,
                        unit_type: unitType,
                        unit_id: unitId,
                        quantity: 1
                    },
                    success: function(response) {
                        console.log('Cart add response:', response);
                        if (response.success) {
                            // Get the item_id after adding
                            $.ajax({
                                url: '{{ route("cart.item-quantity") }}',
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    product_short_url: productShortUrl,
                                    product_variant_short_url: variantShortUrl,
                                    unit_type: unitType,
                                    unit_id: unitId
                                },
                                success: function(qtyResponse) {
                                    console.log('Item quantity response:', qtyResponse);
                                    cartItemsState[cartKey] = {
                                        quantity: qtyResponse.quantity || 1,
                                        unitType: unitType,
                                        unitId: unitId,
                                        price: price,
                                        itemId: qtyResponse.item_id,
                                        variantShortUrl: variantShortUrl
                                    };
                                    console.log('Updated cart state:', cartItemsState);
                                    showQuantityControls(wrapper, qtyResponse.quantity || 1, price);
                                },
                                error: function(xhr) {
                                    console.error('Error getting item quantity:', xhr);
                                }
                            });
                            updateCartCount();
                        } else {
                            console.error('Cart add failed:', response);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error adding to cart:', xhr);
                        console.log('Response text:', xhr.responseText);
                        alert('Error adding to cart. Please try again.');
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        btnText.removeClass('d-none');
                        btnLoading.addClass('d-none');
                    }
                });
            });
            
            // Plus button
            $(document).on('click', '.home-cart-plus', function() {
                const wrapper = $(this).closest('.cart-toggle-wrapper');
                const productShortUrl = wrapper.data('product-short-url');
                const variantShortUrl = wrapper.data('variant-short-url') || null;
                const price = parseFloat(wrapper.data('price')) || 0;
                const cartKey = getCartKey(productShortUrl, variantShortUrl);
                
                if (cartItemsState[cartKey]) {
                    cartItemsState[cartKey].quantity++;
                    const qty = cartItemsState[cartKey].quantity;
                    
                    wrapper.find('.home-cart-quantity').val(qty);
                    wrapper.find('.home-cart-total').text('$' + (qty * price).toFixed(2));
                    
                    debouncedUpdate(cartKey, qty);
                }
            });
            
            // Minus button
            $(document).on('click', '.home-cart-minus', function() {
                const wrapper = $(this).closest('.cart-toggle-wrapper');
                const productShortUrl = wrapper.data('product-short-url');
                const variantShortUrl = wrapper.data('variant-short-url') || null;
                const price = parseFloat(wrapper.data('price')) || 0;
                const cartKey = getCartKey(productShortUrl, variantShortUrl);
                
                if (cartItemsState[cartKey] && cartItemsState[cartKey].quantity > 1) {
                    cartItemsState[cartKey].quantity--;
                    const qty = cartItemsState[cartKey].quantity;
                    
                    wrapper.find('.home-cart-quantity').val(qty);
                    wrapper.find('.home-cart-total').text('$' + (qty * price).toFixed(2));
                    
                    debouncedUpdate(cartKey, qty);
                }
            });
            
            // Delete/remove from cart
            $(document).on('click', '.home-cart-delete', function() {
                const wrapper = $(this).closest('.cart-toggle-wrapper');
                const productShortUrl = wrapper.data('product-short-url');
                const variantShortUrl = wrapper.data('variant-short-url') || null;
                const cartKey = getCartKey(productShortUrl, variantShortUrl);
                
                if (!cartItemsState[cartKey] || !cartItemsState[cartKey].itemId) {
                    console.error('No item_id found for removal');
                    return;
                }
                
                const itemId = cartItemsState[cartKey].itemId;
                
                $.ajax({
                    url: '{{ route("cart.remove") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        item_id: itemId
                    },
                    success: function(response) {
                        if (response.success) {
                            delete cartItemsState[cartKey];
                            hideQuantityControls(wrapper);
                            updateCartCount();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error removing from cart:', xhr);
                    }
                });
            });
            
            function showQuantityControls(wrapper, quantity, price) {
                wrapper.addClass('active');
                wrapper.find('.home-cart-quantity').val(quantity);
                wrapper.find('.home-cart-total').text('$' + (quantity * price).toFixed(2));
            }
            
            function hideQuantityControls(wrapper) {
                wrapper.removeClass('active');
            }
            
            function debouncedUpdate(cartKey, quantity) {
                clearTimeout(updateTimeout);
                updateTimeout = setTimeout(function() {
                    if (!cartItemsState[cartKey] || !cartItemsState[cartKey].itemId) {
                        console.error('No item_id found for update');
                        return;
                    }
                    
                    const itemId = cartItemsState[cartKey].itemId;
                    
                    $.ajax({
                        url: '{{ route("cart.update") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            item_id: itemId,
                            quantity: quantity
                        },
                        success: function(response) {
                            updateCartCount();
                        },
                        error: function(xhr) {
                            console.error('Error updating cart:', xhr);
                        }
                    });
                }, 500);
            }
            
            function updateCartCount() {
                $.ajax({
                    url: '{{ route("cart.count") }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const count = response.count || 0;
                            if ($('#cartItemsCountText').length) {
                                $('#cartItemsCountText').text('(' + count + ' items)');
                            }
                            // Update any cart badge in header
                            if ($('.cart-count-badge').length) {
                                $('.cart-count-badge').text(count);
                            }
                        }
                    }
                });
            }
            
            // Initialize - check if any items are already in cart
            function initCartState() {
                $('.cart-toggle-wrapper').each(function() {
                    const wrapper = $(this);
                    const productShortUrl = wrapper.data('product-short-url');
                    const variantShortUrl = wrapper.data('variant-short-url') || null;
                    const unitType = parseInt(wrapper.data('unit-type')) || 0;
                    const unitId = parseInt(wrapper.data('unit-id')) || 0;
                    const price = parseFloat(wrapper.data('price')) || 0;
                    const cartKey = getCartKey(productShortUrl, variantShortUrl);
                    
                    $.ajax({
                        url: '{{ route("cart.item-quantity") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            product_short_url: productShortUrl,
                            product_variant_short_url: variantShortUrl,
                            unit_type: unitType,
                            unit_id: unitId
                        },
                        success: function(response) {
                            if (response.success && response.quantity > 0) {
                                cartItemsState[cartKey] = {
                                    quantity: response.quantity,
                                    unitType: unitType,
                                    unitId: unitId,
                                    price: price,
                                    itemId: response.item_id,
                                    variantShortUrl: variantShortUrl
                                };
                                showQuantityControls(wrapper, response.quantity, price);
                            }
                        }
                    });
                });
            }
            
            // Initialize cart state
            initCartState();
        });
    </script>

@endpush


