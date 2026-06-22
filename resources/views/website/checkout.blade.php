@extends('layouts.website')

@section('title', 'Secure Checkout | Chetan Imitation')

@section('content')
    <section class="section-space">

        <div class="container-1440">

            <!-- Heading -->

            <div class="text-center mb-10">

               <h2 class="font-moglan hero-title">
                    Secure Checkout
                </h2>

               <p class="hero-para">
                    Complete your order securely and receive your jewelry at your doorstep.
                </p>

            </div>

            <div class="grid xl:grid-cols-[953px_1fr] gap-6 items-start">

                <!-- LEFT SIDE -->

                <div class="space-y-6">

                    <!-- =====================
                DELIVERY ADDRESS
                ===================== -->

                    <!-- DELIVERY ADDRESS -->

                    <div class="border border-[#D5D5D5] bg-white rounded-sm overflow-hidden">

                        <!-- Header -->

                        <div class="flex items-center justify-between px-3 sm:px-5 py-[19px] border-b border-[#D5D5D5] flex-row gap-4 flex-wrap">

                            <div class="flex items-center gap-2 md:gap-[15px]">

                                <span
                                    class="w-[34px] h-[34px] rounded-full bg-[#B4771E] text-white text-base sm:text-lg flex items-center justify-center font-medium">
                                    1
                                </span>

                                <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-medium text-[#131615]">
                                    Delivery Address
                                </h3>

                            </div>

                            <button
                                class="bg-[#B4771E] hover:bg-[#b67d1f] text-white text-sm md:text-lg font-medium px-4 md:px-5 h-[40px] transition flex gap-3 md:gap-[9px] items-center"  onclick="openModal()">

                                <i class="fa-solid fa-plus"></i> Add New

                            </button>

                        </div>
<!-- Overlay -->


<div id="addressModal"
    class="fixed inset-0 z-50 hidden bg-black/50 overflow-y-auto p-4">

    <div class="min-h-full flex items-center justify-center py-5">

        <!-- Modal Box -->

        <div
            class="relative w-full max-w-[750px] bg-white rounded-[8px]
            p-4 sm:p-6 md:p-[30px]
            max-h-[90vh] border border-[#D5D5D5]
            overflow-y-auto">

            <!-- Close -->

            <button
                onclick="closeModal()"
                class="absolute top-4 right-4 md:top-6 md:right-6 text-[35px] leading-none text-[#131615]">

                &times;

            </button>


            <!-- Heading -->

            <h2 class="text-[24px] md:text-[30px] leading-[24px] md:leading-[30px] font-medium text-[#131615] mb-[31px]">

                Deliver To

            </h2>



            <!-- Full Name -->

            <div class="mb-5">

                <label class="block text-base md:text-xl text-[#131615] mb-3 font-semibold">

                    Full Name

                </label>

                <input
                    type="text"
                    id="addr_name"
                    placeholder="Enter Your Full Name"
                    class="w-full h-[48px] md:h-[56px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none">

            </div>




            <!-- Mobile -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">

                <div>

                    <label class="block text-base md:text-xl text-[#131615] mb-3 font-semibold">

                        Mobile Number

                    </label>

                    <input
                        type="text"
                        id="addr_phone"
                        placeholder="Enter Your Mobile Number"
                        class="w-full h-[48px] md:h-[56px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none">

                </div>


                <div>

                     <label class="block text-base md:text-xl text-[#131615] mb-3 font-semibold">

                        Alternate Phone Number (Optional)

                    </label>

                    <input
                        type="text"
                        id="addr_alternate_phone"
                        placeholder="Enter Your Mobile Number"
                        class="w-full h-[48px] md:h-[56px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none">

                </div>

            </div>




            <!-- Email -->

            <div class="mb-5">

                 <label class="block text-base md:text-xl text-[#131615] mb-3 font-semibold">

                    Email address

                </label>

                <input
                    type="email"
                    id="addr_email"
                    value="{{ auth('customer')->user()->email ?? '' }}"
                    class="w-full h-[48px] md:h-[56px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none">

            </div>




            <!-- Address -->

            <div class="mb-5">

                <label class="block text-base md:text-xl text-[#131615] mb-3 font-semibold">

                    Flat/House/Building Name

                </label>

                <textarea
                    id="addr_address"
                    rows="4"
                    placeholder="Enter Flat/House/Building Name"
                     class="w-full text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none py-3"></textarea>

            </div>




            <!-- City State -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">

                <div>

                    <label class="block text-base md:text-xl text-[#131615] mb-3 font-semibold">

                        Town / City

                    </label>

                    <input
                        type="text"
                        id="addr_city"
                        placeholder="Town / City"
                        class="w-full h-[48px] md:h-[56px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none">

                </div>



                <div>

                    <label class="block text-base md:text-xl text-[#131615] mb-3 font-semibold">

                        State / County

                    </label>

                    <select
                        id="addr_state"
                         class="w-full h-[48px] md:h-[56px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none">

                        <option value="">

                            Select an Option...

                        </option>

                        <option value="Gujarat">

                            Gujarat

                        </option>

                        <option value="Maharashtra">

                            Maharashtra

                        </option>

                    </select>

                </div>

            </div>




            <!-- Address Type -->

      <div class="mb-7">

    <label class="block text-base md:text-xl text-[#131615] mb-3 font-semibold">

        Type Of Address

    </label>

    <div class="flex flex-wrap gap-4">

        <!-- Home -->

        <input
            type="radio"
            name="addressType"
            id="home"
            value="home"
            class="hidden peer/home"
            checked>

        <label
            for="home"
            class="cursor-pointer py-[10px] px-5 border border-[#D5D5D5]
            rounded flex items-center gap-[8px]
            text-base sm:text-xl
            text-[#131615]
            peer-checked/home:bg-[#B4771E1A]
            peer-checked/home:border-[#B4771E1A]
            peer-checked/home:text-[#B4771E]
            transition-all">

            <svg xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-6 h-6">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />

            </svg>

            Home

        </label>



        <!-- Work -->

        <input
            type="radio"
            name="addressType"
            id="work"
            value="work"
            class="hidden peer/work">

        <label
            for="work"
            class="cursor-pointer py-[10px] px-5 border border-[#D5D5D5]
            rounded flex items-center gap-[8px]
            text-base sm:text-xl
            text-[#131615]
            peer-checked/work:bg-[#B4771E1A]
            peer-checked/work:border-[#B4771E1A]
            peer-checked/work:text-[#B4771E]
            transition-all">

            <svg xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-6 h-6">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />

            </svg>

            Work

        </label>

    </div>

</div>




            <!-- Save -->

            <button
                id="saveAddressBtn"
                onclick="saveCustomerAddress(event)"
                class="w-full h-[52px] md:h-[58px]
                bg-[#B4771E]
                hover:bg-[#a86f17]
                text-white
                text-lg md:text-[24px]
                font-medium
                rounded">

                Save Address

            </button>


        </div>

    </div>

</div>

                        @forelse($addresses as $addr)
                        <div class="address-card border-b border-[#D5D5D5] px-5 py-5 sm:py-[30px] cursor-pointer {{ $addr->is_default ? 'bg-[#B4771E0D] active-address' : '' }}" data-address-id="{{ $addr->id }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                        <img src="{{ asset('website/assets/images/' . ($addr->type === 'work' ? 'home1.png' : 'home.png')) }}" />
                                        <span class="text-sm sm:text-base lg:text-xl text-[#131615] font-normal">
                                            Deliver To:
                                        </span>
                                        <span class="font-semibold text-sm sm:text-base lg:text-xl text-[#131615]">
                                            {{ $addr->name }}, {{ $addr->phone }}
                                        </span>
                                        @if($addr->is_default)
                                        <span class="bg-[#B4771E29] text-[#B4771E] text-sm sm:text-base lg:text-lg px-2 sm:px-[15px] py-[6px] font-semibold rounded-[2px] leading-[20px]">
                                            Default
                                        </span>
                                        @endif
                                    </div>
                                    <p class="mt-[19px] text-sm sm:text-lg text-[#3D403F] leading-5 sm:leading-6">
                                        {{ $addr->address }}, {{ $addr->city }}, {{ $addr->state }}
                                    </p>
                                </div>
                                <button>
                                    <i class="fa-solid fa-ellipsis text-[#3D403F]"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="p-5 text-center text-gray-500">
                            No saved addresses. Please add a new delivery address to proceed.
                        </div>
                        @endforelse

                    <!-- =====================
                ORDER SUMMARY
                ===================== -->

                    <div class="border border-[#D5D5D5]">

                          <div class="flex items-center justify-between px-5 py-[19px] border-b border-[#D5D5D5] ">

                            <div class="flex items-center gap-[15px]">

                                <span
                                    class="w-[34px] h-[34px] rounded-full bg-[#B4771E] text-white text-base sm:text-lg flex items-center justify-center font-medium">
                                    1
                                </span>

                                <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-medium text-[#131615]">
                                   Order Summary
                                </h3>

                            </div>
                        </div>

                        @forelse($cartItems as $item)
                            @php
                                $itemProduct = $item->product;
                                $itemVariant = $item->productVariant;
                                $itemPrice = $itemVariant ? $itemVariant->sale_price : $itemProduct->sale_price;
                                $itemMrp = $itemProduct->mrp;
                                $itemQty = $item->qty;
                                $itemDetailUrl = route('product.detail', $itemProduct->slug);
                                if ($itemVariant) {
                                    $itemDetailUrl .= '?variant=' . $itemVariant->id;
                                }
                            @endphp
                            <div class="border-b border-[#D5D5D5] p-3 lg:p-[25px]">
                                <div class="flex flex-col md:flex-row gap-4">
                                    <!-- Image -->
                                    <div class="relative shrink-0">
                                        <a href="{{ $itemDetailUrl }}">
                                            <img
                                                src="{{ $itemProduct->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png') }}"
                                                alt="{{ $itemProduct->name }}"
                                                class="w-[200px] h-[240px] object-cover">
                                        </a>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="max-w-[240px] md:max-w-[500px] truncate text-base md:text-[22px] lg:text-[26px] leading-[26px] lg:leading-[36px] font-semibold text-[#131615]">
                                            <a href="{{ $itemDetailUrl }}">{{ $itemProduct->name }}</a>
                                        </h3>

                                        <div class="flex items-center gap-2 mt-5">
                                            <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">
                                                ₹{{ number_format($itemPrice, 0) }}
                                            </span>
                                            @if($itemMrp && $itemMrp > $itemPrice)
                                            <span class="text-[#999] line-through">
                                                ₹{{ number_format($itemMrp, 0) }}
                                            </span>
                                            @endif
                                            <span class="text-[#757575] text-sm md:text-base ml-2">Qty: {{ $itemQty }}</span>
                                        </div>

                                        <div class="sm:mt-[20px] text-[14px]">
                                            <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap">
                                                <span class="font-medium text-[#131615] w-[120px]">Category:</span>
                                                <span class="text-[#757575] ml-2">{{ $itemProduct->category?->name ?? 'N/A' }}</span>
                                            </p>
                                            @if($itemVariant && $itemVariant->attributeValue)
                                            <p class="text-base sm:text-lg sm:leading-[18px] mb-[10px] md:mb-[15px] flex flex-wrap">
                                                <span class="font-medium text-[#131615] w-[120px]">{{ $itemVariant->attributeValue->attribute->name }}:</span>
                                                <span class="text-[#777] ml-2">{{ $itemVariant->attributeValue->value }}</span>
                                            </p>
                                            @endif
                                            <p class="text-base sm:text-lg sm:leading-[18px] flex flex-wrap">
                                                <span class="font-medium text-[#131615] w-[120px]">Availability:</span>
                                                <span class="text-[#777] ml-2">{{ $itemProduct->inventories_sum_quantity > 0 ? 'In Stock' : 'Out of Stock' }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-5 text-center text-gray-500">
                                Your cart is empty.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- RIGHT SIDEBAR -->

                <div class="space-y-4">

                    <!-- Coupon -->

                    <div class="mb-[30px]">

                        <h3 class="text-lg md:text-[22px] font-medium mb-3">
                            Have a Coupon?
                        </h3>

                        <div class="flex gap-2 flex-wrap md:flex-nowrap">

                            <input type="text" placeholder="Enter Coupon Code"
                                class="h-[44px] lg:h-[56px] border border-[#D5D5D5] px-2 sm:px-4 bg-white text-base md:text-lg leading-[18px] placeholder:text-lg">

                            <button class="bg-[#B4771E] text-white px-6 h-[44px] lg:h-[56px] whitespace-nowrap text-base md:text-[22px]">

                                Apply Coupon

                            </button>

                        </div>

                    </div>

                    <!-- Price Details -->

             
                <div class="border border-[#D5D5D5] p-4 md:p-5">

                    <h3 class="text-lg md:text-[22px] md:leading-[22px] font-medium text-[#131615]">
                        Price Details
                    </h3>

                    <div class="border-t border-[#D5D5D5] mt-[20px] pt-[20px] space-y-5 text-[14px]">

                        <div class="flex justify-between text-base sm:text-xl sm:leading-[20px] ">
                            <span class="font-medium text-[#131615]">Subtotal</span>
                            <span class="font-normal text-[#3D403F]">₹{{ number_format($subtotal, 0) }}</span>
                        </div>

                        <div class="flex justify-between text-base sm:text-xl sm:leading-[20px] ">
                            <span class="font-medium text-[#131615]">Discount</span>
                            <span class="font-normal text-[#3D403F]">-₹{{ number_format($discount, 0) }}</span>
                        </div>

                     <div class="flex justify-between text-base sm:text-xl sm:leading-[20px] ">
                            <span class="font-medium text-[#131615]">Shipping</span>
                             <span class="font-normal text-[#3D403F]">{{ $shipping > 0 ? '₹' . number_format($shipping, 0) : 'Free' }}</span>
                        </div>

                        <div class="flex justify-between text-base sm:text-xl sm:leading-[20px] ">
                            <span class="font-medium text-[#131615]">Estimated Tax</span>
                             <span class="font-normal text-[#3D403F]">₹0</span>
                        </div>

                    </div>

                    <div class="border-t border-[#D5D5D5] mt-4 pt-4 flex justify-between">

                        <span class="font-medium text-lg md:text-[22px] lg:text-[24px] md:leading-[22px] lg:leading-[24px]">
                            Total
                        </span>

                        <span class="font-bold text-[#B4771E] text-lg md:text-[22px] lg:text-[24px] md:leading-[22px] lg:leading-[24px]">
                            ₹{{ number_format($total, 0) }}
                        </span>

                    </div>

                    <button  onclick="openSuccessModal()"
                        class="common-btn !w-full mt-[30px]">

                       Place Order

                    </button>


                </div>
<!-- Overlay -->

<div
    id="successModal"
    class="fixed inset-0 z-50 hidden bg-black/50 overflow-y-auto p-4">

    <div class="min-h-full flex items-center justify-center py-5">

        <!-- Modal -->

        <div
            class="relative w-full max-w-[720px]
            bg-white rounded-[8px]
            p-4 sm:p-6 md:p-[33px]
            max-h-[90vh]
            overflow-y-auto">

            <!-- Close -->

            <button
                onclick="closeSuccessModal()"
                class="absolute top-4 right-4 text-[32px] text-[#131615]">

                &times;

            </button>


            <!-- Success Icon -->

            <div class="flex justify-center">
                    <img src="{{ asset('website/assets/images/') }}rightcheck.png" alt="" class="w-[200px] md:w-auto">
            </div>


            <!-- Heading -->

            <h2
                class="text-center font-moglan
                text-[30px]
                sm:text-[40px]
                md:text-[50px]
                leading-tight md:leading-[50px]
                text-[#131615]
                mt-4">

                Order Placed Successfully!

            </h2>



            <!-- Text -->

            <p
                class="text-center
                text-[#3D403F]
                text-base
                md:text-xl font-normal
                max-w-[520px]
                mx-auto
                mt-5">

                Thank you for shopping with Chetan Imitation.
                Your order has been confirmed and is now being processed.

            </p>



            <!-- Order Details -->

            <div class="border border-[#D5D5D5] mt-8 p-[20px] md:p-[30px]">

                <!-- Row -->

                <div class="flex justify-between items-center border-b border-[#D5D5D5] pb-5">
                    <div class="flex items-center gap-[15px]">
                      <img src="{{ asset('website/assets/images/') }}order1.png" alt="">

                        <span class=" text-lg sm:text-xl font-semibold">

                            Order ID

                        </span>

                    </div>

                    <span class="text-[#3D403F] text-base sm:text-lg">

                        #CI2026071542

                    </span>

                </div>
                <!-- Row -->

                <div class="flex justify-between items-center border-b border-[#D5D5D5] py-5">
                    <div class="flex items-center gap-[15px]">
                      <img src="{{ asset('website/assets/images/') }}order1.png" alt="">

                        <span class=" text-lg sm:text-xl font-semibold">
Order Amount

                        </span>

                    </div>

                    <span class="text-[#3D403F] text-base sm:text-lg">

                        ₹{{ number_format($total, 0) }}

                    </span>

                </div>
                <!-- Row -->

                <div class="flex justify-between items-center py-5">
                    <div class="flex items-center gap-[15px]">
                      <img src="{{ asset('website/assets/images/') }}order1.png" alt="">

                        <span class=" text-lg sm:text-xl font-semibold">

                         Estimated Delivery

                        </span>

                    </div>

                    <span class="text-[#3D403F] text-base sm:text-lg">

                        4–7 Business Days

                    </span>

                </div>


            </div>



            <!-- Info -->

            <div
                class="bg-[#B4771E]/10
                p-5 rounded-[5px]  mt-4
                flex gap-[15px] items-start">

                <img src="{{ asset('website/assets/images/') }}mail.png" alt="">

                <p class="text-[#131615] text-base md:text-xl">

                    A confirmation email and order details have been sent to your
                    registered email address and mobile number.

                </p>

            </div>



            <!-- Buttons -->

            <button
                class="w-full h-[52px] md:h-[68px] bg-[#B4771E] text-white
                text-base md:text-[22px] md:leading-[24px] mt-10">
                View My Orders
            </button>

            <button
                onclick="closeSuccessModal()"
                class="w-full h-[52px] md:h-[68px]
                border border-[#131615]
                text-[#131615]
                text-base md:text-[22px] md:leading-[24px]
                mt-[17px]">

                Continue Shopping

            </button>

        </div>

    </div>

</div>
                    <!-- Why Shop -->

                    <div class="border border-[#D5D5D5] p-4 md:p-5">

                         <h3 class="text-lg md:text-[22px] md:leading-[22px] font-medium text-[#131615] mb-[24px]">
                            Why Shop With Us?
                        </h3>

                         <div class="space-y-4 text-[#131615]">

                             <div class="flex gap-3 text-base md:text-xl font-normal">
                                 <i class="fa-solid fa-check text-[#B4771E] mt-1"></i>
                                <span>100% Secure Checkout</span>
                            </div>

                             <div class="flex gap-3 text-base md:text-xl font-normal">
                                 <i class="fa-solid fa-check text-[#B4771E] mt-1"></i>
                                <span>Premium Quality jewelry</span>
                            </div>

                             <div class="flex gap-3 text-base md:text-xl font-normal">
                                 <i class="fa-solid fa-check text-[#B4771E] mt-1"></i>
                                <span>Easy Return and Exchange</span>
                            </div>

                             <div class="flex gap-3 text-base md:text-xl font-normal">
                                 <i class="fa-solid fa-check text-[#B4771E] mt-1"></i>
                                <span>Secure Packaging</span>
                            </div>

                        </div>

                    </div>

</section>

<!-- =========================
YOU MAY ALSO LIKE
========================= -->
@if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
<section class="section-space">

    <div class="container-1440">

        <!-- Heading -->

        <div class="text-center mb-10 lg:mb-10">

            <h2 class="font-moglan hero-title">
                You May Also Like
            </h2>

        </div>

        <!-- Products -->

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @include('website.partials.product-grid-items', ['products' => $relatedProducts])
        </div>

    </div>

</section>
@endif
@endsection

@section('page-js')
<script>
const modal = document.getElementById("addressModal");

function openModal(){
    modal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
}

function closeModal(){
    modal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
}

// overlay click
modal.addEventListener("click",(e)=>{
    if(e.target === modal){
        closeModal();
    }
});

// esc press
document.addEventListener("keydown",(e)=>{
    if(e.key === "Escape"){
        closeModal();
    }
});

const successModal = document.getElementById("successModal");

function openSuccessModal(){
    successModal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
}

function closeSuccessModal(){
    successModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
}

successModal.addEventListener("click",(e)=>{
    if(e.target === successModal){
        closeSuccessModal();
    }
});
</script>
@endsection
