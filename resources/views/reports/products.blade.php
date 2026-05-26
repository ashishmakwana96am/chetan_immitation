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
                            <h4 class="mb-0 mt-1">{{ $products->where('status', 'active')->count() }}</h4>
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
                            <span class="text-muted">Out of Stock</span>
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
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Stock</label>
                    <select id="filterStock" class="form-select">
                        <option value="">All</option>
                        <option value="in">In Stock</option>
                        <option value="out">Out of Stock</option>
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
                            <td>
                                <a href="{{ route('admin.products.show', $product['id']) }}" class="fw-semibold">
                                    {{ $product['name'] }}
                                </a>
                            </td>
                            <td><code>{{ $product['sku'] }}</code></td>
                            <td><span class="badge bg-label-primary">{{ $product['category'] }}</span></td>
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
            responsive : true,
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

        // -------------------------------------------------------
        // Products by Category Pie Chart
        // -------------------------------------------------------
        const categoryData = @json(
            $products->groupBy('category')->map(fn($g) => $g->count())->sortDesc()
        );

        new ApexCharts(document.getElementById('categoryPieChart'), {
            chart   : { type: 'donut', height: 300 },
            series  : Object.values(categoryData),
            labels  : Object.keys(categoryData),
            legend  : { position: 'bottom' },
            dataLabels: { enabled: true },
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
            colors : ['#7367f0'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
            dataLabels : { enabled: false },
        }).render();

    });
    </script>
@endsection
