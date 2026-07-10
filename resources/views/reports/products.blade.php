@extends('layouts.app')

@section('title', 'Products Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <style>
        .variant-toggle {
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            line-height: 1;
        }
        .variant-toggle i {
            font-size: 1rem;
            transition: transform 0.2s ease;
        }
        .variant-toggle.is-open i {
            transform: rotate(90deg);
        }
        #productsReportTable tr.child td.child {
            padding: 0 !important;
            background-color: #fbfbfc;
        }
        .variant-table {
            margin-bottom: 0;
        }
        .variant-table th,
        .variant-table td {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8125rem;
        }
        .variant-table thead th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .variant-table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }
        .variant-table tbody tr:last-child {
            border-bottom: none;
        }
        .parent-row:hover {
            background-color: #f8f9fa;
        }
        @media (max-width: 767.98px) {
            .variant-table {
                min-width: 600px;
            }
        }
    </style>
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
                            <h4 class="mb-0 mt-1">{{ $totalProducts }}</h4>
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
                            <h4 class="mb-0 mt-1">{{ $activeProductCount }}</h4>
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
            <div class="d-flex gap-2 d-none" id="filterActionButtons">
                <button type="button" id="applyFiltersBtn" class="btn btn-sm btn-primary">
                    <i class="ti ti-filter me-1"></i> Apply
                </button>
                <button type="button" id="clearFiltersBtn" class="btn btn-sm btn-label-secondary">
                    <i class="ti ti-refresh me-1"></i> Clear
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3" onsubmit="return false;">
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
                    <label class="form-label">Filter by Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="2">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Stock</label>
                    <select id="filterStock" class="form-select">
                        <option value="">All</option>
                        <option value="in">In Stock</option>
                        <option value="out">SOLD OUT</option>
                    </select>
                </div>
            </form>
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
                        <th>Barcode</th>
                        <th>Category</th>
                        <th class="text-end">Purchase Price</th>
                        <th class="text-end">Sale Price</th>
                        <th class="text-end">Margin</th>
                        <th class="text-end">Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $parentProducts = $products->where('is_parent', true)->values();
                        $variantProducts = $products->where('is_parent', false)->groupBy('id');
                        $groupedProducts = $parentProducts->map(fn ($product) => [
                            'parent' => $product,
                            'variants' => $variantProducts->get($product['id'], collect())->values(),
                        ]);
                    @endphp

                    @foreach($groupedProducts as $index => $group)
                        @php
                            $parent = $group['parent'];
                            $variants = $group['variants'];
                            $hasVariants = $variants->count() > 0;
                            $margin    = $parent['sale_price'] - $parent['purchase_price'];
                            $marginPct = $parent['purchase_price'] > 0 ? round(($margin / $parent['purchase_price']) * 100, 1) : 0;
                        @endphp
                        <!-- Parent Row -->
                        <tr class="parent-row" data-product-id="{{ $parent['id'] }}"
                            data-category-id="{{ $parent['category_id'] }}"
                            data-status="{{ $parent['status'] }}"
                            data-stock="{{ $parent['total_stock'] }}"
                            data-has-variants="{{ $hasVariants ? '1' : '0' }}">
                            <td>{{ $index + 1 }}</td>
                            <td data-order="{{ $parent['name'] }} 000_parent">
                                <div class="d-flex align-items-center">
                                    @if($hasVariants)
                                        <button type="button" class="btn btn-icon btn-sm variant-toggle me-2" data-product-id="{{ $parent['id'] }}" aria-expanded="false">
                                            <i class="ti ti-chevron-right"></i>
                                        </button>
                                    @else
                                        <span class="me-2" style="width: 24px;"></span>
                                    @endif
                                    @if($parent['image_url'])
                                        <img src="{{ $parent['image_url'] }}" alt="{{ $parent['name'] }}" class="rounded me-2 product-thumbnail" style="width: 32px; height: 32px; object-fit: cover;">
                                    @else
                                        <div class="rounded bg-label-secondary me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="ti ti-photo text-muted" style="font-size: 1rem;"></i>
                                        </div>
                                    @endif
                                    <a href="{{ route('admin.products.show', $parent['id']) }}" class="fw-semibold mb-0">
                                        {{ $parent['name'] }}
                                    </a>
                                </div>
                            </td>
                            <td><code>{{ $parent['barcode'] }}</code></td>
                            <td><span class="badge bg-label-primary">{{ $parent['category'] }}</span></td>
                            <td class="text-end">{{ format_price($parent['purchase_price']) }}</td>
                            <td class="text-end">{{ format_price($parent['sale_price']) }}</td>
                            <td class="text-end">
                                <span class="badge {{ $margin >= 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ format_price($margin) }} ({{ $marginPct }}%)
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="badge {{ $parent['total_stock'] > 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $parent['total_stock'] }}
                                </span>
                            </td>
                            <td>{!! status_badge($parent['status']) !!}</td>
                        </tr>

                    @endforeach
                </tbody>
            </table>
            @foreach($groupedProducts as $group)
                @php
                    $parent = $group['parent'];
                    $variants = $group['variants'];
                @endphp
                @if($variants->count())
                    <template id="variants-template-{{ $parent['id'] }}">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm mb-0 variant-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th>Variant</th>
                                        <th class="text-end">Purchase Price</th>
                                        <th class="text-end">Sale Price</th>
                                        <th class="text-end">Margin</th>
                                        <th class="text-end">Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($variants as $vIndex => $variant)
                                        @php
                                            $vMargin    = $variant['sale_price'] - $variant['purchase_price'];
                                            $vMarginPct = $variant['purchase_price'] > 0 ? round(($vMargin / $variant['purchase_price']) * 100, 1) : 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $vIndex + 1 }}</td>
                                            <td class="ps-5">{{ $variant['variant_name'] }}</td>
                                            <td class="text-end">{{ format_price($variant['purchase_price']) }}</td>
                                            <td class="text-end">{{ format_price($variant['sale_price']) }}</td>
                                            <td class="text-end">
                                                <span class="badge {{ $vMargin >= 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                                    {{ format_price($vMargin) }} ({{ $vMarginPct }}%)
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $variant['total_stock'] > 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                                    {{ $variant['total_stock'] }}
                                                </span>
                                            </td>
                                            <td>{!! status_badge($variant['status']) !!}</td>
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
            pageLength : 25,
            columnDefs : [
                {
                    targets: 0,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }
            ],
        });

        $('#productsReportTable tbody').on('click', '.variant-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(this);
            const productId = $btn.data('product-id');
            const template = document.getElementById('variants-template-' + productId);
            const row = table.row($btn.closest('tr'));

            if (!template) {
                return;
            }

            if (row.child.isShown()) {
                row.child.hide();
                $btn.removeClass('is-open').attr('aria-expanded', 'false');
            } else {
                row.child(template.innerHTML).show();
                $btn.addClass('is-open').attr('aria-expanded', 'true');
            }

            table.columns.adjust();
        });

        function applyFilters() {
            window.showAjaxLoader && window.showAjaxLoader();

            setTimeout(function () {
                const cat    = $('#filterCategory').val();
                const status = $('#filterStatus').val();
                const stock  = $('#filterStock').val();

                $.fn.dataTable.ext.search = [];
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    const row   = $(table.row(dataIndex).node());

                    const total = parseInt(row.data('stock') || 0);
                    if (cat    && row.data('category-id') != cat)    return false;
                    if (status && String(row.data('status')) !== status) return false;
                    if (stock === 'in'  && total <= 0)                return false;
                    if (stock === 'out' && total > 0)                 return false;
                    return true;
                });
                table.draw();

                window.hideAjaxLoader && window.hideAjaxLoader();
            }, 150);
        }

        function updateFilterButtonsVisibility() {
            const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                return $(el).val();
            });
            $('#filterActionButtons').toggleClass('d-none', !hasValue);
        }

        $(document).on('input change', '#filterForm', function () {
            updateFilterButtonsVisibility();
        });
        updateFilterButtonsVisibility();

        $('#applyFiltersBtn').on('click', applyFilters);

        $('#clearFiltersBtn').on('click', function() {
            $('#filterCategory').val('').trigger('change.select2');
            $('#filterStatus').val('').trigger('change.select2');
            $('#filterStock').val('').trigger('change.select2');
            updateFilterButtonsVisibility();
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
            chart  : { type: 'bar', height: 340, toolbar: { show: false } },
            series : [{ name: 'Stock', data: top10.map(p => p.stock).reverse() }],
            xaxis  : {
                categories: top10.map(p => p.name).reverse(),
                labels: {
                    style: { colors: '#5d596c', fontFamily: 'Public Sans' },
                    formatter: function (val) { return parseInt(val); }
                }
            },
            yaxis  : {
                labels: {
                    style: { colors: '#5d596c', fontFamily: 'Public Sans', fontWeight: 500 },
                    maxWidth: 220
                }
            },
            colors : ['#B4771E'],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
            dataLabels : {
                enabled: true,
                style: { fontSize: '11px', fontFamily: 'Public Sans', fontWeight: '600', colors: ['#fff'] },
                formatter: function (val) { return parseInt(val); },
                offsetX: 0
            },
            grid: {
                borderColor: '#e5e5e5',
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } },
                padding: { top: -15, right: 10, bottom: -10, left: 10 }
            },
            tooltip: {
                y: { formatter: function (val) { return val + ' units'; } }
            },
        }).render();

    });
    </script>
@endsection
