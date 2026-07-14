<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Chetan Imitation')</title>
    @if(str_contains(request()->getHost(), 'royalgujarati'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/favicon.png') }}" />
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Custom Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="{{ asset('website/assets/css/font.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('website/assets/css/style.css') }}">
    <style>
        html, body {
            overflow-x: hidden;
            width: 100%;
        }
        @keyframes marquee {
            0% {
                transform: translateX(100vw);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        .hover-gold-filter:hover img {
            filter: brightness(0) saturate(100%) invert(48%) sepia(58%) saturate(1354%) hue-rotate(10deg) brightness(98%) contrast(93%);
        }
        .hover-gold-filter img {
            transition: filter 0.3s ease;
        }
        .search-container:focus-within img, .search-container:hover img {
            filter: brightness(0) saturate(100%) invert(48%) sepia(58%) saturate(1354%) hue-rotate(10deg) brightness(98%) contrast(93%);
        }
        .search-container img {
            transition: filter 0.3s ease;
        }

        /* Custom Toast Notification styles */
        .custom-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 380px;
            width: calc(100% - 48px);
            pointer-events: none;
        }
        .custom-toast {
            position: relative;
            overflow: hidden;
            pointer-events: auto;
            background: rgba(19, 22, 21, 0.94);
            backdrop-filter: blur(10px);
            color: #ffffff;
            border: 1px solid rgba(180, 119, 30, 0.2);
            border-left: 5px solid #B4771E;
            padding: 16px 20px 18px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 0 15px rgba(180, 119, 30, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            transform: translateX(130%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .custom-toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        .custom-toast.hide {
            transform: translateX(130%);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .custom-toast-content {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .custom-toast-icon {
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .custom-toast-icon i {
            animation: pop-heart 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        .custom-toast-message {
            font-family: 'Nunito', sans-serif;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.4;
            letter-spacing: 0.2px;
        }
        .custom-toast-close {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            padding: 4px;
            font-size: 18px;
            line-height: 1;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .custom-toast-close:hover {
            color: #ffffff;
            transform: scale(1.15);
        }
        .custom-toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #B4771E, #F5C06A);
            width: 100%;
            animation: toast-progress 4000ms linear forwards;
        }
        @keyframes pop-heart {
            0% { transform: scale(0.6); opacity: 0; }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes toast-progress {
            0% { width: 100%; }
            100% { width: 0%; }
        }
    </style>
    @yield('page-css')
</head>
<body>

    <!-- Header -->
    <header class="relative z-50">
        <!-- Top Bar -->
        <div class="bg-[#B4771E] py-2 sm:py-[12px] flex items-center overflow-hidden relative">
            <p class="text-white text-sm sm:text-base whitespace-nowrap" style="animation: marquee 25s linear infinite;">
                {{ \App\Models\Setting::getValue('announcement_text', 'Festive Season Sale: Up to 40% Off | Free Shipping on Orders Above ₹1999') }}
            </p>
        </div>

        <!-- Navbar -->
        <div class="bg-[#131615]">
            <div class="container-1440">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('website/assets/images/logo.png') }}" class="w-[104px] xl:w-[150px] 2xl:w-auto">
                    </a>

                    <!-- Desktop Menu -->
                    <nav class="hidden lg:flex items-center gap-4 xl:gap-10">
                        <a href="{{ url('/') }}" class="{{ request()->routeIs('home') ? 'text-[#B4771E]' : 'text-white' }} hover:text-[#B4771E] text-base 2xl:text-lg pb-1 transition-colors duration-300">
                            Home
                        </a>

                        <!-- Mega Menu -->
                        <div class="group relative" onmouseenter="setHeaderCategoryArrow(true)" onmouseleave="resetSubmenus(); setHeaderCategoryArrow(false)" onfocusin="setHeaderCategoryArrow(true)" onfocusout="setHeaderCategoryArrow(false)">
                            <a href="{{ route('shop-by-category') }}" class="flex items-center gap-2 text-white hover:text-[#B4771E] text-base 2xl:text-lg pb-1 transition-colors duration-300">
                                Shop By Category
                                <i id="desktopShopArrow" class="fa-solid fa-angle-down text-xl transition-transform duration-300"></i>
                            </a>                            <!-- Dropdown -->
                            <!-- Dropdown -->
                            <div class="absolute top-full left-0 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-300">
                                <div class="w-[270px] border border-[#D5D5D5] p-4 bg-white">
                                    @foreach($sharedCategories as $index => $cat)
                                    <div class="relative">
                                        <a href="{{ route('shop-by-category', $cat->slug) }}" onmouseenter="showSubmenu('submenu-{{ $cat->id }}',this)" class="menu-btn w-full flex justify-between items-center {{ $index === 0 ? 'pb-[15px] border-b' : ($index === count($sharedCategories) - 1 ? 'pt-[15px]' : 'py-[10px] border-b') }} text-base text-[#131615] hover:text-[#B4771E] transition-colors duration-300">
                                            <span>{{ $cat->name }}</span>
                                            @if($cat->subCategories->isNotEmpty())
                                                <i class="fa-solid fa-plus text-sm"></i>
                                            @endif
                                        </a>

                                        @if($cat->subCategories->isNotEmpty())
                                        <div id="submenu-{{ $cat->id }}" class="submenu hidden absolute top-0 left-full ml-4 bg-white border border-[#D5D5D5] min-w-[250px] p-4 z-50">
                                            @forelse($cat->subCategories as $subIndex => $sub)
                                                @php
                                                    $isFirst = ($subIndex === 0);
                                                    $isLast = ($subIndex === count($cat->subCategories) - 1);
                                                    $class = 'block text-[#131615] text-base hover:text-[#B4771E] transition-colors duration-300 ';
                                                    if ($isFirst && $isLast) {
                                                        $class .= 'py-0';
                                                    } elseif ($isFirst) {
                                                        $class .= 'pb-3 border-b';
                                                    } elseif ($isLast) {
                                                        $class .= 'pt-3';
                                                    } else {
                                                        $class .= 'py-3 border-b';
                                                    }
                                                @endphp
                                                <a href="{{ route('shop-by-category', ['slug' => $cat->slug, 'sub_category' => $sub->slug]) }}" class="{{ $class }}">{{ $sub->name }}</a>
                                            @empty
                                            @endforelse
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-[#B4771E]' : 'text-white' }} hover:text-[#B4771E] text-base 2xl:text-lg pb-1 transition-colors duration-300">
                            About Us
                        </a>
                        
                        <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'text-[#B4771E]' : 'text-white' }} hover:text-[#B4771E] text-base 2xl:text-lg pb-1 transition-colors duration-300">
                            Contact Us
                        </a>
                    </nav>

                    <!-- Right Side -->
                    <div class="flex items-center gap-2 sm:gap-5">
                        <!-- Mobile Search Icon -->

                        <div class="search-container items-center w-[170px] sm:w-[200px] 2xl:w-[370px] rounded-sm h-[34px] lg:h-[40px] border border-[#D5D5D533] pl-4 pr-3 bg-[#FFFFFF08] focus-within:border-[#B4771E] transition-all duration-300 flex">
                            @php
                                $headerSearchValue = request()->routeIs('shop-by-category')
                                    ? (request('search') ?? session('shop_filters.search'))
                                    : null;
                            @endphp
                            <input type="text" id="headerSearch" placeholder="Search" value="{{ $headerSearchValue }}" class="w-full bg-transparent text-white text-xs lg:text-base placeholder:text-xs placeholder:lg:text-base outline-none" onkeydown="if(event.key==='Enter'){const v=this.value.trim();if(v)window.location='{{ url('/shop') }}?search='+encodeURIComponent(v);}">
                            <svg id="clearHeaderSearch" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-white/60 hover:text-white cursor-pointer mr-2 {{ $headerSearchValue ? '' : 'hidden' }} shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 lg:size-6 text-white shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>

                        <a href="{{ auth('customer')->check() ? route('wishlist') : route('login') . '?intended=' . urlencode(route('wishlist')) }}" class="relative hover-gold-filter hidden lg:block">
                           <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                </svg>

                            @auth('customer')
                            @php $wishlistCount = auth('customer')->user()->wishlists()->count(); @endphp
                            <span id="wishlistBadge" class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px] {{ $wishlistCount > 0 ? '' : 'hidden' }}">{{ $wishlistCount }}</span>
                            @else
                            <span id="wishlistBadge" class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px] hidden">0</span>
                            @endauth
                        </a>

                        <a href="{{ route('cart') }}" class="relative hover-gold-filter hidden lg:block">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>

                            @auth('customer')
                            @php $cartCount = \App\Models\CartItem::where('customer_id', auth('customer')->id())->sum('qty'); @endphp
                            <span id="cartBadge" class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px] {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                            @else
                            @php
                                $guestCart = session()->get('guest_cart', []);
                                $cartCount = array_sum(array_column($guestCart, 'qty'));
                            @endphp
                            <span id="cartBadge" class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px] {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                            @endauth
                        </a>

                        @auth('customer')
                        <div class="relative items-center hidden lg:flex" id="userMenuWrap">
                            <button id="userMenuBtn" class="text-white hover-gold-filter focus:outline-none flex items-center p-0 bg-transparent border-0" type="button">
                               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>

                            </button>
                            <div id="userMenuDropdown" class="hidden absolute right-0 top-full w-[200px] z-50" style="padding-top:8px;">
                                <div class="bg-white border border-[#D5D5D5] rounded-[4px] shadow-lg">
                                <div class="px-4 py-3 border-b border-[#D5D5D5]">
                                    <p id="navbarCustomerName" class="text-sm font-semibold text-[#131615] truncate">{{ Auth::guard('customer')->user()->name }}</p>
                                    <p class="text-xs text-[#757575] truncate">{{ Auth::guard('customer')->user()->email }}</p>
                                </div>
                                <a href="{{ route('customer.profile') }}"
                                    class="flex items-center gap-2 w-full px-4 py-3 text-base text-[#131615] hover:bg-[#f9f3e8] hover:text-[#B4771E] transition border-b border-[#D5D5D5]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-4 h-4 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    My Account
                                </a>
                                <!-- <a href="{{ route('cart') }}"
                                    class="flex items-center gap-2 w-full px-4 py-3 text-base text-[#131615] hover:bg-[#f9f3e8] hover:text-[#B4771E] transition border-b border-[#D5D5D5]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-4 h-4 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    My Cart
                                </a>
                                <a href="{{ route('wishlist') }}"
                                    class="flex items-center gap-2 w-full px-4 py-3 text-base text-[#131615] hover:bg-[#f9f3e8] hover:text-[#B4771E] transition border-b border-[#D5D5D5]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-4 h-4 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                    My Wishlist
                                </a> -->
                                <button id="logoutBtn" type="button"
                                    class="w-full text-left px-4 py-3 text-base text-[#dc2626] hover:bg-[#fff5f5] transition flex items-center gap-2">
                                       <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 shrink-0 rotate-[180deg]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                                    </svg>
                                    <span id="logoutBtnText">Logout</span>
                                    <svg id="logoutSpinner" class="hidden animate-spin w-4 h-4 text-[#dc2626]" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                </button>
                                </div>
                                <form id="logoutForm" method="POST" action="{{ route('customer.logout') }}" class="hidden">
                                    @csrf
                                </form>
                            </div>
                        </div>
                        @else
                        <a href="{{ route('login') }}" class="text-white hover-gold-filter hidden lg:block">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </a>
                        @endauth
                        <!-- Mobile Button -->
                        <button id="menuBtn" class="lg:hidden text-white text-[24px]">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <div
    id="mobileSearchPopup"
    class="fixed inset-0 bg-black/50 z-[9999] hidden"
>
    <div class="bg-white p-4">
        <div class="flex items-center gap-3">

            <input
                type="text"
                id="mobileSearchInput"
                placeholder="Search products..."
                class="flex-1 border border-gray-300 rounded px-4 h-11 outline-none"
            >

            <button id="closeMobileSearch">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>

        </div>
    </div>
</div>
    </header>

    <div id="mobileMenu" class="fixed top-0 left-0 w-full h-full bg-white z-[999] translate-x-full transition duration-300 overflow-y-auto">
        <!-- Top Bar inside Mobile Menu -->
        <div class="bg-[#131615]">
            <div class="bg-[#B4771E] py-2 sm:py-[12px] flex items-center overflow-hidden relative">
                <p class="text-white text-sm sm:text-base whitespace-nowrap" style="animation: marquee 25s linear infinite;">
                    {{ \App\Models\Setting::getValue('announcement_text', 'Festive Season Sale: Up to 40% Off | Free Shipping on Orders Above ₹1999') }}
                </p>
            </div>
            <div class="px-5 flex items-center justify-between">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('website/assets/images/logo.png') }}" class="w-[104px] xl:w-[150px] 2xl:w-auto">
                </a>
                <button id="closeMenu" class="text-white text-2xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="px-[15px] py-[30px]">
            
        @auth('customer')
    <div class="border-b">

    <button onclick="toggleMenu('userMenu','userArrow')"
        class="w-full pb-4 md:pb-5 flex justify-between items-center text-[#131615] hover:text-[#B4771E] text-lg transition-colors duration-300">

        <div class="flex items-center gap-3">

        <!-- User Icon -->
        <div class="w-12 h-12 rounded-full border border-[#EFE7DB]
            bg-[#FAF7F2] flex items-center justify-center shrink-0">

            <svg xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="#B4771E"
                class="w-6 h-6">

                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0
                    3.75 3.75 0 0 1 7.5 0ZM4.501
                    20.118a7.5 7.5 0 0 1 14.998 0A17.933
                    17.933 0 0 1 12 21.75c-2.676
                    0-5.216-.584-7.499-1.632Z"/>
            </svg>

        </div>

        <!-- User Info -->
        <div class="text-left">

            <p id="mobileNavbarCustomerName" class="font-semibold text-lg leading-[22px] text-[#131615]">
                {{ auth('customer')->user()->name }}
            </p>

            <p class="text-[13px] text-[#757575] mt-1">
                {{ auth('customer')->user()->email }}
            </p>

        </div>

    </div>

        <i id="userArrow"
            class="fa-solid fa-angle-down transition-transform duration-300">
            </i>

    </button>

    <div id="userMenu" class="hidden pb-4">

        @php
            $cartCount = \App\Models\CartItem::where('customer_id', auth('customer')->id())->sum('qty');
            $wishlistCount = auth('customer')->user()->wishlists()->count();
        @endphp

        <!-- Cart -->
        <a href="{{ route('cart') }}"
            class="flex items-center justify-between py-3 text-[#131615] hover:text-[#B4771E]">

            <div class="flex items-center gap-3">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>

                <span>My Cart</span>

            </div>

            <div class="flex items-center gap-2">

                <span
                    class="w-6 h-6 rounded-full bg-[#B4771E]
                    text-white text-xs font-semibold
                    flex items-center justify-center">

                    {{ $cartCount }}

                </span>

                <i class="fa-solid fa-angle-right text-sm"></i>

            </div>

        </a>

            <!-- Wishlist -->
        <a href="{{ route('wishlist') }}"
            class="flex items-center justify-between py-3 text-[#131615] hover:text-[#B4771E]">

            <div class="flex items-center gap-3">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>

                <span>My Wishlist</span>

            </div>

            <div class="flex items-center gap-2">

                <span
                    class="w-6 h-6 rounded-full bg-[#B4771E]
                    text-white text-xs font-semibold
                    flex items-center justify-center">

                    {{ $wishlistCount }}

                </span>

                <i class="fa-solid fa-angle-right text-sm"></i>

            </div>

        </a>

        <!-- Logout -->
        <button id="mobileLogoutBtn"
            class="w-full flex items-center gap-3 py-3 text-[#dc2626] hover:text-[#b91c1c]">

         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 rotate-[180deg]">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
        </svg>


            <span>Logout</span>

        </button>

        <form id="mobileLogoutForm"
            method="POST"
            action="{{ route('customer.logout') }}"
            class="hidden">
            @csrf
        </form>

    </div>

</div>
@endauth

@guest('customer')

<a href="{{ route('login') }}"
    class="flex items-center gap-3 py-4 border-b text-[#131615] hover:text-[#B4771E] text-lg transition-colors duration-300">

    <svg xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        class="w-6 h-6">

        <path stroke-linecap="round"
            stroke-linejoin="round"
            d="M15.75 6a3.75 3.75 0 1 1-7.5 0
            3.75 3.75 0 0 1 7.5 0ZM4.501
            20.118a7.5 7.5 0 0 1 14.998 0A17.933
            17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
    </svg>

    <span>Login</span>

</a>

@endguest

            <a href="{{ url('/') }}" class="block py-4 md:py-5 border-b {{ request()->routeIs('home') ? 'text-[#B4771E]' : 'text-[#131615]' }} hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">Home</a>

            <!-- Shop By Category -->
            <div class="border-b">
                <button onclick="toggleMenu('shopMenu','shopArrow')" class="w-full py-4 md:py-5 flex justify-between items-center text-[#131615] hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">
                    <span>Shop By Category</span>
                    <i id="shopArrow" class="fa-solid fa-angle-down transition duration-300"></i>
                </button>

                <div id="shopMenu" class="hidden">
                    <div class="border-b">
                        <a href="{{ route('shop-by-category') }}" class="block w-full py-3 md:py-5 text-[#131615] hover:text-[#B4771E] font-semibold text-base md:text-lg leading-[18px] pl-4 transition-colors duration-300">
                            View All Products
                        </a>
                    </div>
                    @foreach($sharedCategories as $index => $cat)
                    <div class="border-b">
                        @if($cat->subCategories->isNotEmpty())
                            <div class="w-full flex justify-between items-center pl-4 transition-colors duration-300">
                                <a href="{{ route('shop-by-category', $cat->slug) }}" class="flex-1 py-3 md:py-5 text-[#131615] hover:text-[#B4771E] text-base md:text-lg leading-[18px] transition-colors duration-300">
                                    {{ $cat->name }}
                                </a>
                                <button onclick="toggleMenu('mobile-submenu-{{ $cat->id }}','mobile-icon-{{ $cat->id }}')" class="py-3 md:py-5 px-4 md:px-5 text-base md:text-lg text-[#131615] hover:text-[#B4771E] focus:outline-none transition-colors duration-300" type="button">
                                    <span id="mobile-icon-{{ $cat->id }}"><i class="fa-solid fa-plus"></i></span>
                                </button>
                            </div>
                            <ul id="mobile-submenu-{{ $cat->id }}" class="hidden pl-10 pb-4 text-[#757575] text-base space-y-4 list-disc">
                                <li><a href="{{ route('shop-by-category', $cat->slug) }}" class="text-[#757575] hover:text-[#B4771E] font-semibold transition-colors duration-300">View All {{ $cat->name }}</a></li>
                                @foreach($cat->subCategories as $sub)
                                <li><a href="{{ route('shop-by-category', ['slug' => $cat->slug, 'sub_category' => $sub->slug]) }}" class="text-[#757575] hover:text-[#B4771E] transition-colors duration-300">{{ $sub->name }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <a href="{{ route('shop-by-category', $cat->slug) }}" class="block w-full py-5 text-[#131615] hover:text-[#B4771E] text-lg leading-[18px] pl-4 transition-colors duration-300">
                                {{ $cat->name }}
                            </a>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('about') }}" class="block py-4 border-b {{ request()->routeIs('about') ? 'text-[#B4771E]' : 'text-[#131615]' }} hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">About Us</a>
            <a href="{{ route('contact') }}" class="block py-4 {{ request()->routeIs('contact') ? 'text-[#B4771E]' : 'text-[#131615]' }} hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">Contact Us</a>
        </div>
    </div>

    <!-- Main Content -->
    @yield('content')

    @if(!request()->routeIs(['login', 'register', 'forgot-password', 'otp-verification', 'customer.reset-password', 'password.reset']))
    <!-- Newsletter -->
    <!-- <section class="relative">
        <div class="relative py-[80px] overflow-hidden">
            <img src="{{ asset('website/assets/images/Newsletter.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-[#131615]/70"></div>
            <div class="relative z-10 h-full flex items-center justify-center px-5">
                <div class="text-center w-full">
                    <div class="w-12 h-[2px] bg-[#B4771E] mx-auto mb-5"></div>
                    <h2 class="font-moglan text-white text-[32px] md:text-[48px] lg:text-[54px] leading-none">
                        Stay Updated with New Jewellery Collections
                    </h2>
                    <p class="mt-[15px] text-white/90 text-[14px] md:text-[20px] font-normal">
                        Subscribe to receive exclusive offers, styling tips, and first access to new arrivals
                    </p>
                    <div class="max-w-[520px] mx-auto mt-8 flex flex-col gap-3 sm:gap-0 sm:flex-row">
                        <input type="email" placeholder="Enter your email" class="flex-1 h-[40px] md:h-[58px] py-3 md:py-5 px-4 md:text-lg outline-none bg-white rounded-t-[4px] sm:rounded-l-[4px] sm:rounded-r-none rounded-b-none sm:rounded-b-[4px] text-base placeholder:text-base placeholder:md:text-lg">
                        <button class="h-[40px] md:h-[58px] px-8 bg-[#B4771E] text-white md:text-lg hover:bg-[#b57a1f] transition rounded-b-[4px] sm:rounded-r-[4px] sm:rounded-l-none rounded-t-none sm:rounded-t-r-[4px] flex items-center justify-center text-base">
                            Subscribe
                        </button>
                    </div>
                    <p class="mt-3 text-[#D5D5D5] text-base sm:text-lg lg:text-xl">
                        We respect your privacy. Unsubscribe at any time.
                    </p>
                </div>
            </div>
        </div>
    </section> -->
    @endif

    <!-- Footer -->
    <footer class="bg-[#131615]">
        <div class="container-1440">
            <div class="grid lg:grid-cols-[1.5fr_0.9fr_0.6fr_1.1fr_1.8fr] md:grid-cols-2 gap-9 py-10">
                <!-- Logo -->
                <div>
                    <a href="{{ url('/') }}"><img src="{{ asset('website/assets/images/footer_logo.png') }}" alt="Logo" class="mb-[25px]"></a>
                    <p class="text-[#D5D5D5] text-base font-normal">
                        Premium imitation jewellery crafted for weddings, festivals, and everyday elegance. Discover timeless designs that blend tradition, beauty, and affordability.
                    </p>
                </div>

                <!-- Category -->
                <div>
                    <h3 class="text-[#B4771E] text-[18px] lg:text-lg font-semibold mb-5">Shop By Category</h3>
                    <ul class="space-y-3 md:space-y-4 text-[#D5D5D5] text-base">
                        @foreach($sharedCategories->shuffle()->take(6) as $cat)
                        <li><a href="{{ route('shop-by-category', $cat->slug) }}" class="hover:text-[#B4771E] transition">{{ $cat->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="text-[#B4771E] text-[18px] lg:text-lg font-semibold mb-5">Company</h3>
                    <ul class="space-y-3 md:space-y-4 text-[#D5D5D5] text-base">
                        <li><a href="{{ route('about') }}" class="hover:text-[#B4771E] transition">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-[#B4771E] transition">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h3 class="text-[#B4771E] text-[18px] lg:text-lg font-semibold mb-5">Customer Service</h3>
                    <ul class="space-y-3 md:space-y-4 text-[#D5D5D5] text-base">
                        <li><a href="{{ route('terms') }}" class="hover:text-[#B4771E] transition">Terms & Conditions</a></li>
                        <li><a href="{{ route('delivery-returns') }}" class="hover:text-[#B4771E] transition">Deliveries & Returns</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-[#B4771E] transition">Privacy Policy</a></li>
                        <li><a href="{{ route('refund-cancellation') }}" class="hover:text-[#B4771E] transition">Refund & Cancellation Policy</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-[#B4771E] text-[18px] lg:text-lg font-semibold mb-5">Contact Us</h3>
                    <ul class="space-y-4 text-[#D5D5D5] text-base">
                        <li class="flex items-center gap-4">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-[#B4771E]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                            <a href="tel:+919876543210" class="hover:text-[#B4771E] transition">+91 98765 43210</a>
                        </li>
                        <li class="flex items-center gap-4">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" Box="0 0 24 24" stroke-width="1.5" stroke="currentColor"  class="w-5 h-5 text-[#B4771E]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <a href="mailto:info@chetanimitation.com" class="hover:text-[#B4771E] transition">info@chetanimitation.com</a>
                        </li>
                        <li class="flex items-start gap-4">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-[#B4771E] shrink-0 mt-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            <a href="https://maps.google.com/?q=G-14+Abc+market+Abc+circle+Sudama+chowk+Mota+Varachha+Surat+Gujarat+394101+India" target="_blank" class="hover:text-[#B4771E] transition">
                                G-14 Abc market, Abc circle, Sudama chowk, Mota Varachha, Surat, Gujarat 394101, India
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Bottom Footer -->
        <div class="border-t border-white/10">
            <div class="container-1440">
                <div class="flex flex-col md:flex-row items-center justify-between gap-5 py-[20px]">
                    <p class="text-[#D5D5D5] text-base text-center md:text-left">
                        © {{ date('Y') }} Chetan Imitation. All Rights Reserved | Developed by <a href="https://www.risingstarinfotech.com/" target="_blank" class="text-[#B4771E] hover:text-[#B4771E]">Rising Star Infotech</a>
                    </p>

                    <!-- Social -->
                    <div class="flex items-center gap-[15px]">
                        <a href="https://www.facebook.com/" class="w-[38px] h-[38px] rounded-full border border-[#FFFFFF1A] flex items-center justify-center text-white bg-[#FFFFFF0D] hover:bg-[#B4771E] hover:border-[#B4771E] hover:text-white transition">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/chetan_imitation?igsh=Zm9lNHNoaTQ3c2t4&utm_source=qr" class="w-[38px] h-[38px] rounded-full border border-[#FFFFFF1A] flex items-center justify-center text-white bg-[#FFFFFF0D] hover:bg-[#B4771E] hover:border-[#B4771E] hover:text-white transition">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="https://wa.me/919876543210" class="w-[38px] h-[38px] rounded-full border border-[#FFFFFF1A] flex items-center justify-center text-white bg-[#FFFFFF0D] hover:bg-[#B4771E] hover:border-[#B4771E] hover:text-white transition">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="{{ asset('website/assets/js/main.js') }}?v=1.0.1"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js" integrity="sha512-bPs7Ae6pVvhOSiIcyUClR7/q2OAsRiovw4vAkX+zJbw3ShAeeqezq50RIIcIURq7Oa20rW2n2q+fyXBNcU9lrw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
   <script>
    const mobileMenu = document.getElementById('mobileMenu');

window.addEventListener('resize', function () {
    if (window.innerWidth >= 1024) { // lg breakpoint
        mobileMenu.classList.remove('translate-x-0');
        mobileMenu.classList.add('translate-x-full');
    }
});
   </script>
    @auth('customer')
    <script>
    $(function () {
        var $wrap = $('#userMenuWrap');
        var $menu = $('#userMenuDropdown');
        var hideTimer;

        // Toggle on click for mobile/touch devices
        // $('#userMenuBtn').on('click', function (e) {
        //     e.stopPropagation();
        //     $menu.toggleClass('hidden');
        // });

        $(function () {
            const $btn = $('#userMenuBtn');
            const $menu = $('#userMenuDropdown');

            $btn.on('click touchstart', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if ($menu.hasClass('hidden')) {
                    $menu.removeClass('hidden');
                } else {
                    $menu.addClass('hidden');
                }
            });

            $(document).on('click touchstart', function (e) {
                if (!$(e.target).closest('#userMenuWrap').length) {
                    $menu.addClass('hidden');
                }
            });

            $menu.on('click touchstart', function (e) {
                e.stopPropagation();
            });
        });

        // Hover for desktop users
        $wrap.on('mouseenter', function () {
            clearTimeout(hideTimer);
            $menu.removeClass('hidden');
        });

        $wrap.on('mouseleave', function () {
            hideTimer = setTimeout(function () {
                $menu.addClass('hidden');
            }, 120);
        });

        // Close on clicking outside
        $(document).on('click', function () {
            $menu.addClass('hidden');
        });

        // Prevent close on clicking inside the menu
        $menu.on('click', function (e) {
            e.stopPropagation();
        });

        $('#logoutBtn').on('click', function () {
            $(this).prop('disabled', true);
            $('#logoutBtnText').text('Logging out...');
            $('#logoutSpinner').removeClass('hidden');

            $.ajax({
                url: '{{ route('customer.logout') }}',
                method: 'POST',
                data: { _token: $('input[name="_token"]', '#logoutForm').val() },
                success: function (res) {
                    window.location.href = (res && res.redirect_url) ? res.redirect_url : '{{ route('login') }}';
                },
                error: function () {
                    $('#logoutForm').submit();
                }
            });
        });
    });
    </script>
    @endauth

    <script>
    // Global wishlist helper functions
    window.updateWishlistBadge = function (count) {
        var badge = document.getElementById('wishlistBadge');
        if (!badge) return;
        badge.textContent = count;
        if (count > 0) {
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    };

    window.showWishlistToast = function (message, isSuccess) {
        var container = document.querySelector('.custom-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'custom-toast-container';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'custom-toast';

        var iconClass = 'fa-solid fa-heart-circle-check text-[#B4771E]';
        if (isSuccess === false) {
            iconClass = 'fa-solid fa-heart-circle-xmark text-red-500';
        } else if (message.includes('Remove') || message.includes('remove') || message.includes('Removed')) {
            iconClass = 'fa-solid fa-heart-circle-xmark text-red-500';
        }

        toast.innerHTML = `
            <div class="custom-toast-content">
                <span class="custom-toast-icon"><i class="${iconClass}"></i></span>
                <span class="custom-toast-message">${message}</span>
            </div>
            <button class="custom-toast-close">&times;</button>
            <div class="custom-toast-progress"></div>
        `;

        container.appendChild(toast);

        // Trigger animation
        setTimeout(function() {
            toast.classList.add('show');
        }, 10);

        var closeBtn = toast.querySelector('.custom-toast-close');
        var hideTimeout = setTimeout(closeToast, 4000);

        function closeToast() {
            clearTimeout(hideTimeout);
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }

        closeBtn.addEventListener('click', closeToast);
    };

    document.addEventListener('DOMContentLoaded', function () {
        var pendingToast = sessionStorage.getItem('wishlistToastPending');
        if (pendingToast && window.showWishlistToast) {
            window.showWishlistToast(pendingToast);
            sessionStorage.removeItem('wishlistToastPending');
        }
    });

    (function () {
        var isLoggedIn = {{ auth('customer')->check() ? 'true' : 'false' }};
        var csrfToken  = '{{ csrf_token() }}';

        // Update card links when a variant is selected
        document.addEventListener('change', function (e) {
            var select = e.target.closest('.grid-variant-select');
            if (!select) return;

            var card = select.closest('.product-card');
            if (!card) return;

            var variantId = select.value;
            var detailLinks = card.querySelectorAll('.product-detail-link');

            detailLinks.forEach(function (link) {
                var url = new URL(link.href, window.location.origin);
                if (variantId) {
                    url.searchParams.set('variant', variantId);
                } else {
                    url.searchParams.delete('variant');
                }
                link.href = url.pathname + url.search;
            });
        });

        document.addEventListener('click', function (e) {
            // Only handle grid-item wishlist buttons (not detail page which has its own handler)
            var btn = e.target.closest('.wishlist-btn[data-toggle-url]');
            if (!btn) return;
            // Skip if the button is the main product wishlist button on details page
            if (btn.dataset.isMainWishlist === '1') return;

            e.preventDefault();
            e.stopPropagation();

            if (btn.dataset.loading === '1') return;
            btn.dataset.loading = '1';

            var card = btn.closest('.product-card');
            var variantId = null;
            if (card) {
                var select = card.querySelector('.grid-variant-select');
                if (select && select.value) {
                    variantId = select.value;
                }
            }

            if (!isLoggedIn) {
                btn.dataset.loading = '0';
                var intended = btn.dataset.currentUrl || window.location.href;
                var pendingData = {
                    product_id: btn.dataset.productId,
                    product_variant_id: variantId
                };
                sessionStorage.setItem('pendingWishlist', JSON.stringify(pendingData));
                window.location.href = btn.dataset.loginUrl + '?intended=' + encodeURIComponent(intended);
                return;
            }

            var productId = btn.dataset.productId;
            var toggleUrl = btn.dataset.toggleUrl;
            var svg       = btn.querySelector('svg');

            fetch(toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ product_id: productId, product_variant_id: variantId }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.dataset.loading = '0';
                var nowInWishlist = (data.status === 'added' || data.status === 'updated');
                
                // Sync ALL wishlist buttons on the page with the same product_id
                document.querySelectorAll('.wishlist-btn[data-product-id="' + productId + '"]').forEach(function(b) {
                    var icon = b.querySelector('svg, .wishlist-icon');
                    if (!icon) return;
                    if (nowInWishlist) {
                        b.dataset.inWishlist = '1';
                        icon.classList.remove('fill-transparent', 'text-[#131615]');
                        icon.classList.add('fill-[#E01B1B]', 'text-[#E01B1B]');
                    } else {
                        b.dataset.inWishlist = '0';
                        icon.classList.remove('fill-[#E01B1B]', 'text-[#E01B1B]');
                        icon.classList.add('fill-transparent', 'text-[#131615]');
                    }
                });
                
                if (nowInWishlist) {
                    window.showWishlistToast('Product added to your wishlist! ❤️');
                } else {
                    window.showWishlistToast('Product removed from your wishlist.');
                }
                window.updateWishlistBadge(data.count);
                
                document.dispatchEvent(new CustomEvent('wishlistToggled', { detail: Object.assign({ product_id: productId }, data) }));
            })
            .catch(function () {
                btn.dataset.loading = '0';
            });
        });
    })();
    </script>

    <script>
    (function () {
        var csrfToken = '{{ csrf_token() }}';

        // ── Global Add to Cart function ──────────────────────────────────────────
        window.addToCart = function (productId, variantId, qty, btn, loginUrl, pairType) {
            var cartAddUrl = '{{ route('cart.add') }}';
            var cartLoginUrl = loginUrl || ('{{ route('login') }}?intended={{ urlencode(route('cart')) }}');
            var isLoggedIn = {{ auth('customer')->check() ? 'true' : 'false' }};

            var originalText = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            }



            fetch(cartAddUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    variant_id: variantId || null,
                    qty: qty || 1,
                    pair_type: pairType || 'single',
                }),
            })
            .then(function (r) {
                if (r.status === 401) {
                    throw new Error('UNAUTHORIZED');
                }
                return r.json().then(function (data) {
                    if (!r.ok) {
                        throw new Error(data.message || 'Server error occurred.');
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                updateCartBadge(data.count);
                showToast('Item added to cart! 🛒');
                document.dispatchEvent(new CustomEvent('cartUpdated'));
            })
            .catch(function (error) {
                if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                if (error.message === 'UNAUTHORIZED') {
                    window.location.href = cartLoginUrl;
                } else {
                    showToast(error.message || 'Something went wrong.', 'error');
                }
            });
        };

        // Guest cart synchronization and cleanup will be executed at the end of the IIFE

        // Cart badge update
        function updateCartBadge(count) {
            var badge = document.getElementById('cartBadge');
            if (!badge) return;
            badge.textContent = count;
            if (count > 0) badge.classList.remove('hidden');
            else badge.classList.add('hidden');
        }
        window.updateCartBadge = updateCartBadge;

        // Toast (reuse same showWishlistToast)
        function showToast(message, type) {
            if (window.showWishlistToast) {
                window.showWishlistToast(message, type !== 'error');
            }
        }

        // ── Grid "Add to Cart" click delegate ────────────────────────────────────
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.add-to-cart-btn');
            if (!btn) return;

            // Block disabled / sold-out buttons
            if (btn.disabled || btn.hasAttribute('disabled')) return;

            var card      = btn.closest('.product-card');
            var productId = btn.dataset.productId;
            var loginUrl  = btn.dataset.loginUrl;
            var variantId = null;

            if (card) {
                var select = card.querySelector('.grid-variant-select');
                if (select) {
                    if (!select.value) {
                        if (window.showWishlistToast) {
                            window.showWishlistToast('Please select attribute first', false);
                        } else {
                            alert('Please select attribute first');
                        }
                        return;
                    }
                    variantId = select.value;
                }
            }

            window.addToCart(productId, variantId, 1, btn, loginUrl);
        });

        // ── Search Input clear functionality ──────────────────────────────────────
        const headerSearch = document.getElementById('headerSearch');
        const clearHeaderSearch = document.getElementById('clearHeaderSearch');

        if (headerSearch && clearHeaderSearch) {
            const toggleClearIcon = () => {
                if (headerSearch.value.trim().length > 0) {
                    clearHeaderSearch.classList.remove('hidden');
                } else {
                    clearHeaderSearch.classList.add('hidden');
                }
            };

            headerSearch.addEventListener('input', toggleClearIcon);
            
            clearHeaderSearch.addEventListener('click', () => {
                headerSearch.value = '';
                clearHeaderSearch.classList.add('hidden');
                headerSearch.focus();

                if (window.location.pathname.includes('/shop')) {
                    window.location.href = '{{ url('/shop') }}?clear_search=1';
                } else {
                    fetch('{{ url('/shop') }}?clear_search=1', { redirect: 'manual', keepalive: true });
                }
            });
        }

        // ── Auto-open third party URLs in new tab ───────────────────────────────
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:')) {
                return;
            }

            try {
                const linkUrl = new URL(link.href);
                if (linkUrl.host !== window.location.host) {
                    link.setAttribute('target', '_blank');
                    const existingRel = link.getAttribute('rel') || '';
                    if (!existingRel.includes('noopener')) {
                        link.setAttribute('rel', (existingRel + ' noopener noreferrer').trim());
                    }
                }
            } catch (err) {
                // Ignore invalid URLs
            }
        });

        // Clean up old guest cart cookie and localStorage
        if (localStorage.getItem('guest_cart')) {
            localStorage.removeItem('guest_cart');
        }
        document.cookie = "guest_cart=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT;";
    })();
    </script>

    <!-- Custom Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="fixed inset-0 z-50 hidden bg-black/50 p-4 !mt-0 overflow-y-auto">
        <div class="min-h-full flex items-center justify-center !mt-0">
            <div class="relative w-full max-w-[500px] bg-white rounded-[8px] p-6 sm:p-8 border border-[#D5D5D5] shadow-lg text-center">
                <!-- Close Button -->
                <button onclick="closeDeleteConfirmModal()" class="absolute top-4 right-4 text-[32px] text-[#131615] leading-none">&times;</button>
                
                <!-- Alert Icon / Warning Sign -->
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center text-red-500">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Heading -->
                <h3 class="text-xl sm:text-2xl font-semibold text-[#131615] mb-2">Are you sure?</h3>
                <!-- Description -->
                <p class="text-gray-500 text-sm sm:text-base mb-6">You want to delete this address?</p>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button id="deleteConfirmCancelBtn" onclick="closeDeleteConfirmModal()" class="flex items-center justify-center w-full h-[52px] border-2 border-[#131615] text-[#131615] text-lg font-medium transition common-btn bg-transparent hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E]">
                        Cancel
                    </button>
                    <button id="deleteConfirmConfirmBtn" class="w-full bg-[#B4771E] text-white text-lg font-medium h-[52px] common-btn transition flex justify-center items-center">
                        Yes, delete it!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    window.showDeleteConfirm = function(onConfirm) {
        const modal = document.getElementById('deleteConfirmModal');
        const confirmBtn = document.getElementById('deleteConfirmConfirmBtn');
        if (!modal || !confirmBtn) return;

        modal.classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
        document.body.classList.add('modal-open');

        const handleConfirm = function(e) {
            e.preventDefault();
            window.closeDeleteConfirmModal();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        };

        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        newConfirmBtn.addEventListener('click', handleConfirm);
    };

    window.closeDeleteConfirmModal = function() {
        const modal = document.getElementById('deleteConfirmModal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
        document.body.classList.remove('modal-open');
    };
    </script>

    <!-- Floating WhatsApp Chat Button -->
    <a href="https://wa.me/919876543210" target="_blank" rel="noopener"
        class="fixed bottom-5 right-5 z-[999] w-[56px] h-[56px] rounded-full bg-[#25D366] flex items-center justify-center shadow-lg hover:scale-110 transition"
        aria-label="Chat with us on WhatsApp">
        <span class="absolute inline-flex h-full w-full rounded-full bg-[#25D366] opacity-75 animate-ping"></span>
        <i class="fa-brands fa-whatsapp text-white text-[28px] relative"></i>
    </a>

    @yield('page-js')
</body>
</html>
