<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <div class="bg-[#050b0d]">
            <div class="container-1440">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('website/assets/images/logo.png') }}" class="w-[110px] xl:w-[150px] 2xl:w-auto">
                    </a>

                    <!-- Desktop Menu -->
                    <nav class="hidden lg:flex items-center gap-7 xl:gap-10">
                        <a href="{{ url('/') }}" class="text-[#B4771E] text-lg pb-1">
                            Home
                        </a>

                        <!-- Mega Menu -->
                        <div class="group relative" onmouseleave="resetSubmenus()">
                            <button class="flex items-center gap-2 text-white group-hover:text-[#B4771E] text-lg pb-1 transition-colors duration-300">
                                Shop By Category
                                <i class="fa-solid fa-angle-down text-xl"></i>
                            </button>                            <!-- Dropdown -->
                            <!-- Dropdown -->
                            <div class="absolute top-full left-0 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition duration-300">
                                <div class="w-[270px] border border-[#D5D5D5] p-4 bg-white">
                                    @foreach($sharedCategories as $index => $cat)
                                    <div class="relative">
                                        <button onmouseenter="showSubmenu('submenu-{{ $cat->id }}',this)" class="menu-btn w-full flex justify-between items-center {{ $index === 0 ? 'pb-[15px] border-b' : ($index === count($sharedCategories) - 1 ? 'pt-[15px]' : 'py-[10px] border-b') }} text-base text-[#131615] hover:text-[#B4771E] focus:outline-none transition-colors duration-300">
                                            <span>{{ $cat->name }}</span>
                                            @if($cat->subCategories->isNotEmpty())
                                                <i class="fa-solid fa-plus text-sm"></i>
                                            @endif
                                        </button>

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
                                                <a href="#" class="{{ $class }}">{{ $sub->name }}</a>
                                            @empty
                                            @endforelse
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <a href="#" class="text-white hover:text-[#B4771E] text-lg pb-1 transition-colors duration-300">
                            About Us
                        </a>

                        <a href="#" class="text-white hover:text-[#B4771E] text-lg pb-1 transition-colors duration-300">
                            Contact Us
                        </a>
                    </nav>

                    <!-- Right Side -->
                    <div class="hidden lg:flex items-center gap-5">
                        <div class="search-container flex items-center w-[200px] 2xl:w-[370px] rounded-sm h-[40px] border border-[#D5D5D533] pl-4 pr-3 bg-[#FFFFFF08] focus-within:border-[#B4771E] transition-all duration-300">
                            <input type="text" placeholder="Search" class="w-full bg-transparent text-white text-base placeholder:text-base outline-none">
                            <img src="{{ asset('website/assets/images/search.png') }}" alt="" class="text-white w-[16px] h-[16px] pointer-events-none ml-2 shrink-0">
                        </div>

                        <a href="#" class="relative hover-gold-filter">
                            <img src="{{ asset('website/assets/images/heart.png') }}" alt="heart">
                            <span class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px]">0</span>
                        </a>

                        <a href="#" class="relative hover-gold-filter">
                            <img src="{{ asset('website/assets/images/cart.png') }}" alt="cart">
                            <span class="absolute -top-2 -right-2 w-[18px] h-[18px] rounded-full bg-[#B78326] text-white text-[11px] font-medium flex items-center justify-center pt-[2px]">0</span>
                        </a>

                        <a href="#" class="text-white hover-gold-filter">
                            <img src="{{ asset('website/assets/images/user.png') }}" alt="">
                        </a>
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
        <div class="bg-[#050b0d]">
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
            <a href="{{ url('/') }}" class="block pb-5 border-b text-[#131615] hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">Home</a>

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
                                <li><a href="#" class="text-[#757575] hover:text-[#B4771E] transition-colors duration-300">{{ $sub->name }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <a href="#" class="block w-full py-5 text-[#131615] hover:text-[#B4771E] text-lg leading-[18px] pl-4 transition-colors duration-300">
                                {{ $cat->name }}
                            </a>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <a href="#" class="block py-4 border-b text-[#131615] hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">About Us</a>
            <a href="#" class="block py-4 text-[#131615] hover:text-[#B4771E] text-lg leading-[18px] transition-colors duration-300">Contact Us</a>
        </div>
    </div>

    <!-- Main Content -->
    @yield('content')

    <!-- Footer -->
    <footer class="bg-[#131615]">
        <div class="container-1440">
            <div class="grid lg:grid-cols-[1.5fr_0.9fr_0.6fr_1.1fr_1.8fr] md:grid-cols-2 gap-9 py-14">
                <!-- Logo -->
                <div>
                    <a href="{{ url('/') }}"><img src="{{ asset('website/assets/images/footer_logo.png') }}" alt="Logo" class="mb-[25px]"></a>
                    <p class="text-[#D5D5D5] text-base lg:text-lg leading-6 font-normal">
                        Premium imitation jewelry crafted for weddings, festivals, and everyday elegance. Discover timeless designs that blend tradition, beauty, and affordability.
                    </p>
                </div>

                <!-- Category -->
                <div>
                    <h3 class="text-[#B4771E] text-[18px] lg:text-lg font-semibold mb-5">Shop By Category</h3>
                    <ul class="space-y-3 md:space-y-5 text-[#D5D5D5] text-base lg:text-lg lg:leading-[20px]">
                        @foreach($sharedCategories->shuffle()->take(6) as $cat)
                        <li><a href="#" class="hover:text-[#B4771E] transition">{{ $cat->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="text-[#B4771E] text-[18px] lg:text-lg font-semibold mb-5">Company</h3>
                    <ul class="space-y-3 md:space-y-5 text-[#D5D5D5] text-base lg:text-lg lg:leading-[16px]">
                        <li><a href="#" class="hover:text-[#B4771E] transition">About Us</a></li>
                        <li><a href="#" class="hover:text-[#B4771E] transition">Contact</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h3 class="text-[#B4771E] text-[18px] lg:text-lg font-semibold mb-5">Customer Service</h3>
                    <ul class="space-y-3 md:space-y-5 text-[#D5D5D5] text-base lg:text-lg lg:leading-[28px]">
                        <li><a href="#" class="hover:text-[#B4771E] transition">Terms & Conditions</a></li>
                        <li><a href="#" class="hover:text-[#B4771E] transition">Deliveries & Returns</a></li>
                        <li><a href="#" class="hover:text-[#B4771E] transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-[#B4771E] transition">Refund & Cancellation Policy</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-[#B4771E] text-[18px] lg:text-lg font-semibold mb-5">Contact Us</h3>
                    <ul class="space-y-3 md:space-y-5 text-[#D5D5D5] text-base lg:text-lg lg:leading-[28px]">
                        <li class="flex gap-3">
                            <i class="fa-solid fa-phone text-[#B4771E] mt-1"></i>
                            <a href="tel:+919876543210" class="hover:text-[#B4771E] transition">+91 98765 43210</a>
                        </li>
                        <li class="flex gap-3">
                            <i class="fa-regular fa-envelope text-[#B4771E] mt-1"></i>
                            <a href="mailto:info@chetanimitation.com" class="hover:text-[#B4771E] transition">info@chetanimitation.com</a>
                        </li>
                        <li class="flex gap-3">
                            <i class="fa-solid fa-location-dot text-[#B4771E] mt-1"></i>
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
                <div class="flex flex-col md:flex-row items-center justify-between gap-5 py-[30px]">
                    <p class="text-[#D5D5D5] text-base lg:text-lg lg:leading-[18px] text-center md:text-left">
                        © 2026 Chetan Imitation. All Rights Reserved | Developed by <a href="https://www.risingstarinfotech.com/" target="_blank" class="text-[#B4771E] hover:text-[#B4771E]">Rising Star Infotech</a>
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
    @yield('page-js')
</body>
</html>
