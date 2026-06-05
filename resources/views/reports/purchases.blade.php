@extends('layouts.app')

@section('title', 'Purchase Reports')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <style>
        #purchasesReportTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #purchasesReportTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #purchasesReportTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #purchasesReportTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Purchase Reports</h4>
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
            <form method="GET" action="{{ route('admin.reports.purchases') }}" id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Invoice Status</label>
                    <select name="status" class="form-select no-select2">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approve" {{ $status === 'approve' ? 'selected' : '' }}>Approve</option>
                        <option value="decline" {{ $status === 'decline' ? 'selected' : '' }}>Decline</option>
                    </select>
                </div>

            </form>
        </div>
    </div>

    <div id="report-results">
        <div id="chart-data" 
             data-purchases-trend='@json($purchasesTrend)' 
             data-supplier-data='@json($supplierData)'>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Purchases</span>
                            <h4 class="mb-0 mt-1">{{ format_price($totalPurchases) }}</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-currency-dollar ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Invoices</span>
                            <h4 class="mb-0 mt-1">{{ $invoiceCount }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-file-text ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Confirmed Invoices</span>
                            <h4 class="mb-0 mt-1">{{ $confirmedCount }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-circle-check ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Draft Invoices</span>
                            <h4 class="mb-0 mt-1">{{ $draftCount }}</h4>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ti ti-file-pencil ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Monthly Purchases (Bar) -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Purchases Trend (Monthly)</h5></div>
                <div class="card-body">
                    <div id="purchasesTrendChart"></div>
                </div>
            </div>
        </div>

        <!-- Supplier Distribution (Donut) -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Purchases by Supplier</h5></div>
                <div class="card-body">
                    <div id="supplierChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Table & Top Purchased Products Tabs -->
    <div class="card">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-invoices" role="tab">
                        <i class="ti ti-file-text me-1"></i> Invoices List
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-products" role="tab">
                        <i class="ti ti-box me-1"></i> Top Purchased Products
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content p-0">
            <!-- Invoices Tab -->
            <div class="tab-pane fade show active" id="tab-invoices" role="tabpanel">
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="purchasesReportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice No</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th class="text-end text-nowrap">Total Amount</th>
                                <th>Actions</th>
                                <th class="d-none">date_group</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $index => $invoice)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><code>{{ $invoice->invoice_no }}</code></td>
                                    <td><span class="fw-semibold">{{ $invoice->supplier->name ?? 'Unknown' }}</span></td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-label-secondary',
                                                'approve' => 'bg-label-success',
                                                'decline' => 'bg-label-danger',
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Pending',
                                                'approve' => 'Approve',
                                                'decline' => 'Decline',
                                            ];
                                            $badgeColor = $statusColors[$invoice->status] ?? 'bg-label-secondary';
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">{{ $statusLabels[$invoice->status] ?? ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold">{{ format_price($invoice->total_amount) }}</td>
                                    <td>
                                        <div class="dropdown table-action-dropdown">
                                            <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span>Actions</span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                                <a href="{{ route('admin.purchases.show', $invoice->id) }}" class="dropdown-item">
                                                    <i class="ti ti-eye me-2"></i>View
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none">{{ $invoice->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Purchased Products Tab -->
            <div class="tab-pane fade" id="tab-products" role="tabpanel">
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="purchasedProductsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th class="text-end text-nowrap">Qty Purchased</th>
                                <th class="text-end text-nowrap">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productPurchases as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('admin.products.show', $item->product_id) }}" class="fw-semibold">
                                            {{ $item->product->name ?? 'Unknown' }}
                                        </a>
                                    </td>
                                    <td><code>{{ $item->product->sku ?? '-' }}</code></td>
                                    <td class="text-end text-nowrap fw-bold text-info">{{ $item->qty_purchased }}</td>
                                    <td class="text-end text-nowrap fw-bold text-success">{{ format_price($item->total_cost) }}</td>
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
    let purchasesTrendChart = null;
    let supplierChart = null;

    function initReport() {
        // Initialize DataTables (destroy first if already exists)
        if ($.fn.DataTable.isDataTable('#purchasesReportTable')) {
            $('#purchasesReportTable').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#purchasedProductsTable')) {
            $('#purchasedProductsTable').DataTable().destroy();
        }

        $('#purchasesReportTable').DataTable({
            responsive : false,
            order      : [[6, 'desc']],
            columnDefs : [{ targets: 6, visible: false }],
            rowGroup   : {
                dataSrc: 6,
                startRender: function (rows, group) {
                    return $('<tr class="group-header"/>')
                        .append('<td colspan="6"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' invoice' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                }
            },
        });

        $('#purchasedProductsTable').DataTable({
            responsive : false,
            order      : [[3, 'desc']],
        });

        // Fetch Chart Data from DOM attributes to bypass jQuery cache
        const chartDataEl = $('#chart-data');
        const purchasesTrend = JSON.parse(chartDataEl.attr('data-purchases-trend') || '{}');
        const supplierData = JSON.parse(chartDataEl.attr('data-supplier-data') || '{}');

        // Purchases Trend Chart
        const months = Object.keys(purchasesTrend);
        const values = Object.values(purchasesTrend);

        if (purchasesTrendChart) {
            purchasesTrendChart.destroy();
            purchasesTrendChart = null;
        }
        if (months.length > 0) {
            purchasesTrendChart = new ApexCharts(document.getElementById('purchasesTrendChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{ name: 'Purchases', data: values }],
                xaxis: { categories: months },
                colors: ['#7367f0'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                dataLabels: { enabled: false },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return '{{ currency_symbol() }}' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            });
            purchasesTrendChart.render();
        } else {
            $('#purchasesTrendChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }

        // Supplier Horizontal Bar Chart
        const suppliers = Object.keys(supplierData);
        const supplierValues = Object.values(supplierData);

        if (supplierChart) {
            supplierChart.destroy();
            supplierChart = null;
        }
        if (suppliers.length > 0) {
            supplierChart = new ApexCharts(document.getElementById('supplierChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '55%',
                        distributed: true
                    }
                },
                colors: ['#7367f0', '#28c76f', '#00cfe8', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8'],
                series: [{
                    name: 'Purchases',
                    data: supplierValues
                }],
                xaxis: {
                    categories: suppliers,
                    labels: {
                        style: {
                            colors: '#5d596c',
                            fontFamily: 'Public Sans'
                        },
                        formatter: function(val) {
                            return '₹' + parseFloat(val).toLocaleString('en-IN');
                        }
                    }
                },
                yaxis: {
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
                        return '₹' + parseFloat(val).toLocaleString('en-IN');
                    },
                    offsetX: 0
                },
                legend: { show: false },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '₹' + parseFloat(val).toLocaleString('en-IN');
                        }
                    }
                },
                grid: {
                    borderColor: '#e5e5e5',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } },
                    padding: { top: -15, right: 10, bottom: -10, left: 10 }
                },
                noData: { text: 'No data available' }
            });
            supplierChart.render();
        } else {
            $('#supplierChart').html('<div class="text-center py-5 text-muted">No data available</div>');
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
    });
    </script>
@endsection
