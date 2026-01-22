@extends('front.layout', [
    'metaInfo' => [
        'title' => $product?->seo_title,
        'content' => $product?->seo_description,
        'url' => route('product.index', ['product_slug' => $product?->slug, 'short_url' => $product?->short_url]),
        'keywords' => implode(', ', ($product?->tags) ?? [])
    ]
])

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>

<style>
    .mainSlider .swiper-slide img {
        height: 500px!important;
        width: 826px!important;
        object-fit: contain!important;
    }

    .thumbSlider .swiper-slide img {
        height: 149px!important;
        width: 149px!important;
        object-fit: contain!important;
    }

    .bundle-items-section {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .bundle-items-section h5 {
        font-weight: 600;
        margin-bottom: 15px;
        color: #203a72;
    }

    .bundle-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .bundle-item:last-child {
        border-bottom: none;
    }

    .bundle-item-name {
        font-weight: 500;
        color: #333;
    }

    .bundle-item-details {
        font-size: 13px;
        color: #666;
    }

    .bundle-item-price {
        font-weight: 600;
        color: #28a745;
    }

    .pricing-display-section {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        background-color: #fafafa;
    }

    .price-display {
        font-size: 24px;
        font-weight: 600;
        color: #203a72;
    }

    .price-display .mrp-price {
        text-decoration: line-through;
        color: #999;
        font-size: 18px;
        margin-right: 10px;
    }

    .price-display .final-price {
        color: #28a745;
    }

    .discount-badge {
        background-color: #dc3545;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 10px;
    }

    .add-to-cart-section {
        transition: all 0.3s ease;
    }

    .cart-quantity-display {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 15px;
    }

    .cart-quantity-display .quantity-info {
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 4px;
    }

    .cart-quantity-display .quantity-value {
        font-size: 18px;
        font-weight: 600;
        color: #203a72;
    }

    #removeFromCartBtn {
        white-space: nowrap;
    }

    .cart-quantity-controls {
        display: none;
    }

    .cart-quantity-controls.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    .quantity-group {
        width: 130px;
    }

    .quantity-group .form-control {
        background: #fff;
    }

    .quantity-group .btn {
        padding: 0.5rem 0.75rem;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
</style>
@endpush

@section('content')

<!-- beadcrum Section Start -->
<section>
    <div class="bred-pro">
        <div class="container">
            <div class="breadcrumb-container">
                <ol class="breadcrumb">
                    <li><a href="{{ route('home')  }}">Home</a></li>
                    @forelse($categoryHierarchy as $categoryLevel)
                        @if(!isset($categoryLevel['display']))
                            <li><a href="{{ route('category.index', ['category_slug' => $categoryLevel['slug'], 'short_url' => $categoryLevel['short_url']]) }}">{{ $categoryLevel['name'] }}</a></li>
                        @else
                            <li><a>{{ $categoryLevel['name'] }}</a></li>
                        @endif
                    @empty
                    @endforelse
                    <li><a href="#" class="text-truncate">{{ $product->name }}</a></li>
                </ol>
            </div>
        </div>
    </div>
</section>
<!-- beadcrum Section End -->
<!-- MAin-section Content Start -->

<section class="pro-dt-hero"> 
    <div class="pro-detail-block">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="pro-dtl-slider">
                        <div class="product-gallery">
                            <!-- Main Image Slider -->
                            <div class="swiper mainSlider">
                                <div class="swiper-wrapper">
                                    @if(isset($product->primaryImage->id) && is_file(public_path('storage/' . $product->primaryImage->file)))
                                        <div class="swiper-slide">
                                            <img src="{{ asset('storage/' . $product->primaryImage->file) }}" alt="{{ $product->name }}" />
                                        </div>
                                    @endif
                                    @foreach ($product->secondaryImages as $row)
                                        @if(isset($row->id) && is_file(public_path('storage/' . $row->file)))
                                            <div class="swiper-slide">
                                                <img src="{{ asset('storage/' . $row->file) }}" alt="{{ $product->name }}" />
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <!-- Navigation -->
                                <div class="swiper-button-prev"></div>
                                <div class="swiper-button-next"></div>
                            </div>

                            <!-- Thumbnail Slider -->
                            <div class="swiper thumbSlider mt-3">
                                    <div class="swiper-wrapper">

                                    @if(isset($product->primaryImage->id) && is_file(public_path('storage/' . $product->primaryImage->file)))
                                        <div class="swiper-slide">
                                            <img src="{{ asset('storage/' . $product->primaryImage->file) }}" alt="{{ $product->name }}" />
                                        </div>
                                    @endif
                                    @foreach ($product->secondaryImages as $row)
                                        @if(isset($row->id) && is_file(public_path('storage/' . $row->file)))
                                            <div class="swiper-slide">
                                                <img src="{{ asset('storage/' . $row->file) }}" alt="{{ $product->name }}" />
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="detail-right">
                        <h2 class="h-40 mb-2">{{ $product->name }}</h2>
                        
                        <div class="bult-div">
                            <!-- Bundle Items Section -->
                            @if(!empty($bundleItemsData) && count($bundleItemsData) > 0)
                            <div class="bundle-items-section">
                                <h5><i class="fa fa-th-list me-2"></i>What's Included in This Bundle</h5>
                                @foreach($bundleItemsData as $item)
                                <div class="bundle-item">
                                    <div>
                                        <div class="bundle-item-name">
                                            {{ $item['product_name'] }}
                                            @if($item['variant_name'])
                                                <span class="text-muted">- {{ $item['variant_name'] }}</span>
                                            @endif
                                        </div>
                                        <div class="bundle-item-details">
                                            {{ $item['quantity'] }} × {{ $item['unit_name'] }}
                                        </div>
                                    </div>
                                    <div class="bundle-item-price">
                                        ${{ number_format($item['total_price'], 2) }}
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            <!-- Pricing Display Section -->
                            <div id="pricingDisplay" class="pricing-display-section">
                                <div class="price-stock-info mb-3">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <span class="fw-bold">Bundle Price</span>
                                        @if($totalStock > 0)
                                            <span class="badge bg-success">In Stock: {{ $totalStock }} available</span>
                                        @else
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @endif
                                    </div>
                                    <div id="priceDisplay" class="price-display">
                                        @if($bundlePricing['discount_amount'] > 0)
                                            <span class="mrp-price">${{ number_format($bundlePricing['base_price'], 2) }}</span>
                                            <span class="final-price">${{ number_format($bundlePricing['final_price'], 2) }}</span>
                                            <span class="discount-badge">
                                                @if($bundlePricing['discount_type'] == 0)
                                                    {{ $bundlePricing['discount_value'] }}% OFF
                                                @else
                                                    ${{ number_format($bundlePricing['discount_value'], 2) }} OFF
                                                @endif
                                            </span>
                                        @else
                                            <span class="final-price">${{ number_format($bundlePricing['final_price'], 2) }}</span>
                                        @endif
                                    </div>
                                    @if($bundlePricing['price_type'] == 0 && $bundlePricing['subtotal'] > 0)
                                        <small class="text-muted">
                                            Bundle of {{ count($bundleItemsData) }} items (Total value: ${{ number_format($bundlePricing['subtotal'], 2) }})
                                        </small>
                                    @endif
                                </div>

                                <div class="add-to-cart-section mt-4">
                                    <div class="cart-quantity-display" id="cartQuantityDisplay" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="quantity-info">Quantity in cart:</div>
                                                <div class="quantity-value" id="cartQuantityValue">0</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeFromCartBtn">
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" class="me-1">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                Remove
                                            </button>
                                        </div>
                                    </div>

                                    <div class="cart-quantity-controls" id="cartQuantityControls">
                                        <div class="row align-items-center w-100">
                                            <div class="col-12">
                                                <label class="form-label fw-bold mb-2">Quantity</label>
                                                <div class="input-group quantity-group">
                                                    <button class="btn btn-outline-secondary btn-minus" type="button" id="qtyMinus">−</button>
                                                    <input type="number" class="form-control text-center" id="productQuantity" value="1" min="1" step="1">
                                                    <button class="btn btn-outline-secondary btn-plus" type="button" id="qtyPlus">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="addToCartInitial">
                                        <button type="button" class="btn cart-btn w-100" id="addToCartBtn" @if($totalStock <= 0) disabled @endif>
                                            <span class="btn-text">@if($totalStock > 0) Add to Cart @else Out of Stock @endif</span>
                                            <span class="btn-loading d-none">
                                                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                                Adding...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="produc-crt-section mt-3">
                                <a class="cart-like cursor-pointer" id="addToWishlist" data-product="{{ json_encode([
                                        'type' => 'bundled',
                                        'variant' => null
                                    ]) }}">
                                    <img src="{{ asset('front-theme/images/not-added-to-wishlist.png') }}" style="height: 25px;position:relative;bottom:3px;">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- MAin-section Content Start -->
   
