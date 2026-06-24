@extends('layouts.website')

@section('title', 'My Orders | Chetan Imitation')

@section('content')
<div class="container-1440 section-space">
 
  <!-- Page Title (dynamic) -->
  <div class="text-center mb-7">
     <h2 id="pageTitle" class="font-moglan hero-title">My Orders</h2>
     <p id="pageSubtitle" class="hero-para">Track your orders, view purchase history, and manage your jewelry collections with ease.</p>
  </div>
 
  <button
    id="sidebarToggle"
    class="lg:hidden text-base flex items-center gap-2 border border-[#D5D5D5] px-3 py-2 rounded-md mb-4"
>
    <svg xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">
        <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"/>
    </svg>

    <span id="mobileSidebarTitle" class="font-medium">
      My Orders
    </span>
</button>

<div
    id="sidebarOverlay"
    class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden">
</div>

  <div class="flex flex-col lg:flex-row gap-6 items-start">
 
    <!-- ══ SIDEBAR ══ -->
   <aside
    id="accountSidebar"
    class="
    fixed lg:static
    top-0 left-[-100%]
    lg:left-auto
    w-[320px] lg:w-[360px]
    h-screen lg:h-auto
    bg-white
    z-50
    lg:z-auto
    border border-gray-200
    overflow-y-auto
    transition-all duration-300
    rounded-none lg:rounded-lg
    shrink-0
