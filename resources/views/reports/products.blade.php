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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Products Report</h4>
        <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
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
                <div class="card-header"><h5 class="mb-0">Products by Sub Category</h5></div>
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
        <div class="card-header">
            <h5 class="mb-0">Filter Report</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3" onsubmit="return false;">
                <div class="col-md-3">
                    <label class="form-label">Filter by Sub Category</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">All Sub Categories</option>
                        @foreach($subCategories as $subCat)
                            <option value="{{ $subCat->id }}">{{ $subCat->name }}</option>
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

    <!-- Products Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Products Detail</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="productsReportTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Barcode</th>
                        <th class="d-none">Category</th>
                        <th>Sub Category</th>
                        <th class="text-end">Purchase Price</th>
                        <th class="text-end">Sale Price</th>
                        <th class="text-end">Margin</th>
                        <th class="text-end">Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
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
            responsive: false,
            serverSide: true,
            processing: false,
            pageLength: 25,
            order: [[1, 'asc']],
            ajax: {
                url: '{{ route('admin.reports.products.data') }}',
                data: function(d) {
                    d.sub_category_id = $('#filterCategory').val() || '';
                    d.status = $('#filterStatus').val() || '';
                    d.stock = $('#filterStock').val() || '';
                }
            },
            columns: [
                { data: 'index', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'barcode', name: 'barcode' },
                { data: 'sub_category', orderable: false },
                { data: 'purchase_price', className: 'text-end' },
                { data: 'sale_price', className: 'text-end' },
                { data: 'margin_badge', className: 'text-end', orderable: false },
                { data: 'stock_badge', className: 'text-end', orderable: false },
                { data: 'status_badge', orderable: false },
            ]
        });

        table.on('preXhr.dt', function () {
            window.showAjaxLoader && window.showAjaxLoader();
        });

        table.on('xhr.dt draw.dt', function () {
            window.hideAjaxLoader && window.hideAjaxLoader();
            $('#productsReportTable_processing').css('display', 'none');
        });

        $('#productsReportTable tbody').on('click', '.variant-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(this);
            const row = table.row($btn.closest('tr'));
            const rowData = row.data();

            if (row.child.isShown()) {
                row.child.hide();
                $btn.removeClass('is-open').attr('aria-expanded', 'false');
            } else if (rowData && rowData.variants && rowData.variants.length > 0) {
                let html = '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
                html += '<table class="table table-sm mb-0 variant-table">';
                html += '<thead class="table-light"><tr><th style="width: 40px;">#</th><th>Variant</th><th class="text-end">Purchase Price</th><th class="text-end">Sale Price</th><th class="text-end">Margin</th><th class="text-end">Stock</th><th>Status</th></tr></thead>';
                html += '<tbody>';
                rowData.variants.forEach(function(v) {
                    html += '<tr>';
                    html += '<td>' + v.index + '</td>';
                    html += '<td class="ps-5">' + v.name + '</td>';
                    html += '<td class="text-end">' + v.purchase_price + '</td>';
                    html += '<td class="text-end">' + v.sale_price + '</td>';
                    html += '<td class="text-end">' + v.margin_badge + '</td>';
                    html += '<td class="text-end">' + v.stock_badge + '</td>';
                    html += '<td>' + v.status_badge + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                row.child(html).show();
                $btn.addClass('is-open').attr('aria-expanded', 'true');
            }
        });

        function applyFilters() {
            table.draw();
        }

        let isFiltered = false;

        function updateFilterButtonsVisibility() {
            const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                return $(el).val();
            });
            $('#filterActionButtons').toggleClass('d-none', !hasValue);

            if (!hasValue && isFiltered) {
                isFiltered = false;
                applyFilters();
            }
        }

        $(document).on('input change', '#filterForm', function () {
            updateFilterButtonsVisibility();
        });
        updateFilterButtonsVisibility();

        $('#applyFiltersBtn').on('click', function () {
            isFiltered = true;
            applyFilters();
        });

        $('#clearFiltersBtn').on('click', function() {
            isFiltered = false;
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

            let url = "{{ route('admin.reports.products.export-excel') }}";
            let params = [];
            if (cat) params.push('sub_category_id=' + cat);
            if (status) params.push('status=' + status);
            if (stock) params.push('stock=' + stock);

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            if (window.showAjaxLoader) {
                $('.loader-status').text('Exporting Excel');
                window.showAjaxLoader();
            }

            $.ajax({
                url: url,
                type: 'GET',
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data, status, xhr) {
                    if (window.hideAjaxLoader) {
                        window.hideAjaxLoader();
                        $('.loader-status').text('Loading');
                    }

                    const disposition = xhr.getResponseHeader('Content-Disposition');
                    let filename = 'products_report.xlsx';
                    if (disposition && disposition.indexOf('filename=') !== -1) {
                        const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }

                    const blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    const downloadUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = downloadUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(downloadUrl);
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Excel report downloaded successfully!');
                    }
                },
                error: function () {
                    if (window.hideAjaxLoader) {
                        window.hideAjaxLoader();
                        $('.loader-status').text('Loading');
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to export report. Please try again.');
                    }
                }
            });
        });

        // -------------------------------------------------------
        // Products by Category Horizontal Bar Chart
        // -------------------------------------------------------
        const categoryData = @json($categoryChartData);
        const catKeys = Object.keys(categoryData);
        const catValues = Object.values(categoryData).map(v => parseInt(v) || 1);

        const categoryChartEl = document.getElementById('categoryPieChart');
        if (categoryChartEl) {
            new ApexCharts(categoryChartEl, {
                chart   : { type: 'bar', height: Math.max(300, catKeys.length * 32 + 60), toolbar: { show: false } },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '60%',
                        distributed: true
                    }
                },
                colors  : ['#B4771E', '#28c76f', '#328693', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8', '#6610f2', '#20c997', '#fd7e14', '#6f42c1', '#e83e8c'],
                series  : [{
                    name: 'Products',
                    data: catValues
                }],
                xaxis   : {
                    categories: catKeys,
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
        }

        // -------------------------------------------------------
        // Top 10 Products by Stock Bar Chart
        // -------------------------------------------------------
        const top10Raw = @json($top10ChartData);
        const top10 = Array.isArray(top10Raw) ? top10Raw : (top10Raw && typeof top10Raw === 'object' ? Object.values(top10Raw) : []);
        const top10Names = top10.map(p => p.name).reverse();
        const top10Stock = top10.map(p => Math.max(1, parseInt(p.stock) || 1)).reverse();

        const topStockChartEl = document.getElementById('topStockChart');
        if (topStockChartEl) {
            new ApexCharts(topStockChartEl, {
                chart  : { type: 'bar', height: 340, toolbar: { show: false } },
                series : [{ name: 'Stock', data: top10Stock }],
                xaxis  : {
                    categories: top10Names,
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
        }

    });
    </script>
@endsection
