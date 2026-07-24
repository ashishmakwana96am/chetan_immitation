<script type="application/json" id="reportProductsData">@json($products->values())</script>

@php
    $totalStockUnits    = $products->where('is_parent', true)->sum('total');
    $totalPurchaseValue = $products->where('is_parent', true)->sum('purchase_value');
    $totalMrpValue      = $products->where('is_parent', true)->sum('mrp_value');
    $totalPairUnits     = $products->where('is_parent', true)->sum('pair_count');

    $totalLoosePcs      = $products->where('is_parent', true)->sum('loose_pcs');

    $reportStockParts = [];
    if ($totalPairUnits > 0) {
        $reportStockParts[] = number_format($totalPairUnits) . ' Pair' . ($totalPairUnits > 1 ? 's' : '');
    }
    if ($totalLoosePcs > 0 || count($reportStockParts) === 0) {
        $reportStockParts[] = number_format($totalLoosePcs) . ' Pcs';
    }
    $reportStockDisplay = implode(', ', $reportStockParts);
@endphp

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted small">Total Products</span>
                        <h4 class="mb-0 mt-1">{{ $activeProductCount }}</h4>
                    </div>
                    <span class="badge bg-label-primary rounded p-2"><i class="ti ti-box ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted small">Total Stock Units</span>
                        <h4 class="mb-0 mt-1 text-info">{{ $reportStockDisplay }}</h4>
                    </div>
                    <span class="badge bg-label-info rounded p-2"><i class="ti ti-stack ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted small">Total Purchase Value</span>
                        <h4 class="mb-0 mt-1 text-primary">{{ format_price($totalPurchaseValue) }}</h4>
                    </div>
                    <span class="badge bg-label-warning rounded p-2"><i class="ti ti-currency-rupee ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted small">Total MRP Value</span>
                        <h4 class="mb-0 mt-1 text-success">{{ format_price($totalMrpValue) }}</h4>
                    </div>
                    <span class="badge bg-label-success rounded p-2"><i class="ti ti-chart-dots ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted small">SOLD OUT</span>
                        <h4 class="mb-0 mt-1 text-danger">{{ $soldoutProductCount }}</h4>
                    </div>
                    <span class="badge bg-label-danger rounded p-2"><i class="ti ti-alert-triangle ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted small">Locations / Branches</span>
                        <h4 class="mb-0 mt-1">{{ $locations->count() }}</h4>
                    </div>
                    <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-map-pin ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Stock per Location (Bar) -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Total Stock per Location</h5></div>
            <div class="card-body">
                <div id="locationStockChart"></div>
            </div>
        </div>
    </div>

    <!-- Stock Distribution (Stacked Bar - Top 10 products) -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Top 10 Products — Stock by Location</h5></div>
            <div class="card-body">
                <div id="stackedStockChart"></div>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alert -->
@php $lowStock = $products->filter(fn($p) => $p['total'] > 0 && $p['total'] <= 5)->sortBy('total'); @endphp
@if($lowStock->count())
    <div class="alert alert-warning d-flex align-items-center mb-4 flex-wrap" role="alert">
        <i class="ti ti-alert-triangle me-2 fs-5"></i>
        <strong>{{ $lowStock->count() }} product(s)</strong>&nbsp;have low stock (≤ 5 units).
    </div>
@endif

<!-- Filters -->
<div class="card mb-4" id="filterReportCard">
    <div class="card-header">
        <h5 class="mb-0">Filter Report</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.stock-inventory') }}" id="filterForm" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">From Date <small class="text-muted">(Last Purchase)</small></label>
                <input type="text" name="from_date" class="form-control flatpickr" value="{{ $fromDate }}" placeholder="DD-MM-YYYY" />
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date <small class="text-muted">(Last Purchase)</small></label>
                <input type="text" name="to_date" class="form-control flatpickr" value="{{ $toDate }}" placeholder="DD-MM-YYYY" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Filter by Category</label>
                <select id="filterCategory" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Filter by Stock</label>
                <select id="filterStock" class="form-select">
                    <option value="">All</option>
                    <option value="in">In Stock</option>
                    <option value="low">Low Stock (≤ 5)</option>
                    <option value="out">SOLD OUT</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Show Products Older Than</label>
                <select id="filterAge" class="form-select">
                    <option value="">Any Age</option>
                    <option value="30">30 Days</option>
                    <option value="60">60 Days</option>
                    <option value="90">90 Days</option>
                    <option value="180">180 Days</option>
                    <option value="365">365 Days</option>
                    <option value="custom">Custom Days</option>
                </select>
            </div>
            <div class="col-md-3 d-none" id="customAgeWrapper">
                <label class="form-label">Custom Days</label>
                <input type="number" id="filterAgeCustom" class="form-control" min="1" placeholder="e.g. 45" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Sort By</label>
                <select id="sortBy" class="form-select">
                    <option value="">Default</option>
                    <option value="age_desc">Inventory Age (Oldest First)</option>
                    <option value="age_asc">Inventory Age (Newest First)</option>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 mt-4 d-none" id="filterActionButtons">
                <button type="button" id="clearFiltersBtn" class="btn btn-outline-primary">
                    <i class="ti ti-refresh me-1"></i> Clear
                </button>
                <button type="button" id="applyFiltersBtn" class="btn btn-primary">
                    <i class="ti ti-filter me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Stock Table -->