">
      <div class="m-4 pb-4 border-b border-[#D5D5D5]">
        <p class="text-base md:text-lg lg:text-xl font-semibold text-[#131615]">My Account</p>
      </div>
      <nav class="space-y-2 mb-[10px]">
        <button data-tab="orders" onclick="switchTab('orders',this)"
          class="tab-btn active w-full flex items-center gap-[10px] px-5 py-[15px] text-base md:text-lg font-medium text-left">
          
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M20.3071 10.9712C20.7013 10.9712 21.0796 11.1277 21.3589 11.4058L22.7134 12.7612C22.9919 13.0402 23.1488 13.4178 23.1489 13.812C23.1489 14.2064 22.992 14.5847 22.7134 14.8638L15.2603 22.3179C15.1863 22.3921 15.0916 22.4431 14.9888 22.4634L11.6011 23.1401L11.4985 23.1499C11.4203 23.15 11.3427 23.1325 11.272 23.0991C11.2015 23.0658 11.1389 23.0177 11.0894 22.9575C11.0397 22.8972 11.004 22.8262 10.9849 22.7505C10.9658 22.6746 10.9638 22.5948 10.979 22.5181L11.6567 19.1304C11.6767 19.0275 11.7266 18.9325 11.8013 18.8589L19.2554 11.4058L19.3647 11.3071C19.6296 11.0909 19.9622 10.9712 20.3071 10.9712ZM20.3081 12.0269C20.1943 12.0269 20.0849 12.0724 20.0044 12.1528L12.6636 19.4927L12.1724 21.9458L14.6245 21.4556L21.9663 14.1147C22.0468 14.0343 22.0923 13.9248 22.0923 13.811C22.0922 13.6974 22.0466 13.5887 21.9663 13.5083L20.6118 12.1528C20.5314 12.0724 20.4219 12.0269 20.3081 12.0269ZM4.4126 2.92529C4.55294 2.92529 4.68738 2.98133 4.78662 3.08057C4.88576 3.17979 4.94189 3.31432 4.94189 3.45459C4.94181 3.59482 4.88578 3.72945 4.78662 3.82861C4.6874 3.92775 4.55286 3.98389 4.4126 3.98389H2.49561C1.73094 3.98406 1.10906 4.60594 1.10889 5.37061V17.8296C1.10906 18.5943 1.73094 19.2161 2.49561 19.2163H9.20459C9.34482 19.2164 9.47945 19.2724 9.57861 19.3716C9.67777 19.4707 9.7338 19.6054 9.73389 19.7456C9.73389 19.8858 9.67765 20.0204 9.57861 20.1196C9.47945 20.2188 9.34482 20.2748 9.20459 20.2749H2.49561C1.8472 20.2743 1.22559 20.0166 0.76709 19.5581C0.308594 19.0996 0.0508868 18.478 0.050293 17.8296V5.37061C0.0504693 4.02171 1.14671 2.92547 2.49561 2.92529H4.4126ZM13.9956 2.92529C15.3447 2.92529 16.4417 4.0216 16.4419 5.37061V11.1206C16.4419 11.2609 16.3858 11.3954 16.2866 11.4946C16.1874 11.5939 16.0529 11.6499 15.9126 11.6499C15.7723 11.6499 15.6378 11.5939 15.5386 11.4946C15.4394 11.3954 15.3833 11.2609 15.3833 11.1206V5.37061C15.3831 4.60583 14.7604 3.98389 13.9956 3.98389H12.0796C11.9394 3.98389 11.8048 3.92765 11.7056 3.82861C11.6064 3.72945 11.5504 3.59482 11.5503 3.45459C11.5503 3.31425 11.6063 3.1798 11.7056 3.08057C11.8048 2.98133 11.9392 2.92529 12.0796 2.92529H13.9956Z" fill="currentColor" stroke="currentColor" stroke-width="0.1"/>
      <path d="M13.0376 13.4663C13.1779 13.4663 13.3124 13.5224 13.4116 13.6216C13.5108 13.7207 13.5668 13.8554 13.5669 13.9956C13.5669 14.1359 13.5108 14.2704 13.4116 14.3696C13.3124 14.4689 13.1779 14.5249 13.0376 14.5249H3.45459C3.31425 14.5249 3.1798 14.4689 3.08057 14.3696C2.98133 14.2704 2.92529 14.1359 2.92529 13.9956C2.92538 13.8554 2.9814 13.7207 3.08057 13.6216C3.17977 13.5225 3.3144 13.4663 3.45459 13.4663H13.0376ZM13.0376 10.5913C13.1779 10.5913 13.3124 10.6474 13.4116 10.7466C13.5108 10.8457 13.5668 10.9804 13.5669 11.1206C13.5669 11.2609 13.5108 11.3954 13.4116 11.4946C13.3124 11.5939 13.1779 11.6499 13.0376 11.6499H3.45459C3.31425 11.6499 3.1798 11.5939 3.08057 11.4946C2.98133 11.3954 2.92529 11.2609 2.92529 11.1206C2.92538 10.9804 2.9814 10.8457 3.08057 10.7466C3.17977 10.6475 3.3144 10.5913 3.45459 10.5913H13.0376ZM13.0376 7.71631C13.1779 7.71631 13.3124 7.77245 13.4116 7.87158C13.5108 7.97074 13.5668 8.10538 13.5669 8.24561C13.5669 8.38587 13.5108 8.52041 13.4116 8.61963C13.3124 8.71887 13.1779 8.7749 13.0376 8.7749H3.45459C3.31425 8.7749 3.1798 8.71887 3.08057 8.61963C2.98133 8.52039 2.92529 8.38595 2.92529 8.24561C2.92538 8.10538 2.98141 7.97074 3.08057 7.87158C3.17977 7.77255 3.3144 7.71631 3.45459 7.71631H13.0376ZM8.24561 0.050293C9.41216 0.050293 10.3908 0.871275 10.6333 1.96631H12.0796C12.2198 1.96639 12.3545 2.02242 12.4536 2.12158C12.5528 2.22074 12.6088 2.35538 12.6089 2.49561V4.4126C12.6089 5.23275 11.9408 5.8999 11.1206 5.8999H5.37061C4.5506 5.89973 3.8833 5.23264 3.8833 4.4126V2.49561C3.88339 2.35538 3.93941 2.22074 4.03857 2.12158C4.1378 2.02245 4.27233 1.96631 4.4126 1.96631H5.85889C6.10138 0.871411 7.07922 0.0504458 8.24561 0.050293ZM8.24561 1.10889C7.48094 1.10906 6.85906 1.73094 6.85889 2.49561C6.85889 2.6358 6.80265 2.77042 6.70361 2.86963C6.60445 2.96879 6.46982 3.02482 6.32959 3.0249H4.94189V4.4126C4.94189 4.64842 5.13483 4.84113 5.37061 4.84131H11.1206C11.3565 4.84131 11.5503 4.64853 11.5503 4.4126V3.0249H10.1626C10.0223 3.0249 9.88781 2.96887 9.78857 2.86963C9.68944 2.77041 9.6333 2.63587 9.6333 2.49561C9.63312 1.73083 9.01042 1.10889 8.24561 1.10889Z" fill="currentColor" stroke="currentColor" stroke-width="0.1"/>
      </svg>

          My Order
        </button>
        <button data-tab="addresses" onclick="switchTab('addresses',this)"
          class="tab-btn w-full flex items-center gap-3 px-5 py-3 text-base md:text-lg text-[#3D403F] font-normal text-left">
         
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15 10.5C15 11.2956 14.6839 12.0587 14.1213 12.6213C13.5587 13.1839 12.7956 13.5 12 13.5C11.2044 13.5 10.4413 13.1839 9.87868 12.6213C9.31607 12.0587 9 11.2956 9 10.5C9 9.70435 9.31607 8.94129 9.87868 8.37868C10.4413 7.81607 11.2044 7.5 12 7.5C12.7956 7.5 13.5587 7.81607 14.1213 8.37868C14.6839 8.94129 15 9.70435 15 10.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M19.5 10.5C19.5 17.642 12 21.75 12 21.75C12 21.75 4.5 17.642 4.5 10.5C4.5 8.51088 5.29018 6.60322 6.6967 5.1967C8.10322 3.79018 10.0109 3 12 3C13.9891 3 15.8968 3.79018 17.3033 5.1967C18.7098 6.60322 19.5 8.51088 19.5 10.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

          Addresses
        </button>
        <button data-tab="account" onclick="switchTab('account',this)"
          class="tab-btn w-full flex items-center gap-3 px-5 py-3 text-base md:text-lg text-[#3D403F] font-normal text-left">
         
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15.75 6C15.75 6.99456 15.3549 7.94839 14.6516 8.65165C13.9484 9.35491 12.9945 9.75 12 9.75C11.0054 9.75 10.0516 9.35491 9.34833 8.65165C8.64506 7.94839 8.24998 6.99456 8.24998 6C8.24998 5.00544 8.64506 4.05161 9.34833 3.34835C10.0516 2.64509 11.0054 2.25 12 2.25C12.9945 2.25 13.9484 2.64509 14.6516 3.34835C15.3549 4.05161 15.75 5.00544 15.75 6ZM4.50098 20.118C4.53311 18.1504 5.33731 16.2742 6.74015 14.894C8.14299 13.5139 10.0321 12.7405 12 12.7405C13.9679 12.7405 15.857 13.5139 17.2598 14.894C18.6626 16.2742 19.4668 18.1504 19.499 20.118C17.1464 21.1968 14.5881 21.7535 12 21.75C9.32398 21.75 6.78398 21.166 4.50098 20.118Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

          Account Details
        </button>
        <button data-tab="logout" onclick="switchTab('logout',this)"
          class="tab-btn w-full flex items-center gap-3 px-5 py-3 text-base md:text-lg text-[#3D403F] font-normal text-left">
          
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14.5498 17.5C14.246 17.5 13.9998 17.7462 13.9998 18.05V20.25C13.9988 21.1609 13.2606 21.8991 12.3499 21.9001H4.6499C3.73896 21.8991 3.0008 21.1609 2.99982 20.25V3.75C3.00076 2.83928 3.73896 2.10107 4.6499 2.10009H12.3499C13.2608 2.10107 13.999 2.83928 13.9998 3.75V5.95C13.9998 6.25379 14.2462 6.5 14.5498 6.5C14.8536 6.5 15.0999 6.25379 15.0999 5.95V3.75C15.0984 2.23183 13.868 1.0015 12.3499 1H4.6499C3.1319 1.00185 1.90175 2.232 1.8999 3.75V20.25C1.90175 21.768 3.1319 22.9982 4.6499 23H12.3499C13.868 22.9983 15.0984 21.768 15.0999 20.25V18.0499C15.0999 17.7462 14.8536 17.5 14.5498 17.5Z" fill="#3D403F" stroke="currentColor" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M21.5388 11.6112L18.2388 8.3111C18.0202 8.10012 17.6721 8.10614 17.4611 8.32472C17.2552 8.53784 17.2552 8.87575 17.4611 9.08892L19.8221 11.45H7.94977C7.64611 11.45 7.3999 11.6963 7.3999 12C7.3999 12.3038 7.64611 12.55 7.94977 12.55H19.822L17.4609 14.9112C17.2424 15.1222 17.2364 15.4705 17.4475 15.6888C17.6585 15.9074 18.0067 15.9134 18.2252 15.7024C18.2297 15.6979 18.2342 15.6935 18.2388 15.6888L21.5386 12.3888C21.7535 12.1743 21.7537 11.8263 21.539 11.6115C21.539 11.6113 21.5388 11.6113 21.5388 11.6111L21.5388 11.6112Z" fill="#3D403F" stroke="currentColor" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

          Logout
        </button>
      </nav>
    </aside>
 
    <!-- ══ TAB PANELS ══ -->
    <div class="flex-1 w-full lg:min-w-0">
 
      <!-- ─── TAB: MY ORDERS ─────────────────────────── -->
      <div id="tab-orders" class="tab-panel">
          <!-- Search & Filter -->
          <div class="flex flex-col lg:flex-row gap-[19px] mb-5 w-full">
              <div class="flex flex-1 border border-[#D5D5D5] bg-white overflow-hidden rounded-[7px]">
                  <input id="searchInput" type="text" placeholder="Search your orders here" oninput="filterOrders()" class="flex-1 px-5 py-2 text-base placeholder:text-lg text-[#131615] outline-none">
                  <button onclick="filterOrders()" class="bg-[#B4771E] hover:bg-[#ad741b] transition-all px-3 md:px-[30px] py-2 text-base text-white font-medium flex items-center gap-[10px] border rounded-r-[7px]">
                     
                      <svg width="16" height="16" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <g clip-path="url(#clip0_219_1838)">
                      <path d="M17 17L12.3806 12.3806M12.3806 12.3806C13.6309 11.1304 14.3333 9.43473 14.3333 7.66663C14.3333 5.89853 13.6309 4.20285 12.3806 2.95261C11.1304 1.70237 9.43473 1 7.66663 1C5.89853 1 4.20285 1.70237 2.95261 2.95261C1.70237 4.20285 1 5.89853 1 7.66663C1 9.43473 1.70237 11.1304 2.95261 12.3806C4.20285 13.6309 5.89853 14.3333 7.66663 14.3333C9.43473 14.3333 11.1304 13.6309 12.3806 12.3806Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                      </g>
                      <defs>
                      <clipPath id="clip0_219_1838">
                      <rect width="18" height="18" fill="white"/>
                      </clipPath>
                      </defs>
                      </svg>

                     <span class="hidden md:block"> Search Orders</span>
                  </button>
              </div>
             <div class="relative w-full lg:w-[250px]">
                  <select id="statusFilter" onchange="filterOrders()" class="rounded-[7px] appearance-none w-full py-2 px-5 border border-[#D5D5D5] bg-white text-base text-[#131615] outline-none">
                      <option value="all">All Orders</option>
                      <option value="Pending">Pending</option>
                      <option value="Approved">Approved</option>
                      <option value="Shipped">Shipped</option>
                      <option value="Out for delivery">Out for delivery</option>
                      <option value="Delivered">Delivered</option>
                      <option value="Cancelled">Cancelled</option>
                  </select>
                  <div class="pointer-events-none absolute top-[48%] -translate-y-[50%] right-3 w-5 h-5">
                      <svg class="w-4 h-4 text-[#131615]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                  </div>
              </div>
          </div>
      
          <!-- Orders List -->
          <div id="orderList" class="space-y-4">
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
              <button onclick="openAddrModal()" class="bg-[#B4771E] hover:bg-[#b67d1f] text-white text-sm md:text-base font-medium px-4 h-[35px] transition flex gap-3 md:gap-[9px] items-center rounded-sm">
                  + Add
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
            <img id="avatarImg"
              src="{{ auth('customer')->user()->avatar ? asset(auth('customer')->user()->avatar) : '' }}"
              onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(auth('customer')->user()->name) }}&background=B4771E&color=fff&size=120&bold=true'"
              class="w-[90px] md:w-[120px] h-[90px] md:h-[120px] rounded-full object-cover border-2 border-gray-200" />
            <label class="absolute bottom-0 right-0 w-[30px] h-[30px] bg-[#B4771E] rounded-full flex items-center justify-center cursor-pointer">
              <span class="text-white text-sm leading-none">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10.9905 2.10621L12.2017 0.894326C12.4542 0.641843 12.7966 0.5 13.1537 0.5C13.5108 0.5 13.8532 0.641843 14.1057 0.894326C14.3582 1.14681 14.5 1.48925 14.5 1.84631C14.5 2.20338 14.3582 2.54582 14.1057 2.7983L3.7896 13.1144C3.41004 13.4937 2.94198 13.7725 2.42767 13.9256L0.5 14.5L1.07435 12.5723C1.22747 12.058 1.50629 11.59 1.88562 11.2104L10.9913 2.10621H10.9905ZM10.9905 2.10621L12.8845 4.00013" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            </span>
              <input type="file" id="avatarFileInput" accept="image/*" class="hidden" onchange="changeAvatar(event)" />
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
                <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-[50%] text-gray-500 hover:text-gray-700" style="background: none; border: none; padding: 0; outline: none; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;" data-target="pw1" tabindex="-1">
                  <svg class="eye-off" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
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
                <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-[50%] text-gray-500 hover:text-gray-700" style="background: none; border: none; padding: 0; outline: none; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;" data-target="pw2" tabindex="-1">
                  <svg class="eye-off" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
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
                <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-[50%] text-gray-500 hover:text-gray-700" style="background: none; border: none; padding: 0; outline: none; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;" data-target="pw3" tabindex="-1">
                  <svg class="eye-off" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  <svg class="eye hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
              <p class="field-error text-sm text-red-600 mt-1 hidden" id="pw3-error"></p>
            </div>
          </div>
          <button onclick="saveAccount(this)" class="common-btn md:h-[52px] rounded-sm">Save Changes</button>
          <p id="acctSaved" class="hidden text-xs text-green-600 mt-2 font-medium">✓ Changes saved successfully!</p>
        </div>
      </div>
 
      <!-- ─── TAB: LOGOUT ────────────────────────────── -->
      <div id="tab-logout" class="tab-panel hidden">
          <div class="flex flex-col justify-center items-center mx-auto p-8 text-center">
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
                  <button onclick="switchTab('orders', document.querySelector('[data-tab=orders]'))" class="common-btn h-[40px] md:h-[52px] flex items-center justify-center border border-[#131615] text-[#131615] transition common-btn bg-transparent hover:text-[#fff] hover:bg-[#B4771E] hover:border-[#B4771E]">
                      Cancel
                  </button>
                  <form action="{{ route('customer.logout') }}" method="POST" id="logout-form" class="inline">
                      @csrf
                      <button type="submit" class="common-btn w-full h-[40px] md:h-[52px]">
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
    <div class="min-h-full flex items-center justify-center !mt-0 py-5">
        <div class="relative w-full max-w-[750px] bg-white rounded-[8px] p-4 sm:p-5 max-h-[90vh] border border-[#D5D5D5] overflow-y-auto scrollbar-hide">
            
            <!-- Close Button -->
            <button onclick="closeModal()" class="absolute top-4 right-4 md:top-6 md:right-6 text-[35px] leading-none text-[#131615]">
                &times;
            </button>
            
            <!-- Heading -->
            <h2 id="addrModalTitle" class="text-xl lg:text-[22px] lg:leading-[24px] font-medium text-[#131615] mb-4">
                Deliver To
            </h2>
            
            <!-- Full Name -->
            <div class="mb-4">
                <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Full Name <span class="text-red-600">*</span>
                </label>
                <input type="text" id="addr_name" name="name" placeholder="Enter Your Full Name" class="addr-input w-full h-[48px] text-[#757575] text-base  placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="name"></p>
            </div>
            
            <!-- Mobile and Alt Phone -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Mobile Number <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="addr_phone" name="phone" placeholder="Enter Your Mobile Number" maxlength="10" inputmode="numeric" class="addr-input w-full h-[48px] text-[#757575] text-base  placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="phone"></p>
                </div>
                <div>
                     <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Alternate Phone Number
                    </label>
                    <input type="text" id="addr_alternate_phone" name="alternate_phone" placeholder="Enter Your Mobile Number" maxlength="10" inputmode="numeric" class="addr-input w-full h-[48px] text-[#757575] text-base  placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="alternate_phone"></p>
                </div>
            </div>
            
            <!-- Email -->
            <div class="mb-4">
                 <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Email address
                </label>
                <input type="email" id="addr_email" name="email" value="{{ auth('customer')->user()->email ?? '' }}" class="addr-input w-full h-[48px] text-[#757575] text-base  placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="email"></p>
            </div>
            
            <!-- Address text -->
            <div class="mb-4">
                <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                    Flat/House/Building Name <span class="text-red-600">*</span>
                </label>
                <textarea id="addr_address" name="address" rows="3" placeholder="Enter Flat/House/Building Name" class="addr-input w-full min-h-28 text-[#757575] text-base  placeholder:text-base border border-[#D5D5D5] px-4 outline-none py-3 focus:border-[#B4771E] resize-y"></textarea>
                <p class="addr-error mt-2 text-sm text-red-600" data-error-for="address"></p>
            </div>
            
            <!-- City & State -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        Town / City <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="addr_city" name="city" placeholder="Town / City" class="addr-input w-full h-[48px] text-[#757575] text-base  placeholder:text-base border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                    <p class="addr-error mt-2 text-sm text-red-600" data-error-for="city"></p>
                </div>
                <div>
                    <label class="block text-base md:text-lg text-[#131615] mb-2 font-semibold">
                        State / County <span class="text-red-600">*</span>
                    </label>
                    <select id="addr_state" name="state" class="addr-input w-full h-[48px] text-[#757575] text-base  border border-[#D5D5D5] px-4 outline-none focus:border-[#B4771E]">
                        <option value="">Select an Option...</option>
                        <option value="Gujarat">Gujarat</option>
                        <option value="Maharashtra">Maharashtra</option>
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
                    <input type="radio" name="addressType" id="home" value="home" class="hidden peer/home" checked>
                    <label for="home" class="cursor-pointer py-[6px] px-4 border border-[#D5D5D5]
                      rounded flex items-center gap-[8px] text-base text-[#131615] peer-checked/home:bg-[#B4771E1A]
                      peer-checked/home:border-[#B4771E1A] peer-checked/home:text-[#B4771E] transition-all">
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
                    
                    <input type="radio" name="addressType" id="work" value="work" class="hidden peer/work">
                    <label for="work" class="cursor-pointer py-[8px] px-4 border border-[#D5D5D5] rounded flex items-center gap-[8px] text-base  text-[#131615] peer-checked/work:bg-[#B4771E1A] peer-checked/work:border-[#B4771E1A] peer-checked/work:text-[#B4771E] transition-all">
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
            <button id="saveAddressBtn" onclick="saveCustomerAddress(event)" class="common-btn w-full h-[52px] rounded-sm">
                Save Address
            </button>
        </div>
    </div>
</div>
 
<!-- ── ADDRESS ACTION DROPDOWN ── -->
<div id="addrDropdown" class="absolute right-0 top-full mt-2 w-[180px] bg-white border border-[#D5D5D5]
   rounded-[8px] shadow-[0_4px_20px_rgba(0,0,0,0.12)] overflow-hidden z-20 hidden address-dropdown">
  <button onclick="editAddr()" class="w-full flex items-center gap-3 p-3 text-[#4A4A4A] hover:bg-[#FAFAFA] transition">
     <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.1767 2.48937L13.5825 1.08271C13.8756 0.789642 14.273 0.625 14.6875 0.625C15.102 0.625 15.4994 0.789642 15.7925 1.08271C16.0856 1.37577 16.2502 1.77325 16.2502 2.18771C16.2502 2.60216 16.0856 2.99964 15.7925 3.29271L6.94333 12.1419C6.50277 12.5822 5.95947 12.9058 5.3625 13.0835L3.125 13.7502L3.79167 11.5127C3.9694 10.9157 4.29303 10.3724 4.73333 9.93187L12.1767 2.48937ZM12.1767 2.48937L14.375 4.68771M13.125 10.4169V14.3752C13.125 14.8725 12.9275 15.3494 12.5758 15.701C12.2242 16.0527 11.7473 16.2502 11.25 16.2502H2.5C2.00272 16.2502 1.52581 16.0527 1.17417 15.701C0.822544 15.3494 0.625 14.8725 0.625 14.3752V5.62521C0.625 5.12793 0.822544 4.65101 1.17417 4.29938C1.52581 3.94775 2.00272 3.75021 2.5 3.75021H6.45833" stroke="#3D403F" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
      <span class="text-base font-normal">
                                                    Edit
                                                </span>
  </button>
 <div class="mx-3 border-t border-[#D5D5D5]"></div>
  <button onclick="deleteAddr()" class="w-full flex items-center gap-3 p-3
                                                text-[#4A4A4A] hover:bg-[#FFF7F7] transition">
  <svg width="15" height="18" viewBox="0 0 15 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.78345 6.25043L9.49512 13.7504M5.50512 13.7504L5.21679 6.25043M10.6251 3.2446C11.5949 3.31968 12.5617 3.43003 13.5235 3.57543C13.8085 3.61877 14.0918 3.6646 14.3751 3.71377M13.5235 3.57543L12.6335 15.1446C12.5971 15.6156 12.3843 16.0556 12.0376 16.3765C11.6909 16.6974 11.2359 16.8756 10.7635 16.8754H4.23679C3.76437 16.8756 3.30931 16.6974 2.9626 16.3765C2.6159 16.0556 2.40311 15.6156 2.36679 15.1446L1.47679 3.57543M1.47679 3.57543C1.19179 3.61793 0.908455 3.66377 0.625122 3.71293M1.47679 3.57543C2.43857 3.43003 3.40532 3.31968 4.37512 3.2446M10.6251 3.2446V2.48127C10.6251 1.49793 9.86679 0.677934 8.88346 0.647101C7.96147 0.617633 7.03878 0.617633 6.11679 0.647101C5.13346 0.677934 4.37512 1.49877 4.37512 2.48127V3.2446M10.6251 3.2446C8.54489 3.08383 6.45535 3.08383 4.37512 3.2446" stroke="#3D403F" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
   <span class="text-base font-normal">
                                                    Remove
                                                </span>
  </button>
         <div class="mx-3 border-t border-[#D5D5D5]"></div>
  <div class="border-t border-[#F0F0F0] set-default-divider"></div>
  <button onclick="setDefault()" class="w-full flex items-center gap-3 p-3 text-[#4A4A4A] hover:bg-[#FAFAFA] transition set-default-btn">
   <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M5.625 8.75L7.5 10.625L10.625 6.25M15.625 8.125C15.625 9.10991 15.431 10.0852 15.0541 10.9951C14.6772 11.9051 14.1247 12.7319 13.4283 13.4283C12.7319 14.1247 11.9051 14.6772 10.9951 15.0541C10.0852 15.431 9.10991 15.625 8.125 15.625C7.14009 15.625 6.16482 15.431 5.25487 15.0541C4.34493 14.6772 3.51814 14.1247 2.8217 13.4283C2.12526 12.7319 1.57281 11.9051 1.1959 10.9951C0.818993 10.0852 0.625 9.10991 0.625 8.125C0.625 6.13588 1.41518 4.22822 2.8217 2.8217C4.22822 1.41518 6.13588 0.625 8.125 0.625C10.1141 0.625 12.0218 1.41518 13.4283 2.8217C14.8348 4.22822 15.625 6.13588 15.625 8.125Z" stroke="#3D403F" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
     <span class="text-base font-normal">
                                                    Set as Default
                                                </span>
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

      $totalQty = $o->items->sum('quantity');
      
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
    @php
      $totalMrp = $o->items->sum(function($item) {
          $mrp = $item->product ? (float)$item->product->mrp : (float)$item->price;
          if ($mrp <= 0) {
              $mrp = (float)$item->price;
          }
          return round($mrp) * $item->quantity;
      });
    @endphp
    {
      id: {{ $o->id }},
      orderId: @json($o->order_no),
      name: @json($productName),
      price: {{ $o->final_amount ?? 0 }},
      mrp: {{ $totalMrp ?? 0 }},
      orderDate: @json($o->created_at->format('d M Y')),
      deliveryDate: @json($o->status == \App\Models\Order::STATUS_DELIVERED && $o->updated_at ? $o->updated_at->format('d M Y') : '-'),
      quantity: {{ $totalQty }},
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
  logout:    ['Log Out',      ''],
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

  document.getElementById('mobileSidebarTitle').textContent = titles[tab][0];
  if (tab === 'orders') renderOrders();
  if (tab === 'addresses') renderAddresses();

  // Set hash in URL
  if (window.location.hash !== '#' + tab) {
    window.location.hash = tab;
  }

  // Close mobile sidebar if open
  if (window.closeSidebar) window.closeSidebar();
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
 
function orderDetailUrl(id) {
  return '{{ route('customer.profile.view-order', ['id' => '__ID__']) }}'.replace('__ID__', id);
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
      <div class="border border-[#D5D5D5] p-4 bg-white">
        <div class="flex flex-col sm:flex-row gap-4">
            <!-- Product Image -->
            <a href="${orderDetailUrl(o.id)}" class="group relative shrink-0 sm:w-[190px] sm:h-[190px] overflow-hidden block">
                <img
                    src="${o.img}"
                    alt="${o.name}"
                    class="sm:w-[190px] sm:h-[190px] object-cover transform transition-all duration-700 ease-in-out group-hover:scale-105">
            </a>
            <!-- Product Details -->
            <div class="flex-1 min-w-0 flex justify-between flex-col">
                <h3 class="block product-title text-base md:text-[22px] font-semibold w-full min-w-0 overflow-hidden text-ellipsis whitespace-nowrap">
                    <a href="${orderDetailUrl(o.id)}" class="text-[#131615] hover:text-[#B4771E] transition">${o.name}</a>
                </h3>
                <!-- Price + Status -->
                <div class="flex justify-between items-center mt-[23px]">
                    <div class="flex items-center gap-2">
                        <span class="text-[#B4771E] text-base md:text-[22px] lg:text-[26px] font-bold">
                            ₹${o.price.toLocaleString('en-IN')}
                        </span>
                        ${o.mrp > o.price ? `
                        <span class="text-[#757575] line-through text-base md:text-lg">
                            ₹${o.mrp.toLocaleString('en-IN')}
                        </span>
                        ` : ''}
                    </div>
                    <div class="flex items-center gap-2 ${statusColor(o.status)} text-base md:text-lg">
                        <span class="w-[8px] h-[8px] rounded-full ${statusDot(o.status)}"></span>
                        ${o.status}
                    </div>
                </div>
                <!-- Details + Button -->
                <div class="flex justify-between md:items-end mt-[30px] flex-col md:flex-row gap-3">
                    <div class="space-y-2">
                        ${o.orderId && o.orderId.trim() !== '-' ? `
                        <div class="flex text-base">
                            <span class="font-medium text-[#131615] w-[120px]">
                                Order ID:
                            </span>
                            <span class="text-[#757575] ml-2">
                                ${o.orderId}
                            </span>
                        </div>` : ''}
                        ${o.orderDate && o.orderDate.trim() !== '-' ? `
                        <div class="flex text-base">
                           <span class="font-medium text-[#131615] w-[120px]">
                                Order Date:
                            </span>
                            <span class="text-[#757575] ml-2">
                                ${o.orderDate}
                            </span>
                        </div>` : ''}
                        ${o.deliveryDate && o.deliveryDate.trim() !== '-' ? `
                        <div class="flex text-base">
                             <span class="font-medium text-[#131615] w-[120px]">
                                Delivery Date:
                            </span>
                            <span class="text-[#757575] ml-2">
                                ${o.deliveryDate}
                            </span>
                        </div>` : ''}
                    </div>
                    <button onclick="viewOrder(${o.id})" class="common-btn h-[46px] text-lg">
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

function scrollToOrderList() {
  const listEl = document.getElementById('orderList');
  if (!listEl || !listEl.firstElementChild) return;

  const yOffset = -150;
  const y = listEl.getBoundingClientRect().top + window.pageYOffset + yOffset;
  window.scrollTo({ top: y, behavior: 'smooth' });
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
function goOrderPage(n) { orderPage = n; renderOrders(); scrollToOrderList(); }
function filterOrders() { orderPage=1; renderOrders(); }
 
function viewOrder(id) {
  window.location.href = '{{ route('customer.profile.view-order', ['id' => '__ID__']) }}'.replace('__ID__', id);
}
 
// ─────────────────────────────────────────────
// ADDRESSES
// ─────────────────────────────────────────────
function addrIcon(type) {
  if (type==='home') {
      return `
  <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M2.0625 10.9998L10.2703 2.79107C10.6737 2.38865 11.3263 2.38865 11.7288 2.79107L19.9375 10.9998M4.125 8.93732V18.2186C4.125 18.7878 4.587 19.2498 5.15625 19.2498H8.9375V14.7811C8.9375 14.2118 9.3995 13.7498 9.96875 13.7498H12.0313C12.6005 13.7498 13.0625 14.2118 13.0625 14.7811V19.2498H16.8438C17.413 19.2498 17.875 18.7878 17.875 18.2186V8.93732M7.5625 19.2498H15.125" stroke="#131615" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
  `;
    }
    return `
  <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M3.4375 19.25H18.5625M4.125 2.75H17.875M4.8125 2.75V19.25M17.1875 2.75V19.25M8.25 6.1875H9.625M8.25 8.9375H9.625M8.25 11.6875H9.625M12.375 6.1875H13.75M12.375 8.9375H13.75M12.375 11.6875H13.75M8.25 19.25V16.1563C8.25 15.587 8.712 15.125 9.28125 15.125H12.7188C13.288 15.125 13.75 15.587 13.75 16.1563V19.25" stroke="#131615" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
`;
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
    <div class="px-3 md:px-4 py-3 md:py-4 sm:py-[30px] ${a.isDefault ? 'bg-[#f5f2ee]' : 'bg-white'} ${index === addresses.length - 1 ? '' : 'border-b border-[#D5D5D5]'}" data-address-id="${a.id}">
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
              <span class="bg-[#B4771E29] text-[#B4771E] text-sm sm:text-base px-2 sm:px-[15px] py-[4px] font-semibold rounded-[2px] leading-[20px] default-badge">
                Default
              </span>
            ` : ''}
          </div>
          <p class="mt-2 text-sm md:text-base text-[#3D403F] leading-[22px]">
            ${a.addr}, ${a.city}, ${a.state} ${a.pin ? a.pin + ', ' : ''}India
          </p>
        </div>
        <button onclick="toggleAddrDropdown(${a.id}, event)" class="w-6 h-6 flex justify-center items-center address-menu-btn p-2 hover:bg-black/5 rounded-full transition focus:outline-none">
          <i class="fa-solid fa-ellipsis text-[#3D403F]"></i>
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
  
  const idToDelete = activeDropdownId;
  document.getElementById('addrDropdown').classList.add('hidden');
  
  window.showDeleteConfirm(() => {
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
          window.showWishlistToast(data.message || 'Address deleted successfully.', true);
        } else {
          alert(data.message || 'Address deleted successfully.');
        }
        renderAddresses();
      } else {
        if (window.showWishlistToast) {
          window.showWishlistToast(data.message || 'Failed to delete address.', false);
        } else {
          alert(data.message || 'Failed to delete address.');
        }
      }
    })
    .catch(err => {
      console.error('Error deleting address:', err);
      if (window.showWishlistToast) {
        window.showWishlistToast('Something went wrong.', false);
      } else {
        alert('Something went wrong.');
      }
    });
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

    document.getElementById("addressModal").classList.remove("hidden");

    document.documentElement.classList.add("modal-open");

    document.body.classList.add("modal-open");

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
  const file = e.target.files[0];
  if (!file) return;

  // Show preview immediately
  const reader = new FileReader();
  reader.onload = ev => document.getElementById('avatarImg').src = ev.target.result;
  reader.readAsDataURL(file);

  // Upload to server
  const formData = new FormData();
  formData.append('avatar', file);

  fetch('{{ route('customer.profile.avatar') }}', {
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
      // Set the persisted URL so page refresh also shows correct image
      document.getElementById('avatarImg').src = data.avatar_url;
      document.getElementById('avatarFileInput').value = '';
      if (window.showWishlistToast) window.showWishlistToast(data.message || 'Profile photo updated!');
    } else {
      if (window.showWishlistToast) window.showWishlistToast(data.message || 'Failed to update photo.', false);
    }
  })
  .catch(() => {
    if (window.showWishlistToast) window.showWishlistToast('Something went wrong uploading photo.', false);
  });
}

function updateNavbarCustomerName(name) {
  const navName = document.getElementById('navbarCustomerName');
  if (navName) navName.textContent = name;
  const mobileNavName = document.getElementById('mobileNavbarCustomerName');
  if (mobileNavName) mobileNavName.textContent = name;
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
      if (data.name) {
        document.getElementById('acctName').value = data.name;
      }
      updateNavbarCustomerName(data.name || name);
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
  const nameInput = document.getElementById('acctName');

  if (nameInput) {
    nameInput.addEventListener('input', function() {});
  }

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
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Check hash or query param for active tab
    const urlParams = new URLSearchParams(window.location.search);
    let tab = urlParams.get('tab') || window.location.hash.replace('#', '');
    
    if (tab && ['orders', 'addresses', 'account', 'logout'].includes(tab)) {
        const tabBtn = document.querySelector(`[data-tab="${tab}"]`);
        if (tabBtn) {
            switchTab(tab, tabBtn);
        }
    }

    // Listen for hashchange event to handle back/forward navigation
    window.addEventListener('hashchange', () => {
        let newTab = window.location.hash.replace('#', '');
        if (newTab && ['orders', 'addresses', 'account', 'logout'].includes(newTab)) {
            const tabBtn = document.querySelector(`[data-tab="${newTab}"]`);
            if (tabBtn) {
                switchTab(newTab, tabBtn);
            }
        }
    });

    const sidebar = document.getElementById("accountSidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const toggleBtn = document.getElementById("sidebarToggle");

    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.remove("left-[-100%]");
            sidebar.classList.add("left-0");
            overlay.classList.remove("hidden");

            document.body.style.overflow = "hidden";
        });
    }

    function closeSidebar() {
        sidebar.classList.remove("left-0");
        sidebar.classList.add("left-[-100%]");
        overlay.classList.add("hidden");

        document.body.style.overflow = "";
    }

    overlay?.addEventListener("click", closeSidebar);

    window.closeSidebar = closeSidebar;
});
</script>
@endsection
