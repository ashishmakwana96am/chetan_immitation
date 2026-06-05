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

    @php
      try {
          $modules = \App\Models\Module::whereNull('parent_id')->with('children')->orderBy('sort_order')->get();
      } catch (\Exception $e) {
          $modules = collect();
      }
    @endphp

    @foreach($modules as $module)
      @php
        $isVisible = false;
        if (!is_null($module->permission)) {
            if (auth()->check() && auth()->user()->can($module->permission)) {
                $isVisible = true;
            }
        } else {
            if ($module->children->count() === 0) {
                $isVisible = true; // Always show if no permission and no children
            } else {
                foreach ($module->children as $child) {
                    if (is_null($child->permission) || (auth()->check() && auth()->user()->can($child->permission))) {
                        $isVisible = true;
                        break;
                    }
                }
            }
        }
      @endphp

      @if($isVisible)
        @if($module->children->count() > 0)
          <li class="menu-header small text-uppercase">
            <span class="menu-header-text">{{ $module->name }}</span>
          </li>
          @foreach($module->children as $child)
            @if(is_null($child->permission) || (auth()->check() && auth()->user()->can($child->permission)))
              <li class="menu-item {{ active_menu($child->active_pattern) }}">
                <a href="{{ Route::has($child->route) ? route($child->route) : 'javascript:void(0);' }}" class="menu-link">
                  <i class="menu-icon tf-icons {{ $child->icon ?? 'ti ti-circle' }}"></i>
                  <div>{{ $child->name }}</div>
                </a>
              </li>
            @endif
          @endforeach
        @else
          {{-- Flat Menu Item (e.g. Dashboard, Users, Locations) --}}
          <li class="menu-item {{ active_menu($module->active_pattern) }}">
            <a href="{{ (!is_null($module->route) && Route::has($module->route)) ? route($module->route) : 'javascript:void(0);' }}" class="menu-link">
              <i class="menu-icon tf-icons {{ $module->icon ?? 'ti ti-circle' }}"></i>
              <div>{{ $module->name }}</div>
            </a>
          </li>
        @endif
      @endif
    @endforeach

  </ul>
</aside>
