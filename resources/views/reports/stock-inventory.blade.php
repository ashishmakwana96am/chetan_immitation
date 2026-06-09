@extends('layouts.app')

@section('title', 'Stock Inventory Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Stock Inventory Report</h4>
        <button id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Products</span>
                            <h4 class="mb-0 mt-1">{{ count($products) }}</h4>
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
                            <h4 class="mb-0 mt-1">{{ $products->sum('total') }}</h4>
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
                            <span class="text-muted">Out of Stock</span>
                            <h4 class="mb-0 mt-1">{{ $products->where('total', 0)->count() }}</h4>
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

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Report</h5>
            <button type="button" id="resetFilters" class="btn btn-sm btn-label-secondary">
                <i class="ti ti-refresh me-1"></i> Reset
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Category</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Stock</label>
                    <select id="filterStock" class="form-select">
                        <option value="">All</option>
                        <option value="in">In Stock</option>
                        <option value="low">Low Stock (≤ 5)</option>
                        <option value="out">Out of Stock</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Stock Detail by Location</h5></div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="stockTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        @foreach($locations as $location)
                            <th class="text-center">{{ $location->name }}</th>
                        @endforeach
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $index => $product)
                        <tr data-category-id="{{ $product['category_id'] }}"
                            data-total="{{ $product['total'] }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <a href="{{ route('admin.products.show', $product['id']) }}" class="fw-semibold">
                                    {{ $product['name'] }}
                                </a>
                            </td>
                            <td><code>{{ $product['sku'] }}</code></td>
                            <td><span class="badge bg-label-primary">{{ $product['category'] }}</span></td>
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    $(document).ready(function () {

        // -------------------------------------------------------
        // DataTable
        // -------------------------------------------------------
        const table = $('#stockTable').DataTable({
            responsive : false,
            order      : [[1, 'asc']],
        });

        function applyFilters() {
            const cat   = $('#filterCategory').val();
            const stock = $('#filterStock').val();

            $.fn.dataTable.ext.search = [];
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                const row   = $(table.row(dataIndex).node());
                const total = parseInt(row.data('total'));
                if (cat              && row.data('category-id') != cat) return false;
                if (stock === 'in'   && total <= 0)                     return false;
                if (stock === 'low'  && (total === 0 || total > 5))     return false;
                if (stock === 'out'  && total > 0)                      return false;
                return true;
            });
            table.draw();
        }

        $('#filterCategory, #filterStock').on('change', applyFilters);

        $('#resetFilters').on('click', function() {
            $('#filterCategory').val('');
            $('#filterStock').val('');
            applyFilters();
        });

        $('#exportExcelBtn').on('click', function() {
            const cat = $('#filterCategory').val();
            const stock = $('#filterStock').val();
            
            let url = "{{ route('admin.reports.stock-inventory.export') }}?";
            let params = [];
            if (cat) params.push('category_id=' + cat);
            if (stock) params.push('stock=' + stock);
            
            window.location.href = url + params.join('&');
        });

        // -------------------------------------------------------
        // Stock per Location Bar Chart
        // -------------------------------------------------------
        const locations = @json($locations->pluck('name'));
        const locationIds = @json($locations->pluck('id'));
        const products  = @json($products->values());

        const locationTotals = locationIds.map(function (locId) {
            return products.reduce(function (sum, p) {
                return sum + (p.stock[locId] || 0);
            }, 0);
        });

        // Total Stock per Location Horizontal Bar Chart
        new ApexCharts(document.getElementById('locationStockChart'), {
            chart  : { type: 'bar', height: 380, toolbar: { show: false } },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '55%',
                    distributed: true
                }
            },
            colors  : ['#B4771E', '#28c76f', '#328693', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8'],
            series : [{ name: 'Total Stock', data: locationTotals }],
            xaxis  : { 
                categories: locations,
                labels: {
                    style: {
                        colors: '#5d596c',
                        fontFamily: 'Public Sans'
                    },
                    formatter: function(val) {
                        return parseInt(val);
                    }
                }
            },
            yaxis  : {
                labels: {
                    style: {
                        colors: '#5d596c',
                        fontFamily: 'Public Sans',
                        fontWeight: 500
                    }
                }
            },
            dataLabels : { 
                enabled: true,
                style: {
                    fontSize: '11px',
                    fontFamily: 'Public Sans',
                    fontWeight: '600',
                    colors: ['#fff']
                },
                formatter: function(val) {
                    return parseInt(val);
                }
            },
            legend: { show: false },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' Units';
                    }
                }
            },
            grid: {
                borderColor: '#e5e5e5',
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } },
                padding: { top: -15, right: 10, bottom: -10, left: 10 }
            }
        }).render();

        // -------------------------------------------------------
        // Top 10 Products Horizontal Stacked Bar by Location
        // -------------------------------------------------------
        const top10 = products.slice().sort((a, b) => b.total - a.total).slice(0, 10);

        const stackedSeries = locationIds.map(function (locId, i) {
            return {
                name : locations[i],
                data : top10.map(p => p.stock[locId] || 0),
            };
        });

        new ApexCharts(document.getElementById('stackedStockChart'), {
            chart  : { type: 'bar', height: 380, stacked: true, toolbar: { show: false } },
            series : stackedSeries,
            xaxis  : { 
                categories: top10.map(p => p.name),
                labels: {
                    style: {
                        colors: '#5d596c',
                        fontFamily: 'Public Sans'
                    },
                    formatter: function(val) {
                        return parseInt(val);
                    }
                }
            },
            yaxis  : {
                labels: {
                    style: {
                        colors: '#5d596c',
                        fontFamily: 'Public Sans',
                        fontWeight: 500
                    }
                }
            },
            plotOptions: { 
                bar: { 
                    horizontal: true,
                    borderRadius: 4, 
                    barHeight: '60%' 
                } 
            },
            dataLabels : { 
                enabled: true,
                style: {
                    fontSize: '10px',
                    fontFamily: 'Public Sans',
                    fontWeight: '600',
                    colors: ['#fff']
                },
                formatter: function(val) {
                    return val > 0 ? val : '';
                }
            },
            legend     : { 
                position: 'bottom',
                fontFamily: 'Public Sans',
                fontSize: '11px',
                labels: { colors: '#5d596c' },
                itemMargin: { horizontal: 8, vertical: 4 }
            },
            grid: {
                borderColor: '#e5e5e5',
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } },
                padding: { top: -15, right: 10, bottom: -10, left: 10 }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' Units';
                    }
                }
            }
        }).render();

    });
    </script>
@endsection
