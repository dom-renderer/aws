<header>
    <nav class="navbar navbar-expand-lg px-0 py-2">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="#">
        <img src="{{ asset('assets/images/aw-log.svg') }}" class="h-8" alt="...">
        </a>
        <!-- Navbar toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
            aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Collapse -->
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <!-- Nav -->
            <div class="navbar-nav mx-lg-auto head-middle">
                <form action="" class="w-100">
                <div class="header__srh-box">
                    <div class="srh-left">
                        <i class="fa fa-bars" aria-hidden="true"></i>
                        <input type="text" placeholder="Search">
                    </div>
                    <div class="srh-rgt">
                        <i class="fa fa-search" aria-hidden="true"></i>
                    </div>
                </div>
                </form>
            </div>
            <!-- Right navigation -->
            <div class="navbar-nav ms-lg-4">
                <div class="navbar-nav mx-lg-auto">
                <a class="nav-item nav-link cart" href="#">
                <img src="{{ asset('assets/images/cart.svg') }}" alt="">
                <span>33</span>
                </a>
                <a class="nav-item nav-link cart notification-clk" href="#">
                <img src="{{ asset('assets/images/notification.svg') }}" alt="">
                <span>11</span>
                </a>
                <a class="nav-item nav-link user-admn" href="">
                <span>
                <img src="{{ asset('assets/images/user-admin.svg') }}" alt="">
                </span>
                </a>
                </div>
            </div>
        </div>
    </div>
    </nav>
    <div class="menu-account">
    <div class="container">
        <h3 class="h-24">Your Account</h3>
        <div class="act-switch">
            <p>Switch Accounts</p>
            <p>Sign Out</p>
        </div>
        <ul>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-1.svg') }}" alt="">
                <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-2.svg') }}" alt="">
                <span>Order History</span>
                </a>
            </li>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-3.svg') }}" alt="">
                <span>Wishlist</span>
                </a>
            </li>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-4.svg') }}" alt="">
                <span>Addresses</span>
                </a>
            </li>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-5.svg') }}" alt="">
                <span>Profile Settings</span>
                </a>
            </li>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-6.svg') }}" alt="">
                <span>Invoices</span>
                </a>
            </li>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-7.svg') }}" alt="">
                <span>Payment Methods</span>
                </a>
            </li>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-8.svg') }}" alt="">
                <span>Notifications</span>
                </a>
            </li>
            <li>
                <a href="" class="btn">
                <img src="{{ asset('assets/images/menuicon-9.svg') }}" alt="">
                <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    </div>
    <div class="notification">
    <div class="notification-block">
        <h3 class="h-24">Notifications</h3>
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all"
                type="button" role="tab">
                All
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="unread-tab" data-bs-toggle="tab" data-bs-target="#unread"
                type="button" role="tab">
                Unread
                </button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- All Tab -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <ul class="notification-list">
                <li class="notification-item">
                    <img src="{{ asset('assets/images/nitficatio9m.svg') }}" alt="">
                    <div>
                        <div class="notification-text">Your order has been processed.</div>
                        <div class="notification-time">5m</div>
                    </div>
                    <span class="notification-dot"></span>
                </li>
                <li class="notification-item">
                    <img src="{{ asset('assets/images/nitficatio9m.svg') }}" alt="">
                    <div>
                        <div class="notification-text">Your order shipped.</div>
                        <div class="notification-time">6d</div>
                    </div>
                </li>
                <li class="notification-item">
                    <img src="{{ asset('assets/images/nitficatio9m.svg') }}" alt="">
                    <div>
                        <div class="notification-text">Check out our Summer Sale</div>
                        <div class="notification-time">12d</div>
                    </div>
                </li>
                <li class="notification-item">
                    <img src="{{ asset('assets/images/nitficatio9m.svg') }}" alt="">
                    <div>
                        <div class="notification-text">20% off all OKF products!</div>
                        <div class="notification-time">5m</div>
                    </div>
                </li>
                </ul>
            </div>
            <!-- Unread Tab -->
            <div class="tab-pane fade" id="unread" role="tabpanel">
                <ul class="notification-list">
                <li class="notification-item">
                    <img src="{{ asset('assets/images/nitficatio9m.svg') }}" alt="">
                    <div>
                        <div class="notification-text">Your order has been processed.</div>
                        <div class="notification-time">5m</div>
                    </div>
                    <span class="notification-dot"></span>
                </li>
                </ul>
            </div>
        </div>
    </div>
    </div>
</header>