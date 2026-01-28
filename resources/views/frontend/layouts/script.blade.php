<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
<script>

</script>


    </script>
    <script>
        const products = [{

                price: "$299",
                images: [
                    "images/food-img2.png",
                    "images/food-img3.png",
                    "images/food-img4.png",
                    "images/food-img4.png"
                ]
            },

        ];

        const productsGrid = document.getElementById('productsGrid');
        const slideshowIntervals = {};

        products.forEach((product, index) => {
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.dataset.productIndex = index;

            const imageContainer = document.createElement('div');
            imageContainer.className = 'product-image-container';

            product.images.forEach((imgSrc, imgIndex) => {
                const img = document.createElement('img');
                img.src = imgSrc;
                img.className = `product-image ${imgIndex === 0 ? 'active' : ''}`;
                img.alt = product.name;
                imageContainer.appendChild(img);
            });

            const indicatorContainer = document.createElement('div');
            indicatorContainer.className = 'image-indicator';
            product.images.forEach((_, imgIndex) => {
                const dot = document.createElement('div');
                dot.className = `indicator-dot ${imgIndex === 0 ? 'active' : ''}`;
                indicatorContainer.appendChild(dot);
            });
            imageContainer.appendChild(indicatorContainer);

            const productInfo = document.createElement('div');
            productInfo.className = 'product-info';
            productInfo.innerHTML = `
                
                <div class="wish-list">
                    <button class="btn">
                        <img src="images/menuicon-3.svg" alt="">
                        Wishlist
                    </button>
                </div>
            `;

            productCard.appendChild(imageContainer);
            productCard.appendChild(productInfo);
            productsGrid.appendChild(productCard);

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
                    slideshowIntervals[index] = setInterval(changeImage, 1000); // 1s
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const userAdmin = document.querySelector(".user-admn");
            const menuAccount = document.querySelector(".menu-account");

            userAdmin.addEventListener("click", function(e) {
                e.preventDefault();
                menuAccount.classList.toggle("active");
            });

            // Optional: click outside to close
            document.addEventListener("click", function(e) {
                if (!menuAccount.contains(e.target) && !userAdmin.contains(e.target)) {
                    menuAccount.classList.remove("active");
                }
            });
        });



        document.querySelectorAll('.product-card').forEach(card => {
            const wishlist = card.querySelector('.wish-list');

            card.addEventListener('mouseenter', () => {
                wishlist.classList.add('show');
                setTimeout(() => wishlist.classList.add('animate'), 10);
            });

            card.addEventListener('mouseleave', () => {
                wishlist.classList.remove('animate');
                wishlist.addEventListener('transitionend', function handler() {
                    wishlist.classList.remove('show');
                    wishlist.removeEventListener('transitionend', handler);
                });
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const userAdmin = document.querySelector(".notification-clk");
            const menuAccount = document.querySelector(".notification");

            userAdmin.addEventListener("click", function(e) {
                e.preventDefault();
                menuAccount.classList.toggle("active");
            });

            // Optional: click outside to close
            document.addEventListener("click", function(e) {
                if (!menuAccount.contains(e.target) && !userAdmin.contains(e.target)) {
                    menuAccount.classList.remove("active");
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const addToCartBtns = document.querySelectorAll(".cart-btn"); // supports multiple Add to Cart buttons
            const cartPanel = document.getElementById("cartPanel");
            const closeCart = document.getElementById("closeCart");

            // Ensure initial state: panel not visible in layout
            if (!cartPanel.classList.contains('hidden') && !cartPanel.classList.contains('active')) {
                cartPanel.classList.add('hidden'); // start hidden (display:none)
            }

            // Show panel: remove .hidden, force reflow, then add .active to animate
            function showPanel() {
                cartPanel.classList.remove('hidden'); // make renderable
                // Force reflow so the browser sees the element before adding .active
                void cartPanel.offsetWidth;
                cartPanel.classList.add('active');
            }

            // Hide panel: remove .active to trigger transition, then add .hidden after transition ends
            function hidePanel() {
                // If already hidden, nothing to do
                if (cartPanel.classList.contains('hidden') || !cartPanel.classList.contains('active')) return;

                // Remove visible class -> triggers CSS transition
                cartPanel.classList.remove('active');

                // Listen for transitionend (only once) then hide from layout
                const onTransitionEnd = function(e) {
                    // only react to transform (guard against nested children transitions)
                    if (e.target === cartPanel && (e.propertyName === 'transform' || e.propertyName ===
                            'opacity')) {
                        cartPanel.classList.add('hidden'); // remove from layout after animation
                        cartPanel.removeEventListener('transitionend', onTransitionEnd);
                    }
                };
                cartPanel.addEventListener('transitionend', onTransitionEnd);
            }

            // Hook up Add to Cart buttons (supports more than one)
            addToCartBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    showPanel();
                });
            });

            // Close button
            if (closeCart) {
                closeCart.addEventListener('click', (e) => {
                    e.preventDefault();
                    hidePanel();
                });
            }

            // Close on clicking outside the panel
            document.addEventListener('click', (e) => {
                if (cartPanel.classList.contains('hidden')) return;
                const clickedInside = cartPanel.contains(e.target);
                const clickedAddBtn = e.target.closest('.cart-btn');
                if (!clickedInside && !clickedAddBtn) {
                    hidePanel();
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !cartPanel.classList.contains('hidden')) {
                    hidePanel();
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Support multiple wrappers
            const wrappers = document.querySelectorAll('.cart-toggle-wrapper');

            wrappers.forEach(wrapper => {
                const addBtn = wrapper.querySelector('.cart-btn');
                const cartHome = wrapper.querySelector('.cart-home');
                const deleteBtn = wrapper.querySelector('.cart-delete');
                const btnPlus = wrapper.querySelector('.btn-plus');
                const btnMinus = wrapper.querySelector('.btn-minus');
                const qtyInput = wrapper.querySelector('.quantity-value');

                // Safety: if button or cartHome missing, skip
                if (!addBtn || !cartHome) return;

                // Prevent default navigation for anchor
                addBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation(); // stop document click handler from immediately closing
                    // toggle active: show cart replacement
                    wrapper.classList.add('active');
                    cartHome.setAttribute('aria-hidden', 'false');
                    // focus management (optional) — focus the plus button for keyboard users
                    if (btnPlus) btnPlus.focus();
                    // Add a short delay to allow outside-click listener to initialize safely
                });

                // Clicking the delete icon returns to Add button
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        wrapper.classList.remove('active');
                        cartHome.setAttribute('aria-hidden', 'true');
                    });
                }

                // Click outside -> revert
                document.addEventListener('click', (e) => {
                    // if wrapper is not active, nothing to do
                    if (!wrapper.classList.contains('active')) return;

                    // if the click is inside the wrapper, ignore
                    if (wrapper.contains(e.target)) return;

                    // else hide the cart-home and show the button again
                    wrapper.classList.remove('active');
                    cartHome.setAttribute('aria-hidden', 'true');
                });

                // Support Escape key to close when active
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && wrapper.classList.contains('active')) {
                        wrapper.classList.remove('active');
                        cartHome.setAttribute('aria-hidden', 'true');
                    }
                });

                // Quantity buttons (increment / decrement)
                if (btnPlus && btnMinus && qtyInput) {
                    btnPlus.addEventListener('click', (e) => {
                        e.stopPropagation();
                        let v = parseInt(qtyInput.value || '1', 10);
                        if (isNaN(v)) v = 1;
                        qtyInput.value = v + 1;
                    });

                    btnMinus.addEventListener('click', (e) => {
                        e.stopPropagation();
                        let v = parseInt(qtyInput.value || '1', 10);
                        if (isNaN(v)) v = 1;
                        if (v > 1) qtyInput.value = v - 1;
                    });
                }
            });
        });
    </script>
