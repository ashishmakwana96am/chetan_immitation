@extends('layouts.website')

@section('title', 'My Orders | Chetan Imitation')

@section('content')
<div class="container-1440 section-space">
 
  <!-- Page Title (dynamic) -->
  <div class="text-center mb-10">
     <h2 id="pageTitle" class="font-moglan hero-title">My Orders</h2>
     <p id="pageSubtitle" class="hero-para">Track your orders, view purchase history, and manage your jewelry collections with ease.</p>
  </div>
 
  <div class="flex flex-col lg:flex-row gap-6 items-start">
 
    <!-- ══ SIDEBAR ══ -->
    <aside class="w-full lg:w-[360px] shrink-0 border border-gray-200 rounded-lg">
      <div class="m-5 pb-5 border-b border-[#D5D5D5]">
        <p class="text-base md:text-lg lg:text-[22px] font-semibold text-[#131615]">My Account</p>
      </div>
      <nav class="space-y-[10px] mb-[10px]">
        <button data-tab="orders" onclick="switchTab('orders',this)"
          class="tab-btn active w-full flex items-center gap-[10px] px-5 py-[15px] text-base md:text-lg lg:text-xl text-[#B4771E] font-medium text-left">
          <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          My Order
        </button>
        <button data-tab="addresses" onclick="switchTab('addresses',this)"
          class="tab-btn w-full flex items-center gap-3 px-5 py-3 text-base md:text-lg lg:text-xl text-[#3D403F] font-normal text-left">
          <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Addresses
        </button>
        <button data-tab="account" onclick="switchTab('account',this)"
          class="tab-btn w-full flex items-center gap-3 px-5 py-3 text-base md:text-lg lg:text-xl text-[#3D403F] font-normal text-left">
          <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Account Details
        </button>
        <button data-tab="logout" onclick="switchTab('logout',this)"
          class="tab-btn w-full flex items-center gap-3 px-5 py-3 text-base md:text-lg lg:text-xl  text-[#3D403F] font-normal text-left">
          <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
          </svg>
          Logout
        </button>
      </nav>
    </aside>
 
    <!-- ══ TAB PANELS ══ -->
    <div class="flex-1 min-w-0">
 
      <!-- ─── TAB: MY ORDERS ─────────────────────────── -->
      <div id="tab-orders" class="tab-panel">
          <!-- Search & Filter -->
          <div class="flex flex-col lg:flex-row gap-[19px] mb-[30px]">
              <div class="flex flex-1 border border-[#D5D5D5] bg-white overflow-hidden rounded-[7px]">
                  <input id="searchInput" type="text" placeholder="Search your orders here" oninput="filterOrders()" class="flex-1 px-5 py-3 md:py-4 text-base md:text-lg placeholder:text-lg text-[#131615] outline-none">
                  <button onclick="filterOrders()" class="bg-[#B4771E] hover:bg-[#ad741b] transition-all px-3 md:px-[30px] py-3 md:py-4 text-base md:text-lg text-white font-medium flex items-center gap-[10px]">
                      <i class="fa-solid fa-magnifying-glass text-lg"></i>
                     <span class="hidden md:block"> Search Orders</span>
                  </button>
              </div>
             <div class="relative w-full lg:w-[180px]">
                  <select id="statusFilter" onchange="filterOrders()" class="rounded-[7px] appearance-none w-full py-3 sm:py-4 px-5 border border-[#D5D5D5] bg-white text-base md:text-xl text-[#131615] outline-none">
                      <option value="all">All Orders</option>
                      <option value="Pending">Pending</option>
                      <option value="Approved">Approved</option>
                      <option value="Shipped">Shipped</option>
                      <option value="Out for delivery">Out for delivery</option>
                      <option value="Delivered">Delivered</option>
                      <option value="Cancelled">Cancelled</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                      <svg class="w-4 h-4 text-[#131615]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                  </div>
              </div>
          </div>
      
          <!-- Orders List -->
          <div id="orderList" class="space-y-4 md:space-y-5">
              <!-- Orders are dynamically rendered via javascript -->
          </div>
      
          <div id="pagination" class="w-full mt-6"></div>
      
          <!-- Empty State -->
          <div id="noOrders" class="hidden text-center section-space">
              <i class="fa-regular fa-clipboard text-[55px] text-[#cccccc]"></i>
              <h3 class="mt-4 text-[20px] font-medium text-[#3D403F]">No Orders Found</h3>
              <p class="text-[#888] mt-2">You haven't placed any orders yet.</p>
          </div>
      </div>
 
      <!-- ─── TAB: BILLING ADDRESS ──────────────────── -->
      <div id="tab-addresses" class="tab-panel hidden">
          <div class="flex justify-end mb-4">
              <button onclick="openAddrModal()" class="bg-[#B4771E] hover:bg-[#b67d1f] text-white text-sm md:text-lg font-medium px-4 md:px-5 h-[40px] transition flex gap-3 md:gap-[9px] items-center">
                  + Add New
              </button>
          </div>
          <!-- Address Wrapper -->
          <div id="addrList" class="border border-[#D5D5D5] bg-white overflow-hidden">
              <!-- Addresses are dynamically rendered via javascript -->
          </div>
      </div>
 
      <!-- ─── TAB: ACCOUNT DETAILS ───────────────────── -->
      <div id="tab-account" class="tab-panel hidden">
        <div>
          <!-- Avatar -->
          <div class="relative w-[90px] md:w-[120px] h-[90px] md:h-[120px] mb-6">
            <img id="avatarImg" src="https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=160&q=80"
              class="w-[90px] md:w-[120px] h-[90px] md:h-[120px] rounded-full object-cover border-2 border-gray-200" />
            <label class="absolute bottom-0 right-0 w-[30px] h-[30px] bg-[#B4771E] rounded-full flex items-center justify-center cursor-pointer">
              <span class="text-white text-sm leading-none">✎</span>
              <input type="file" accept="image/*" class="hidden" onchange="changeAvatar(event)" />
            </label>
          </div>
 
          <!-- Personal info -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10">
            <div>
              <label class="block text-base md:text-lg font-medium text-[#131615] mb-1.5">Full Name</label>
              <input type="text" id="acctName" value="{{ auth('customer')->user()->name }}" placeholder="Enter Your Full Name"
                class="w-full border border-[#D5D5D5] rounded px-3 py-2.5 text-sm md:text-base placeholder:text-sm placeholder:md:text-base text-[#131615] placeholder:text-[#757575]" />
              <p class="field-error text-sm text-red-600 mt-1 hidden" id="acctName-error"></p>
            </div>
            <div>
              <label class="block text-base md:text-lg font-medium text-[#131615] mb-1.5">Display name</label>
              <input type="text" id="acctDisplayName" value="{{ auth('customer')->user()->name }}"
                class="w-full border border-[#D5D5D5] rounded px-3 py-2.5 text-sm md:text-base placeholder:text-sm placeholder:md:text-base text-[#131615] placeholder:text-[#757575]" />
              <p class="text-sm md:text-base text-[#3D403F]">This will be how your name will be displayed in the account section and in reviews</p>
            </div>
            <div>
              <label class="block text-base md:text-lg font-medium text-[#131615] mb-1.5">Email address</label>
              <input type="email" id="acctEmail" value="{{ auth('customer')->user()->email }}" disabled
                class="w-full border border-[#D5D5D5] rounded px-3 py-2.5 text-sm md:text-base placeholder:text-sm placeholder:md:text-base text-[#131615] placeholder:text-[#757575] bg-gray-100 cursor-not-allowed" />
            </div>
            <div>
              <label class="block text-base md:text-lg font-medium text-[#131615] mb-1.5">Mobile Number</label>
              <input type="tel" id="acctPhone" value="{{ auth('customer')->user()->phone }}" placeholder="Enter Your Mobile Number"
                class="w-full border border-[#D5D5D5] rounded px-3 py-2.5 text-sm md:text-base placeholder:text-sm placeholder:md:text-base text-[#131615] placeholder:text-[#757575]" />
              <p class="field-error text-sm text-red-600 mt-1 hidden" id="acctPhone-error"></p>
            </div>
          </div>
 
          <!-- Password change -->
          <h3 class="font-moglan text-3xl font-normal text-[#131615] mb-4">Password change</h3>
          <div class="space-y-5 mb-9">
            <div>
              <label class="block text-base md:text-lg font-medium text-[#131615] mb-1.5">Current password (leave blank to leave unchanged)</label>
              <div class="relative">
                <input id="pw1" type="password" placeholder="Enter Current Password" autocomplete="new-password"
                  class="w-full border border-[#D5D5D5] rounded px-3 py-2.5 text-sm md:text-base placeholder:text-sm placeholder:md:text-base text-[#131615] placeholder:text-[#757575] pr-11" />
                <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700" style="background: none; border: none; padding: 0; outline: none; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;" data-target="pw1" tabindex="-1">
                  <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <p class="field-error text-sm text-red-600 mt-1 hidden" id="pw1-error"></p>
            </div>
            <div>
              <label class="block text-base md:text-lg font-medium text-[#131615] mb-1.5">New password (leave blank to leave unchanged)</label>
              <div class="relative">
                <input id="pw2" type="password" placeholder="Enter New Password" autocomplete="new-password"
                  class="w-full border border-[#D5D5D5] rounded px-3 py-2.5 text-sm md:text-base placeholder:text-sm placeholder:md:text-base text-[#131615] placeholder:text-[#757575] pr-11" />
                <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700" style="background: none; border: none; padding: 0; outline: none; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;" data-target="pw2" tabindex="-1">
                  <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <p class="field-error text-sm text-red-600 mt-1 hidden" id="pw2-error"></p>
            </div>
            <div>
              <label class="block text-base md:text-lg font-medium text-[#131615] mb-1.5">Confirm new password</label>
              <div class="relative">
                <input id="pw3" type="password" placeholder="Confirm New Password" autocomplete="new-password"
                  class="w-full border border-[#D5D5D5] rounded px-3 py-2.5 text-sm md:text-base placeholder:text-sm placeholder:md:text-base text-[#131615] placeholder:text-[#757575] pr-11" />
                <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700" style="background: none; border: none; padding: 0; outline: none; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;" data-target="pw3" tabindex="-1">
                  <svg class="eye-off" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <p class="field-error text-sm text-red-600 mt-1 hidden" id="pw3-error"></p>
            </div>
          </div>
          <button onclick="saveAccount(this)" class="common-btn">Save Changes</button>
          <p id="acctSaved" class="hidden text-xs text-green-600 mt-2 font-medium">✓ Changes saved successfully!</p>
        </div>
      </div>
 
      <!-- ─── TAB: LOGOUT ────────────────────────────── -->
      <div id="tab-logout" class="tab-panel hidden">
          <div class="max-w-[420px] mx-auto p-8 text-center">
              <div class="w-20 h-20 rounded-full bg-[#FFF7EA] flex items-center justify-center mx-auto mb-6">
                  <svg class="w-10 h-10 text-[#B4771E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                  </svg>
              </div>
              <h3 class="text-[24px] md:text-[28px] font-semibold text-[#131615] mb-3">Are you sure?</h3>
              <p class="text-[#757575] text-sm md:text-base leading-7 mb-8">
                  You are about to logout from your account.
              </p>
              <div class="flex items-center justify-center gap-4">
                  <button onclick="switchTab('orders', document.querySelector('[data-tab=orders]'))" class="h-[50px] px-8 border border-[#D5D5D5] text-[#131615] hover:border-[#B4771E] transition">
                      Cancel
                  </button>
                  <form action="{{ route('customer.logout') }}" method="POST" id="logout-form" class="inline">
                      @csrf
                      <button type="submit" class="h-[50px] px-8 bg-[#B4771E] hover:bg-[#9D6617] text-white font-medium transition">
                          Logout
                      </button>
                  </form>
              </div>
          </div>
      </div>
 
    </div><!-- /panels -->
  </div>