<!-- Description Content Start -->
<section class="Description">
    <div class="Description__block">
        <div class="Description__box">
            <h2 class="h-30">Product Description</h2>
            <div class="Description__text">
                <div class="top-des ">
                    {!! $product->long_description !!}
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Description Content End -->

    @if($recentlyViewedProducts->isNotEmpty())
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

@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/3.4.1/js/swiper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    $(document).ready(function () {
        var thumbSlider = new Swiper(".thumbSlider", {
            spaceBetween: 10,
            slidesPerView: 5,
            freeMode: true,
            watchSlidesProgress: true,
            breakpoints: {
                320: { slidesPerView: 3, spaceBetween: 20 },
                576: { slidesPerView: 4, spaceBetween: 20 },
                992: { slidesPerView: 5, spaceBetween: 20 },
            },
        });

        // Main Image Slider
        var mainSlider = new Swiper(".mainSlider", {
            spaceBetween: 10,
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            thumbs: {
                swiper: thumbSlider,
            },
        });

        // Wishlist Logic
        let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
        
        function setWishlistIcon(inWishlist) {
            let imageElement = $('#addToWishlist').find('img');
            if (inWishlist) {
                imageElement.attr('src', '{{ asset("front-theme/images/added-to-wishlist.png") }}');
            } else {
                imageElement.attr('src', '{{ asset("front-theme/images/not-added-to-wishlist.png") }}');
            }
        }

        function currentItemKeyWishlist() {
            let info = $('#addToWishlist').data('product');
            return {
                product_short_url: '{{ $product->short_url }}',
                product_variant_short_url: null
            };
        }

        function localHasItemWishlist() {
            let key = currentItemKeyWishlist();
            return (JSON.parse(localStorage.getItem('wishlist')) || []).some(function (x) {
                return x.product_short_url === key.product_short_url && x.product_variant_short_url === key.product_variant_short_url;
            });
        }

        function localAddOrRemoveWishlist() {
            let key = currentItemKeyWishlist();
            let wl = JSON.parse(localStorage.getItem('wishlist')) || [];
            let exists = wl.some(function (x) { return x.product_short_url === key.product_short_url && x.product_variant_short_url === key.product_variant_short_url; });
            if (exists) {
                wl = wl.filter(function (x) { return !(x.product_short_url === key.product_short_url && x.product_variant_short_url === key.product_variant_short_url); });
                localStorage.setItem('wishlist', JSON.stringify(wl));
                setWishlistIcon(false);
                return false;
            } else {
                wl.push(key);
                localStorage.setItem('wishlist', JSON.stringify(wl));
                setWishlistIcon(true);
                return true;
            }
        }

        function mergeLocalToServerWishlist() {
            let wl = JSON.parse(localStorage.getItem('wishlist')) || [];
            if (!wl.length) { return; }
            $.ajax({
                url: '{{ route("wishlist.merge") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    items: wl
                },
                success: function() {
                    localStorage.removeItem('wishlist');
                }
            });
        }

        function initWishlistIconFromStatus() {
            $.ajax({
                url: '{{ route("wishlist.status") }}',
                method: 'GET',
                success: function(resp) {
                    if (!resp?.success) { setWishlistIcon(localHasItemWishlist()); return; }
                    if (!resp.logged_in) {
                        setWishlistIcon(localHasItemWishlist());
                        return;
                    }
                    mergeLocalToServerWishlist();
                    let key = currentItemKeyWishlist();
                    let inList = (resp.wishlists || []).some(function (x) {
                        return x.product_short_url === key.product_short_url && x.product_variant_short_url === key.product_variant_short_url;
                    });
                    setWishlistIcon(inList);
                },
                error: function() {
                    setWishlistIcon(localHasItemWishlist());
                }
            });
        }

        initWishlistIconFromStatus();

        $(document).on('click', '#addToWishlist', function () {
            let info = $(this).data('product');
            if (!info || !info.type) { return; }

            $.ajax({
                url: '{{ route("wishlist.status") }}',
                method: 'GET',
                success: function(resp) {
                    if (resp?.logged_in) {
                        let key = currentItemKeyWishlist();
                        $.ajax({
                            url: '{{ route("wishlist.toggle") }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                product_short_url: key.product_short_url,
                                product_variant_short_url: key.product_variant_short_url
                            },
                            success: function(r) {
                                setWishlistIcon(!!r?.in_wishlist);
                            },
                            error: function(xhr) {
                                if (xhr && xhr.status === 401) {
                                    localAddOrRemoveWishlist();
                                }
                            }
                        });
                    } else {
                        localAddOrRemoveWishlist();
                    }
                },
                error: function() {
                    localAddOrRemoveWishlist();
                }
            });
        });

        // Cart Logic for Bundled Products
        let currentCartQuantity = 0;
        let currentCartItemId = null;
        let updateTimeout = null;

        function checkSpecificCartItem() {
            $.ajax({
                url: '{{ route('cart.item-quantity') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_short_url: '{{ $product->short_url }}',
                    product_variant_short_url: null,
                    unit_type: 0,
                    unit_id: 0
                },
                success: function(response) {
                    if (response.success && response.quantity > 0) {
                        currentCartItemId = response.item_id;
                        currentCartQuantity = response.quantity;
                        showCartQuantityControls(response.quantity);
                    } else {
                        hideCartQuantityControls();
                    }
                },
                error: function() {
                    hideCartQuantityControls();
                }
            });
        }

        function showCartQuantityControls(quantity) {
            currentCartQuantity = quantity;
            $('#addToCartInitial').hide();
            $('#cartQuantityDisplay').show();
            $('#cartQuantityValue').text(quantity);
            $('#cartQuantityControls').addClass('show');
            $('#productQuantity').val(quantity);
        }

        function hideCartQuantityControls() {
            currentCartQuantity = 0;
            currentCartItemId = null;
            $('#addToCartInitial').show();
            $('#cartQuantityDisplay').hide();
            $('#cartQuantityControls').removeClass('show');
            $('#productQuantity').val(1);
        }

        function updateCartQuantity(quantity) {
            if (!currentCartItemId || quantity < 1) {
                return;
            }

            clearTimeout(updateTimeout);
            updateTimeout = setTimeout(function() {
                $.ajax({
                    url: '{{ route('cart.update') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        item_id: currentCartItemId,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.success) {
                            updateCartCount();
                            $('#cartQuantityValue').text(quantity);
                            currentCartQuantity = quantity;
                        }
                    },
                    error: function() {
                        checkSpecificCartItem();
                    }
                });
            }, 500);
        }

        function updateCartCount() {
            $.ajax({
                url: '{{ route('cart.count') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const count = response.count || 0;
                        if ($('#cartItemsCountText').length) {
                            $('#cartItemsCountText').text('(' + count + ' items)');
                        }
                    }
                }
            });
        }

        $('#qtyPlus').on('click', function() {
            const input = $('#productQuantity');
            const currentVal = parseFloat(input.val()) || 1;
            const newVal = currentVal + 1;
            input.val(newVal);
            
            if (currentCartItemId) {
                updateCartQuantity(newVal);
            }
        });

        $('#qtyMinus').on('click', function() {
            const input = $('#productQuantity');
            const currentVal = parseFloat(input.val()) || 1;
            if (currentVal > 1) {
                const newVal = currentVal - 1;
                input.val(newVal);
                
                if (currentCartItemId) {
                    updateCartQuantity(newVal);
                }
            }
        });

        $('#productQuantity').on('change', function() {
            const quantity = parseFloat($(this).val()) || 1;
            if (quantity < 1) {
                $(this).val(1);
                return;
            }
            
            if (currentCartItemId) {
                updateCartQuantity(quantity);
            }
        });

        $('#addToCartBtn').on('click', function() {
            const btn = $(this);
            const btnText = btn.find('.btn-text');
            const btnLoading = btn.find('.btn-loading');
            const quantity = 1;

            btn.prop('disabled', true);
            btnText.addClass('d-none');
            btnLoading.removeClass('d-none');

            $.ajax({
                url: '{{ route('cart.add') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_short_url: '{{ $product->short_url }}',
                    product_variant_short_url: null,
                    unit_type: 0,
                    unit_id: 0,
                    quantity: quantity
                },
                success: function(response) {
                    if (response.success) {
                        updateCartCount();
                        setTimeout(function() {
                            checkSpecificCartItem();
                        }, 300);
                    }
                },
                error: function() {
                    checkSpecificCartItem();
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btnText.removeClass('d-none');
                    btnLoading.addClass('d-none');
                }
            });
        });

        $('#removeFromCartBtn').on('click', function() {
            if (!currentCartItemId) {
                return;
            }

            if (!confirm('Are you sure you want to remove this item from cart?')) {
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true);

            $.ajax({
                url: '{{ route('cart.remove') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    item_id: currentCartItemId
                },
                success: function(response) {
                    if (response.success) {
                        updateCartCount();
                        hideCartQuantityControls();
                    }
                },
                error: function() {
                    checkSpecificCartItem();
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });

        // Initial check for cart item
        setTimeout(function() {
            checkSpecificCartItem();
        }, 500);
    }); 
</script>
@endpush