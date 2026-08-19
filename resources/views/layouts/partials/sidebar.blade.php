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

  <div class="menu-sidebar-search px-3 py-2">
    <div class="input-group input-group-merge">
      <span class="input-group-text"><i class="ti ti-search"></i></span>
      <input type="text" id="menuSearchInput" class="form-control" placeholder="Search menu..." autocomplete="off">
    </div>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1" id="menuInnerList">

    @php
      try {
          $modules = \Illuminate\Support\Facades\Cache::remember('admin_sidebar_modules', 3600, function () {
              return \App\Models\Module::whereNull('parent_id')->with('children')->orderBy('sort_order')->get();
          });
      } catch (\Exception $e) {
          $modules = collect();
      }
    @endphp

    @foreach($modules as $module)
      @php
        $checkChildVisible = function($child) {
            if (!auth()->check()) return false;
            if (in_array($child->permission, ['manage branch balances', 'view settings'])) {
                return auth()->user()->hasRole('super-admin');
            }
            return is_null($child->permission) || auth()->user()->can($child->permission);
        };

        $isVisible = false;
        if (!is_null($module->permission)) {
            if (auth()->check()) {
                if (in_array($module->permission, ['manage branch balances', 'view settings'])) {
                    $isVisible = auth()->user()->hasRole('super-admin');
                } else {
                    $isVisible = auth()->user()->can($module->permission);
                }
            }
        } else {
            if ($module->children->count() === 0) {
                $isVisible = true;
            } else {
                foreach ($module->children as $child) {
                    if ($checkChildVisible($child)) {
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
            @if($checkChildVisible($child))
              <li class="menu-item {{ active_menu($child->active_pattern) }}">
                <a href="{{ Route::has($child->route) ? route($child->route) : 'javascript:void(0);' }}" class="menu-link">
                  <i class="menu-icon tf-icons {{ $child->icon ?? 'ti ti-circle' }}"></i>
                  <div>{{ $child->name }}</div>
                  @if($child->route === 'admin.sales.index')
                    @php
                      try {
                          $authUser = auth()->user();
                          $pendingSalesCacheKey = 'admin_sidebar_pending_sales_count_' . ($authUser->location_id && !$authUser->hasRole('super-admin') ? $authUser->location_id : 'all');
                          $pendingSalesCount = \Illuminate\Support\Facades\Cache::remember($pendingSalesCacheKey, 30, function () use ($authUser) {
                              return \App\Models\Order::where('status', 1)
                                  ->when(
                                      $authUser->location_id && !$authUser->hasRole('super-admin'),
                                      fn($q) => $q->where('location_id', $authUser->location_id)
                                  )
                                  ->count();
                          });
                      } catch (\Exception $e) {
                          $pendingSalesCount = 0;
                      }
                    @endphp
                    @if($pendingSalesCount > 0)
                      <span class="badge bg-primary rounded-pill ms-auto notranslate" translate="no" style="background-color: #B78326 !important; color: #fff !important; width: 20px; height: 20px; display: inline-flex !important; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; flex-shrink: 0;">{{ $pendingSalesCount }}</span>
                    @endif
                  @endif
                  @if($child->route === 'admin.contact-inquiries.index')
                    @php
                      try {
                          $todayInquiriesCount = \Illuminate\Support\Facades\Cache::remember('admin_sidebar_today_inquiries_count_' . today()->toDateString(), 30, function () {
                              return \App\Models\ContactInquiry::whereDate('created_at', today())->count();
                          });
                      } catch (\Exception $e) {
                          $todayInquiriesCount = 0;
                      }
                    @endphp
                    @if($todayInquiriesCount > 0)
                      <span class="badge bg-primary rounded-pill ms-auto notranslate" translate="no" style="background-color: #B78326 !important; color: #fff !important; width: 20px; height: 20px; display: inline-flex !important; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; flex-shrink: 0;">{{ $todayInquiriesCount }}</span>
                    @endif
                  @endif
                  @if($child->route === 'admin.purchase-bills.index')
                    @php
                      try {
                          $authUser = auth()->user();
                          $isTransferRestricted = $authUser->location_id && !$authUser->hasRole('super-admin');
                          $pendingTransfersCacheKey = 'admin_sidebar_pending_transfers_count_' . ($isTransferRestricted ? $authUser->location_id : 'all');
                          $pendingTransfersCount = \Illuminate\Support\Facades\Cache::remember($pendingTransfersCacheKey, 30, function () use ($authUser, $isTransferRestricted) {
                              return \App\Models\PurchaseBill::where('status', \App\Models\PurchaseBill::STATUS_PENDING)
                                  ->when($isTransferRestricted, function ($q) use ($authUser) {
                                      $q->where(function ($sub) use ($authUser) {
                                          $sub->where('from_location_id', $authUser->location_id)
                                              ->orWhere('to_location_id', $authUser->location_id);
                                      });
                                  })
                                  ->count();
                          });
                      } catch (\Exception $e) {
                          $pendingTransfersCount = 0;
                      }
                    @endphp
                    <span id="purchase-bill-pending-badge" class="badge bg-primary rounded-pill ms-auto notranslate" translate="no" style="{{ $pendingTransfersCount > 0 ? 'display: inline-flex !important;' : 'display: none !important;' }} background-color: #B78326 !important; color: #fff !important; width: 20px; height: 20px; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; flex-shrink: 0;">{{ $pendingTransfersCount }}</span>
                  @endif
                  @if($child->route === 'admin.ledgers.branch')
                    @php
                      try {
                          $authUser = auth()->user();
                          $isBtRestricted = $authUser->location_id && !$authUser->hasRole('super-admin');
                          $pendingBtCacheKey = 'admin_sidebar_pending_bt_count_' . ($isBtRestricted ? $authUser->location_id : 'all');
                          $pendingBtCount = \Illuminate\Support\Facades\Cache::remember($pendingBtCacheKey, 30, function () use ($authUser, $isBtRestricted) {
                              return \App\Models\BranchBalanceTransfer::where('status', \App\Models\BranchBalanceTransfer::STATUS_PENDING)
                                  ->when($isBtRestricted, function ($q) use ($authUser) {
                                      $q->where(function ($sub) use ($authUser) {
                                          $sub->where('from_location_id', $authUser->location_id)
                                              ->orWhere('to_location_id', $authUser->location_id);
                                      });
                                  })
                                  ->count();
                          });
                      } catch (\Exception $e) {
                          $pendingBtCount = 0;
                      }
                    @endphp
                    <span id="branch-ledger-pending-badge" class="badge bg-primary rounded-pill ms-auto notranslate" translate="no" style="{{ $pendingBtCount > 0 ? 'display: inline-flex !important;' : 'display: none !important;' }} background-color: #B78326 !important; color: #fff !important; width: 20px; height: 20px; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; flex-shrink: 0;">{{ $pendingBtCount }}</span>
                  @endif
                </a>
              </li>
            @endif
          @endforeach
        @else
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

<script>
    (function () {
        const searchInput = document.getElementById('menuSearchInput');
        const menuList = document.getElementById('menuInnerList');
        if (!searchInput || !menuList) return;

        function resetMenuScroll() {
            if (menuList) menuList.scrollTop = 0;
            const layoutMenu = document.getElementById('layout-menu');
            if (layoutMenu) layoutMenu.scrollTop = 0;
            if (menuList && menuList.parentElement) menuList.parentElement.scrollTop = 0;

            const menuInnerShadow = document.querySelector('.menu-inner-shadow');
            if (menuInnerShadow) {
                menuInnerShadow.style.display = 'none';
            }

            if (menuList && menuList._ps) {
                try { menuList._ps.update(); } catch (e) {}
            }
            if (layoutMenu && layoutMenu._ps) {
                try { layoutMenu._ps.update(); } catch (e) {}
            }
        }

        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            const items = Array.from(menuList.children);
            let currentHeader = null;
            let headerHasVisibleChild = false;

            items.forEach((item) => {
                if (item.classList.contains('menu-header')) {
                    if (currentHeader) {
                        currentHeader.style.display = headerHasVisibleChild ? '' : 'none';
                    }
                    currentHeader = item;
                    headerHasVisibleChild = false;
                    return;
                }

                if (item.classList.contains('menu-item')) {
                    const label = item.querySelector('.menu-link div');
                    const text = label ? label.textContent.trim().toLowerCase() : '';
                    const matches = query === '' || text.includes(query);
                    item.style.display = matches ? '' : 'none';
                    if (matches) headerHasVisibleChild = true;
                }
            });

            if (currentHeader) {
                currentHeader.style.display = headerHasVisibleChild ? '' : 'none';
            }

            resetMenuScroll();
        });

        searchInput.addEventListener('focus', function () {
            resetMenuScroll();
        });
    })();
</script>

@if(Route::has('admin.purchase-bills.pending-count'))
<script>
    window.refreshPurchaseBillBadge = function () {
        const badge = document.getElementById('purchase-bill-pending-badge');
        if (!badge) return;
        fetch('{{ route('admin.purchase-bills.pending-count') }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(res => {
                if (res.status !== 'success') return;
                badge.textContent = res.count;
                badge.style.display = res.count > 0 ? 'inline-flex' : 'none';
            })
            .catch(() => {});
    };
</script>
@endif

@if(Route::has('admin.ledgers.branch.pending-count'))
<script>
    window.refreshBranchLedgerBadge = function () {
        const badge = document.getElementById('branch-ledger-pending-badge');
        if (!badge) return;
        fetch('{{ route('admin.ledgers.branch.pending-count') }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(res => {
                if (res.status !== 'success') return;
                badge.textContent = res.count;
                badge.style.display = res.count > 0 ? 'inline-flex' : 'none';
            })
            .catch(() => {});
    };
</script>
@endif

<script>
    (function() {
        setInterval(function() {
            if (typeof window.refreshPurchaseBillBadge === 'function') window.refreshPurchaseBillBadge();
            if (typeof window.refreshBranchLedgerBadge === 'function') window.refreshBranchLedgerBadge();
        }, 15000);
    })();
</script>
