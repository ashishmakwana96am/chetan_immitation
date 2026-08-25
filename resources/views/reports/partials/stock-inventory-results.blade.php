<script type="application/json" id="locationChartData">@json($locationChartData)</script>
<script type="application/json" id="stackedChartData">@json($stackedChartData)</script>

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
                        <div class="mb-0 mt-1 text-info fw-bold lh-sm" style="font-size: 0.95rem; line-height: 1.2;">{!! $totalStockDisplay !!}</div>
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
                        <h4 class="mb-0 mt-1 text-primary">{{ $totalPurchaseValueDisplay }}</h4>
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
                        <h4 class="mb-0 mt-1 text-success">{{ $totalMrpValueDisplay }}</h4>
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
@if($lowStockCount > 0)
    <div class="alert alert-warning d-flex align-items-center mb-4 flex-wrap" role="alert">
        <i class="ti ti-alert-triangle me-2 fs-5"></i>
        <strong>{{ $lowStockCount }} product(s)</strong>&nbsp;have low stock (≤ 5 units).
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
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Stock Detail by Location</h5>
    </div>
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
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold" id="stockTableFooterRow">
                    <td colspan="5" class="text-end">Total:</td>
                    @foreach($locations as $location)
                        <td class="text-center text-primary text-nowrap" data-loc-total="{{ $location->id }}">…</td>
                    @endforeach
                    <td class="text-center text-primary text-nowrap" data-footer="qty">…</td>
                    <td class="text-end text-primary text-nowrap" data-footer="purchase">…</td>
                    <td class="text-end text-success text-nowrap" data-footer="mrp">…</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