</div>
 
<!-- ── ORDER VIEW MODAL ── -->
<div id="orderModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
  <div class="bg-white rounded-sm w-full max-w-md p-6 shadow-xl">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-[#131615]">Order Details</h3>
      <button onclick="closeModal('orderModal')" class="text-[#131615] hover:text-gray-600 text-xl">✕</button>
    </div>
    <div id="orderModalBody" class="text-sm text-gray-600 space-y-2"></div>
    <button onclick="closeModal('orderModal')" class="mt-5 w-full bg-[#c48622] hover:bg-amber-800 text-white py-2.5 rounded text-sm font-medium transition-colors">Close</button>
  </div>
</div>
 
<!-- ── ADD/EDIT ADDRESS MODAL (copied from checkout.address) ── -->
<div id="addressModal" class="fixed inset-0 z-50 hidden bg-black/50 overflow-y-auto p-4">
    <div class="min-h-full flex items-center justify-center py-5">
        <div class="relative w-full max-w-[750px] bg-white rounded-[8px] p-4 sm:p-6 md:p-[24px] max-h-[90vh] border border-[#D5D5D5] overflow-y-auto scrollbar-none">
            
            <!-- Close Button -->
            <button onclick="closeModal()" class="absolute top-4 right-4 md:top-6 md:right-6 text-[35px] leading-none text-[#131615]">
                &times;
            </button>
            
            <!-- Heading -->
            <h2 id="addrModalTitle" class="text-[24px] md:text-[30px] leading-[24px] md:leading-[30px] font-medium text-[#131615] mb-[20px]">
                Deliver To
            </h2>
            
            <!-- Full Name -->
            <div class="mb-4">
                <label class="block text-base md:text-xl text-[#131615] mb-2 font-semibold">
                    Full Name <span class="text-red-600">*</span>
                </label>
                <input type="text" id="addr_name" name="name" placeholder="Enter Your Full Name" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="name"></p>
            </div>
            
            <!-- Mobile and Alt Phone -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-base md:text-xl text-[#131615] mb-2 font-semibold">
                        Mobile Number <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="addr_phone" name="phone" placeholder="Enter Your Mobile Number" maxlength="10" inputmode="numeric" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="phone"></p>
                </div>
                <div>
                     <label class="block text-base md:text-xl text-[#131615] mb-2 font-semibold">
                        Alternate Phone Number (Optional)
                    </label>
                    <input type="text" id="addr_alternate_phone" name="alternate_phone" placeholder="Enter Your Mobile Number" maxlength="10" inputmode="numeric" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="alternate_phone"></p>
                </div>
            </div>
            
            <!-- Email -->
            <div class="mb-4">
                 <label class="block text-base md:text-xl text-[#131615] mb-2 font-semibold">
                    Email address
                </label>
                <input type="email" id="addr_email" name="email" value="{{ auth('customer')->user()->email ?? '' }}" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="email"></p>
            </div>
            
            <!-- Address text -->
            <div class="mb-4">
                <label class="block text-base md:text-xl text-[#131615] mb-2 font-semibold">
                    Flat/House/Building Name <span class="text-red-600">*</span>
                </label>
                <textarea id="addr_address" name="address" rows="3" placeholder="Enter Flat/House/Building Name" class="addr-input w-full text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none py-3 focus:border-[#B4771E] resize-y"></textarea>
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="address"></p>
            </div>
            
            <!-- City & State -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-base md:text-xl text-[#131615] mb-2 font-semibold">
                        Town / City <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="addr_city" name="city" placeholder="Town / City" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base sm:text-lg placeholder:text-base placeholder:sm:text-lg border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="city"></p>
                </div>
                <div>
                    <label class="block text-base md:text-xl text-[#131615] mb-2 font-semibold">
                        State / County <span class="text-red-600">*</span>
                    </label>
                    <select id="addr_state" name="state" class="addr-input w-full h-[48px] md:h-[50px] text-[#757575] text-base sm:text-lg border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                        <option value="">Select an Option...</option>
                        <option value="Gujarat">Gujarat</option>
                        <option value="Maharashtra">Maharashtra</option>
                    </select>
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="state"></p>
                </div>
            </div>
            
            <!-- Address Type -->
            <div class="mb-5">
                <label class="block text-base md:text-xl text-[#131615] mb-2 font-semibold">
                    Type Of Address
                </label>
                <div class="flex flex-wrap gap-4">
                    <input type="radio" name="addressType" id="home" value="home" class="hidden peer/home" checked>
                    <label for="home" class="cursor-pointer py-[8px] px-4 border border-[#D5D5D5] rounded flex items-center gap-[8px] text-base sm:text-lg text-[#131615] peer-checked/home:bg-[#B4771E1A] peer-checked/home:border-[#B4771E1A] peer-checked/home:text-[#B4771E] transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        Home
                    </label>
                    
                    <input type="radio" name="addressType" id="work" value="work" class="hidden peer/work">
                    <label for="work" class="cursor-pointer py-[8px] px-4 border border-[#D5D5D5] rounded flex items-center gap-[8px] text-base sm:text-lg text-[#131615] peer-checked/work:bg-[#B4771E1A] peer-checked/work:border-[#B4771E1A] peer-checked/work:text-[#B4771E] transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                        </svg>
                        Work
                    </label>
                </div>
            </div>
            
            <!-- Default Address Checkbox -->
            <div class="mb-5 flex items-center gap-[10px]">
                <div class="relative flex items-center justify-center w-[22px] h-[22px] shrink-0 rounded-[5px] border-2 border-[#B4771E] bg-white transition-colors duration-200">
                    <input type="checkbox" id="addr_is_default" name="is_default" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer z-10 peer">
                    <svg class="w-[13px] h-[13px] text-[#B4771E] opacity-0 peer-checked:opacity-100 transition-opacity duration-200" viewBox="0 0 12 10" fill="none">
                        <path d="M1 5L4.5 8.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <label for="addr_is_default" class="text-base text-[#3D403F] cursor-pointer select-none">
                    Set as default address
                </label>
            </div>
            
            <!-- Success/Failure Feedback Box -->
            <div id="addressSuccess" class="hidden mb-5 border border-green-200 bg-green-50 px-4 py-3 text-green-700"></div>
            <div id="addressFailure" class="hidden mb-5 border border-red-200 bg-red-50 px-4 py-3 text-red-700"></div>
            
            <!-- Save Button -->
            <button id="saveAddressBtn" onclick="saveCustomerAddress(event)" class="w-full h-[52px] md:h-[58px] bg-[#B4771E] hover:bg-[#a86f17] text-white text-lg md:text-[24px] font-medium rounded">
                Save Address
            </button>
        </div>
    </div>
</div>
 
<!-- ── ADDRESS ACTION DROPDOWN ── -->
<div id="addrDropdown" class="absolute z-50 bg-white border border-[#E5E5E5] rounded-[8px] shadow-lg py-2 min-w-[170px] hidden text-[#131615]">
  <button onclick="editAddr()" class="w-full text-left px-5 py-3 text-base flex items-center gap-3 hover:bg-gray-50 transition-colors">
    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.83 20.08a4.5 4.5 0 0 1-2.012 1.037l-3.11 1.353a.75.75 0 0 1-.993-.993l1.353-3.11a4.5 4.5 0 0 1 1.037-2.012L16.862 4.487Zm0 0L19.5 7.125" />
    </svg>
    <span>Edit</span>
  </button>
  <div class="border-t border-[#F0F0F0]"></div>
  <button onclick="deleteAddr()" class="w-full text-left px-5 py-3 text-base flex items-center gap-3 hover:bg-gray-50 transition-colors">
    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9 9m6 12V5.25a3 3 0 0 0-3-3H9a3 3 0 0 0-3 3V21m12 0H6" />
    </svg>
    <span>Remove</span>
  </button>
  <div class="border-t border-[#F0F0F0] set-default-divider"></div>
  <button onclick="setDefault()" class="w-full text-left px-5 py-3 text-base flex items-center gap-3 hover:bg-gray-50 transition-colors set-default-btn">
    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <span>Set as Default</span>
  </button>
</div>
@endsection

@section('page-js')
<script>
// ─────────────────────────────────────────────
// DATA
// ─────────────────────────────────────────────
const orders = [
  @foreach($orders as $o)
    @php
      $firstItem = $o->items->first();
      $productName = $firstItem && $firstItem->product ? $firstItem->product->name : 'Order #' . $o->order_no;
      $productImg = ($firstItem && $firstItem->product && $firstItem->product->primaryImage) 
          ? $firstItem->product->primaryImage->image_url 
          : asset('website/assets/images/detailpage.png');
      
      if ($o->items->count() > 1) {
          $productName .= ' + ' . ($o->items->count() - 1) . ' more item(s)';
      }
      
      $statusStr = 'Pending';
      if ($o->status == \App\Models\Order::STATUS_APPROVE) {
          $statusStr = 'Approved';
      } elseif ($o->status == \App\Models\Order::STATUS_SHIPPED) {
          $statusStr = 'Shipped';
      } elseif ($o->status == \App\Models\Order::STATUS_OUT_FOR_DELIVERY) {
          $statusStr = 'Out for delivery';
      } elseif ($o->status == \App\Models\Order::STATUS_DELIVERED) {
          $statusStr = 'Delivered';
      } elseif ($o->status == \App\Models\Order::STATUS_DECLINE) {
          $statusStr = 'Cancelled';
      }
    @endphp
    {
      id: {{ $o->id }},
      orderId: @json($o->order_no),
      name: @json($productName),
      price: {{ $o->final_amount ?? 0 }},
      mrp: {{ $o->final_amount ?? 0 }},
      orderDate: @json($o->created_at->format('d M Y')),
      deliveryDate: @json($o->status == \App\Models\Order::STATUS_DELIVERED && $o->updated_at ? $o->updated_at->format('d M Y') : '-'),
      status: @json($statusStr),
      img: @json($productImg)
    },
  @endforeach
];
 
let addresses = [
  @foreach($addresses as $addr)
  @php
      $pin = '';
      if (preg_match('/\b\d{6}\b/', $addr->address, $matches)) {
          $pin = $matches[0];
      }
  @endphp
  {
    id: {{ $addr->id }},
    name: @json($addr->name),
    phone: @json($addr->phone),
    alternate_phone: @json($addr->alternate_phone),
    email: @json($addr->email),
    addr: @json($addr->address),
    city: @json($addr->city),
    state: @json($addr->state),
    type: @json($addr->type),
    isDefault: {{ $addr->is_default ? 'true' : 'false' }},
    pin: @json($pin)
  },
  @endforeach
];
 
let orderPage = 1;
const perPage = 4;
let activeDropdownId = null;
let editingAddressId = null;
 
// ─────────────────────────────────────────────
// TAB SWITCH
// ─────────────────────────────────────────────
const titles = {
  orders:    ['My Orders',       'Track your orders, view purchase history, and manage your jewelry collections with ease.'],
  addresses: ['Billing address', 'Manage your delivery addresses for faster and hassle-free checkout.'],
  account:   ['Account Details', 'Manage your personal information, contact details, and account preferences.'],
  logout:    ['My Account',      ''],
};
 
function switchTab(tab, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
  document.getElementById('tab-' + tab).classList.remove('hidden');
  document.querySelectorAll('.tab-btn').forEach(b => {
    b.classList.remove('active','text-[#B4771E]','font-medium');
    b.classList.add('text-[#3D403F]','font-normal');
  });
  btn.classList.add('active','text-[#B4771E]','font-medium');
  btn.classList.remove('text-[#3D403F]','font-normal');
  document.getElementById('pageTitle').textContent = titles[tab][0];
  document.getElementById('pageSubtitle').textContent = titles[tab][1];
  if (tab === 'orders') renderOrders();
  if (tab === 'addresses') renderAddresses();
}
 
// ─────────────────────────────────────────────
// ORDERS
// ─────────────────────────────────────────────
function statusColor(s) {
  if (s==='Delivered') return 'text-[#16A135]';
  if (s==='Cancelled') return 'text-[#E01B1B]';
  if (s==='Shipped')   return 'text-[#16A135]';
  if (s==='Out for delivery') return 'text-[#16A135]';
  if (s==='Approved')  return 'text-[#16A135]';
  return 'text-[#B4771E]';
}
function statusDot(s) {
  if (s==='Delivered') return 'bg-[#16A135]';
  if (s==='Cancelled') return 'bg-[#E01B1B]';
  if (s==='Shipped')   return 'bg-[#16A135]';
  if (s==='Out for delivery') return 'bg-[#16A135]';
  if (s==='Approved')  return 'bg-[#16A135]';
  return 'bg-[#B4771E]';
}
 
function getFilteredOrders() {
  const q  = document.getElementById('searchInput').value.toLowerCase();
  const st = document.getElementById('statusFilter').value;
  return orders.filter(o => {
    const mq = !q || o.name.toLowerCase().includes(q) || o.orderId.toLowerCase().includes(q);
    const ms = st==='all' || o.status===st;
    return mq && ms;
  });
}
 
function renderOrders() {
  const filtered = getFilteredOrders();
  const total = filtered.length;
  const totalPages = Math.ceil(total/perPage);
  if (orderPage > totalPages) orderPage = Math.max(1,totalPages);
  const slice = filtered.slice((orderPage-1)*perPage, orderPage*perPage);
 
  const list = document.getElementById('orderList');
  const none = document.getElementById('noOrders');
 
  if (!slice.length) { list.innerHTML=''; none.classList.remove('hidden'); }
  else {
    none.classList.add('hidden');
    list.innerHTML = slice.map(o=>`
      <div class="border border-[#D5D5D5] p-4 md:p-[25px] bg-white">
        <div class="flex flex-col lg:flex-row items-start gap-5">
            <!-- Product Image -->
            <div class="shrink-0 w-full md:w-auto">
                <img
                    src="${o.img}"
                    alt="${o.name}"
                    class="w-full md:w-[230px] md:h-[230px] object-cover">
            </div>
            <!-- Product Details -->
            <div class="flex-1 w-full">
                <h3 class="text-base md:text-[22px] lg:text-[24px] font-medium text-[#131615] leading-[1.4]">
                    ${o.name}
                </h3>
                <!-- Price + Status -->
                <div class="flex justify-between items-center mt-[23px]">
                    <div class="flex items-center gap-2">
                        <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[24px] lg:leading-[24px] font-semibold">
                            ₹${o.price.toLocaleString('en-IN')}
                        </span>
                        <span class="text-[#757575] text-[16px] line-through">
                            ₹${o.mrp.toLocaleString('en-IN')}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 ${statusColor(o.status)} text-base md:text-[22px] lg:text-[24px] lg:leading-[24px]">
                        <span class="w-[8px] h-[8px] rounded-full ${statusDot(o.status)}"></span>
                        ${o.status}
                    </div>
                </div>
                <!-- Details + Button -->
                <div class="flex justify-between md:items-end mt-[30px] flex-col md:flex-row gap-3">
                    <div class="space-y-4 md:space-y-5">
                        <div class="flex">
                            <span class="w-[100px] md:w-[130px] text-sm sm:text-base md:text-lg md:leading-[18px] font-medium text-[#131615]">
                                Order ID:
                            </span>
                            <span class="text-[#757575] text-sm sm:text-base md:text-lg md:leading-[18px]">
                                ${o.orderId}
                            </span>
                        </div>
                        <div class="flex">
                           <span class="w-[100px] md:w-[130px] text-sm sm:text-base md:text-lg md:leading-[18px] font-medium text-[#131615]">
                                Order Date:
                            </span>
                            <span class="text-[#757575] text-sm sm:text-base md:text-lg md:leading-[18px]">
                                ${o.orderDate}
                            </span>
                        </div>
                        <div class="flex">
                             <span class="w-[100px] md:w-[130px] text-sm sm:text-base md:text-lg md:leading-[18px] font-medium text-[#131615]">
                                Delivery Date:
                            </span>
                            <span class="text-[#757575] text-sm sm:text-base md:text-lg md:leading-[18px]">
                                ${o.deliveryDate}
                            </span>
                        </div>
                    </div>
                    <button onclick="viewOrder(${o.id})" class="px-5 lg:px-[35px] rounded-sm py-[15px] bg-[#B4771E] hover:bg-[#b3771e] text-white text-base lg:text-[22px] leading-[22px] font-semibold transition">
                        View Order
                    </button>
                </div>
            </div>
        </div>
      </div>
    `).join('');
  }
  renderOrderPagination(totalPages);
}
 
function renderOrderPagination(totalPages) {
  const pg = document.getElementById('pagination');
  if (totalPages <= 1) { pg.innerHTML = ''; return; }

  // Previous Page Button
  let prev = '';
  if (orderPage === 1) {
    prev = `
      <span class="h-10 sm:h-[47px] min-w-10 sm:min-w-[104px] max-w-full px-2 sm:px-3 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex items-center justify-center gap-1 sm:gap-2 opacity-50 cursor-not-allowed whitespace-nowrap">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18L9 12L15 6" />
          </svg>
          <span class="hidden sm:inline">Previous</span>
      </span>`;
  } else {
    prev = `
      <button onclick="goOrderPage(${orderPage - 1})" class="h-10 sm:h-[47px] min-w-10 sm:min-w-[104px] max-w-full px-2 sm:px-3 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex items-center justify-center gap-1 sm:gap-2 hover:bg-gray-50 cursor-pointer whitespace-nowrap">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18L9 12L15 6" />
          </svg>
          <span class="hidden sm:inline">Previous</span>
      </button>`;
  }

  // Page Numbers
  let pages = '';
  for (let i = 1; i <= totalPages; i++) {
    if (i === orderPage) {
      pages += `<button onclick="goOrderPage(${i})" class="h-10 sm:h-[47px] min-w-10 sm:min-w-[47px] px-2 sm:px-4 bg-[#B67A1E] text-white border border-[#7575751E] text-sm sm:text-base lg:text-lg font-medium cursor-pointer flex justify-center items-center">${i}</button>`;
    } else {
      pages += `<button onclick="goOrderPage(${i})" class="h-10 sm:h-[47px] min-w-10 sm:min-w-[47px] px-2 sm:px-4 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium hover:bg-gray-50 cursor-pointer flex justify-center items-center">${i}</button>`;
    }
  }

  // Next Page Button
  let next = '';
  if (orderPage === totalPages) {
    next = `
      <span class="h-10 sm:h-[47px] min-w-10 sm:min-w-[84px] max-w-full px-2 sm:px-3 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex items-center justify-center gap-1 sm:gap-2 opacity-50 cursor-not-allowed whitespace-nowrap">
          <span class="hidden sm:inline">Next</span>
          <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6L15 12L9 18" />
          </svg>
      </span>`;
  } else {
    next = `
      <button onclick="goOrderPage(${orderPage + 1})" class="h-10 sm:h-[47px] min-w-10 sm:min-w-[84px] max-w-full px-2 sm:px-3 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex items-center justify-center gap-1 sm:gap-2 hover:bg-gray-50 cursor-pointer whitespace-nowrap">
          <span class="hidden sm:inline">Next</span>
          <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6L15 12L9 18" />
          </svg>
      </button>`;
  }

  pg.innerHTML = `
    <nav class="mt-8 md:mt-10 w-full max-w-full overflow-visible px-1" role="navigation">
        <div class="flex w-full max-w-full flex-nowrap items-center justify-center gap-2 md:gap-3">
            ${prev}
            ${pages}
            ${next}
        </div>
    </nav>
  `;
}
function goOrderPage(n) { orderPage=n; renderOrders(); }
function filterOrders() { orderPage=1; renderOrders(); }
 
function viewOrder(id) {
  window.location.href = '{{ route('customer.profile.view-order', ['id' => '__ID__']) }}'.replace('__ID__', id);
}
 
// ─────────────────────────────────────────────
// ADDRESSES
// ─────────────────────────────────────────────
function addrIcon(type) {
  if (type==='home') {
    return `<img src="/website/assets/images/home.png" alt="Home">`;
  }
  return `<img src="/website/assets/images/home1.png" alt="Work">`;
}
 
function renderAddresses() {
  const list = document.getElementById('addrList');
  if (addresses.length === 0) {
    list.innerHTML = `
      <div class="p-8 text-center text-gray-500">
        No saved addresses. Please add a new delivery address to proceed.
      </div>
    `;
    return;
  }
  
  list.innerHTML = addresses.map((a, index)=>`
    <div class="px-6 py-5 ${a.isDefault ? 'bg-[#f5f2ee]' : 'bg-white'} ${index === addresses.length - 1 ? '' : 'border-b border-[#D5D5D5]'}" data-address-id="${a.id}">
      <div class="flex justify-between items-start">
        <div class="flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            ${addrIcon(a.type)}
            <span class="text-base md:text-lg text-[#131615]">
              Deliver To:
            </span>
            <span class="text-base md:text-lg font-medium text-[#131615]">
              ${a.name}${a.pin ? ', ' + a.pin : ''}
            </span>
            ${a.isDefault ? `
              <span class="bg-[#B4771E29] text-[#B4771E] text-sm md:text-base px-3 py-[4px]">
                Default
              </span>
            ` : ''}
          </div>
          <p class="mt-3 text-sm md:text-base text-[#3D403F] leading-[22px]">
            ${a.addr}, ${a.city}, ${a.state} ${a.pin ? a.pin + ', ' : ''}India
          </p>
        </div>
        <button onclick="toggleAddrDropdown(${a.id}, event)" class="text-[#777] text-[18px] focus:outline-none">
          <i class="fa-solid fa-ellipsis"></i>
        </button>
      </div>
    </div>
  `).join('');
}
 
function toggleAddrDropdown(id, e) {
  e.stopPropagation();
  const dd = document.getElementById('addrDropdown');
  if (activeDropdownId===id && !dd.classList.contains('hidden')) {
    dd.classList.add('hidden'); activeDropdownId=null; return;
  }
  activeDropdownId = id;
  
  const a = addresses.find(x => x.id === id);
  if (a.isDefault) {
    dd.querySelector('.set-default-btn').classList.add('hidden');
    dd.querySelector('.set-default-divider').classList.add('hidden');
  } else {
    dd.querySelector('.set-default-btn').classList.remove('hidden');
    dd.querySelector('.set-default-divider').classList.remove('hidden');
  }
  
  const rect = e.currentTarget.getBoundingClientRect();
  dd.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
  dd.style.left = (rect.right - 170 + window.scrollX) + 'px';
  dd.classList.remove('hidden');
}
document.addEventListener('click', ()=>{ document.getElementById('addrDropdown').classList.add('hidden'); activeDropdownId=null; });
 
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function deleteAddr() {
  if (!activeDropdownId) return;
  if (!confirm('Are you sure you want to delete this address?')) return;
  
  const idToDelete = activeDropdownId;
  document.getElementById('addrDropdown').classList.add('hidden');
  
  fetch('{{ route('checkout.address.delete') }}', {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ address_id: idToDelete })
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      addresses = addresses.filter(a => a.id !== idToDelete);
      
      if (data.new_default_id) {
        addresses.forEach(a => a.isDefault = (a.id === data.new_default_id));
      } else if (addresses.length > 0 && !addresses.some(a => a.isDefault)) {
        addresses[0].isDefault = true;
      }
      
      if (window.showWishlistToast) {
        window.showWishlistToast(data.message || 'Address deleted successfully.');
      }
      renderAddresses();
    } else {
      if (window.showWishlistToast) {
        window.showWishlistToast(data.message || 'Failed to delete address.', false);
      }
    }
  })
  .catch(err => {
    console.error('Error deleting address:', err);
    if (window.showWishlistToast) {
      window.showWishlistToast('Something went wrong.', false);
    }
  });
}
 
function setDefault() {
  if (!activeDropdownId) return;
  
  const idToDefault = activeDropdownId;
  document.getElementById('addrDropdown').classList.add('hidden');
  
  fetch('{{ route('checkout.address.set-default') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ address_id: idToDefault })
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      addresses.forEach(a => a.isDefault = (a.id === idToDefault));
      if (window.showWishlistToast) {
        window.showWishlistToast(data.message || 'Default address updated successfully.');
      }
      renderAddresses();
    } else {
      if (window.showWishlistToast) {
        window.showWishlistToast(data.message || 'Failed to set default address.', false);
      }
    }
  })
  .catch(err => {
    console.error('Error setting default address:', err);
    if (window.showWishlistToast) {
      window.showWishlistToast('Something went wrong.', false);
    }
  });
}
 
