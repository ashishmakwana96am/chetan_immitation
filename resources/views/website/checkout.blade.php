@extends('layouts.website')

@section('title', 'Secure Checkout | Chetan Imitation')

@section('content')
    <section class="section-space">
        <div class="container-1440">
            <!-- Heading -->

            <div class="text-center mb-7">
               <h2 class="font-moglan hero-title">
                    Secure Checkout
                </h2>
               <p class="hero-para">
                    Complete your order securely and receive your jewelry at your doorstep.
                </p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,70%)_1fr] 2xl:grid-cols-[953px_1fr] gap-6 items-start ">
                <!-- LEFT SIDE -->

                <div class="min-w-0 space-y-6">
                    <!-- =====================
                DELIVERY ADDRESS
                ===================== -->

                    <!-- DELIVERY ADDRESS -->

                    <div class="min-w-0 border border-[#D5D5D5] bg-white rounded-sm">
                        <!-- Header -->

                        <div class="flex items-center justify-between px-3 sm:px-3 py-3 border-b border-[#D5D5D5] flex-row gap-4 flex-wrap">
                            <div class="flex items-center gap-2 md:gap-[15px]">
                                <span
                                    class="w-7 h-7 rounded-full bg-[#B4771E] text-white text-base flex items-center justify-center font-medium">
                                    1
                                </span>
                                <h3 class="text-base sm:text-lg md:text-xl font-medium text-[#131615]">
                                    Delivery Address
                                </h3>
                            </div>
                            <button
                                class="bg-[#B4771E] hover:bg-[#b67d1f] text-white text-sm md:text-base font-medium px-4 h-[35px] transition flex gap-3 md:gap-[9px] items-center rounded-sm"  onclick="openModal()">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
<!-- Overlay -->

<style>
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.active-address {
    border-left: 4px solid #B4771E !important;
}
</style>
<div id="addressModal"
   class="fixed inset-0 z-50 hidden bg-black/50 overflow-hidden p-4 !mt-0">
    <div class="min-h-full flex items-center justify-center !mt-0 py-5">
        <!-- Modal Box -->

        <div
            class="relative w-full max-w-[750px] bg-white rounded-[8px]
            p-4 sm:p-5 max-h-[90vh] border border-[#D5D5D5]
            overflow-y-auto" >
            <!-- Close -->

            <button
                onclick="closeModal()"
                class="absolute top-4 right-4 md:top-6 md:right-6 text-[35px] leading-none text-[#131615]">
                &times;
            </button>
            <!-- Heading -->

            <h2 class="text-xl lg:text-[22px] lg:leading-[24px] font-medium text-[#131615] mb-4">
                Deliver To
            </h2>
            <!-- Full Name -->

            <div class="mb-4">
                <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Full Name <span class="text-red-600">*</span>
                </label>
                <input
                    type="text"
                    id="addr_name"
                    name="name"
                    placeholder="Enter Your Full Name"
                    class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base  placeholder:text-base  border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="name"></p>
            </div>
            <!-- Mobile -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Mobile Number <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="text"
                        id="addr_phone"
                        name="phone"
                        placeholder="Enter Your Mobile Number"
                        maxlength="10"
                        inputmode="numeric"
                        class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base  placeholder:text-base  border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="phone"></p>
                </div>
                <div>
                     <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Alternate Phone Number (Optional)
                    </label>
                    <input
                        type="text"
                        id="addr_alternate_phone"
                        name="alternate_phone"
                        placeholder="Enter Your Mobile Number"
                        maxlength="10"
                        inputmode="numeric"
                        class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base  placeholder:text-base  border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="alternate_phone"></p>
                </div>
            </div>
            <!-- Email -->

            <div class="mb-4">
                 <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Email address
                </label>
                <input
                    type="email"
                    id="addr_email"
                    name="email"
                    placeholder="Enter Email address"
                    value="{{ auth('customer')->user()->email ?? '' }}"
                    class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base  placeholder:text-base  border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="email"></p>
            </div>
            <!-- Address -->

            <div class="mb-4">
                <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Flat/House/Building Name <span class="text-red-600">*</span>
                </label>
                <textarea
                    id="addr_address"
                    name="address"
                    rows="3"
                    placeholder="Enter Flat/House/Building Name"
                     class="addr-input w-full text-[#757575] text-base min-h-28 placeholder:text-base border border-[#D5D5D5] px-4 outline-none py-3 focus:border-[#B4771E] resize-y rounded-sm"></textarea>
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="address"></p>
            </div>
            <!-- City State -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Town / City <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="text"
                        id="addr_city"
                        name="city"
                        placeholder="Town / City"
                        class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base  placeholder:text-base  border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="city"></p>
                </div>
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        State / County <span class="text-red-600">*</span>
                    </label>
                    <select
                        id="addr_state"
                        name="state"
                         class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base  placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E] rounded-sm">
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
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="state"></p>
                </div>
            </div>
            <!-- Address Type -->

      <div class="mb-5">
    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
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
            class="cursor-pointer py-[6px] px-4 border border-[#D5D5D5]
            rounded flex items-center gap-[8px]
            text-base
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
                class="w-5 h-5">
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
            class="cursor-pointer py-[6px] px-4 border border-[#D5D5D5]
            rounded flex items-center gap-[8px]
            text-base
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
                class="w-5 h-5">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
            </svg>
            Work
        </label>
    </div>
