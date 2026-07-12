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

                @php
                    $status = $order->status;
                    $step1_done = true;
                    $step2_done = in_array($status, [2, 3, 4, 5]);
                    $step3_done = in_array($status, [3, 4, 5]);
                    $step4_done = in_array($status, [4, 5]);
                    $step5_done = ($status == 5);
                    $is_cancelled = ($status == 6);
                @endphp

                <!-- Order Status / Tracking (Shown Once) -->
                <div class="border border-[#D5D5D5] p-5 md:p-6 bg-white">
                    <h3 class="text-[#131615] text-lg md:text-[22px] font-semibold mb-6">
                        Order Status
                    </h3>

                    @if($is_cancelled)
                    <div class="grid grid-cols-2 relative max-w-[400px]">
                        <!-- Item 1 (Order Placed) -->
                        <div class="relative text-center">
                            <div class="absolute top-[10px] left-1/2 w-full h-[2px] bg-red-500"></div>
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

                        <!-- Item 2 (Cancelled) -->
                        <div class="relative text-center">
                            <div class="relative z-10 w-5 h-5 rounded-full bg-red-500 mx-auto flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="white" class="w-3 h-3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                            <p class="text-red-600 font-semibold text-xs sm:text-sm md:text-base mt-3">
                                Cancelled
                                @if($order->updated_at)
                                    <span class="text-muted d-block small font-normal" style="margin-top: 4px;">{{ $order->updated_at->format('M d, Y') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>



                    @else
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
                    @if($order->shipped_client_url || $order->tracking_id)
                    <div class="mt-6 border-t border-[#EAEAEA] pt-5">
                        <h4 class="text-[#131615] text-sm font-semibold mb-3">Shipping & Tracking Information</h4>
                        <div class="grid sm:grid-cols-2 gap-4 bg-gray-50 border border-gray-100 rounded p-4">
                            @if($order->shipped_client_url)
                            <div>
                                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Shipping URL / Courier Partner</p>
                                <p class="text-sm mt-1">
                                    <a href="{{ $order->shipped_client_url }}" target="_blank" class="text-green-600 hover:text-green-700 font-medium underline break-all inline-flex items-center gap-1">
                                        Track Order
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                </p>
                            </div>
                            @endif
                            @if($order->tracking_id)
                            <div>
                                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Tracking ID</p>
                                <p class="text-[#131615] font-semibold text-sm mt-1 select-all">{{ $order->tracking_id }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    @endif
                </div>

                <!-- Product Card(s) -->
                @foreach($order->items as $item)
                @php
                    $productImg = ($item->product && $item->product->primaryImage)
                        ? $item->product->primaryImage->image_url
                        : asset('website/assets/images/detailpage.png');
                @endphp
                <div class="border border-[#D5D5D5] p-3 md:p-4 bg-white group">
                    <div class="">
                        <div class="flex flex-col md:flex-row gap-4">
                            <!-- Image -->
                            <div class="relative shrink-0 sm:w-[190px] sm:h-[190px] overflow-hidden cursor-pointer">
                                <img src="{{ $productImg }}" alt="" class="sm:w-[190px] sm:h-[190px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0 flex justify-between flex-col">
                                <div class="flex-1 min-w-0 overflow-hidden">
                                <h3 class="product-title text-base md:text-[22px] font-semibold text-[#131615] hover:text-[#B4771E] transition break-words whitespace-normal">
                                    {{ $item->product->name ?? '' }}
                                </h3>
                            </div>

                                <div class="flex items-center gap-2 mt-3">
                                    <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">
                                        {{ website_price($item->price) }}
                                    </span>
                                    @php
                                        $pairType = $item->pair_type ?? 'single';
                                        $product = $item->product;
                                        if ($product) {
                                            if ($pairType === 'pair' && $product->pair_product && $product->pair_mrp) {
                                                $mrp = (float) $product->pair_mrp;
                                            } else {
                                                $mrp = (float) $product->mrp;
                                            }
                                        } else {
                                            $mrp = $item->price; // Fallback if no product
                                        }
                                    @endphp
                                    @if($mrp > $item->price)
                                    <span class="text-[#757575] line-through text-base md:text-lg">
                                        {{ website_price($mrp) }}
                                    </span>
                                    @endif
                                </div>

                                <div class="mt-2 sm:mt-4 space-y-2">
                                    <p class="text-base flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Order ID:</span>
                                        <span class="text-[#757575] ml-2">#{{ $order->order_no }}</span>
                                    </p>

                                    <p class="text-base flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Category:</span>
                                        <span class="text-[#757575] ml-2">{{ $item->product->category->name ?? 'Imitation Jewellery' }}</span>
                                    </p>

                                    <p class="text-base flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Quantity:</span>
                                        <span class="text-[#757575] ml-2">
                                            {{ $item->quantity }}
                                            <span class="font-medium ml-1 {{ ($item->pair_type ?? 'single') === 'pair' ? 'text-[#B4771E]' : '' }}">{{ ($item->pair_type ?? 'single') === 'pair' ? 'Pairs' : 'Pcs' }}</span>
                                        </span>
                                    </p>

                                    <p class="text-base flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Order Date:</span>
                                        <span class="text-[#757575] ml-2">{{ $order->created_at->format('d M Y') }}</span>
                                    </p>

                                    @php
                                        $deliveryDateVal = $order->status == 5 && $order->updated_at ? $order->updated_at->format('d M Y') : '-';
                                    @endphp
                                    @if(trim($deliveryDateVal) !== '-')
                                    <p class="text-base flex flex-wrap">
                                        <span class="font-medium text-[#131615] w-[120px]">Delivery Date:</span>
                                        <span class="text-[#757575] ml-2">{{ $deliveryDateVal }}</span>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>



                    @if($order->status == \App\Models\Order::STATUS_DELIVERED && $item->product)
                    @php
                        $existingReview = $reviewsByProduct->get($item->product_id);
                        $reviewAuthorName = auth('customer')->user()->name;
                        $reviewAuthorAvatar = auth('customer')->user()->avatar
                            ? asset(auth('customer')->user()->avatar)
                            : 'https://ui-avatars.com/api/?name=' . urlencode($reviewAuthorName) . '&background=B4771E&color=fff&size=120&bold=true';
                    @endphp
                    <div class="border-t mt-6 pt-6" id="review-block-{{ $item->product_id }}">
                        @if($existingReview)
                        <div class="review-submitted">
                            <h3 class="text-xl text-[#131615] font-semibold">Your Review</h3>
                            <p class="text-sm text-[#757575] mt-1">Review submitted for {{ $item->product->name }}</p>
                            <div class="mt-4 border border-[#D5D5D5] p-4">
                                <h4 class="text-[#131615] text-lg font-medium">{{ $existingReview->created_at->format('l, F j, Y') }}</h4>
                                @if($existingReview->comment)
                                <p class="mt-3 text-[#3D403F] text-base">{{ $existingReview->comment }}</p>
                                @endif
                                @if($existingReview->images->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($existingReview->images as $reviewImage)
                                        <img src="{{ $reviewImage->image_url }}" alt="Review photo" class="w-[90px] h-[90px] object-cover rounded-sm border border-[#D5D5D5]">
                                    @endforeach
                                </div>
                                @endif
                                <div class="border-t border-[#e3e3e3] mt-4 pt-4 flex items-center gap-4">
                                    <img src="{{ $reviewAuthorAvatar }}" alt="{{ $reviewAuthorName }}" class="w-[50px] h-[50px] rounded-full object-cover">
                                    <div>
                                        <h5 class="text-[#131615] text-lg font-medium">{{ $reviewAuthorName }}</h5>
                                        @include('website.partials.star-rating', ['rating' => $existingReview->rating, 'size' => 'md', 'class' => 'mt-1'])
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="review-form-wrap" data-product-id="{{ $item->product_id }}" data-order-id="{{ $order->id }}">
                            <h3 class="text-xl text-[#131615] font-semibold">Write A Review</h3>
                            <p class="text-sm text-[#757575] mt-1">Share your experience with {{ $item->product->name }}</p>
                            <div class="flex gap-0.5 mt-3 review-stars" role="radiogroup" aria-label="Rating">
                                @for($star = 1; $star <= 5; $star++)
                                <div class="review-star-item relative w-5 h-5 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute inset-0 w-5 h-5" viewBox="0 0 512 512" fill="#D2D2D2" aria-hidden="true">
                                        <path d="m512 197.816-186.039-12.231L255.898 9.569l-70.063 176.016L0 197.816l142.534 121.026-46.772 183.589L255.898 401.21l160.137 101.221-46.772-183.589z"/>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="review-star-fill absolute inset-0 w-5 h-5 pointer-events-none" viewBox="0 0 512 512" fill="#B4771E" style="clip-path: inset(0 100% 0 0);" aria-hidden="true">
                                        <path d="m512 197.816-186.039-12.231L255.898 9.569l-70.063 176.016L0 197.816l142.534 121.026-46.772 183.589L255.898 401.21l160.137 101.221-46.772-183.589z"/>
                                    </svg>
                                    <button type="button" class="review-half-btn absolute left-0 top-0 w-1/2 h-full z-10 cursor-pointer" data-rating="{{ $star - 0.5 }}" aria-label="{{ $star - 0.5 }} stars"></button>
                                    <button type="button" class="review-half-btn absolute right-0 top-0 w-1/2 h-full z-10 cursor-pointer" data-rating="{{ $star }}" aria-label="{{ $star }} stars"></button>
                                </div>
                                @endfor
                            </div>
                            <input type="hidden" class="review-rating-input" value="0">
                            <textarea placeholder="Write Your Review" class="review-comment-input w-full h-[120px] border border-[#D5D5D5] px-5 py-4 outline-none resize-none text-[#131615] placeholder:text-[#757575] placeholder:text-sm leading-6 mt-5"></textarea>

                            <div class="mt-4">
                                <label class="review-image-picker-btn inline-flex items-center gap-2 border border-[#D5D5D5] px-4 py-2 text-sm text-[#3D403F] cursor-pointer hover:border-[#B4771E] transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 4.5h18v15H3v-15z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 6.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                    </svg>
                                    <span class="review-image-picker-label">Choose Pictures</span>
                                    <input type="file" accept="image/png,image/jpeg,image/webp" class="review-image-input hidden" multiple>
                                </label>
                                <div class="review-image-preview-grid mt-3 flex flex-wrap gap-2"></div>
                            </div>

                            <p class="review-error text-sm text-red-600 mt-2 hidden"></p>
                            <button type="button" class="review-submit-btn common-btn lg:h-[50px] mt-5" onclick="submitProductReview(this)">
                                Submit
                            </button>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach

                @if($order->cancellationRequest)
                <div class="border border-[#D5D5D5] p-5 md:p-6 bg-white">
                    <h3 class="text-[#131615] text-lg md:text-[22px] font-semibold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-[#B4771E]"></i>
                        Cancellation Request Details
                    </h3>
                    <div class="space-y-3 text-sm md:text-base text-[#131615]">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-[#131615]">Request Status:</span>
                            <span class="font-bold px-2 py-0.5 rounded-[3px] text-white text-xs uppercase tracking-wider
                                @if($order->cancellationRequest->status === 'pending') bg-[#B4771E]
                                @elseif($order->cancellationRequest->status === 'approved') bg-green-600
                                @elseif($order->cancellationRequest->status === 'rejected') bg-red-600
                                @endif">
                                {{ $order->cancellationRequest->status }}
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold text-[#131615]">Cancellation Reason:</span>
                            <p class="mt-1 text-[#757575] bg-gray-50 p-3 border border-[#EAEAEA] rounded-[4px] italic">
                                "{{ $order->cancellation_reason ?? $order->cancellationRequest->cancellation_reason }}"
                            </p>
                        </div>
                        <div class="text-xs text-[#757575] pt-2 border-t border-[#EAEAEA]">
                            @if($order->cancellationRequest->status === 'pending')
                                Your request was submitted on {{ $order->cancellationRequest->created_at->format('d M Y, h:i A') }} and is currently under review by our team.
                            @elseif($order->cancellationRequest->status === 'approved')
                                This request was approved on {{ $order->cancellationRequest->updated_at->format('d M Y, h:i A') }}. Refund of {{ website_price($order->cancellationRequest->refund_amount) }} has been processed. Note: The refunded amount will be credited to your account within 5-7 business days.
                            @elseif($order->cancellationRequest->status === 'rejected')
                                This request was rejected on {{ $order->cancellationRequest->updated_at->format('d M Y, h:i A') }}.
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- RIGHT -->
            @php
                $subtotal = $order->items->sum(function($item) {
                    return (float)$item->total;
                });
                $finalAmount = (float)$order->final_amount;
                $shippingCost = (float)$order->shipping_charge;
                $discount = $subtotal - ($finalAmount - $shippingCost);
                if ($discount < 0) $discount = 0;
                $hasCancellationRequest = $order->cancellationRequest !== null;
                $isDeliveredWithin24Hours = (int) $order->status === \App\Models\Order::STATUS_DELIVERED 
                    && $order->delivered_at 
                    && now()->diffInHours($order->delivered_at) <= 24;

                $canCancelOrder = !$hasCancellationRequest && (
                    in_array((int) $order->status, [
                        \App\Models\Order::STATUS_PENDING,
                        \App\Models\Order::STATUS_APPROVE,
                        \App\Models\Order::STATUS_SHIPPED,
                        \App\Models\Order::STATUS_OUT_FOR_DELIVERY,
                    ], true) || $isDeliveredWithin24Hours
                );
            @endphp
            <div class="space-y-5">
                <!-- Delivery Details -->
                <div class="border border-[#D5D5D5] p-4 bg-white">
                    <h3 class="text-[16px] md:text-[18px] font-semibold text-[#131615]">
                        Delivery details
                    </h3>
                    <div class="mt-5 flex items-start gap-3">
                        <img src="{{ asset('website/assets/images/home.png') }}" alt="" class="w-5 h-5 shrink-0 mt-1">
                        <p class="text-sm md:text-base leading-[24px] text-[#3D403F]">
                            <span class="font-semibold text-[#131615]">
                                {{ ucfirst($order->customerAddress->type ?? 'Home') }}:
                            </span>
                            {{ $order->customerAddress->address ?? '' }}, {{ $order->customerAddress->city ?? '' }}, {{ $order->customerAddress->state ?? '' }}{{ !empty($order->customerAddress->pincode) ? ' - ' . $order->customerAddress->pincode : '' }}
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
                <div class="border border-[#D5D5D5] p-4 bg-white">
                    <h3 class="text-[16px] md:text-[18px] font-semibold text-[#131615]">
                        Price Details
                    </h3>
                    <div class="border-t border-[#D5D5D5] mt-4 pt-4 space-y-4">
                        <div class="flex justify-between text-base md:text-lg font-medium">
                            <span>Subtotal</span>
                            <span class="text-[#3D403F]">{{ website_price($subtotal) }}</span>
                        </div>
                        @if($discount > 0)
                        <div class="flex justify-between text-base md:text-lg font-medium">
                            <span class="text-[#131615]">
                                Discount
                            </span>
                            <span class="text-[#3D403F]">-{{ website_price($discount) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-base md:text-lg font-medium">
                            <span class="text-[#131615]">Shipping</span>
                            <span class="text-[#3D403F]">{{ $shippingCost > 0 ? website_price($shippingCost) : 'Free' }}</span>
                        </div>
                    </div>
                    <div class="border-t mt-4 pt-4 flex justify-between">
                        <span class="font-semibold text-lg md:text-xl">
                            Total
                        </span>
                        <span class="font-bold text-[#B4771E] text-lg md:text-xl">
                            {{ website_price($finalAmount) }}
                        </span>
                    </div>
                    <div class="mt-4 flex justify-between flex-wrap">
                        <p class="mb-4 text-base md:text-lg">
                            Paid By :
                        </p>
                        <p class="mb-4 text-sm md:text-base">
                            @if(strtolower($order->payment_method) === 'cod')
                            <span class="text-[#131615] flex items-center flex-wrap gap-2">
                            <svg width="28" height="21" viewBox="0 0 32 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 8.76953C12.4159 8.76953 9.5 11.6854 9.5 15.2695C9.5 18.8537 12.4159 21.7695 16 21.7695C19.5841 21.7695 22.5 18.8537 22.5 15.2695C22.5 11.6854 19.5841 8.76953 16 8.76953ZM16 20.7695C12.9673 20.7695 10.5 18.3022 10.5 15.2695C10.5 12.2368 12.9673 9.76953 16 9.76953C19.0327 9.76953 21.5 12.2368 21.5 15.2695C21.5 18.3022 19.0327 20.7695 16 20.7695Z" fill="#131615"/>
                                <path d="M30.4414 6.76988L30.1218 4.21281C30.0183 3.38469 29.2599 2.79838 28.4259 2.91331L27.7021 3.01681L27.232 1.13638C27.1837 0.942589 27.0971 0.760412 26.9774 0.600545C26.8577 0.440678 26.7072 0.306344 26.5348 0.205432C26.3625 0.104521 26.1717 0.039065 25.9737 0.0129103C25.7757 -0.0132445 25.5745 0.000428904 25.3818 0.0531267L1.42431 6.77181C0.63225 6.81138 0 7.46819 0 8.26988V22.2699C0 23.0969 0.672938 23.7699 1.5 23.7699H30.5C31.3271 23.7699 32 23.0969 32 22.2699V8.26988C32 7.49525 31.4002 6.76988 30.4414 6.76988ZM28.565 3.90356C28.6306 3.8945 28.6973 3.89855 28.7613 3.91548C28.8253 3.93241 28.8853 3.96189 28.9378 4.00221C28.9903 4.04252 29.0343 4.09286 29.0672 4.15032C29.1001 4.20777 29.1213 4.27118 29.1294 4.33688L29.4336 6.76994H8.52637L28.565 3.90356ZM25.6469 1.01738C25.711 1.00007 25.778 0.995732 25.8438 1.0046C25.9096 1.01348 25.973 1.03539 26.0303 1.06905C26.0876 1.10272 26.1376 1.14746 26.1773 1.20067C26.2171 1.25388 26.2459 1.31448 26.2619 1.37894L26.707 3.15919L8.95531 5.69838L25.6469 1.01738ZM31 22.2699C31 22.5456 30.7757 22.7699 30.5 22.7699H1.5C1.22431 22.7699 1 22.5456 1 22.2699V8.26988C1 7.99456 1.22375 7.7705 1.49894 7.76994C1.65731 7.76963 -0.994499 7.76988 30.5 7.76988C30.7757 7.76988 31 7.99419 31 8.26988V22.2699Z" fill="#131615"/>
                                <path d="M29.5 10.2695C28.9486 10.2695 28.5 9.82091 28.5 9.26953C28.5 9.13692 28.4473 9.00975 28.3536 8.91598C28.2598 8.82221 28.1326 8.76953 28 8.76953H21.5C21.3674 8.76953 21.2402 8.82221 21.1464 8.91598C21.0527 9.00975 21 9.13692 21 9.26953C21 9.40214 21.0527 9.52932 21.1464 9.62308C21.2402 9.71685 21.3674 9.76953 21.5 9.76953H27.5633C27.7446 10.4713 28.2983 11.025 29 11.2062V19.3328C28.2982 19.514 27.7446 20.0677 27.5633 20.7695H21.5C21.3674 20.7695 21.2402 20.8221 21.1464 20.9159C21.0527 21.0097 21 21.1369 21 21.2695C21 21.4021 21.0527 21.5293 21.1464 21.623C21.2402 21.7168 21.3674 21.7695 21.5 21.7695H28C28.1326 21.7695 28.2598 21.7168 28.3536 21.623C28.4473 21.5293 28.5 21.4021 28.5 21.2695C28.5 20.7181 28.9486 20.2695 29.5 20.2695C29.6326 20.2695 29.7598 20.2168 29.8536 20.123C29.9473 20.0293 30 19.9021 30 19.7695V10.7695C30 10.6369 29.9473 10.5097 29.8535 10.416C29.7598 10.3222 29.6326 10.2695 29.5 10.2695ZM10.5 20.7695H4.43669C4.25544 20.0677 3.70175 19.5141 3 19.3328V11.2062C3.70181 11.025 4.25544 10.4713 4.43669 9.76953H10.5C10.6326 9.76953 10.7598 9.71685 10.8536 9.62308C10.9473 9.52932 11 9.40214 11 9.26953C11 9.13692 10.9473 9.00975 10.8536 8.91598C10.7598 8.82221 10.6326 8.76953 10.5 8.76953H4C3.86739 8.76953 3.74021 8.82221 3.64645 8.91598C3.55268 9.00975 3.5 9.13692 3.5 9.26953C3.5 9.82091 3.05137 10.2695 2.5 10.2695C2.36739 10.2695 2.24021 10.3222 2.14645 10.416C2.05268 10.5097 2 10.6369 2 10.7695V19.7695C2 19.9021 2.05268 20.0293 2.14645 20.1231C2.24021 20.2169 2.36739 20.2695 2.5 20.2695C3.05137 20.2695 3.5 20.7182 3.5 21.2695C3.5 21.4021 3.55268 21.5293 3.64645 21.6231C3.74021 21.7169 3.86739 21.7695 4 21.7695H10.5C10.6326 21.7695 10.7598 21.7169 10.8536 21.6231C10.9473 21.5293 11 21.4021 11 21.2695C11 21.1369 10.9473 21.0097 10.8536 20.916C10.7598 20.8222 10.6326 20.7695 10.5 20.7695Z" fill="#131615"/>
                                <path d="M4.5 15.2695C4.5 16.0966 5.17294 16.7695 6 16.7695C6.82706 16.7695 7.5 16.0966 7.5 15.2695C7.5 14.4425 6.82706 13.7695 6 13.7695C5.17294 13.7695 4.5 14.4425 4.5 15.2695ZM6.5 15.2695C6.5 15.5452 6.27569 15.7695 6 15.7695C5.72431 15.7695 5.5 15.5452 5.5 15.2695C5.5 14.9938 5.72431 14.7695 6 14.7695C6.27569 14.7695 6.5 14.9938 6.5 15.2695ZM27.5 15.2695C27.5 14.4425 26.8271 13.7695 26 13.7695C25.1729 13.7695 24.5 14.4425 24.5 15.2695C24.5 16.0966 25.1729 16.7695 26 16.7695C26.8271 16.7695 27.5 16.0967 27.5 15.2695ZM25.5 15.2695C25.5 14.9938 25.7243 14.7695 26 14.7695C26.2757 14.7695 26.5 14.9938 26.5 15.2695C26.5 15.5452 26.2757 15.7695 26 15.7695C25.7243 15.7695 25.5 15.5452 25.5 15.2695Z" fill="#131615"/>
                                <circle cx="16" cy="15.3848" r="3" fill="#131615"/>
                            </svg>
                            Cash on Delivery
                            </span>
                            @else
                            <span class="text-[#131615] flex items-center flex-wrap gap-2">
                                <i class="fa-solid fa-credit-card text-xl text-amber-700 mr-1"></i>  Online Payment
                            </span>
                            @endif
                        </p>
                        <a href="{{ route('customer.profile.order-invoice', $order->id) }}" class="w-full bg-[#B4771E] text-white text-lg font-medium h-[52px] transition common-btn mt-3 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Download Invoice
                        </a>
                        @if($canCancelOrder)
                        <button type="button" id="openCancelOrderModal" class="flex items-center justify-center w-full h-[52px] border mt-4 border-red-500 text-red-600 text-lg font-medium transition bg-transparent hover:text-white hover:bg-red-600 hover:border-red-600 rounded-md">
                            Cancel Order
                        </button>
                        @endif
                        <a href="{{ route('customer.profile') }}" class="flex items-center justify-center w-full h-[52px] border mt-4 border-[#131615] text-[#131615] text-lg font-medium transition common-btn bg-transparent hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E] text-print-hide">
                            Back To Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@if($canCancelOrder)
<div id="cancelOrderModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-[520px] bg-white shadow-2xl rounded-md">
        <div class="flex items-start justify-between gap-4 border-b border-[#E5E5E5] p-5">
            <div>
                <h3 class="text-[#131615] text-xl font-semibold">Cancel Order</h3>
                <p class="text-[#757575] text-sm mt-1">Please share a short remark so our team can process the cancellation.</p>
            </div>
            <button type="button" class="cancel-order-close text-[#757575] hover:text-[#131615] text-2xl leading-none" aria-label="Close cancel order dialog">&times;</button>
        </div>
        <form id="cancelOrderForm" action="{{ route('customer.profile.cancel-order', $order->id) }}" method="POST" class="p-5">
            @csrf
            <label for="cancelOrderRemark" class="block text-sm font-semibold text-[#131615] mb-2">
                Cancellation Remark <span class="text-red-600">*</span>
            </label>
            <textarea id="cancelOrderRemark" name="cancellation_reason" rows="4" maxlength="500" class="w-full border border-[#D5D5D5] px-4 py-3 outline-none text-[#131615] placeholder:text-[#757575] resize-none focus:border-[#B4771E] rounded-md" placeholder="Tell us why you want to cancel this order"></textarea>
            <div class="mt-2 flex items-center justify-between gap-3">
                <p id="cancelOrderError" class="hidden text-sm text-red-600"></p>
                <p class="ml-auto text-xs text-[#757575]"><span id="cancelRemarkCount">0</span>/500</p>
            </div>
            <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="button" class="cancel-order-close h-[46px] px-6 border border-[#D5D5D5] text-[#131615] font-medium rounded-md transition-all duration-300 ease-in-out hover:border-[#131615] hover:bg-[#131615] hover:text-white hover:shadow-md active:scale-[0.98]">
                    Keep Order
                </button>
                <button type="submit" id="confirmCancelOrderBtn" class="h-[46px] px-6 bg-red-600 text-white font-medium  rounded-md  transition-all duration-300 ease-in-out hover:bg-white hover:border-[#C0392B] hover:text-[#C0392B] hover:shadow-md active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60">
                    Confirm Cancellation
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Follow Our Jewellery Journey Section -->
<section class="section-space-bottom">
    <div class="">
        <div class="text-center px-5">
            <h2 class="hero-title">
                Follow Our Jewellery Journey
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

@section('page-js')
<script>
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('cancelOrderModal');
    const openBtn = document.getElementById('openCancelOrderModal');
    const form = document.getElementById('cancelOrderForm');
    const remark = document.getElementById('cancelOrderRemark');
    const errorEl = document.getElementById('cancelOrderError');
    const countEl = document.getElementById('cancelRemarkCount');
    const submitBtn = document.getElementById('confirmCancelOrderBtn');

    if (!modal || !openBtn || !form || !remark || !errorEl || !countEl || !submitBtn) return;

    function showCancelError(message) {
        errorEl.textContent = message || '';
        errorEl.classList.toggle('hidden', !message);
    }

    function openCancelModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        remark.focus();
    }

    function closeCancelModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        form.reset();
        countEl.textContent = '0';
        showCancelError('');
    }

    openBtn.addEventListener('click', openCancelModal);

    modal.querySelectorAll('.cancel-order-close').forEach(btn => {
        btn.addEventListener('click', closeCancelModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeCancelModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeCancelModal();
    });

    remark.addEventListener('input', function () {
        countEl.textContent = remark.value.length;
        if (remark.value.trim().length >= 5) showCancelError('');
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const reason = remark.value.trim();
        if (reason.length < 5) {
            showCancelError('Please enter a cancellation remark of at least 5 characters.');
            remark.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Cancelling...';
        showCancelError('');

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ cancellation_reason: reason })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'Unable to cancel this order.');
            }
            return data;
        })
        .then(data => {
            if (window.showWishlistToast) {
                window.showWishlistToast(data.message || 'Order cancelled successfully.', true);
            }
            setTimeout(() => window.location.reload(), 900);
        })
        .catch(error => {
            showCancelError(error.message || 'Something went wrong. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Cancellation';
        });
    });
});

function paintReviewStars(container, rating) {
    const value = parseFloat(rating) || 0;
    container.querySelectorAll('.review-star-item').forEach((item, index) => {
        const fill = Math.min(1, Math.max(0, value - index));
        const fillEl = item.querySelector('.review-star-fill');
        if (!fillEl) return;
        fillEl.style.clipPath = fill > 0
            ? `inset(0 ${(1 - fill) * 100}% 0 0)`
            : 'inset(0 100% 0 0)';
    });
}

document.querySelectorAll('.review-form-wrap').forEach(wrap => {
    const starsWrap = wrap.querySelector('.review-stars');
    const ratingInput = wrap.querySelector('.review-rating-input');

    starsWrap.querySelectorAll('.review-half-btn').forEach(btn => {
        btn.addEventListener('mouseenter', () => paintReviewStars(wrap, parseFloat(btn.dataset.rating)));
        btn.addEventListener('click', () => {
            const rating = parseFloat(btn.dataset.rating);
            ratingInput.value = rating;
            paintReviewStars(wrap, rating);
        });
    });

    starsWrap.addEventListener('mouseleave', () => {
        paintReviewStars(wrap, parseFloat(ratingInput.value) || 0);
    });
});

function buildStarRatingHtml(rating, size) {
    const value = parseFloat(rating) || 0;
    const starSize = size || 20;
    const path = 'm512 197.816-186.039-12.231L255.898 9.569l-70.063 176.016L0 197.816l142.534 121.026-46.772 183.589L255.898 401.21l160.137 101.221-46.772-183.589z';

    return Array.from({ length: 5 }, (_, index) => {
        const fill = Math.min(1, Math.max(0, value - index));
        const clipRight = fill > 0 ? (1 - fill) * 100 : 100;
        const fillSvg = fill > 0
            ? `<svg xmlns="http://www.w3.org/2000/svg" class="absolute top-0 left-0" width="${starSize}" height="${starSize}" viewBox="0 0 512 512" fill="#B4771E" style="clip-path: inset(0 ${clipRight}% 0 0);"><path d="${path}"/></svg>`
            : '';

        return `<span class="relative inline-block shrink-0" style="width:${starSize}px;height:${starSize}px;"><svg xmlns="http://www.w3.org/2000/svg" width="${starSize}" height="${starSize}" viewBox="0 0 512 512" fill="#D2D2D2"><path d="${path}"/></svg>${fillSvg}</span>`;
    }).join('');
}

function renderSubmittedReviewHtml(review) {
    const stars = buildStarRatingHtml(review.rating, 20);

    const commentHtml = review.comment
        ? `<p class="mt-3 text-[#3D403F] text-base">${review.comment.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>`
        : '';

    const imageHtml = (review.images && review.images.length)
        ? `<div class="mt-3 flex flex-wrap gap-2">${review.images.map(url => `<img src="${url}" alt="Review photo" class="w-[90px] h-[90px] object-cover rounded-sm border border-[#D5D5D5]">`).join('')}</div>`
        : '';

    return `
        <div class="review-submitted">
            <h3 class="text-xl text-[#131615] font-semibold">Your Review</h3>
            <div class="mt-4 border border-[#D5D5D5] p-4">
                <h4 class="text-[#131615] text-lg font-medium">${review.created_at}</h4>
                ${commentHtml}
                ${imageHtml}
                <div class="border-t border-[#e3e3e3] mt-4 pt-4 flex items-center gap-4">
                    <img src="${review.author_avatar}" alt="${review.author_name}" class="w-[50px] h-[50px] rounded-full object-cover">
                    <div>
                        <h5 class="text-[#131615] text-lg font-medium">${review.author_name}</h5>
                        <div class="flex items-center gap-0.5 mt-1">${stars}</div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

const REVIEW_MAX_IMAGES = 5;

function setReviewImageFiles(wrap, files) {
    wrap.reviewFiles = files;
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    wrap.querySelector('.review-image-input').files = dt.files;
    renderReviewImagePreviews(wrap, files);
}

function renderReviewImagePreviews(wrap, files) {
    const grid = wrap.querySelector('.review-image-preview-grid');
    grid.innerHTML = '';
    files.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = function (ev) {
            const item = document.createElement('div');
            item.className = 'relative inline-block';
            item.innerHTML = `
                <img src="${ev.target.result}" class="w-[90px] h-[90px] object-cover rounded-sm border border-[#D5D5D5]" alt="Preview">
                <button type="button" class="review-image-remove-btn absolute -top-2 -right-2 w-6 h-6 rounded-full bg-white border border-[#D5D5D5] text-[#3D403F] flex items-center justify-center text-sm leading-none" data-index="${idx}">&times;</button>
            `;
            grid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
}

document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('review-image-input')) return;

    const wrap = e.target.closest('.review-form-wrap');
    const errorEl = wrap.querySelector('.review-error');
    errorEl.classList.add('hidden');
    errorEl.textContent = '';

    let files = (wrap.reviewFiles || []).concat(Array.from(e.target.files));

    if (files.length > REVIEW_MAX_IMAGES) {
        errorEl.textContent = 'You can upload a maximum of ' + REVIEW_MAX_IMAGES + ' pictures.';
        errorEl.classList.remove('hidden');
        files = files.slice(0, REVIEW_MAX_IMAGES);
    }

    const oversized = files.some(f => f.size > 5 * 1024 * 1024);
    if (oversized) {
        errorEl.textContent = 'Each image must be less than 5 MB.';
        errorEl.classList.remove('hidden');
        files = files.filter(f => f.size <= 5 * 1024 * 1024);
    }

    setReviewImageFiles(wrap, files);
});

document.addEventListener('click', function (e) {
    if (!e.target.classList.contains('review-image-remove-btn')) return;

    const wrap = e.target.closest('.review-form-wrap');
    const idx = parseInt(e.target.dataset.index, 10);
    const files = (wrap.reviewFiles || []).filter((_, i) => i !== idx);
    setReviewImageFiles(wrap, files);
});

function submitProductReview(btn) {
    const wrap = btn.closest('.review-form-wrap');
    const productId = wrap.dataset.productId;
    const orderId = wrap.dataset.orderId;
    const rating = parseFloat(wrap.querySelector('.review-rating-input').value);
    const comment = wrap.querySelector('.review-comment-input').value.trim();
    const imageInput = wrap.querySelector('.review-image-input');
    const errorEl = wrap.querySelector('.review-error');

    errorEl.classList.add('hidden');
    errorEl.textContent = '';

    if (!rating || rating < 0.5) {
        errorEl.textContent = 'Please select a star rating.';
        errorEl.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Submitting...';

    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('product_id', productId);
    formData.append('rating', rating);
    formData.append('comment', comment);
    Array.from(imageInput.files).forEach(file => {
        formData.append('images[]', file);
    });

    fetch('{{ route('customer.reviews.store') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const block = document.getElementById('review-block-' + productId);
            if (block) block.innerHTML = renderSubmittedReviewHtml(data.review);
            if (window.showWishlistToast) {
                window.showWishlistToast(data.message || 'Review submitted successfully!');
            }
        } else {
            errorEl.textContent = data.message || 'Failed to submit review.';
            errorEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Submit';
        }
    })
    .catch(() => {
        errorEl.textContent = 'Something went wrong. Please try again.';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Submit';
    });
}
</script>
@endsection