<div class="card" id="stockDetailCard">
    <div class="card-header"><h5 class="mb-0">Stock Detail by Location</h5></div>
    <div class="card-datatable table-responsive">
        <table class="table border-top" id="stockTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Last Purchase Date</th>
                    <th>Barcode</th>
                    <th>Category</th>
                    @foreach($locations as $location)
                        <th class="text-center">{{ $location->name }}</th>
                    @endforeach
                    <th class="text-center">Total Qty</th>
                    <th class="text-end">Purchase Value</th>
                    <th class="text-end">MRP Value</th>
                    <th class="text-center">Inventory Age</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $parentProductsList = $products->where('is_parent', true)->values();
                    $variantProductsMap = $products->where('is_parent', false)->groupBy('id');
                    $groupedStockProducts = $parentProductsList->map(fn ($prod) => [
                        'parent' => $prod,
                        'variants' => $variantProductsMap->get($prod['id'], collect())->values(),
                    ]);
                @endphp

                @foreach($groupedStockProducts as $index => $group)
                    @php
                        $product = $group['parent'];
                        $variants = $group['variants'];
                        $hasVariants = $variants->count() > 0;
                    @endphp
                    <tr class="parent-row"
                        data-product-id="{{ $product['id'] }}"
                        data-category-id="{{ $product['category_id'] }}"
                        data-total="{{ $product['total'] }}"
                        data-age="{{ $product['age_sort'] }}"
                        data-has-variants="{{ $hasVariants ? '1' : '0' }}">
                        <td>{{ $index + 1 }}</td>
                        <td data-order="{{ $product['name'] }} 000_parent">
                            <div class="d-flex align-items-center">
                                @if($hasVariants)
                                    <button type="button" class="btn btn-icon btn-sm variant-toggle me-2" data-product-id="{{ $product['id'] }}" aria-expanded="false">
                                        <i class="ti ti-chevron-right"></i>
                                    </button>
                                @else
                                    <span class="me-2" style="width: 24px;"></span>
                                @endif
                                <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="rounded me-2 product-thumbnail" style="width: 32px; height: 32px; object-fit: cover;">
                                <a href="{{ route('admin.products.show', $product['id']) }}" class="fw-semibold">
                                    {{ $product['name'] }}
                                </a>
                            </div>
                        </td>
                        <td data-order="{{ $product['last_purchase_date'] ?? '' }}">{{ $product['last_purchase_display'] }}</td>
                        <td><code>{{ $product['barcode'] }}</code></td>
                        <td><span class="badge bg-label-primary">{{ $product['category'] }}</span></td>
                        @foreach($locations as $location)
                            @php 
                                $qty = $product['stock'][$location->id] ?? 0;
                                $displayQty = (isset($product['product_obj']) && $product['product_obj'] instanceof \App\Models\Product) 
                                    ? $product['product_obj']->formatStockDisplay($qty) 
                                    : $qty;
                            @endphp
                            <td class="text-center">
                                <span class="badge {{ $qty > 5 ? 'bg-label-success' : ($qty > 0 ? 'bg-label-warning' : 'bg-label-secondary') }}">
                                    {{ $displayQty }}
                                </span>
                            </td>
                        @endforeach
                        <td class="text-center">
                            @php
                                $totalDisplay = (isset($product['product_obj']) && $product['product_obj'] instanceof \App\Models\Product) 
                                    ? $product['product_obj']->formatStockDisplay($product['total']) 
                                    : $product['total'];
                            @endphp
                            <span class="badge {{ $product['total'] > 5 ? 'bg-label-success' : ($product['total'] > 0 ? 'bg-label-warning' : 'bg-label-danger') }} fw-bold">
                                {{ $totalDisplay }}
                            </span>
                        </td>
                        <td class="text-end fw-semibold">{{ format_price($product['purchase_value']) }}</td>
                        <td class="text-end fw-semibold text-success">{{ format_price($product['mrp_value']) }}</td>
                        <td class="text-center" data-order="{{ $product['age_sort'] }}">
                            @if(is_null($product['age_days']))
                                <span class="badge bg-label-secondary">{{ $product['age_display'] }}</span>
                            @elseif($product['age_days'] >= 180)
                                <span class="badge bg-label-danger">{{ $product['age_display'] }}</span>
                            @elseif($product['age_days'] >= 90)
                                <span class="badge bg-label-warning">{{ $product['age_display'] }}</span>
                            @else
                                <span class="badge bg-label-success">{{ $product['age_display'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="{{ 5 + $locations->count() }}" class="text-end">Total:</td>
                    <td class="text-center text-primary">{{ $reportStockDisplay }}</td>
                    <td class="text-end text-primary">{{ format_price($totalPurchaseValue) }}</td>
                    <td class="text-end text-success">{{ format_price($totalMrpValue) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        @foreach($groupedStockProducts as $group)
            @php
                $product = $group['parent'];
                $variants = $group['variants'];
            @endphp
            @if($variants->count())
                <template id="variants-template-{{ $product['id'] }}">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm mb-0 variant-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Variant</th>
                                    <th>Last Purchase Date</th>
                                    @foreach($locations as $location)
                                        <th class="text-center">{{ $location->name }}</th>
                                    @endforeach
                                    <th class="text-center">Total Qty</th>
                                    <th class="text-end">Purchase Value</th>
                                    <th class="text-end">MRP Value</th>
                                    <th class="text-center">Inventory Age</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($variants as $vIndex => $variant)
                                    <tr>
                                        <td>{{ $vIndex + 1 }}</td>
                                        <td class="ps-4">{{ $variant['variant_name'] }}</td>
                                        <td>{{ $variant['last_purchase_display'] }}</td>
                                        @foreach($locations as $location)
                                            @php 
                                                $vQty = $variant['stock'][$location->id] ?? 0;
                                                $vDisplayQty = (isset($product['product_obj']) && $product['product_obj'] instanceof \App\Models\Product)
                                                    ? $product['product_obj']->formatStockDisplay($vQty)
                                                    : $vQty;
                                            @endphp
                                            <td class="text-center">
                                                <span class="badge {{ $vQty > 5 ? 'bg-label-success' : ($vQty > 0 ? 'bg-label-warning' : 'bg-label-secondary') }}">
                                                    {{ $vDisplayQty }}
                                                </span>
                                            </td>
                                        @endforeach
                                        @php
                                            $vTotalDisplay = (isset($product['product_obj']) && $product['product_obj'] instanceof \App\Models\Product)
                                                ? $product['product_obj']->formatStockDisplay($variant['total'])
                                                : $variant['total'];
                                        @endphp
                                        <td class="text-center">
                                            <span class="badge {{ $variant['total'] > 5 ? 'bg-label-success' : ($variant['total'] > 0 ? 'bg-label-warning' : 'bg-label-danger') }} fw-bold">
                                                {{ $vTotalDisplay }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold">{{ format_price($variant['purchase_value']) }}</td>
                                        <td class="text-end fw-semibold text-success">{{ format_price($variant['mrp_value']) }}</td>
                                        <td class="text-center">
                                            @if(is_null($variant['age_days']))
                                                <span class="badge bg-label-secondary">{{ $variant['age_display'] }}</span>
                                            @elseif($variant['age_days'] >= 180)
                                                <span class="badge bg-label-danger">{{ $variant['age_display'] }}</span>
                                            @elseif($variant['age_days'] >= 90)
                                                <span class="badge bg-label-warning">{{ $variant['age_display'] }}</span>
                                            @else
                                                <span class="badge bg-label-success">{{ $variant['age_display'] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </template>
            @endif
        @endforeach
    </div>
</div>
