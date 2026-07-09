<script type="application/json" id="reportProductsData">@json($products->values())</script>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted">Total Products</span>
                        <h4 class="mb-0 mt-1">{{ $activeProductCount }}</h4>
                    </div>
                    <span class="badge bg-label-primary rounded p-2"><i class="ti ti-box ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted">Total Stock Units</span>
                        @php
                            $totalStockUnits = $products->where('is_parent', true)->sum('total');
                        @endphp
                        <h4 class="mb-0 mt-1">{{ number_format($totalStockUnits) }}</h4>
                    </div>
                    <span class="badge bg-label-success rounded p-2"><i class="ti ti-stack ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted">SOLD OUT</span>
                        <h4 class="mb-0 mt-1">{{ $soldoutProductCount }}</h4>
                    </div>
                    <span class="badge bg-label-danger rounded p-2"><i class="ti ti-alert-triangle ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted">Locations</span>
                        <h4 class="mb-0 mt-1">{{ $locations->count() }}</h4>
                    </div>
                    <span class="badge bg-label-info rounded p-2"><i class="ti ti-map-pin ti-sm"></i></span>
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
    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <i class="ti ti-alert-triangle me-2 fs-5"></i>
        <strong>{{ $lowStock->count() }} product(s)</strong>&nbsp;have low stock (≤ 5 units).
    </div>
@endif

<!-- Stock Table -->
<div class="card">
    <div class="card-header"><h5 class="mb-0">Stock Detail by Location</h5></div>
    <div class="card-datatable table-responsive">
        <table class="table border-top" id="stockTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Barcode</th>
                    <th>Category</th>
                    @foreach($locations as $location)
                        <th class="text-center">{{ $location->name }}</th>
                    @endforeach
                    <th class="text-center">Total</th>
                    <th class="text-end">Stock Value</th>
                    <th>Last Purchase Date</th>
                    <th class="text-center">Inventory Age</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $index => $product)
                    <tr data-category-id="{{ $product['category_id'] }}"
                        data-total="{{ $product['total'] }}"
                        data-age="{{ $product['age_sort'] }}">
                        <td>{{ $index + 1 }}</td>
                        <td data-order="{{ $product['name'] }} {{ $product['is_parent'] ? '000_parent' : $product['variant_name'] }}">
                            @if($product['is_parent'])
                                <div class="d-flex align-items-center">
                                    @if($product['image_url'])
                                        <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="rounded me-2 product-thumbnail" style="width: 32px; height: 32px; object-fit: cover;">
                                    @else
                                        <div class="rounded bg-label-secondary me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="ti ti-photo text-muted" style="font-size: 1rem;"></i>
                                        </div>
                                    @endif
                                    <a href="{{ route('admin.products.show', $product['id']) }}" class="fw-semibold">
                                        {{ $product['name'] }}
                                    </a>
                                </div>
                            @else
                                <span class="text-muted ps-4">↳ {{ $product['variant_name'] }}</span>
                            @endif
                        </td>
                        <td>
                            @if($product['is_parent'])
                                <code>{{ $product['barcode'] }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($product['is_parent'])
                                <span class="badge bg-label-primary">{{ $product['category'] }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        @foreach($locations as $location)
                            @php $qty = $product['stock'][$location->id] ?? 0; @endphp
                            <td class="text-center">
                                <span class="badge {{ $qty > 5 ? 'bg-label-success' : ($qty > 0 ? 'bg-label-warning' : 'bg-label-secondary') }}">
                                    {{ $qty }}
                                </span>
                            </td>
                        @endforeach
                        <td class="text-center">
                            <span class="badge {{ $product['total'] > 5 ? 'bg-label-success' : ($product['total'] > 0 ? 'bg-label-warning' : 'bg-label-danger') }} fw-bold">
                                {{ $product['total'] }}
                            </span>
                        </td>
                        <td class="text-end">{{ format_price($product['stock_value']) }}</td>
                        <td data-order="{{ $product['last_purchase_date'] ?? '0000-00-00' }}">{{ $product['last_purchase_display'] }}</td>
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
        </table>
    </div>
</div>
