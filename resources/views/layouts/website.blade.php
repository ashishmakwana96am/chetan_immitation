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
        <div class="bg-[#B4771E] py-[12px] flex items-center overflow-hidden relative">
            <p class="text-white text-base whitespace-nowrap" style="animation: marquee 25s linear infinite;">
                Festive Season Sale: Up to 40% Off | Free Shipping on Orders Above ₹1999
            </p>
        </div>

        <!-- Navbar -->
        <div class="bg-[#131615]">
            <div class="container-1440">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('website/assets/images/logo.png') }}" class="w-[110px] xl:w-[150px] 2xl:w-auto">
                    </a>

                    <!-- Desktop Menu -->
                    <nav class="hidden lg:flex items-center gap-7 xl:gap-10">
                        <a href="{{ url('/') }}" class="{{ request()->routeIs('home') ? 'text-[#B4771E]' : 'text-white' }} hover:text-[#B4771E] text-lg pb-1 transition-colors duration-300">
                            Home
                        </a>

                        <!-- Mega Menu -->
                        <div class="group relative" onmouseenter="setHeaderCategoryArrow(true)" onmouseleave="resetSubmenus(); setHeaderCategoryArrow(false)" onfocusin="setHeaderCategoryArrow(true)" onfocusout="setHeaderCategoryArrow(false)">
                            <a href="{{ route('shop-by-category') }}" class="flex items-center gap-2 text-white hover:text-[#B4771E] text-lg pb-1 transition-colors duration-300">
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

                        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-[#B4771E]' : 'text-white' }} hover:text-[#B4771E] text-lg pb-1 transition-colors duration-300">
                            About Us
                        </a>
                        
                        <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'text-[#B4771E]' : 'text-white' }} hover:text-[#B4771E] text-lg pb-1 transition-colors duration-300">
                            Contact Us
                        </a>
                    </nav>

                    <!-- Right Side -->
                    <div class="hidden lg:flex items-center gap-5">
                        <div class="search-container flex items-center w-[200px] 2xl:w-[370px] rounded-sm h-[40px] border border-[#D5D5D533] pl-4 pr-3 bg-[#FFFFFF08] focus-within:border-[#B4771E] transition-all duration-300">
                            <input type="text" id="headerSearch" placeholder="Search" value="{{ request('search') }}" class="w-full bg-transparent text-white text-base placeholder:text-base outline-none" onkeydown="if(event.key==='Enter'){const v=this.value.trim();if(v)window.location='{{ url('/category') }}?search='+encodeURIComponent(v);}">
                            <img src="{{ asset('website/assets/images/search.png') }}" alt="" class="text-white w-[16px] h-[16px] pointer-events-none ml-2 shrink-0">
                        </div>

                        <a href="{{ auth('customer')->check() ? route('wishlist') : route('login') . '?intended=' . urlencode(route('wishlist')) }}" class="relative hover-gold-filter">
                            <img src="{{ asset('website/assets/images/heart.png') }}" alt="heart">
                            @auth('customer')
                            @php $wishlistCount = auth('customer')->user()->wishlists()->count(); @endphp
                            <span id="wishlistBadge" class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px] {{ $wishlistCount > 0 ? '' : 'hidden' }}">{{ $wishlistCount }}</span>
                            @else
                            <span id="wishlistBadge" class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px] hidden">0</span>
                            @endauth
                        </a>

                        <a href="#" class="relative hover-gold-filter">
                            <img src="{{ asset('website/assets/images/cart.png') }}" alt="cart">
                            <span class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px]">0</span>
                        </a>

                        @auth('customer')
                        <div class="relative" id="userMenuWrap">
                            <button id="userMenuBtn" class="text-white hover-gold-filter focus:outline-none" type="button">
                                <img src="{{ asset('website/assets/images/user.png') }}" alt="">
                            </button>
                            <div id="userMenuDropdown" class="hidden absolute right-0 top-full w-[200px] z-50" style="padding-top:8px;">
                                <div class="bg-white border border-[#D5D5D5] rounded-[4px] shadow-lg">
                                <div class="px-4 py-3 border-b border-[#D5D5D5]">
                                    <p class="text-sm font-semibold text-[#131615] truncate">{{ Auth::guard('customer')->user()->name }}</p>
                                    <p class="text-xs text-[#757575] truncate">{{ Auth::guard('customer')->user()->email }}</p>
                                </div>
                                <a href="{{ route('wishlist') }}"
                                    class="flex items-center gap-2 w-full px-4 py-3 text-base text-[#131615] hover:bg-[#f9f3e8] hover:text-[#B4771E] transition border-b border-[#D5D5D5]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-4 h-4 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                    My Wishlist
                                </a>
                                <button id="logoutBtn" type="button"
                                    class="w-full text-left px-4 py-3 text-base text-[#dc2626] hover:bg-[#fff5f5] transition flex items-center gap-2">
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
                        <a href="{{ route('login') }}" class="text-white hover-gold-filter">
                            <img src="{{ asset('website/assets/images/user.png') }}" alt="">
                        </a>
                        @endauth
                    </div>

                    <!-- Mobile Button -->
                    <button id="menuBtn" class="lg:hidden text-white text-[24px]">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div id="mobileMenu" class="fixed top-0 left-0 w-full h-full bg-white z-[999] translate-x-full transition duration-300 overflow-y-auto">
        <!-- Top Bar inside Mobile Menu -->
        <div class="bg-[#131615]">
            <div class="bg-[#B4771E] py-[12px] flex items-center overflow-hidden relative">
                <p class="text-white text-base whitespace-nowrap" style="animation: marquee 25s linear infinite;">
                    Festive Season Sale: Up to 40% Off | Free Shipping on Orders Above ₹1999
                </p>
            </div>
            <div class="px-5 flex items-center justify-between">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('website/assets/images/logo.png') }}" class="w-[110px] xl:w-[150px] 2xl:w-auto">
                </a>
                <button id="closeMenu" class="text-white text-2xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="px-[15px] py-[30px]">
            <a href="{{ url('/') }}" class="block pb-5 border-b {{ request()->routeIs('home') ? 'text-[#B4771E]' : 'text-[#131615]' }} hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">Home</a>

            <!-- Shop By Category -->
            <div class="border-b">
                <button onclick="toggleMenu('shopMenu','shopArrow')" class="w-full py-5 flex justify-between items-center text-[#131615] hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">
                    <span>Shop By Category</span>
                    <i id="shopArrow" class="fa-solid fa-angle-down transition duration-300"></i>
                </button>

                <div id="shopMenu" class="hidden">
                    @foreach($sharedCategories as $index => $cat)
                    <div class="border-b">
                        @if($cat->subCategories->isNotEmpty())
                            <button onclick="toggleMenu('mobile-submenu-{{ $cat->id }}','mobile-icon-{{ $cat->id }}')" class="w-full py-5 flex justify-between items-center text-[#131615] hover:text-[#B4771E] text-lg leading-[18px] pl-4 transition-colors duration-300">
                                <span>{{ $cat->name }}</span>
                                <span id="mobile-icon-{{ $cat->id }}"><i class="fa-solid fa-plus"></i></span>
                            </button>
                            <ul id="mobile-submenu-{{ $cat->id }}" class="hidden pl-10 pb-4 text-[#757575] text-base space-y-4 list-disc">
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

    <!-- Newsletter -->
    <section class="relative">
        <div class="relative py-[80px] overflow-hidden">
            <img src="{{ asset('website/assets/images/Newsletter.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-[#131615]/70"></div>
            <div class="relative z-10 h-full flex items-center justify-center px-5">
                <div class="text-center w-full">
                    <div class="w-12 h-[2px] bg-[#B4771E] mx-auto mb-5"></div>
                    <h2 class="font-moglan text-white text-[32px] md:text-[48px] lg:text-[54px] leading-none">
                        Stay Updated with New Jewelry Collections
                    </h2>
                    <p class="mt-[15px] text-white/90 text-[14px] md:text-[20px] font-normal">
                        Subscribe to receive exclusive offers, styling tips, and first access to new arrivals
                    </p>
                    <div class="max-w-[520px] mx-auto mt-8 flex flex-col gap-3 sm:gap-0 sm:flex-row">
                        <input type="email" placeholder="Enter your email" class="flex-1 h-[58px] py-5 px-4 text-lg outline-none bg-white rounded-t-[4px] sm:rounded-l-[4px] sm:rounded-r-none rounded-b-none sm:rounded-b-[4px] placeholder:text-lg">
                        <button class="h-[58px] px-8 bg-[#B4771E] text-white text-lg hover:bg-[#b57a1f] transition rounded-b-[4px] sm:rounded-r-[4px] sm:rounded-l-none rounded-t-none sm:rounded-t-r-[4px] flex items-center justify-center">
                            Subscribe
                        </button>
                    </div>
                    <p class="mt-3 text-[#D5D5D5] text-lg lg:text-xl">
                        We respect your privacy. Unsubscribe at any time.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#131615]">
        <div class="container-1440">
            <div class="grid lg:grid-cols-[1.5fr_0.9fr_0.6fr_1.1fr_1.8fr] md:grid-cols-2 gap-9 py-10">
                <!-- Logo -->
                <div>
                    <a href="{{ url('/') }}"><img src="{{ asset('website/assets/images/footer_logo.png') }}" alt="Logo" class="mb-[25px]"></a>
                    <p class="text-[#D5D5D5] text-base font-normal">
                        Premium imitation jewelry crafted for weddings, festivals, and everyday elegance. Discover timeless designs that blend tradition, beauty, and affordability.
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
                            <img src="{{ asset('website/assets/images/footer-call.png') }}" alt="">
                            <a href="tel:+919876543210" class="hover:text-[#B4771E] transition">+91 98765 43210</a>
                        </li>
                        <li class="flex items-center gap-4">
                            <img src="{{ asset('website/assets/images/footer-mail.png') }}" alt="">
                            <a href="mailto:info@chetanimitation.com" class="hover:text-[#B4771E] transition">info@chetanimitation.com</a>
                        </li>
                        <li class="flex items-start gap-4">
                            <img src="{{ asset('website/assets/images/footer-location.png') }}" alt="" class="shrink-0 mt-1">
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

    @auth('customer')
    <script>
    $(function () {
        var $wrap = $('#userMenuWrap');
        var $menu = $('#userMenuDropdown');
        var hideTimer;

        $wrap.on('mouseenter', function () {
            clearTimeout(hideTimer);
            $menu.removeClass('hidden');
        });

        $wrap.on('mouseleave', function () {
            hideTimer = setTimeout(function () {
                $menu.addClass('hidden');
            }, 120);
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
            iconClass = 'fa-solid fa-heart-crack text-gray-400';
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

        document.addEventListener('click', function (e) {
            // Only handle grid-item wishlist buttons (not detail page which has its own handler)
            var btn = e.target.closest('.wishlist-btn[data-toggle-url]');
            if (!btn) return;
            // Skip if inside the mainSwiper (detail page handles it)
            if (btn.closest('.mainSwiper')) return;

            e.preventDefault();
            e.stopPropagation();

            if (!isLoggedIn) {
                var intended = btn.dataset.currentUrl || window.location.href;
                var pendingData = {
                    product_id: btn.dataset.productId,
                    product_variant_id: btn.dataset.variantId || null
                };
                sessionStorage.setItem('pendingWishlist', JSON.stringify(pendingData));
                window.location.href = btn.dataset.loginUrl + '?intended=' + encodeURIComponent(intended);
                return;
            }

            var productId = btn.dataset.productId;
            var variantId = btn.dataset.variantId || null;
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
                if (data.status === 'added') {
                    btn.dataset.inWishlist = '1';
                    svg.classList.remove('fill-transparent', 'text-[#131615]');
                    svg.classList.add('fill-[#E01B1B]', 'text-[#E01B1B]');
                    window.showWishlistToast('Product added to your wishlist! ❤️');
                } else {
                    btn.dataset.inWishlist = '0';
                    svg.classList.remove('fill-[#E01B1B]', 'text-[#E01B1B]');
                    svg.classList.add('fill-transparent', 'text-[#131615]');
                    window.showWishlistToast('Product removed from your wishlist.');
                }
                window.updateWishlistBadge(data.count);
            })
            .catch(function () {});
        });
    })();
    </script>

    @yield('page-js')
</body>
</html>