</div>
            <!-- Default Address Checkbox -->
            <div class="mb-5 flex items-center gap-[10px]">
                <div class="relative flex items-center justify-center w-[22px] h-[22px] shrink-0 rounded-[5px] border-2 border-[#B4771E] bg-white transition-colors duration-200">
                    <input
                        type="checkbox"
                        id="addr_is_default"
                        name="is_default"
                        class="absolute inset-0 opacity-0 w-full h-full cursor-pointer z-10 peer">
                    <svg class="w-[13px] h-[13px] text-[#B4771E] opacity-0 peer-checked:opacity-100 transition-opacity duration-200" viewBox="0 0 12 10" fill="none">
                        <path d="M1 5L4.5 8.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <label for="addr_is_default" class="text-base text-[#3D403F] cursor-pointer select-none">
                    Set as default address
                </label>
            </div>
            <div id="addressSuccess" class="hidden mb-5 border border-green-200 bg-green-50 px-4 py-3 text-green-700"></div>
            <div id="addressFailure" class="hidden mb-5 border border-red-200 bg-red-50 px-4 py-3 text-red-700"></div>
            <!-- Save -->

            <button
                id="saveAddressBtn"
                onclick="saveCustomerAddress(event)"
                class="common-btn w-full h-[52px] rounded-sm">
                Save Address
            </button>
        </div>
    </div>
</div>
                        <div id="addressesCardsList">
                            @forelse($addresses as $addr)
                            <div class="address-card border-b-[1px] border-[#D5D5D5] px-3 md:px-4 py-3 md:py-4 sm:py-[30px] cursor-pointer bg-white border-l-[4px] border-l-transparent last:border-b-0 {{ $addr->is_default ? 'active-address default-address-card' : '' }} text-[#131615]" data-address-id="{{ $addr->id }}">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                            <input type="radio" name="selected_address" class="address-radio accent-[#B4771E] w-[18px] h-[18px] mr-1" {{ $addr->is_default ? 'checked' : '' }} />
                                            <!-- <img src="{{ asset('website/assets/images/' . ($addr->type === 'work' ? 'home1.png' : 'home.png')) }}" class="address-icon" /> -->
                                             @if($addr->type === 'work')

                                                <svg width="19" height="21" viewBox="0 0 19 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M0.75 19.5H17.9375M1.53125 0.75H17.1562M2.3125 0.75V19.5M16.375 0.75V19.5M6.21875 4.65625H7.78125M6.21875 7.78125H7.78125M6.21875 10.9062H7.78125M10.9062 4.65625H12.4687M10.9062 7.78125H12.4687M10.9062 10.9062H12.4687M6.21875 19.5V15.9844C6.21875 15.3375 6.74375 14.8125 7.39062 14.8125H11.2969C11.9437 14.8125 12.4687 15.3375 12.4687 15.9844V19.5" stroke="#131615" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>

                                            @else
                                            
                                            <svg width="22" height="21" viewBox="0 0 22 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0.75 10.4211L10.0771 1.09297C10.5354 0.635677 11.2771 0.635677 11.7344 1.09297L21.0625 10.4211M3.09375 8.07734V18.6242C3.09375 19.2711 3.61875 19.7961 4.26562 19.7961H8.5625V14.718C8.5625 14.0711 9.0875 13.5461 9.73437 13.5461H12.0781C12.725 13.5461 13.25 14.0711 13.25 14.718V19.7961H17.5469C18.1937 19.7961 18.7187 19.2711 18.7187 18.6242V8.07734M7 19.7961H15.5937" stroke="#131615" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>

                                            @endif
                                            <span class="text-sm sm:text-base lg:text-lg font-normal text-[#131615]">
                                                Deliver To:
                                            </span>
                                            <span class="font-semibold text-sm sm:text-base lg:text-lg text-[#131615] customer-name-phone">
                                                {{ $addr->name }}, {{ $addr->phone }}
                                            </span>
                                            @if($addr->is_default)
                                            <span class="bg-[#B4771E29] text-[#B4771E] text-sm sm:text-base px-2 sm:px-[15px] py-[4px] font-semibold rounded-[2px] leading-[20px] default-badge">
                                                Default
                                            </span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-sm sm:text-base text-[#3D403F] address-text">
                                            {{ $addr->address }}, {{ $addr->city }}, {{ $addr->state }}
                                        </p>
                                    </div>
                                    <div class="relative address-menu-container">
                                        <button class="w-6 h-6 flex justify-center items-center address-menu-btn p-2 hover:bg-black/5 rounded-full transition focus:outline-none">
                                            <i class="fa-solid fa-ellipsis text-[#3D403F]"></i>
                                        </button>
                                        <!-- <div class="absolute right-0 mt-1 w-36 bg-white border border-[#D5D5D5] shadow-md rounded z-10 hidden address-dropdown text-left">
                                            @if(!$addr->is_default)
                                            <button onclick="setAddressAsDefault({{ $addr->id }}, event)" class="w-full text-left px-4 py-2 text-sm text-[#131615] hover:bg-gray-100 transition set-default-btn">
                                                Set as Default
                                            </button>
                                            @endif
                                            <button onclick="deleteAddress({{ $addr->id }}, event)" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                                Delete
                                            </button>
                                        </div> -->
                                        <div class="absolute right-0 top-full mt-2 w-[180px]
                                            bg-white border border-[#D5D5D5]
                                            rounded-[8px] shadow-[0_4px_20px_rgba(0,0,0,0.12)]
                                            overflow-hidden z-20 hidden address-dropdown">

                                            <!-- Edit -->
                                            <button
                                                onclick="editAddress({{ $addr->id }}, event)"
                                                class="w-full flex items-center gap-3 p-3
                                                text-[#4A4A4A] hover:bg-[#FAFAFA] transition">
                                                <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.1767 2.48937L13.5825 1.08271C13.8756 0.789642 14.273 0.625 14.6875 0.625C15.102 0.625 15.4994 0.789642 15.7925 1.08271C16.0856 1.37577 16.2502 1.77325 16.2502 2.18771C16.2502 2.60216 16.0856 2.99964 15.7925 3.29271L6.94333 12.1419C6.50277 12.5822 5.95947 12.9058 5.3625 13.0835L3.125 13.7502L3.79167 11.5127C3.9694 10.9157 4.29303 10.3724 4.73333 9.93187L12.1767 2.48937ZM12.1767 2.48937L14.375 4.68771M13.125 10.4169V14.3752C13.125 14.8725 12.9275 15.3494 12.5758 15.701C12.2242 16.0527 11.7473 16.2502 11.25 16.2502H2.5C2.00272 16.2502 1.52581 16.0527 1.17417 15.701C0.822544 15.3494 0.625 14.8725 0.625 14.3752V5.62521C0.625 5.12793 0.822544 4.65101 1.17417 4.29938C1.52581 3.94775 2.00272 3.75021 2.5 3.75021H6.45833" stroke="#3D403F" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span class="text-base font-normal">
                                                    Edit
                                                </span>

                                            </button>
                                           <div class="mx-3 border-t border-[#D5D5D5]"></div>
                                            <!-- Delete -->
                                            <button
                                                onclick="deleteAddress({{ $addr->id }}, event)"
                                                class="w-full flex items-center gap-3 p-3
                                                text-[#4A4A4A] hover:bg-[#FFF7F7] transition">
                                                <svg width="15" height="18" viewBox="0 0 15 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.78345 6.25043L9.49512 13.7504M5.50512 13.7504L5.21679 6.25043M10.6251 3.2446C11.5949 3.31968 12.5617 3.43003 13.5235 3.57543C13.8085 3.61877 14.0918 3.6646 14.3751 3.71377M13.5235 3.57543L12.6335 15.1446C12.5971 15.6156 12.3843 16.0556 12.0376 16.3765C11.6909 16.6974 11.2359 16.8756 10.7635 16.8754H4.23679C3.76437 16.8756 3.30931 16.6974 2.9626 16.3765C2.6159 16.0556 2.40311 15.6156 2.36679 15.1446L1.47679 3.57543M1.47679 3.57543C1.19179 3.61793 0.908455 3.66377 0.625122 3.71293M1.47679 3.57543C2.43857 3.43003 3.40532 3.31968 4.37512 3.2446M10.6251 3.2446V2.48127C10.6251 1.49793 9.86679 0.677934 8.88346 0.647101C7.96147 0.617633 7.03878 0.617633 6.11679 0.647101C5.13346 0.677934 4.37512 1.49877 4.37512 2.48127V3.2446M10.6251 3.2446C8.54489 3.08383 6.45535 3.08383 4.37512 3.2446" stroke="#3D403F" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span class="text-base font-normal">
                                                    Remove
                                                </span>

                                            </button>
                                            <div class="mx-3 border-t border-[#D5D5D5]"></div>
                                            @if(!$addr->is_default)

                                            <!-- Set Default -->
                                            <button
                                                onclick="setAddressAsDefault({{ $addr->id }}, event)"
                                                class="w-full flex items-center gap-3 p-3
                                                text-[#4A4A4A] hover:bg-[#FAFAFA] transition">
                                                <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M5.625 8.75L7.5 10.625L10.625 6.25M15.625 8.125C15.625 9.10991 15.431 10.0852 15.0541 10.9951C14.6772 11.9051 14.1247 12.7319 13.4283 13.4283C12.7319 14.1247 11.9051 14.6772 10.9951 15.0541C10.0852 15.431 9.10991 15.625 8.125 15.625C7.14009 15.625 6.16482 15.431 5.25487 15.0541C4.34493 14.6772 3.51814 14.1247 2.8217 13.4283C2.12526 12.7319 1.57281 11.9051 1.1959 10.9951C0.818993 10.0852 0.625 9.10991 0.625 8.125C0.625 6.13588 1.41518 4.22822 2.8217 2.8217C4.22822 1.41518 6.13588 0.625 8.125 0.625C10.1141 0.625 12.0218 1.41518 13.4283 2.8217C14.8348 4.22822 15.625 6.13588 15.625 8.125Z" stroke="#3D403F" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span class="text-base font-normal">
                                                    Set as Default
                                                </span>

                                            </button>

                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div id="noAddressesPlaceholder" class="p-5 text-center text-gray-500">
                                No saved addresses. Please add a new delivery address to proceed.
                            </div>
                            @endforelse
                        </div>
                    </div>
                    <!-- =====================
                ORDER SUMMARY
                ===================== -->

                    <div class="border border-[#D5D5D5]">
                          <div class="flex items-center justify-between px-3 sm:px-3 py-3 border-b border-[#D5D5D5] ">
                            <div class="flex items-center gap-[15px]">
                                <span
                                    class="w-7 h-7 rounded-full bg-[#B4771E] text-white text-base flex items-center justify-center font-medium">
                                    2
                                </span>
                                <h3 class="text-base sm:text-lg md:text-xl font-medium text-[#131615]">
                                   Order Summary
                                </h3>
                            </div>
                        </div>
                        <div class="">
                            @forelse($cartItems as $item)
                                @php
                                    $product  = $item->product;
                                    $variant  = $item->productVariant;
                                    $price    = $variant ? (float)$variant->sale_price : (float)$product->sale_price;
                                    $mrp      = (float)$product->mrp;
                                    $imgUrl   = $product->primaryImage?->image_url ?? asset('website/assets/images/Royal_Bridal.png');
                                    $detailUrl = route('product.detail', $product->slug);
                                    if ($variant) {
                                        $detailUrl .= '?variant=' . $variant->id;
                                    }
                                    $attrLabel = null;
                                    if ($variant && $variant->attributeValue) {
                                        $attrLabel = optional($variant->attributeValue->attribute)->name
                                            ? $variant->attributeValue->attribute->name . ': ' . $variant->attributeValue->value
                                            : $variant->attributeValue->value;
                                    }
                                    $stockQty = $product->inventories_sum_quantity ?? 0;
                                    $inWishlist = auth('customer')->check()
                                        && auth('customer')->user()->wishlists->contains('product_id', $product->id);
                                @endphp

                                <div class="cart-item group wishlist-item border-b border-[#D5D5D5] p-3 lg:p-4 last:border-0" data-id="{{ $item->id }}" data-price="{{ $price }}">
                                    <div class="flex flex-col sm:flex-row gap-4 group">
                                        {{-- Image --}}
                                        <div class="relative shrink-0 sm:w-[190px] sm:h-[190px] overflow-hidden cursor-pointer">
                                            @if($stockQty < 1)
                                            <div class="absolute top-[17px] left-[-39px] z-10 rotate-[-20deg]">
                                                <span class="bg-[#EF1B1B] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SOLD OUT</span>
                                            </div>
                                            @elseif($product->sale)
                                            <div class="absolute top-[10px] left-[-35px] z-10 rotate-[-20deg]">
                                                <span class="bg-[#ef1b1b] text-white text-[12px] font-semibold px-10 py-1 block tracking-wide">SALE</span>
                                            </div>
                                            @endif
                                            <a href="{{ $detailUrl }}">
                                                <img src="{{ $imgUrl }}" alt="{{ $product->name }}"
                                                    class="sm:w-[190px] sm:h-[190px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
                                            </a>
                                            <button class="wishlist-btn absolute top-2 right-2 w-[36px] h-[36px] bg-white rounded-lg flex items-center justify-center outline-none focus:outline-none focus:ring-0"
                                                data-product-id="{{ $product->id }}"
                                                data-variant-id="{{ $variant?->id ?? '' }}"
                                                data-login-url="{{ route('login') }}"
                                                data-toggle-url="{{ route('wishlist.toggle') }}"
                                                data-current-url="{{ url()->current() }}"
                                                data-in-wishlist="{{ $inWishlist ? '1' : '0' }}">
                                               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"
                                                    class="wishlist-icon w-5 h-5 transition-all duration-300 {{ $inWishlist ? 'fill-[#E01B1B] text-[#E01B1B]' : 'fill-transparent text-[#131615]' }}">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                               </svg>
                                            </button>
                                        </div>
                                        {{-- Content --}}
                                        <div class="flex-1 min-w-0 flex flex-col justify-between">
                                            <div>
                                                <a href="{{ $detailUrl }}">
                                                    <h3 class="block product-title text-base md:text-[22px] font-semibold text-[#131615] hover:text-[#B4771E] transition w-full min-w-0 overflow-hidden text-ellipsis whitespace-nowrap">
                                                        {{ $product->name }}
                                                    </h3>
                                                </a>
                                                <div class="flex items-center gap-2 mt-3">
                                                    <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">
                                                        ₹{{ number_format($price, 0) }}
                                                    </span>
                                                    @if($mrp > $price)
                                                    <span class="text-[#999] line-through text-base md:text-lg">
                                                        ₹{{ number_format($mrp, 0) }}
                                                    </span>
                                                    @endif
                                                </div>
                                                <div class="flex justify-between sm:items-center flex-col sm:flex-row gap-4">
                                                    <div class="mt-2 sm:mt-4 space-y-2 text-[14px]">
                                                        @if($product->category)
                                                        <p class="text-base flex flex-wrap">
                                                            <span class="font-medium text-[#131615] w-[120px]">Category:</span>
                                                            <span class="text-[#757575] ml-2">{{ $product->category->name }}</span>
                                                        </p>
                                                        @endif
                                                        @if($attrLabel)
                                                        @php
                                                            $parts = explode(':', $attrLabel, 2);
                                                            $labelName = count($parts) > 1 ? trim($parts[0]) : 'Variant';
                                                            $labelVal = count($parts) > 1 ? trim($parts[1]) : trim($parts[0]);
                                                        @endphp
                                                        <p class="text-base flex flex-wrap">
                                                            <span class="font-medium text-[#131615] w-[120px]">{{ $labelName }}:</span>
                                                            <span class="text-[#757575] ml-2">{{ $labelVal }}</span>
                                                        </p>
                                                        @endif

                                                        <p class="text-base flex flex-wrap">
                                                            <span class="font-medium text-[#131615] w-[120px]">Item Total:</span>
                                                            <span class="text-[#B4771E] font-semibold ml-2 item-total-display">
                                                                ₹{{ number_format($price * $item->qty, 0) }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    {{-- Qty display (static) --}}
                                                    <div class="flex items-center border border-[#D5D5D5] py-[8px] sm:py-[10px] px-[15px] gap-[15px] w-max">
                                                        <span class="text-[#757575] text-sm sm:text-base font-medium">Qty:</span>
                                                        <span class="text-base md:text-[22px] font-semibold text-[#131615]">
                                                            {{ $item->qty }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="border-t border-[#D5D5D5] mt-2 pt-2">
                                                <div class="flex items-center flex-wrap gap-2 text-[#3D403F]">
                                                    <button type="button" onclick="removeCheckoutItem({{ $item->id }})"
                                                        class="after:border-r after:border-[#3D403F] after:h-5 after:sm:mx-4 after:content-[''] after:inline-block hover:text-red-500 flex items-center gap-[10px] text-sm sm:text-base transition">
                                                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 sm:size-5 -mt-0.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                        Remove From Cart
                                                    </button>
                                                    <button type="button" onclick="moveCheckoutItemToWishlist({{ $item->id }}, {{ $product->id }}, {{ $variant?->id ?? 'null' }}, this)"
                                                        class="hover:text-[#B4771E] flex items-center gap-[10px] text-sm sm:text-base transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 sm:size-5 -mt-0.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"></path>
                                                        </svg>
                                                        Move to Wishlist
                                                    </button>
                                                </div>
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

                    <!-- =====================
                    PAYMENT METHOD
                    ===================== -->
                    <div class="border border-[#D5D5D5] mt-4">
                        <div class="flex items-center justify-between px-3 sm:px-3 py-3 border-b border-[#D5D5D5]">
                            <div class="flex items-center gap-[15px]">
                                <span class="w-7 h-7 rounded-full bg-[#B4771E] text-white text-base flex items-center justify-center font-medium">
                                    3
                                </span>
                                <h3 class="text-base sm:text-lg md:text-xl font-medium text-[#131615]">
                                    Payment Method
                                </h3>
                            </div>
                        </div>
                        <div class="p-3 sm:p-5">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <!-- Online Payment (Razorpay) -->
                                <div class="flex-1 cursor-pointer border border-[#B4771E] bg-[#B4771E0D] p-4 rounded hover:bg-[#B4771E0D] transition" id="onlinePaymentOption" onclick="selectPaymentMethod('online')">
                                    <div>
                                        <p class="font-semibold text-[#131615] text-base sm:text-lg">Online Payment</p>
                                        <p class="text-sm text-[#3D403F]">Pay securely using Razorpay (Cards, UPI, Netbanking)</p>
                                    </div>
                                </div>
                                <!-- Cash on Delivery (COD) -->
                                <div class="flex-1 cursor-pointer border border-[#D5D5D5] bg-white p-4 rounded hover:bg-[#B4771E0D]/10 transition hover:border-[#B4771E]" id="codPaymentOption" onclick="selectPaymentMethod('cod')">
                                    <div>
                                        <p class="font-semibold text-[#131615] text-base sm:text-lg">Cash on Delivery (COD)</p>
                                        <p class="text-sm text-[#3D403F]">Pay with cash upon delivery of your order</p>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="selectedPaymentMethod" name="payment_method_select" value="online">
                        </div>
                    </div>
                </div>
                <!-- RIGHT SIDEBAR -->

                <div class="space-y-4">
                    <!-- Coupon -->
                    <div class="mb-6">
                        <h3 class="text-lg md:text-xl font-medium mb-3">
                            Have a Coupon?
                        </h3>
                        <div class="flex gap-2 flex-wrap md:flex-nowrap">
                            <input type="text" id="couponCodeInput" placeholder="Enter Coupon Code"
                                value="{{ session('applied_coupon_code') }}"
                                {{ session()->has('applied_coupon_code') ? 'disabled' : '' }}
                                class="h-[44px] lg:h-[52px] border border-[#D5D5D5] px-2 sm:px-4 bg-white text-base md:text-lg leading-[18px] placeholder:text-base placeholder:md:text-lg rounded-sm flex-grow {{ session()->has('applied_coupon_code') ? 'bg-gray-100 cursor-not-allowed' : '' }}">
                            <button id="couponActionBtn" onclick="handleCouponAction()"
                                class="bg-[#B4771E] text-white px-6 h-[44px] lg:h-[52px] whitespace-nowrap text-base md:text-lg transition rounded-sm">
                                {{ session()->has('applied_coupon_code') ? 'Remove Coupon' : 'Apply Coupon' }}
                            </button>
                        </div>
                    </div>
                    <!-- Price Details -->

                <div class="border border-[#D5D5D5] p-3 md:p-4">
                    <h3 class="text-lg font-medium text-[#131615]">
                        Price Details
                    </h3>
                    <div class="border-t border-[#D5D5D5] mt-3 pt-3 space-y-3 md:space-y-4 text-[14px]">
                        <div class="flex justify-between text-base sm:text-lg ">
                            <span class="font-medium text-[#131615]">Subtotal</span>
                            <span class="font-normal text-[#3D403F]">₹{{ number_format($subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-base sm:text-lg {{ $discount > 0 ? '' : 'hidden' }}" id="checkoutDiscountRow">
                            <span class="font-medium text-[#131615]">Discount</span>
                            <span class="font-normal text-[#3D403F]" id="checkoutDiscountValue">-₹{{ number_format($discount, 0) }}</span>
                        </div>
                        {{--
                        <div class="flex justify-between text-base sm:text-lg">
                            <span class="font-medium text-[#131615]">Shipping</span>
                            <span class="font-normal text-[#3D403F]">{{ $shipping > 0 ? '₹' . number_format($shipping, 0) : 'Free' }}</span>
                        </div>
                        --}}
                        {{--
                        <div class="flex justify-between text-base sm:text-lg">
                            <span class="font-medium text-[#131615]">Estimated Tax</span>
                            <span class="font-normal text-[#3D403F]">₹0</span>
                        </div>
                        --}}
                    </div>
                    <div class="border-t border-[#D5D5D5] mt-4 pt-4 flex justify-between">
                        <span class="font-medium text-lg lg:text-xl">
                            Total
                        </span>
                        <span id="checkoutTotalValue" class="font-bold text-[#B4771E] text-lg md:text-[22px] lg:text-[24px] md:leading-[22px] lg:leading-[24px]">
                            ₹{{ number_format($total, 0) }}
                        </span>
                    </div>
                    <button id="placeOrderBtn" onclick="startPaymentFlow()"
                        class="w-full bg-[#B4771E] text-white text-lg font-medium h-[52px] transition common-btn mt-5 md:mt-7">
                       <span>Place Order</span>
                    </button>
                </div>
<!-- ── PAYMENT VERIFYING LOADER ── -->
<div id="paymentLoader" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="flex flex-col items-center gap-6 px-8 py-10 bg-white rounded-2xl shadow-2xl max-w-[340px] w-full mx-4 text-center">
        <!-- Spinner -->
        <div class="relative w-20 h-20">
            <svg class="animate-spin w-20 h-20 text-[#B4771E]" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="25" cy="25" r="20" stroke="#F3E3C8" stroke-width="5"/>
                <path d="M25 5 A20 20 0 0 1 45 25" stroke="#B4771E" stroke-width="5" stroke-linecap="round"/>
            </svg>
            <!-- Coin icon center -->
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-8 h-8 text-[#B4771E]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33"/>
                </svg>
            </div>
        </div>
        <div>
            <h3 class="text-xl font-semibold text-[#131615] mb-2">Verifying Payment</h3>
            <p class="text-sm text-[#757575] leading-relaxed">Please wait while we confirm your payment. Do not close or refresh the page.</p>
        </div>
        <!-- Animated dots -->
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#B4771E] animate-bounce" style="animation-delay:0s"></span>
            <span class="w-2 h-2 rounded-full bg-[#B4771E] animate-bounce" style="animation-delay:0.15s"></span>
            <span class="w-2 h-2 rounded-full bg-[#B4771E] animate-bounce" style="animation-delay:0.3s"></span>
        </div>
    </div>
</div>

<!-- Overlay -->

<div
    id="successModal"
    class="fixed inset-0 z-40 hidden bg-black/50 p-4 !mt-0 overflow-y-auto ">
    <div class="min-h-full flex items-center justify-center !mt-0">
        <!-- Modal -->

        <div
            class="relative w-full max-w-[720px] bg-white rounded-[8px] p-4 sm:p-6 md:p-6 max-h-[90vh]
            overflow-y-auto scrollbar-hide">
            <!-- Close -->

            <button
                onclick="closeSuccessModal()"
                class="absolute top-4 right-4 text-[32px] text-[#131615]">
                &times;
            </button>
            <!-- Success Icon -->

            <div class="flex justify-center">
                    <img src="{{ asset('website/assets/images/rightcheck.png') }}" alt="" class="w-[80px] md:w-[90px] text-red-500">
            </div>
            <!-- Heading -->

            <h2
                class="text-center font-moglan text-[30px] sm:text-[40px]
                leading-tight text-[#131615] mt-2">
                Order Placed Successfully!
            </h2>
            <!-- Text -->

            <p class="text-center text-[#3D403F] text-sm md:text-base font-normal
                max-w-[520px] mx-auto mt-3">
                Thank you for shopping with Chetan Imitation.
                Your order has been confirmed and is now being processed.
            </p>
            <!-- Order Details -->

            <div class="border border-[#D5D5D5] mt-4 p-4 rounded-sm">
                <!-- Row -->

                <div class="flex justify-between items-center border-b border-[#D5D5D5] pb-3">
                    <div class="flex items-center gap-[15px]">
                        <div class="w-10 h-10 rounded-full bg-[#B4771E]/10 flex justify-center items-center">
                        <svg width="16" height="20" viewBox="0 0 19 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.25 14.75V11.6875C18.25 10.6432 17.8352 9.64169 17.0967 8.90327C16.3583 8.16484 15.3568 7.75 14.3125 7.75H12.5625C12.2144 7.75 11.8806 7.61172 11.6344 7.36558C11.3883 7.11944 11.25 6.7856 11.25 6.4375V4.6875C11.25 3.64321 10.8352 2.64169 10.0967 1.90327C9.35831 1.16484 8.35679 0.75 7.3125 0.75H5.125M5.125 15.625H13.875M5.125 19.125H9.5M7.75 0.75H2.0625C1.338 0.75 0.75 1.338 0.75 2.0625V22.1875C0.75 22.912 1.338 23.5 2.0625 23.5H16.9375C17.662 23.5 18.25 22.912 18.25 22.1875V11.25C18.25 8.46523 17.1438 5.79451 15.1746 3.82538C13.2055 1.85625 10.5348 0.75 7.75 0.75Z" stroke="#B4771E" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        </div>
                        <span class="text-base md:text-lg font-semibold">
                            Order ID
                        </span>
                    </div>
                    <span id="successOrderId" class="text-[#3D403F] text-base sm:text-lg font-mono font-semibold text-end">
                        -
                    </span>
                </div>
                <!-- Row -->

                <div class="flex justify-between items-center border-b border-[#D5D5D5] py-2 md:py-3">
                    <div class="flex items-center gap-[15px]">
                      <div class="w-10 h-10 rounded-full bg-[#B4771E]/10 flex justify-center items-center text-[#B4771E]">
                        
                    <svg width="16" height="16" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.75 6.875H7.75M14.75 10.375H7.75M11.25 17.375L7.75 13.875H9.5C10.4283 13.875 11.3185 13.5063 11.9749 12.8499C12.6313 12.1935 13 11.3033 13 10.375C13 9.44674 12.6313 8.5565 11.9749 7.90013C11.3185 7.24375 10.4283 6.875 9.5 6.875M21.75 11.25C21.75 12.6289 21.4784 13.9943 20.9507 15.2682C20.4231 16.5421 19.6496 17.6996 18.6746 18.6746C17.6996 19.6496 16.5421 20.4231 15.2682 20.9507C13.9943 21.4784 12.6289 21.75 11.25 21.75C9.87112 21.75 8.50574 21.4784 7.23182 20.9507C5.95791 20.4231 4.80039 19.6496 3.82538 18.6746C2.85036 17.6996 2.07694 16.5421 1.54926 15.2682C1.02159 13.9943 0.75 12.6289 0.75 11.25C0.75 8.46523 1.85625 5.79451 3.82538 3.82538C5.79451 1.85625 8.46523 0.75 11.25 0.75C14.0348 0.75 16.7055 1.85625 18.6746 3.82538C20.6438 5.79451 21.75 8.46523 21.75 11.25Z" stroke="#B4771E" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                        </div>
                        <span class="text-base md:text-lg font-semibold">
Order Amount
                        </span>
                    </div>
                    <span id="successOrderAmount" class="text-[#3D403F] text-base font-semibold text-end">
                        ₹{{ number_format($total, 0) }}
                    </span>
                </div>
                <!-- Row -->

                <div class="flex justify-between items-center pt-3">
                    <div class="flex items-center gap-[15px]">
                      <div class="w-10 h-10 rounded-full bg-[#B4771E]/10 flex justify-center items-center">
                        
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>


                        </div>
                        <span class="text-base md:text-lg font-semibold">
                         Estimated Delivery
                        </span>
                    </div>
                    <span class="text-[#3D403F] text-base text-end">
                        4–7 Business Days
                    </span>
                </div>
            </div>
            <!-- Info -->

            <div
                class="bg-[#B4771E]/10 p-4 rounded-[5px] mt-3 flex gap-[15px] items-start">
               
               <div>
                 <svg width="40" height="40" viewBox="0 0 58 58" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_292_2508)">
                <path d="M29 0.150391C44.9078 0.150391 57.8496 13.0922 57.8496 29C57.8496 44.9078 44.9078 57.8496 29 57.8496C13.0922 57.8496 0.150391 44.9078 0.150391 29C0.150391 13.0922 13.0922 0.150391 29 0.150391ZM29 2.56836C14.4257 2.56836 2.56836 14.4257 2.56836 29C2.56836 43.5743 14.4257 55.4316 29 55.4316C43.5743 55.4316 55.4316 43.5743 55.4316 29C55.4316 14.4257 43.5743 2.56836 29 2.56836ZM15.3711 17.1992H42.6289C44.3692 17.1992 45.7852 18.6152 45.7852 20.3555V37.6445C45.7852 39.3848 44.3692 40.8018 42.6289 40.8018H15.3711C13.6307 40.8018 12.2139 39.3848 12.2139 37.6445V20.3555C12.2139 18.6152 13.6308 17.1992 15.3711 17.1992ZM43.123 21.0283L29.752 31.6465C29.5319 31.8214 29.2659 31.9082 29 31.9082C28.7342 31.9082 28.4682 31.8212 28.248 31.6465L14.876 21.0283L14.6328 20.835V37.6445C14.633 37.8401 14.7113 38.0278 14.8496 38.166C14.9879 38.3042 15.1756 38.3826 15.3711 38.3828H42.6289C42.8244 38.3826 43.0121 38.3043 43.1504 38.166C43.2887 38.0278 43.367 37.8401 43.3672 37.6445V20.835L43.123 21.0283ZM17.3262 19.8848L28.9062 29.0811L29 29.1553L29.0938 29.0811L40.6729 19.8848L41.0098 19.6172H16.9902L17.3262 19.8848Z" fill="#B4771E" stroke="#F8F2E9" stroke-width="0.3"/>
                </g>
                <defs>
                <clipPath id="clip0_292_2508">
                <rect width="58" height="58" fill="white"/>
                </clipPath>
                </defs>
                </svg>
               </div>

                <p class="text-[#131615] text-base md:text-lg">
                    A confirmation email and order details have been sent to your
                    registered email address and mobile number.
                </p>
            </div>
            <!-- Buttons -->

            <button
                onclick="window.location.href='{{ route('customer.profile') }}'"
                class="w-full h-[52px] md:h-[68px] bg-[#B4771E] text-white
                text-base md:text-[22px] md:leading-[24px] mt-10">
                View My Orders
            </button>
            <button
                onclick="window.location.href='{{ route('shop-by-category') }}'"
                class="common-btn mt-4 md:mt-5 w-full border-2 border-[#131615] text-[#131615] font-medium transition common-btn bg-transparent hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E]">
                Continue Shopping
            </button>
        </div>
    </div>
</div>

<!-- Failure Modal -->
<div
    id="failureModal"
    class="fixed inset-0 z-40 hidden bg-black/50 p-4 !mt-0 overflow-y-auto ">
    <div class="min-h-full flex items-center justify-center !mt-0">
        <!-- Modal -->
        <div
            class="relative w-full max-w-[720px] bg-white rounded-[8px] p-4 sm:p-6 md:p-[33px]
            max-h-[90vh] overflow-y-auto scrollbar-hide">
            <!-- Close -->

            <button
                onclick="closeFailureModal()"
                class="absolute top-4 right-4 text-[32px] text-[#131615]">
                &times;
            </button>
            <!-- Failure Icon -->

            <div class="flex justify-center">
                <svg class="w-[120px] md:w-[150px] text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <!-- Heading -->

            <h2
                class=" text-red-600 font-moglan text-[30px] sm:text-[40px]
                leading-tight mt-2 text-center">
                Payment Failed!
            </h2>
            <!-- Text -->

            <p class="text-center text-[#3D403F] text-sm md:text-base font-normal
                max-w-[520px] mx-auto mt-3">
                We were unable to process your payment. Please try again or select a different payment option.
            </p>
            <!-- Error Info -->

            <div
                class="bg-red-50 border border-red-200 p-3 rounded-[5px] mt-4 flex gap-[15px] items-start">
                <p id="failureReason" class="text-red-700 text-base">
                    The payment request was cancelled or declined.
                </p>
            </div>
            <!-- Buttons -->

            <button
                onclick="retryPaymentFlow()"
                class="w-full common-btn mt-7">
                Retry Payment
            </button>
            <button
                onclick="closeFailureModal()"
                class="common-btn mt-4 md:mt-5 w-full border-2 border-[#131615] text-[#131615] font-medium transition common-btn bg-transparent hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E]">
                Cancel
            </button>
        </div>
    </div>
</div>
                    <!-- Why Shop -->

                    <div class="border border-[#D5D5D5] p-4 md:p-4">
                         <h3 class="text-lg md:text-xl font-medium text-[#131615] mb-5">
                            Why Shop With Us?
                        </h3>
                         <div class="space-y-3 text-[#131615]">
                             <div class="flex gap-3 text-base md:text-lg font-normal">
                                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-[#B4771E] mt-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>

                                <span>100% Secure Checkout</span>
                            </div>
                             <div class="flex gap-3 text-base md:text-lg font-normal">
                                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-[#B4771E] mt-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>

                                <span>Premium Quality jewelry</span>
                            </div>
                             <div class="flex gap-3 text-base md:text-lg font-normal">
                                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-[#B4771E] mt-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>

                                <span>Easy Return and Exchange</span>
                            </div>
                             <div class="flex gap-3 text-base md:text-lg font-normal">
                                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 text-[#B4771E] mt-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>

                                <span>Secure Packaging</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>
<!-- =========================
YOU MAY ALSO LIKE
========================= -->
@if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
<section class="section-space-bottom">
    <div class="container-1440">
        <!-- Heading -->

        <div class="text-center mb-7">
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
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const CHECKOUT_CART_REMOVE_URL = '{{ route('cart.remove') }}';
const CHECKOUT_WISHLIST_TOGGLE_URL = '{{ route('wishlist.toggle') }}';
const CHECKOUT_LOGIN_URL = '{{ route('login') }}?intended={{ urlencode(route('checkout')) }}';
const CHECKOUT_CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const modal = document.getElementById("addressModal");

function resetAddressForm() {
    document.getElementById('addr_name').value = '';
    document.getElementById('addr_phone').value = '';
    document.getElementById('addr_alternate_phone').value = '';
    document.getElementById('addr_email').value = '{{ auth('customer')->user()->email ?? '' }}';
    document.getElementById('addr_address').value = '';
    document.getElementById('addr_city').value = '';
    document.getElementById('addr_state').value = '';
    document.getElementById('home').checked = true;
    document.getElementById('addr_is_default').checked = false;
    clearAddrErrors();
}

function openModal() {
    modal.classList.remove("hidden");

    document.documentElement.classList.add("modal-open"); // html
    document.body.classList.add("modal-open"); // body
}

function closeModal() {
    modal.classList.add("hidden");

    document.documentElement.classList.remove("modal-open");
    document.body.classList.remove("modal-open");
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
    window.location.href = '{{ route('shop-by-category') }}';
}

successModal.addEventListener("click",(e)=>{
    if(e.target === successModal){
        closeSuccessModal();
    }
});

const failureModal = document.getElementById("failureModal");

function openFailureModal(reason){
    const reasonEl = document.getElementById("failureReason");
    if (reasonEl) {
        reasonEl.textContent = reason || 'Payment transaction was cancelled or declined.';
    }
    failureModal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
}

function closeFailureModal(){
    failureModal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
}

failureModal.addEventListener("click",(e)=>{
    if(e.target === failureModal){
        closeFailureModal();
    }
});

function setAddrFieldError(field, message) {
    const input = document.querySelector(`.addr-input[name="${field}"]`);
    const error = document.querySelector(`.addr-error[data-error-for="${field}"]`);
    if (input) {
        if (message) {
            input.classList.add('border-red-500');
        } else {
            input.classList.remove('border-red-500');
        }
    }
    if (error) {
        error.textContent = message || '';
    }
}

function clearAddrErrors() {
    document.querySelectorAll('.addr-error').forEach(el => el.textContent = '');
    document.querySelectorAll('.addr-input').forEach(el => el.classList.remove('border-red-500'));
    const successBox = document.getElementById('addressSuccess');
    const failureBox = document.getElementById('addressFailure');
    if (successBox) successBox.classList.add('hidden');
    if (failureBox) failureBox.classList.add('hidden');
}

function validateAddressForm() {
    const errors = {};
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneRegex = /^[0-9]{10}$/;

    const name = document.getElementById('addr_name').value.trim();
    const phone = document.getElementById('addr_phone').value.trim();
    const alternatePhone = document.getElementById('addr_alternate_phone').value.trim();
    const email = document.getElementById('addr_email').value.trim();
    const address = document.getElementById('addr_address').value.trim();
    const city = document.getElementById('addr_city').value.trim();
    const state = document.getElementById('addr_state').value;

    if (!name) errors.name = 'Please enter your full name.';
    if (!phone) errors.phone = 'Please enter your mobile number.';
    else if (!phoneRegex.test(phone)) errors.phone = 'Please enter a valid 10 digit mobile number.';

    if (alternatePhone && !phoneRegex.test(alternatePhone)) {
        errors.alternate_phone = 'Please enter a valid 10 digit alternate mobile number.';
    }

    if (email && !emailRegex.test(email)) {
        errors.email = 'Please enter a valid email address.';
    }

    if (!address) errors.address = 'Please enter Flat/House/Building Name.';
    if (!city) errors.city = 'Please enter town / city.';
    if (!state) errors.state = 'Please select state.';

    Object.keys(errors).forEach(field => {
        setAddrFieldError(field, errors[field]);
    });

    return Object.keys(errors).length === 0;
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.addr-input').forEach(input => {
        input.addEventListener('input', function() {
            setAddrFieldError(this.getAttribute('name'), '');
        });
    });

    const phoneInputs = ['addr_phone', 'addr_alternate_phone'];
    phoneInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
            });
        }
    });
});

function createAddressCardHtml(addr) {
    const isActive = addr.is_default ? 'active-address default-address-card' : '';
    const isChecked = addr.is_default ? 'checked' : '';
    const imgName = addr.type === 'work' ? 'home1.png' : 'home.png';
    const defaultBadge = addr.is_default ? `
        <span class="bg-[#B4771E29] text-[#B4771E] text-sm sm:text-base px-2 sm:px-[15px] py-[4px] font-semibold rounded-[2px] leading-[20px] default-badge">
            Default
        </span>
    ` : '';
    const setAsDefaultButton = !addr.is_default ? `
        <button onclick="setAddressAsDefault(${addr.id}, event)" class="w-full text-left px-4 py-2 text-sm text-[#131615] hover:bg-gray-100 transition set-default-btn">
            Set as Default
        </button>
    ` : '';

    return `
        <div class="address-card border-b border-[#D5D5D5] px-3 md:px-4 py-3 md:py-4 cursor-pointer bg-white text-[#131615] ${isActive}" data-address-id="${addr.id}">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <input type="radio" name="selected_address" class="address-radio accent-[#B4771E] w-[18px] h-[18px] mr-1" ${isChecked} />
                        <img src="{{ asset('website/assets/images') }}/${imgName}" class="address-icon" />
                        <span class="text-sm sm:text-base lg:text-lg font-normal text-[#131615]">
                            Deliver To:
                        </span>
                        <span class="font-semibold text-sm sm:text-base lg:text-lg text-[#131615] customer-name-phone">
                            ${addr.name}, ${addr.phone}
                        </span>
                        ${defaultBadge}
                    </div>
                    <p class="mt-2 text-sm sm:text-base text-[#3D403F] address-text">
                        ${addr.address}, ${addr.city}, ${addr.state}
                    </p>
                </div>
                <div class="relative address-menu-container">
                    <button class="w-6 h-6 flex justify-center items-center address-menu-btn p-2 hover:bg-black/5 rounded-full transition focus:outline-none">
                        <i class="fa-solid fa-ellipsis text-[#3D403F]"></i>
                    </button>
                    <div class="absolute right-0 mt-1 w-36 bg-white border border-[#D5D5D5] shadow-md rounded z-10 hidden address-dropdown text-left">
                        ${setAsDefaultButton}
                        <button onclick="deleteAddress(${addr.id}, event)" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function refreshAddressSelection(selectedId) {
    document.querySelectorAll('.address-card').forEach(card => {
        const addrId = parseInt(card.dataset.addressId);
        const radio = card.querySelector('.address-radio');

        if (addrId === parseInt(selectedId)) {
            card.classList.add('active-address');
            if (radio) {
                radio.checked = true;
            }
        } else {
            card.classList.remove('active-address');
            if (radio) {
                radio.checked = false;
            }
        }
    });
}

document.addEventListener('click', function(e) {
    document.querySelectorAll('.address-dropdown').forEach(dropdown => {
        const btn = dropdown.previousElementSibling;
        if (!dropdown.contains(e.target) && (!btn || !btn.contains(e.target))) {
            dropdown.classList.add('hidden');
        }
    });

    const btn = e.target.closest('.address-menu-btn');
    if (btn) {
        e.stopPropagation();
        const dropdown = btn.nextElementSibling;
        const wasHidden = dropdown.classList.contains('hidden');
        document.querySelectorAll('.address-dropdown').forEach(d => d.classList.add('hidden'));
        if (wasHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    const card = e.target.closest('.address-card');
    if (card && !e.target.closest('.address-menu-container')) {
        const addrId = card.dataset.addressId;
        refreshAddressSelection(addrId);
    }
});

function setAddressAsDefault(addressId, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('{{ route('checkout.address.set-default') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ address_id: addressId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            showCheckoutToast(data.message || 'Default address updated.');

            const oldDefault = document.querySelector('.default-address-card');
            if (oldDefault) {
                oldDefault.classList.remove('default-address-card');
                const badge = oldDefault.querySelector('.default-badge');
                if (badge) badge.remove();

                const oldDefaultId = oldDefault.dataset.addressId;
                const dropdownMenu = oldDefault.querySelector('.address-dropdown');
                if (dropdownMenu && !dropdownMenu.querySelector('.set-default-btn')) {
                    const btnHtml = `<button onclick="setAddressAsDefault(${oldDefaultId}, event)" class="w-full text-left px-4 py-2 text-sm text-[#131615] hover:bg-gray-100 transition set-default-btn">Set as Default</button>`;
                    dropdownMenu.insertAdjacentHTML('afterbegin', btnHtml);
                }
            }

            const newDefault = document.querySelector(`.address-card[data-address-id="${addressId}"]`);
            if (newDefault) {
                newDefault.classList.add('default-address-card');

                const namePhoneSpan = newDefault.querySelector('.customer-name-phone');
                if (namePhoneSpan && !newDefault.querySelector('.default-badge')) {
                    namePhoneSpan.insertAdjacentHTML('afterend', `
                        <span class="bg-[#B4771E29] text-[#B4771E] text-sm sm:text-base px-2 sm:px-[15px] py-[4px] font-semibold rounded-[2px] leading-[20px] default-badge">
                            Default
                        </span>
                    `);
                }

                const setDefaultBtn = newDefault.querySelector('.set-default-btn');
                if (setDefaultBtn) setDefaultBtn.remove();
            }

            refreshAddressSelection(addressId);
        } else {
            showCheckoutToast(data.message || 'Failed to update default address.', false);
        }
    })
    .catch(err => {
        console.error('Error setting default address:', err);
        showCheckoutToast('Something went wrong.', false);
    });
}

function deleteAddress(addressId, event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    if (!confirm('Are you sure you want to delete this address?')) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('{{ route('checkout.address.delete') }}', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ address_id: addressId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            showCheckoutToast(data.message || 'Address deleted.');

            const card = document.querySelector(`.address-card[data-address-id="${addressId}"]`);
            const wasDefault = card ? card.classList.contains('default-address-card') : false;
            const wasActive = card ? card.classList.contains('active-address') : false;

            if (card) {
                card.remove();
            }

            const remainingCards = document.querySelectorAll('.address-card');
            if (remainingCards.length === 0) {
                const list = document.getElementById('addressesCardsList');
                if (list) {
                    list.innerHTML = `
                        <div id="noAddressesPlaceholder" class="p-5 text-center text-gray-500">
                            No saved addresses. Please add a new delivery address to proceed.
                        </div>
                    `;
                }
                return;
            }

            if (wasDefault) {
                const latestCard = remainingCards[0];
                const latestId = latestCard.dataset.addressId;

                latestCard.classList.add('default-address-card');
                const namePhoneSpan = latestCard.querySelector('.customer-name-phone');
                if (namePhoneSpan && !latestCard.querySelector('.default-badge')) {
                    namePhoneSpan.insertAdjacentHTML('afterend', `
                        <span class="bg-[#B4771E] text-white text-sm sm:text-base lg:text-lg px-2 sm:px-[15px] py-[6px] font-semibold rounded-[2px] leading-[20px] default-badge">
                            Default
                        </span>
                    `);
                }

                const setDefaultBtn = latestCard.querySelector('.set-default-btn');
                if (setDefaultBtn) setDefaultBtn.remove();

                refreshAddressSelection(latestId);
            } else if (wasActive) {
                const defaultCard = document.querySelector('.default-address-card');
                if (defaultCard) {
                    refreshAddressSelection(defaultCard.dataset.addressId);
                }
            }
        } else {
            showCheckoutToast(data.message || 'Failed to delete address.', false);
        }
    })
    .catch(err => {
        console.error('Error deleting address:', err);
        showCheckoutToast('Something went wrong.', false);
    });
}

function saveCustomerAddress(e) {
    e.preventDefault();
    clearAddrErrors();

    if (!validateAddressForm()) {
        return;
    }

    const btn = document.getElementById('saveAddressBtn');
    btn.disabled = true;
    btn.innerText = 'Saving...';

    const name = document.getElementById('addr_name').value.trim();
    const phone = document.getElementById('addr_phone').value.trim();
    const alternate_phone = document.getElementById('addr_alternate_phone').value.trim();
    const email = document.getElementById('addr_email').value.trim();
    const address = document.getElementById('addr_address').value.trim();
    const city = document.getElementById('addr_city').value.trim();
    const state = document.getElementById('addr_state').value;
    const type = document.querySelector('input[name="addressType"]:checked').value;
    const is_default = document.getElementById('addr_is_default').checked ? 1 : 0;

    const failureBox = document.getElementById('addressFailure');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('{{ route('checkout.address.save') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            name,
            phone,
            alternate_phone,
            email,
            address,
            city,
            state,
            type,
            is_default
        })
    })
    .then(r => {
        if (!r.ok && r.status !== 422) {
            throw new Error('Server error: HTTP ' + r.status);
        }
        return r.json();
    })
    .then(data => {
        if (data.errors) {
            Object.keys(data.errors).forEach(field => {
                setAddrFieldError(field, data.errors[field][0]);
            });
            btn.disabled = false;
            btn.innerText = 'Save Address';
        } else if (data.status === 'success') {
            showCheckoutToast(data.message || 'Address saved successfully.');

            if (data.address.is_default) {
                const oldDefault = document.querySelector('.default-address-card');
                if (oldDefault) {
                    oldDefault.classList.remove('default-address-card');
                    const badge = oldDefault.querySelector('.default-badge');
                    if (badge) badge.remove();

                    const oldDefaultId = oldDefault.dataset.addressId;
                    const dropdownMenu = oldDefault.querySelector('.address-dropdown');
                    if (dropdownMenu && !dropdownMenu.querySelector('.set-default-btn')) {
                        const btnHtml = `<button onclick="setAddressAsDefault(${oldDefaultId}, event)" class="w-full text-left px-4 py-2 text-sm text-[#131615] hover:bg-gray-100 transition set-default-btn">Set as Default</button>`;
                        dropdownMenu.insertAdjacentHTML('afterbegin', btnHtml);
                    }
                }
            }

            const list = document.getElementById('addressesCardsList');
            const noPlaceholder = document.getElementById('noAddressesPlaceholder');
            if (noPlaceholder) {
                noPlaceholder.remove();
            }

            const cardHtml = createAddressCardHtml(data.address);
            if (list) {
                list.insertAdjacentHTML('afterbegin', cardHtml);
            }

            refreshAddressSelection(data.address.id);

            closeModal();
            btn.disabled = false;
            btn.innerText = 'Save Address';
        } else {
            if (failureBox) {
                failureBox.classList.remove('hidden');
                failureBox.textContent = data.message || 'Something went wrong.';
            }
            btn.disabled = false;
            btn.innerText = 'Save Address';
        }
    })
    .catch(err => {
        console.error('Error saving address:', err);
        if (failureBox) {
            failureBox.classList.remove('hidden');
            failureBox.textContent = 'Failed to save address. Please try again.';
        }
        btn.disabled = false;
        btn.innerText = 'Save Address';
    });
}


function showCheckoutToast(message, isSuccess = true) {
    if (window.showWishlistToast) {
        window.showWishlistToast(message, isSuccess);
        return;
    }
    alert(message);
}

function removeCheckoutItem(cartItemId) {
    fetch(CHECKOUT_CART_REMOVE_URL, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CHECKOUT_CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ cart_item_id: cartItemId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            if (window.updateCartBadge) window.updateCartBadge(data.count);
            window.location.reload();
            return;
        }
        showCheckoutToast(data.message || 'Failed to remove item.', false);
    })
    .catch(err => {
        console.error('removeCheckoutItem error:', err);
        showCheckoutToast('Something went wrong. Please try again.', false);
    });
}

function moveCheckoutItemToWishlist(cartItemId, productId, variantId, btn) {
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }

    const isLoggedIn = {{ auth('customer')->check() ? 'true' : 'false' }};
    if (!isLoggedIn) {
        window.location.href = CHECKOUT_LOGIN_URL;
        return;
    }

    fetch(CHECKOUT_WISHLIST_TOGGLE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CHECKOUT_CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            product_variant_id: variantId || null
        })
    })
    .then(r => r.json())
    .then(data => {
        return fetch(CHECKOUT_CART_REMOVE_URL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CHECKOUT_CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ cart_item_id: cartItemId }),
        }).then(r => r.json()).then(cartData => ({ data, cartData }));
    })
    .then(({ data, cartData }) => {
        if (cartData.status === 'success') {
            if (window.updateCartBadge) window.updateCartBadge(cartData.count);
            if (window.updateWishlistBadge) window.updateWishlistBadge(data.count);
            window.location.reload();
            return;
        }
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        showCheckoutToast(cartData.message || 'Failed to move item.', false);
    })
    .catch(err => {
        console.error('moveCheckoutItemToWishlist error:', err);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        showCheckoutToast('Something went wrong. Please try again.', false);
    });
}

let currentRzpInstance = null;
let currentRazorpayOrderId = null;
let paymentCompleted = false;

function selectPaymentMethod(method) {
    const onlineOption = document.getElementById('onlinePaymentOption');
    const codOption = document.getElementById('codPaymentOption');
    const hiddenInput = document.getElementById('selectedPaymentMethod');

    hiddenInput.value = method;

    if (method === 'online') {
        onlineOption.classList.add('border-[#B4771E]', 'bg-[#B4771E0D]');
        onlineOption.classList.remove('border-[#D5D5D5]', 'bg-white');

        codOption.classList.remove('border-[#B4771E]', 'bg-[#B4771E0D]/10');
        codOption.classList.add('border-[#D5D5D5]', 'bg-white');
    } else {
        codOption.classList.add('border-[#B4771E]', 'bg-[#B4771E0D]/10');
        codOption.classList.remove('border-[#D5D5D5]', 'bg-white');

        onlineOption.classList.remove('border-[#B4771E]', 'bg-[#B4771E0D]');
        onlineOption.classList.add('border-[#D5D5D5]', 'bg-white');
    }
}

function handleCouponAction() {
    const input = document.getElementById('couponCodeInput');
    const btn = document.getElementById('couponActionBtn');
    const code = input.value.trim();

    if (!code) {
        showCheckoutToast('Please enter a coupon code.', false);
        return;
    }

    const isApply = btn.textContent.trim() === 'Apply Coupon';
    const url = isApply ? '{{ route('checkout.coupon.apply') }}' : '{{ route('checkout.coupon.remove') }}';
    const bodyData = isApply ? JSON.stringify({ code: code }) : JSON.stringify({});

    btn.disabled = true;
    btn.textContent = isApply ? 'Applying...' : 'Removing...';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CHECKOUT_CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: bodyData
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(err => { throw err; });
        }
        return res.json();
    })
    .then(data => {
        if (data.status === 'success') {
            showCheckoutToast(data.message || 'Coupon action completed.');
            if (isApply) {
                input.disabled = true;
                input.classList.add('bg-gray-100', 'cursor-not-allowed');
                btn.textContent = 'Remove Coupon';
                btn.disabled = false;

                // Show discount row
                const discountRow = document.getElementById('checkoutDiscountRow');
                if (discountRow) {
                    discountRow.classList.remove('hidden');
                }
                const discountVal = document.getElementById('checkoutDiscountValue');
                if (discountVal) {
                    discountVal.textContent = data.discount_label;
                }
                const totalVal = document.getElementById('checkoutTotalValue');
                if (totalVal) {
                    totalVal.textContent = data.total_label;
                }
            } else {
                input.value = '';
                input.disabled = false;
                input.classList.remove('bg-gray-100', 'cursor-not-allowed');
                btn.textContent = 'Apply Coupon';
                btn.disabled = false;

                // Hide discount row
                const discountRow = document.getElementById('checkoutDiscountRow');
                if (discountRow) {
                    discountRow.classList.add('hidden');
                }
                const totalVal = document.getElementById('checkoutTotalValue');
                if (totalVal) {
                    totalVal.textContent = data.total_label;
                }
            }
        } else {
            throw new Error(data.message || 'Coupon action failed.');
        }
    })
    .catch(err => {
        console.error('Coupon action error:', err);
        btn.disabled = false;
        btn.textContent = isApply ? 'Apply Coupon' : 'Remove Coupon';
        showCheckoutToast(err.message || 'Something went wrong with the coupon.', false);
    });
}

function startPaymentFlow() {
    const activeCard = document.querySelector('.address-card.active-address');
    if (!activeCard) {
        showCheckoutToast('Please select a shipping address.', false);
        return;
    }
    const addressId = activeCard.dataset.addressId;

    const paymentMethodSelect = document.getElementById('selectedPaymentMethod');
    const paymentMethod = paymentMethodSelect ? paymentMethodSelect.value : 'online';

    const placeBtn = document.getElementById('placeOrderBtn');
    const originalText = placeBtn.innerHTML;
    placeBtn.disabled = true;
    placeBtn.innerHTML = `<span>Processing...</span>`;

    if (paymentMethod === 'cod') {
        fetch('{{ route('checkout.payment.cod') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CHECKOUT_CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ address_id: addressId })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw err; });
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('successOrderId').textContent = '#' + data.order.order_no;
                document.getElementById('successOrderAmount').textContent = '₹' + data.order.final_amount;
                
                openSuccessModal();
                placeBtn.disabled = false;
                placeBtn.innerHTML = originalText;
            } else {
                throw new Error(data.message || 'Order placement failed.');
            }
        })
        .catch(err => {
            console.error('COD placement error:', err);
            placeBtn.disabled = false;
            placeBtn.innerHTML = originalText;
            showCheckoutToast(err.message || 'Something went wrong while placing order.', false);
        });
    } else {
        paymentCompleted = false;
        fetch('{{ route('checkout.payment.initialize') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CHECKOUT_CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ address_id: addressId })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw err; });
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                currentRazorpayOrderId = data.order_id;
                
                const options = {
                    "key": data.key,
                    "amount": data.amount,
                    "currency": data.currency,
                    "name": "Chetan Imitation",
                    "description": "Order Payment (ORD: " + data.order.order_no + ")",
                    "order_id": data.order_id,
                    "method": {
                        "upi": true,
                        "card": true,
                        "netbanking": true,
                        "wallet": true
                    },
                    "handler": function (response) {
                        paymentCompleted = true;
                        verifyPayment(response, data.order);
                    },
                    "prefill": {
                        "name": data.prefill.name,
                        "email": data.prefill.email,
                        "contact": data.prefill.contact
                    },
                    "theme": {
                        "color": "#B4771E"
                    },
                    "modal": {
                        "ondismiss": function() {
                            handlePaymentDismissed();
                        }
                    }
                };
                
                currentRzpInstance = new Razorpay(options);
                currentRzpInstance.on('payment.failed', function (response) {
                    handlePaymentFailed(response.error);
                });
                currentRzpInstance.open();

                // Reset button
                placeBtn.disabled = false;
                placeBtn.innerHTML = originalText;
            } else {
                throw new Error(data.message || 'Initialization failed.');
            }
        })
        .catch(err => {
            console.error('Payment initialization error:', err);
            placeBtn.disabled = false;
            placeBtn.innerHTML = originalText;
            showCheckoutToast(err.message || 'Something went wrong while placing order.', false);
        });
    }
}

function showPaymentLoader() {
    const loader = document.getElementById('paymentLoader');
    loader.classList.remove('hidden');
    loader.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function hidePaymentLoader() {
    const loader = document.getElementById('paymentLoader');
    loader.classList.add('hidden');
    loader.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function verifyPayment(rzpResponse, orderDetail) {
    showPaymentLoader();

    fetch('{{ route('checkout.payment.verify') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CHECKOUT_CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            razorpay_payment_id: rzpResponse.razorpay_payment_id,
            razorpay_order_id: rzpResponse.razorpay_order_id,
            razorpay_signature: rzpResponse.razorpay_signature
        })
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(err => { throw err; });
        }
        return res.json();
    })
    .then(data => {
        if (data.status === 'success') {
            // Update order success modal values
            document.getElementById('successOrderId').textContent = '#' + data.order.order_no;
            document.getElementById('successOrderAmount').textContent = '₹' + data.order.final_amount;
            
            // Hide loader, open Success Modal
            hidePaymentLoader();
            openSuccessModal();
        } else {
            throw new Error(data.message || 'Verification failed.');
        }
    })
    .catch(err => {
        console.error('Payment verification error:', err);
        hidePaymentLoader();
        openFailureModal(err.message || 'Payment signature verification failed.');
    });
}

function handlePaymentDismissed() {
    if (paymentCompleted) return;
    if (currentRazorpayOrderId) {
        // Mark pending order as failed/declined on backend so it is not left orphan
        fetch('{{ route('checkout.payment.failed') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CHECKOUT_CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ razorpay_order_id: currentRazorpayOrderId })
        })
        .catch(err => console.error('Failed to notify cancellation:', err));
    }
    openFailureModal('Payment window closed before completing transaction.');
}

function handlePaymentFailed(error) {
    if (paymentCompleted) return;
    if (currentRazorpayOrderId) {
        fetch('{{ route('checkout.payment.failed') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CHECKOUT_CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ razorpay_order_id: currentRazorpayOrderId })
        })
        .catch(err => console.error('Failed to notify error:', err));
    }
    openFailureModal(error.description || 'Payment transaction failed.');
}

function retryPaymentFlow() {
    closeFailureModal();
    startPaymentFlow();
}

</script>
@endsection


