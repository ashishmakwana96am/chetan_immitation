@extends('layouts.app')

@section('title', 'Sales Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <style>
        #salesReportTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #salesReportTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #salesReportTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #salesReportTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Sales Report</h4>
        <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
    </div>

    <div id="report-results">
        <div id="chart-data" 
             data-sales-trend='@json($salesTrend)' 
             data-payment-method='@json($paymentMethodData)'>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Sales</span>
                            <h4 class="mb-0 mt-1">{{ format_price($totalSales) }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-chart-line ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Orders</span>
                            <h4 class="mb-0 mt-1">{{ $orderCount }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-shopping-cart ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Avg Order Value</span>
                            <h4 class="mb-0 mt-1">{{ format_price($avgOrderValue) }}</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-calculator ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Payment Split</span>
                            <h5 class="mb-0 mt-2 text-nowrap">
                                <span class="badge bg-label-success">{{ $paidCount }} Paid</span>
                                <span class="badge bg-label-warning">{{ $pendingCount }} Unpaid</span>
                            </h5>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-wallet ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Sales Trend (Area) -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Sales Trend (Monthly)</h5></div>
                <div class="card-body">
                    <div id="salesTrendChart"></div>
                </div>
            </div>
        </div>

        <!-- Payment Method Distribution (Donut) -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Sales by Payment Method</h5></div>
                <div class="card-body">
                    <div id="paymentMethodChart"></div>
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
            <form method="GET" action="{{ route('admin.reports.sales') }}" id="filterForm" class="row g-3">
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">Start Date</label>
                    <input type="text" name="start_date" class="form-control flatpickr" value="{{ $startDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">End Date</label>
                    <input type="text" name="end_date" class="form-control flatpickr" value="{{ $endDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select no-select2">
                        <option value="">All Statuses</option>
                        <option value="1" {{ $paymentStatus == 1 ? 'selected' : '' }}>Pending</option>
                        <option value="2" {{ $paymentStatus == 2 ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select no-select2">
                        <option value="">All Methods</option>
                        <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="online" {{ $paymentMethod === 'online' ? 'selected' : '' }}>Online</option>
                    </select>
                </div>

            </form>
        </div>
    </div>

    <!-- Detail Table & Top Selling Products Tabs -->
    <div class="card">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-orders" role="tab">
                        <i class="ti ti-shopping-cart me-1"></i> Orders List
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-products" role="tab">
                        <i class="ti ti-box me-1"></i> Top Selling Products
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content p-0">
            <!-- Orders Tab -->
            <div class="tab-pane fade show active" id="tab-orders" role="tabpanel">
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="salesReportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order No</th>
                                <th>Customer</th>
                                <th>Location</th>
                                <th>Payment Status</th>
                                <th>Method</th>
                                <th class="text-end text-nowrap">Final Amount</th>
                                <th>Actions</th>
                                <th class="d-none">date_group</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $index => $order)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><code>{{ $order->order_no }}</code></td>
                                    <td><span class="fw-semibold">{{ $order->customer->name ?? 'Walk-in' }}</span></td>
                                    <td><span class="badge bg-label-secondary">{{ $order->location->name ?? '-' }}</span></td>
                                    <td>
                                        @php
                                            $payColors = [
                                                1 => 'bg-label-warning',
                                                2 => 'bg-label-success',
                                            ];
                                            $payLabels = [
                                                1 => 'Pending',
                                                2 => 'Paid',
                                            ];
                                            $badgeColor = $payColors[$order->payment_status] ?? 'bg-label-secondary';
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">{{ $payLabels[$order->payment_status] ?? 'Pending' }}</span>
                                    </td>
                                    <td><span class="text-uppercase small fw-semibold">{{ str_replace('_', ' ', $order->payment_method) }}</span></td>
                                    <td class="text-end text-nowrap fw-semibold">{{ format_price($order->final_amount) }}</td>
                                    <td>
                                        <div class="dropdown table-action-dropdown">
                                            <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span>Actions</span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                                <a href="{{ route('admin.sales.show', $order->id) }}" class="dropdown-item">
                                                    <i class="ti ti-eye me-2"></i>View
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none">{{ $order->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Selling Products Tab -->
            <div class="tab-pane fade" id="tab-products" role="tabpanel">
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="productsReportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th class="text-end text-nowrap">Qty Sold</th>
                                <th class="text-end text-nowrap">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productSales as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('admin.products.show', $item->product_id) }}" class="fw-semibold">
                                            {{ $item->product->name ?? 'Unknown' }}
                                        </a>
                                    </td>
                                    <td><code>{{ $item->product->sku ?? '-' }}</code></td>
                                    <td class="text-end text-nowrap fw-bold text-info">{{ $item->qty_sold }}</td>
                                    <td class="text-end text-nowrap fw-bold text-success">{{ format_price($item->total_revenue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    let salesTrendChart = null;
    let paymentMethodChart = null;

    function initReport() {
        // Initialize DataTables (destroy first if already exists)
        if ($.fn.DataTable.isDataTable('#salesReportTable')) {
            $('#salesReportTable').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#productsReportTable')) {
            $('#productsReportTable').DataTable().destroy();
        }

        $('#salesReportTable').DataTable({
            responsive : false,
            order      : [[8, 'desc']],
            columnDefs : [{ targets: 8, visible: false }],
            rowGroup   : {
                dataSrc: 8,
                startRender: function (rows, group) {
                    return $('<tr class="group-header"/>')
                        .append('<td colspan="8"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' sale' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                }
            },
        });

        $('#productsReportTable').DataTable({
            responsive : false,
            order      : [[3, 'desc']],
        });

        // Fetch Chart Data from DOM attributes to bypass jQuery cache
        const chartDataEl = $('#chart-data');
        const salesTrend = JSON.parse(chartDataEl.attr('data-sales-trend') || '{}');
        const paymentMethodData = JSON.parse(chartDataEl.attr('data-payment-method') || '{}');

        // Sales Trend Chart
        const months = Object.keys(salesTrend);
        const values = Object.values(salesTrend);

        if (salesTrendChart) {
            salesTrendChart.destroy();
            salesTrendChart = null;
        }
        if (months.length > 0) {
            salesTrendChart = new ApexCharts(document.getElementById('salesTrendChart'), {
                chart: { type: 'area', height: 320, toolbar: { show: false } },
                series: [{ name: 'Sales', data: values }],
                xaxis: { categories: months },
                colors: ['#28c76f'],
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.5,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                dataLabels: { enabled: false },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return '{{ currency_symbol() }}' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            });
            salesTrendChart.render();
        } else {
            $('#salesTrendChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }

        // Payment Method Chart
        const methods = Object.keys(paymentMethodData);
        const methodValues = Object.values(paymentMethodData);

        if (paymentMethodChart) {
            paymentMethodChart.destroy();
            paymentMethodChart = null;
        }
        if (methods.length > 0) {
            paymentMethodChart = new ApexCharts(document.getElementById('paymentMethodChart'), {
                chart: { type: 'donut', height: 320 },
                series: methodValues,
                labels: methods.map(m => m.toUpperCase().replace('_', ' ')),
                legend: { position: 'bottom' },
                dataLabels: { enabled: true },
            });
            paymentMethodChart.render();
        } else {
            $('#paymentMethodChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }
    }

    $(document).ready(function () {
        // Initial load
        initReport();

        function loadReport(url) {
            $('#report-results').css('opacity', 0.5);

            $.get(url, function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newResults = $(doc).find('#report-results').html();

                $('#report-results').html(newResults);
                initReport();
            }).always(function () {
                $('#report-results').css('opacity', 1);
            });
        }

        // AJAX Filtering on form field changes
        $('#filterForm').on('change', 'input, select', function () {
            const form = $('#filterForm');
            const url = form.attr('action') + '?' + form.serialize();

            loadReport(url);
        });

        $('#resetFilters').on('click', function () {
            const form = $('#filterForm');

            form[0].reset();
            form.find('input').val('');
            form.find('select').val('').trigger('change.select2');

            loadReport(form.attr('action'));
        });

        $('#exportExcelBtn').on('click', function () {
            const form = $('#filterForm');
            const url = "{{ route('admin.reports.sales.export') }}?" + form.serialize();
            window.location.href = url;
        });
    });
    </script>
@endsection