function editAddr() { 
  document.getElementById('addrDropdown').classList.add('hidden');
  const a = addresses.find(x => x.id === activeDropdownId);
  if (!a) return;
  
  editingAddressId = a.id;
  document.getElementById('addrModalTitle').textContent = 'Deliver To';
  
  document.getElementById('addr_name').value = a.name;
  document.getElementById('addr_phone').value = a.phone;
  document.getElementById('addr_alternate_phone').value = a.alternate_phone || '';
  document.getElementById('addr_email').value = a.email || '';
  document.getElementById('addr_address').value = a.addr;
  document.getElementById('addr_city').value = a.city;
  document.getElementById('addr_state').value = a.state;
  document.getElementById('addr_is_default').checked = a.isDefault;
  
  if (a.type === 'home') {
    document.getElementById('home').checked = true;
  } else {
    document.getElementById('work').checked = true;
  }
  
  openModal('addressModal'); 
}
 
function openAddrModal() { 
  editingAddressId = null;
  document.getElementById('addrModalTitle').textContent = 'Deliver To';
  resetAddressForm();
  openModal('addressModal'); 
}

function resetAddressForm() {
    document.getElementById('addr_name').value = '';
    document.getElementById('addr_phone').value = '';
    document.getElementById('addr_alternate_phone').value = '';
    document.getElementById('addr_email').value = @json(auth('customer')->user()->email ?? '');
    document.getElementById('addr_address').value = '';
    document.getElementById('addr_city').value = '';
    document.getElementById('addr_state').value = '';
    document.getElementById('home').checked = true;
    document.getElementById('addr_is_default').checked = false;
    clearAddrErrors();
}

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
  
  const payload = {
    name,
    phone,
    alternate_phone,
    email,
    address,
    city,
    state,
    type,
    is_default
  };
  
  let url = '{{ route('checkout.address.save') }}';
  let method = 'POST';
  
  if (editingAddressId) {
    payload.address_id = editingAddressId;
    url = '{{ route('checkout.address.update') }}';
    method = 'PATCH';
  }
  
  fetch(url, {
    method: method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    body: JSON.stringify(payload)
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
      const savedAddr = data.address;
      
      let pin = '';
      const pinMatch = savedAddr.address.match(/\b\d{6}\b/);
      if (pinMatch) {
        pin = pinMatch[0];
      }
      
      const clientAddressItem = {
        id: savedAddr.id,
        name: savedAddr.name,
        phone: savedAddr.phone,
        alternate_phone: savedAddr.alternate_phone || '',
        email: savedAddr.email || '',
        addr: savedAddr.address,
        city: savedAddr.city,
        state: savedAddr.state,
        type: savedAddr.type,
        isDefault: savedAddr.is_default,
        pin: pin
      };
      
      if (editingAddressId) {
        addresses = addresses.map(a => a.id === editingAddressId ? clientAddressItem : a);
      } else {
        addresses.push(clientAddressItem);
      }
      
      if (savedAddr.is_default) {
        addresses.forEach(a => {
          if (a.id !== savedAddr.id) a.isDefault = false;
        });
      }
      
      closeModal();
      renderAddresses();
      if (window.showWishlistToast) {
        window.showWishlistToast(data.message || 'Address saved successfully.');
      }
      
      btn.disabled = false;
      btn.innerText = 'Save Address';
    }
  })
  .catch(err => {
    console.error('Error saving address:', err);
    alert('Something went wrong.');
    btn.disabled = false;
    btn.innerText = 'Save Address';
  });
}
 
