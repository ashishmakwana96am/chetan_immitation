<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand ecom" style="display: flex; align-items: center; background-color: #000; position: relative; z-index: 10; padding-top: 5px; padding-bottom: 5px;">
    <a href="{{ route('admin.dashboard') }}" class="app-brand-link" style="flex: 1; overflow: visible; min-width: 0;">
      <span class="app-brand-logo ecom" style="display: flex; align-items: center; width: 100%; overflow: visible;">
        <img src="{{ asset('assets/img/logo.png') }}" alt="Chetan Imitation" style="width: 100%; max-width: 200px; max-height: 85px; height: auto; object-fit: contain; display: block;" />
      </span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto" style="z-index: 11;">
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
                  @if($child->route === 'admin.sales.index')
                    @php
                      try {
                          $pendingSalesCount = \App\Models\Order::where('status', 1)->count();
                      } catch (\Exception $e) {
                          $pendingSalesCount = 0;
                      }
                    @endphp
                    <div class="pending-sales-counter-badge" style="display: {{ $pendingSalesCount > 0 ? 'inline-flex' : 'none' }} !important; align-items: center; justify-content: center; width: 20px; height: 20px; background: #B78326; color: #fff; border-radius: 50%; font-size: 9px; font-weight: 700; line-height: 1; margin-left: auto; flex-shrink: 0;">{{ $pendingSalesCount }}</div>
                  @endif
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
