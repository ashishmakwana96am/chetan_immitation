@extends('layouts.website')

@section('title', 'Order Details | Chetan Imitation')

@section('content')
<section class="section-space">
    <div class="max-w-[1440px] mx-auto px-4">

        <!-- Heading -->
        <div class="text-center mb-10">
            <h2 class="font-moglan text-[34px] md:text-[54px] text-[#131615]">
                Order Details
            </h2>
            <p class="text-[#131615] mt-2 text-sm md:text-lg">
                View complete information about your order, shipment,
                payment, and delivery status.
            </p>
        </div>



        <div class="grid lg:grid-cols-[1fr_400px] gap-6">

            <!-- LEFT -->
            <div class="space-y-5">

                <!-- Product Card(s) -->
                @foreach($order->items as $item)
                @php
                    $productImg = ($item->product && $item->product->primaryImage) 
                        ? $item->product->primaryImage->image_url 
                        : asset('website/assets/images/detailpage.png');
                        
                    $status = $order->status;
                    $step1_done = true;
                    $step2_done = in_array($status, [2, 3, 4, 5]);
                    $step3_done = in_array($status, [3, 4, 5]);
                    $step4_done = in_array($status, [4, 5]);
                    $step5_done = ($status == 5);
                    $is_cancelled = ($status == 6);
                @endphp
                <div class="border border-[#D5D5D5] p-4 md:p-6 bg-white">
                    <div class="">
                        <div class="flex flex-col md:flex-row gap-4">
                            <!-- Image -->
                            <div class="relative shrink-0">
                                <img src="{{ $productImg }}" alt="" class="w-full md:w-[230px] h-[230px] object-cover">
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <h3 class="max-w-[240px] md:max-w-[500px] truncate text-base md:text-[22px] lg:text-[26px] leading-[26px] lg:leading-[36px] font-semibold text-[#131615]">
                                    {{ $item->product->name ?? '' }}
                                </h3>

                                <div class="flex items-center gap-2 mt-5">
                                    <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">
                                        ₹{{ number_format($item->price, 0) }}
                                    </span>
                                    @if($item->product && $item->product->mrp > $item->price)
                                    <span class="text-[#999] line-through">
                                        ₹{{ number_format($item->product->mrp, 0) }}
                                    </span>
                                    @endif
                                </div>

                                <div class="sm:mt-[20px] text-[14px]">
                                    <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap gap-2">
                                        <span class="font-medium text-[#131615] w-[130px] mr-2">Order ID:</span>
                                        <span class="text-[#757575] font-semibold">#{{ $order->order_no }}</span>
                                    </p>

                                    <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap gap-2">
                                        <span class="font-medium text-[#131615] w-[130px] mr-2">Category:</span>
                                        <span class="text-[#757575]">{{ $item->product->category->name ?? 'Imitation Jewelry' }}</span>
                                    </p>

                                    <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap gap-2">
                                        <span class="font-medium text-[#131615] w-[130px] mr-2">Color:</span>
                                        <span class="text-[#777]">Gold Finish</span>
                                    </p>

                                    <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap gap-2">
                                        <span class="font-medium text-[#131615] w-[130px] mr-2">Order Date:</span>
                                        <span class="text-[#757575]">{{ $order->created_at->format('d M Y') }}</span>
                                    </p>

                                    <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap gap-2">
                                        <span class="font-medium text-[#131615] w-[130px] mr-2">Delivery Date:</span>
                                        <span class="text-[#757575]">{{ $order->status == 5 && $order->updated_at ? $order->updated_at->format('d M Y') : '-' }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tracking -->
                    <div class="border-t mt-6 pt-6">
                        <div class="grid grid-cols-5 relative">
                            <!-- Item 1 (Order Placed) -->
                            <div class="relative text-center">
                                <div class="absolute top-[10px] left-1/2 w-full h-[2px] {{ $step2_done ? 'bg-[#1FAF38]' : 'bg-[#D5D5D5]' }}"></div>
                                <div class="relative z-10 w-5 h-5 rounded-full bg-[#1FAF38] mx-auto flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                                <p class="text-[#131615] text-xs sm:text-sm md:text-base mt-3 font-semibold">
                                    Order Placed
                                    <span class="text-muted d-block small font-normal" style="margin-top: 4px;">{{ $order->created_at->format('M d, Y') }}</span>
                                </p>
                            </div>

                            <!-- Item 2 (Order Confirmed) -->
                            <div class="relative text-center">
                                <div class="absolute top-[10px] left-1/2 w-full h-[2px] {{ $step3_done ? 'bg-[#1FAF38]' : 'bg-[#D5D5D5]' }}"></div>
                                <div class="relative z-10 w-5 h-5 rounded-full {{ $step2_done ? 'bg-[#1FAF38]' : 'bg-[#D5D5D5]' }} mx-auto flex items-center justify-center">
                                    @if($step2_done)
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    @endif
                                </div>
                                <p class="{{ $step2_done ? 'text-[#131615] font-semibold' : 'text-[#757575]' }} text-xs sm:text-sm md:text-base mt-3">
                                    Order Confirmed
                                    @if($step2_done && $order->confirmed_at)
                                        <span class="text-muted d-block small font-normal" style="margin-top: 4px;">{{ $order->confirmed_at->format('M d, Y') }}</span>
                                    @endif
                                </p>
                            </div>

                            <!-- Item 3 (Shipped) -->
                            <div class="relative text-center">
                                <div class="absolute top-[10px] left-1/2 w-full h-[2px] {{ $step4_done ? 'bg-[#1FAF38]' : 'bg-[#D5D5D5]' }}"></div>
                                <div class="relative z-10 w-5 h-5 rounded-full {{ $step3_done ? 'bg-[#1FAF38]' : 'bg-[#D5D5D5]' }} mx-auto flex items-center justify-center">
                                    @if($step3_done)
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    @endif
                                </div>
                                <p class="{{ $step3_done ? 'text-[#131615] font-semibold' : 'text-[#757575]' }} text-xs sm:text-sm md:text-base mt-3">
                                    Shipped
                                    @if($step3_done && $order->shipped_at)
                                        <span class="text-muted d-block small font-normal" style="margin-top: 4px;">{{ $order->shipped_at->format('M d, Y') }}</span>
                                    @endif
                                </p>
                            </div>

                            <!-- Item 4 (Out for delivery) -->
                            <div class="relative text-center">
                                <div class="absolute top-[10px] left-1/2 w-full h-[2px] {{ $step5_done ? 'bg-[#1FAF38]' : 'bg-[#D5D5D5]' }}"></div>
                                <div class="relative z-10 w-5 h-5 rounded-full {{ $step4_done ? 'bg-[#1FAF38]' : 'bg-[#D5D5D5]' }} mx-auto flex items-center justify-center">
                                    @if($step4_done)
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    @endif
                                </div>
                                <p class="{{ $step4_done ? 'text-[#131615] font-semibold' : 'text-[#757575]' }} text-xs sm:text-sm md:text-base mt-3">
                                    Out for delivery
                                    @if($step4_done && $order->out_for_delivery_at)
                                        <span class="text-muted d-block small font-normal" style="margin-top: 4px;">{{ $order->out_for_delivery_at->format('M d, Y') }}</span>
                                    @endif
                                </p>
                            </div>

                            <!-- Item 5 (Delivered or Cancelled) -->
                            <div class="relative text-center">
                                <div class="relative z-10 w-5 h-5 rounded-full {{ $is_cancelled ? 'bg-red-500' : ($step5_done ? 'bg-[#1FAF38]' : 'bg-[#D5D5D5]') }} mx-auto flex items-center justify-center">
                                    @if($step5_done || $is_cancelled)
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    @endif
                                </div>
                                <p class="{{ $step5_done ? 'text-[#131615] font-semibold' : ($is_cancelled ? 'text-red-600 font-semibold' : 'text-[#757575]') }} text-xs sm:text-sm md:text-base mt-3">
                                    {{ $is_cancelled ? 'Cancelled' : 'Delivered' }}
                                    @if($step5_done && $order->delivered_at)
                                        <span class="text-muted d-block small font-normal" style="margin-top: 4px;">{{ $order->delivered_at->format('M d, Y') }}</span>
                                    @elseif($is_cancelled)
                                        <span class="text-muted d-block small font-normal" style="margin-top: 4px;">{{ $order->updated_at->format('M d, Y') }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- Review Section (Static/Non-dynamic as requested) -->
                <div class="border border-[#D5D5D5] p-5 bg-white">
                    <h3 class="text-xl text-[#131615] font-semibold">
                        Write A Review
                    </h3>
                    <div class="flex gap-1 text-[#B4771E] text-xl mt-3">
                        <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="">
                        <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="">
                        <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="">
                        <img src="{{ asset('website/assets/images/svg-yello.png') }}" alt="">
                        <img src="{{ asset('website/assets/images/SVG-gray.png') }}" alt="">
                    </div>
                    <textarea placeholder="Write Your Review" class="w-full h-[120px] border border-[#D5D5D5] px-5 py-4 outline-none resize-none text-[#131615] placeholder:text-[#757575] placeholder:text-sm leading-6 mt-5"></textarea>
                    <button class="common-btn ! lg:h-[62px] mt-5">
                        Submit
                    </button>
                </div>
            </div>

            <!-- RIGHT -->
            @php
                $subtotal = $order->items->sum(function($item) {
                    return (float)$item->total;
                });
                $finalAmount = (float)$order->final_amount;
                $hasFreeShipping = ($subtotal > 1999 || $subtotal == 0);
                $shippingCost = $hasFreeShipping ? 0 : 99;
                $discount = $subtotal - $finalAmount;
                if ($discount < 0) $discount = 0;
            @endphp
            <div class="space-y-5">
                <!-- Delivery Details -->
                <div class="border border-[#D5D5D5] p-5 bg-white">
                    <h3 class="text-[16px] md:text-[18px] font-semibold text-[#131615]">
                        Delivery details
                    </h3>
                    <div class="mt-5 flex items-start gap-3">
                        <img src="{{ asset('website/assets/images/home.png') }}" alt="" class="w-5 h-5 shrink-0 mt-1">
                        <p class="text-sm md:text-base leading-[24px] text-[#3D403F]">
                            <span class="font-semibold text-[#131615]">
                                {{ ucfirst($order->customerAddress->type ?? 'Home') }}:
                            </span>
                            {{ $order->customerAddress->address ?? '' }}, {{ $order->customerAddress->city ?? '' }}, {{ $order->customerAddress->state ?? '' }}
                        </p>
                    </div>
                    <div class="border-t border-[#D5D5D5] my-4"></div>
                    <div class="flex items-center gap-3">
                        <i class="fa-regular fa-user text-lg text-[#3D403F] w-5 h-5 flex items-center justify-center shrink-0"></i>
                        <p class="text-sm md:text-base text-[#3D403F]">
                            <span class="font-semibold text-[#131615]">
                                {{ $order->customerAddress->name ?? '' }}:
                            </span>
                            {{ $order->customerAddress->phone ?? '' }}
                        </p>
                    </div>
                </div>

                <!-- Price Details -->
                <div class="border border-[#D5D5D5] p-5 bg-white">
                    <h3 class="text-[16px] md:text-[18px] font-semibold text-[#131615]">
                        Price Details
                    </h3>
                    <div class="border-t border-[#D5D5D5] mt-4 pt-4 space-y-4">
                        <div class="flex justify-between text-base md:text-lg font-medium">
                            <span>Subtotal</span>
                            <span class="text-[#3D403F]">₹{{ number_format($subtotal, 0) }}</span>
                        </div>
                        @if($discount > 0)
                        <div class="flex justify-between text-base md:text-lg font-medium">
                            <span class="text-[#131615]">Discount</span>
                            <span class="text-[#3D403F]">-₹{{ number_format($discount, 0) }}</span>
                        </div>
                        @endif
                        {{--
                        <div class="flex justify-between text-base md:text-lg font-medium">
                            <span class="text-[#131615]">Shipping</span>
                            <span class="text-[#3D403F]">{{ $hasFreeShipping ? 'Free' : '₹99' }}</span>
                        </div>
                        <div class="flex justify-between text-base md:text-lg font-medium">
                            <span class="text-[#131615]">Estimated Tax</span>
                            <span class="text-[#3D403F]">₹0</span>
                        </div>
                        --}}
                    </div>
                    <div class="border-t mt-5 pt-5 flex justify-between">
                        <span class="font-semibold text-lg md:text-xl">
                            Total
                        </span>
                        <span class="font-bold text-[#B4771E] text-lg md:text-xl">
                            ₹{{ number_format($finalAmount, 0) }}
                        </span>
                    </div>
                    <div class="mt-5 flex justify-between flex-wrap">
                        <p class="mb-4 text-lg md:text-xl">
                            Paid By :
                        </p>
                        <p class="mb-4 text-lg md:text-lg">
                            @if(strtolower($order->payment_method) === 'cod')
                            <span class="text-[#131615] flex items-center flex-wrap gap-2">
                                <img src="{{ asset('website/assets/images/payment1.png') }}" alt="" class="w-8">  Cash on Delivery
                            </span>
                            @else
                            <span class="text-[#131615] flex items-center flex-wrap gap-2">
                                <i class="fa-solid fa-credit-card text-2xl text-amber-700 mr-1"></i>  Online Payment
                            </span>
                            @endif
                        </p>
                        <button onclick="window.print()" class="common-btn !w-full !lg:h-[62px]">
                            Download Invoice
                        </button>
                        <a href="{{ route('customer.profile') }}" class="common-btn flex items-center justify-center !w-full mt-4 border border-[#B4771E] !bg-transparent !text-[#B4771E] hover:!bg-[#B4771E] hover:!text-white transition text-print-hide">
                            Back To Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Follow Our Jewelry Journey Section -->
<section class="section-space-bottom">
    <div class="">
        <div class="text-center px-5">
            <h2 class="hero-title">
                Follow Our Jewelry Journey
            </h2>
        </div>
        <div class="mt-10 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
            <a href="#" class="group overflow-hidden">
                <img src="{{ asset('website/assets/images/Rectangle1.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
            </a>
            <a href="#" class="group overflow-hidden">
                <img src="{{ asset('website/assets/images/Rectangle2.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
            </a>
            <a href="#" class="group overflow-hidden">
                <img src="{{ asset('website/assets/images/Rectangle3.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
            </a>
            <a href="#" class="group overflow-hidden">
                <img src="{{ asset('website/assets/images/Rectangle4.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
            </a>
            <a href="#" class="group overflow-hidden">
                <img src="{{ asset('website/assets/images/Rectangle5.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
            </a>
            <a href="#" class="group overflow-hidden">
                <img src="{{ asset('website/assets/images/Rectangle6.png') }}" alt="" class="w-full h-[180px] md:h-[220px] lg:h-[320px] object-cover transition duration-500 group-hover:scale-105">
            </a>
        </div>
        <div class="text-center mt-8 lg:mt-10">
            <a href="#" class="common-btn">
                Follow Us on Instagram
            </a>
        </div>
    </div>
</section>
@endsection