// ─────────────────────────────────────────────
// ACCOUNT DETAILS
// ─────────────────────────────────────────────
function changeAvatar(e) {
  const file=e.target.files[0]; if(!file) return;
  const r=new FileReader(); r.onload=ev=>document.getElementById('avatarImg').src=ev.target.result; r.readAsDataURL(file);
}

function saveAccount(btn) {
  const name = document.getElementById('acctName').value.trim();
  const phone = document.getElementById('acctPhone').value.trim();
  const currentPassword = document.getElementById('pw1').value.trim();
  const newPassword = document.getElementById('pw2').value.trim();
  const confirmPassword = document.getElementById('pw3').value.trim();

  // Clear previous errors
  document.querySelectorAll('.field-error').forEach(el => {
    el.textContent = '';
    el.classList.add('hidden');
  });

  if (!name) {
    showFieldError('acctName', 'Full Name is required.');
    return;
  }
  if (!phone) {
    showFieldError('acctPhone', 'Mobile Number is required.');
    return;
  }

  btn.textContent = 'Saving...';
  btn.disabled = true;

  // 1. Update Profile Details
  fetch('{{ route('customer.profile.update') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ name, phone })
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      // 2. If password change is requested
      if (newPassword || currentPassword || confirmPassword) {
        if (!currentPassword) {
          showFieldError('pw1', 'Current password is required.');
          btn.textContent = 'Save Changes';
          btn.disabled = false;
          return;
        }
        if (!newPassword) {
          showFieldError('pw2', 'New password is required.');
          btn.textContent = 'Save Changes';
          btn.disabled = false;
          return;
        }
        if (newPassword.length < 8) {
          showFieldError('pw2', 'New password must be at least 8 characters.');
          btn.textContent = 'Save Changes';
          btn.disabled = false;
          return;
        }
        if (newPassword !== confirmPassword) {
          showFieldError('pw3', 'Confirm password does not match.');
          btn.textContent = 'Save Changes';
          btn.disabled = false;
          return;
        }

        // Send password update request
        fetch('{{ route('customer.profile.update-password') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            current_password: currentPassword,
            new_password: newPassword,
            new_password_confirmation: confirmPassword
          })
        })
        .then(r2 => r2.json())
        .then(data2 => {
          btn.textContent = 'Save Changes';
          btn.disabled = false;
          if (data2.status === 'error') {
            if (data2.errors) {
              if (data2.errors.current_password) showFieldError('pw1', data2.errors.current_password[0]);
              if (data2.errors.new_password) showFieldError('pw2', data2.errors.new_password[0]);
              if (data2.errors.new_password_confirmation) showFieldError('pw3', data2.errors.new_password_confirmation[0]);
            } else if (data2.message) {
              showFieldError('pw1', data2.message);
            }
          } else if (data2.status === 'success') {
            if (window.showWishlistToast) {
              window.showWishlistToast('Profile and password updated successfully!');
            } else {
              alert('Profile and password updated successfully!');
            }
            // Clear password fields
            document.getElementById('pw1').value = '';
            document.getElementById('pw2').value = '';
            document.getElementById('pw3').value = '';
          }
        })
        .catch(err => {
          console.error(err);
          btn.textContent = 'Save Changes';
          btn.disabled = false;
          alert('Something went wrong during password update.');
        });
      } else {
        // Just profile updated
        btn.textContent = 'Save Changes';
        btn.disabled = false;
        if (window.showWishlistToast) {
          window.showWishlistToast('Profile details updated successfully!');
        } else {
          alert('Profile details updated successfully!');
        }
      }
    } else {
      btn.textContent = 'Save Changes';
      btn.disabled = false;
      alert(data.message || 'Failed to update profile details.');
    }
  })
  .catch(err => {
    console.error('Error updating profile:', err);
    btn.textContent = 'Save Changes';
    btn.disabled = false;
    alert('Something went wrong.');
  });
}

