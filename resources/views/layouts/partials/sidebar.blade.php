<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand ecom">
    <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
      <span class="app-brand-logo ecom">
        <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z" fill="#7367F0" />
          <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z" fill="#161616" />
          <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z" fill="#161616" />
          <path fill-rule="evenodd" clip-rule="evenodd" d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z" fill="#7367F0" />
        </svg>
      </span>
      <span class="app-brand-text ecom menu-text fw-bold" style="font-size: 22px; line-height: 1.2; white-space: normal; text-align: center; padding-left: 5px;">
        Chetan<br>Immitation
      </span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">

    <!-- Dashboard -->
    <li class="menu-item {{ active_menu('admin/dashboard') }}">
      <a href="{{ route('admin.dashboard') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-smart-home"></i>
        <div>Dashboard</div>
      </a>
    </li>

    <!-- Apps & Pages -->
    <li class="menu-header small text-uppercase">
      <span class="menu-header-text">Apps &amp; Pages</span>
    </li>

    <!-- Users -->
    @if(can_any(['view users']))
      <li class="menu-item {{ active_menu('admin/users*') }}">
        <a href="{{ route('admin.users.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-user"></i>
          <div>Users</div>
        </a>
      </li>
    @endif

    <!-- Locations -->
    @if(can_any(['view locations']))
      <li class="menu-item {{ active_menu('admin/locations*') }}">
        <a href="{{ route('admin.locations.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-map-pin"></i>
          <div>Locations</div>
        </a>
      </li>
    @endif

    <!-- Categories -->
    @if(can_any(['view categories']))
      <li class="menu-item {{ active_menu('admin/categories*') }}">
        <a href="{{ route('admin.categories.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-category"></i>
          <div>Categories</div>
        </a>
      </li>
    @endif

    <!-- Products -->
    @if(can_any(['view products']))
      <li class="menu-item {{ active_menu('admin/products*') }}">
        <a href="{{ route('admin.products.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-box"></i>
          <div>Products</div>
        </a>
      </li>
    @endif

    <!-- Suppliers -->
    @if(can_any(['view suppliers']))
      <li class="menu-item {{ active_menu('admin/suppliers*') }}">
        <a href="{{ route('admin.suppliers.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-truck"></i>
          <div>Suppliers</div>
        </a>
      </li>
    @endif

    <!-- Purchases -->
    @if(can_any(['view purchases']))
      <li class="menu-item {{ active_menu('admin/purchases*') }}">
        <a href="{{ route('admin.purchases.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-shopping-cart"></i>
          <div>Purchases</div>
        </a>
      </li>
    @endif

    <!-- Customers -->
    @if(can_any(['view customers']))
      <li class="menu-item {{ active_menu('admin/customers*') }}">
        <a href="{{ route('admin.customers.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-users"></i>
          <div>Customers</div>
        </a>
      </li>
    @endif

    <!-- Sales -->
    @if(can_any(['view sales']))
      <li class="menu-item {{ active_menu('admin/sales*') }}">
        <a href="{{ route('admin.sales.index') }}" class="menu-link">
          <i class="menu-icon tf-icons ti ti-receipt"></i>
          <div>Sales</div>
        </a>
      </li>
    @endif

    <!-- Reports -->
    @if(can_any(['view reports']))
      <li class="menu-item {{ active_menu_open(['admin/reports*']) }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons ti ti-chart-bar"></i>
          <div>Reports</div>
        </a>
        <ul class="menu-sub">
          <li class="menu-item {{ active_menu('admin/reports/products') }}">
            <a href="{{ route('admin.reports.products') }}" class="menu-link">
              <div>Products Report</div>
            </a>
          </li>
          <li class="menu-item {{ active_menu('admin/reports/stock-inventory') }}">
            <a href="{{ route('admin.reports.stock-inventory') }}" class="menu-link">
              <div>Stock Inventory</div>
            </a>
          </li>
        </ul>
      </li>
    @endif

    <!-- Roles & Permissions -->
    @if(can_any(['view roles', 'view permissions']))
      <li class="menu-item {{ active_menu_open(['admin/roles*', 'admin/permissions*']) }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon tf-icons ti ti-shield-lock"></i>
          <div>Roles &amp; Permissions</div>
        </a>
        <ul class="menu-sub">
          @if(can_any(['view roles']))
            <li class="menu-item {{ active_menu('admin/roles*') }}">
              <a href="{{ route('admin.roles.index') }}" class="menu-link">
                <div>Roles</div>
              </a>
            </li>
          @endif
          @if(can_any(['view permissions']))
            <li class="menu-item {{ active_menu('admin/permissions*') }}">
              <a href="{{ route('admin.permissions.index') }}" class="menu-link">
                <div>Permissions</div>
              </a>
            </li>
          @endif
        </ul>
      </li>
    @endif

  </ul>
</aside>
