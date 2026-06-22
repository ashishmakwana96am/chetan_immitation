@extends('layouts.app')

@section('title', 'Products Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Products Report</h4>
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
                            <span class="text-muted">Active Products</span>
                            <h4 class="mb-0 mt-1">{{ $products->where('status', 1)->count() }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-check ti-sm"></i></span>
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
                            <h4 class="mb-0 mt-1">{{ $products->where('total_stock', 0)->count() }}</h4>
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
                            <span class="text-muted">Categories</span>
                            <h4 class="mb-0 mt-1">{{ $categories->count() }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-category ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">

        <!-- Products by Category (Pie) -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Products by Category</h5></div>
                <div class="card-body">
                    <div id="categoryPieChart"></div>
                </div>
            </div>
        </div>

        <!-- Top 10 by Stock (Bar) -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Top 10 Products by Stock</h5></div>
                <div class="card-body">
                    <div id="topStockChart"></div>
                </div>
            </div>
        </div>

    </div>

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
                    <label class="form-label">Filter by Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="2">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Stock</label>
                    <select id="filterStock" class="form-select">
                        <option value="">All</option>
                        <option value="in">In Stock</option>
                        <option value="out">SOLD OUT</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Products Detail</h5></div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="productsReportTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="text-end">Purchase Price</th>
                        <th class="text-end">Sale Price</th>
                        <th class="text-end">Margin</th>
                        <th class="text-end">Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $index => $product)
                        @php
                            $margin    = $product['sale_price'] - $product['purchase_price'];
                            $marginPct = $product['purchase_price'] > 0 ? round(($margin / $product['purchase_price']) * 100, 1) : 0;
                        @endphp
                        <tr data-category-id="{{ $product['category_id'] }}"
                            data-status="{{ $product['status'] }}"
                            data-stock="{{ $product['total_stock'] }}">
                            <td>{{ $index + 1 }}</td>
                            <td data-order="{{ $product['name'] }} {{ $product['is_parent'] ? '000_parent' : $product['variant_name'] }}">
                                @if($product['is_parent'])
                                    <a href="{{ route('admin.products.show', $product['id']) }}" class="fw-semibold">
                                        {{ $product['name'] }}
                                    </a>
                                @else
                                    <span class="text-muted ps-4">↳ {{ $product['variant_name'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if($product['is_parent'])
                                    <code>{{ $product['sku'] }}</code>
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
                            <td class="text-end">{{ format_price($product['purchase_price']) }}</td>
                            <td class="text-end">{{ format_price($product['sale_price']) }}</td>
                            <td class="text-end">
                                <span class="badge {{ $margin >= 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ format_price($margin) }} ({{ $marginPct }}%)
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="badge {{ $product['total_stock'] > 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $product['total_stock'] }}
                                </span>
                            </td>
                            <td>{!! status_badge($product['status']) !!}</td>
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
        const table = $('#productsReportTable').DataTable({
            responsive : false,
            order      : [[1, 'asc']],
        });

        function applyFilters() {
            const cat    = $('#filterCategory').val();
            const status = $('#filterStatus').val();
            const stock  = $('#filterStock').val();

            $.fn.dataTable.ext.search = [];
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                const row   = $(table.row(dataIndex).node());
                const total = parseInt(row.data('total') || row.data('stock'));
                if (cat    && row.data('category-id') != cat)    return false;
                if (status && row.data('status') !== status)      return false;
                if (stock === 'in'  && total <= 0)                return false;
                if (stock === 'out' && total > 0)                 return false;
                return true;
            });
            table.draw();
        }

        $('#filterCategory, #filterStatus, #filterStock').on('change', applyFilters);

        $('#resetFilters').on('click', function() {
            $('#filterCategory').val('').trigger('change.select2');
            $('#filterStatus').val('').trigger('change.select2');
            $('#filterStock').val('').trigger('change.select2');
            applyFilters();
        });

        $('#exportExcelBtn').on('click', function() {
            const cat = $('#filterCategory').val();
            const status = $('#filterStatus').val();
            const stock = $('#filterStock').val();
            
            let url = "{{ route('admin.reports.products.export') }}?";
            let params = [];
            if (cat) params.push('category_id=' + cat);
            if (status) params.push('status=' + status);
            if (stock) params.push('stock=' + stock);
            
            window.location.href = url + params.join('&');
        });

        // -------------------------------------------------------
        // Products by Category Horizontal Bar Chart
        // -------------------------------------------------------
        const categoryData = @json(
            $products->groupBy('category')->map(fn($g) => $g->count())->sortDesc()
        );

        new ApexCharts(document.getElementById('categoryPieChart'), {
            chart   : { type: 'bar', height: 300, toolbar: { show: false } },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '60%',
                    distributed: true
                }
            },
            colors  : ['#B4771E', '#28c76f', '#328693', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8'],
            series  : [{
                name: 'Products',
                data: Object.values(categoryData)
            }],
            xaxis   : {
                categories: Object.keys(categoryData),
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
            yaxis   : {
                labels: {
                    style: {
                        colors: '#5d596c',
                        fontFamily: 'Public Sans',
                        fontWeight: 500
                    }
                }
            },
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '11px',
                    fontFamily: 'Public Sans',
                    fontWeight: '600',
                    colors: ['#fff']
                },
                formatter: function(val) {
                    return parseInt(val);
                },
                offsetX: 0
            },
            legend  : { show: false },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' Products';
                    }
                }
            },
            grid: {
                borderColor: '#e5e5e5',
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } },
                padding: { top: -15, right: 10, bottom: -10, left: 10 }
            },
        }).render();

        // -------------------------------------------------------
        // Top 10 Products by Stock Bar Chart
        // -------------------------------------------------------
        const top10 = @json(
            $products->sortByDesc('total_stock')->take(10)->values()->map(fn($p) => [
                'name'  => $p['name'],
                'stock' => $p['total_stock'],
            ])
        );

        new ApexCharts(document.getElementById('topStockChart'), {
            chart  : { type: 'bar', height: 300, toolbar: { show: false } },
            series : [{ name: 'Stock', data: top10.map(p => p.stock) }],
            xaxis  : { categories: top10.map(p => p.name), labels: { rotate: -30 } },
            colors : ['#B4771E'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
            dataLabels : { enabled: false },
        }).render();

    });
    </script>
@endsection