function showFieldError(id, msg) {
  const errEl = document.getElementById(id + '-error');
  if (errEl) {
    errEl.textContent = msg;
    errEl.classList.remove('hidden');
  }
}

// Bind password togglers (pure JS)
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        
        const eyeOff = this.querySelector('.eye-off');
        const eye = this.querySelector('.eye');
        if (eyeOff && eye) {
          if (isPassword) {
            eyeOff.classList.add('hidden');
            eye.classList.remove('hidden');
          } else {
            eyeOff.classList.remove('hidden');
            eye.classList.add('hidden');
          }
        }
      }
    });
  });
});
 
// ─────────────────────────────────────────────
// MODAL HELPERS
// ─────────────────────────────────────────────
function openModal(id) {
  document.getElementById(id).classList.remove('hidden');
  if (id === 'orderModal') {
    document.getElementById(id).classList.add('flex');
  } else if (id === 'addressModal') {
    document.body.classList.add('overflow-hidden');
  }
}
function closeModal(id) {
  document.getElementById('addressModal').classList.add('hidden');
  document.getElementById('orderModal').classList.add('hidden');
  document.getElementById('orderModal').classList.remove('flex');
  document.body.classList.remove('overflow-hidden');
  resetAddressForm();
}

// Bind overlay clicks
['orderModal','addressModal'].forEach(id=>{
  const modalEl = document.getElementById(id);
  if (modalEl) {
    modalEl.addEventListener('click', function(e){ 
      if(e.target===this) {
        closeModal();
      }
    });
  }
});

// Bind escape key
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        closeModal();
    }
});
 
// ─────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────
renderOrders();
renderAddresses();
</script>
@endsection
