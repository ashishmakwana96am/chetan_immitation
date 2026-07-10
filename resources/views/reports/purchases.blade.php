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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">All Purchase</h4>
        <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
    </div>

    <div id="report-results">
        <div id="chart-data" 
             data-purchases-trend='@json($purchasesTrend)' 
             data-supplier-data='@json($supplierData)'>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Purchases</span>
                            <h4 class="mb-0 mt-1">{{ format_price($totalPurchases) }}</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-currency-rupee ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Purchase</span>
                            <h4 class="mb-0 mt-1">{{ $invoiceCount }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-file-text ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Confirmed Purchase</span>
                            <h4 class="mb-0 mt-1">{{ $confirmedCount }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-circle-check ti-sm"></i></span>
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
            <form method="GET" action="{{ route('admin.reports.purchases') }}" id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="text" name="start_date" class="form-control flatpickr" value="{{ $startDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="text" name="end_date" class="form-control flatpickr" value="{{ $endDate }}" placeholder="DD-MM-YYYY" />
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

            </form>
        </div>
    </div>

    <!-- Detail Table & Top Purchased Products Tabs -->
    <div class="card">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-invoices" role="tab">
                        <i class="ti ti-file-text me-1"></i> Purchase List
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
                                <th>Purchase No</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th class="text-end text-nowrap">Total Amount</th>
                                <th>Actions</th>
                                <th class="d-none">date_group</th>
                                <th class="d-none">date_sort</th>
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
                                                1 => 'bg-label-secondary',
                                                2 => 'bg-label-success',
                                                3 => 'bg-label-danger',
                                            ];
                                            $statusLabels = [
                                                1 => 'Pending',
                                                2 => 'Approve',
                                                3 => 'Decline',
                                            ];
                                            $badgeColor = $statusColors[$invoice->status] ?? 'bg-label-secondary';
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">{{ $statusLabels[$invoice->status] ?? 'Pending' }}</span>
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold">{{ format_price($invoice->total_amount) }}</td>
                                    <td>
                                        <div class="dropdown table-action-dropdown">
                                            <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
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
                                    <td class="d-none">{{ $invoice->created_at->format('Ymd') }}</td>
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
                                <th>Barcode</th>
                                <th class="text-end text-nowrap">Qty Purchased</th>
                                <th class="text-end text-nowrap">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productPurchases as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($item->product?->primaryImage)
                                                <img src="{{ $item->product->primaryImage->image_url }}" alt="{{ $item->product->name }}" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="rounded bg-label-secondary me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="ti ti-photo text-muted" style="font-size: 1.25rem;"></i>
                                                </div>
                                            @endif
                                            <a href="{{ route('admin.products.show', $item->product_id) }}" class="fw-semibold">
                                                {{ $item->product->name ?? 'Unknown' }}
                                            </a>
                                        </div>
                                    </td>
                                    <td><code>{{ $item->product->barcode ?? '-' }}</code></td>
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

    function formatCompactIndian(val) {
        val = parseFloat(val);
        if (isNaN(val)) return '{{ currency_symbol() }}0';
        if (val >= 10000000) {
            return '{{ currency_symbol() }}' + (val / 10000000).toFixed(val % 10000000 === 0 ? 0 : 2) + ' Cr';
        } else if (val >= 100000) {
            return '{{ currency_symbol() }}' + (val / 100000).toFixed(val % 100000 === 0 ? 0 : 2) + ' L';
        } else if (val >= 1000) {
            return '{{ currency_symbol() }}' + (val / 1000).toFixed(val % 1000 === 0 ? 0 : 2) + ' K';
        }
        return '{{ currency_symbol() }}' + val.toLocaleString('en-IN');
    }

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
            order      : [[7, 'desc']],
            orderFixed : { pre: [[7, 'desc']] },
            columnDefs : [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { targets: [6, 7], visible: false }
            ],
            rowGroup   : {
                dataSrc: 6,
                startRender: function (rows, group) {
                    return $('<tr class="group-header"/>')
                        .append('<td colspan="6"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' purchase</span></div></td>');
                }
            },
        });

        $('#purchasedProductsTable').DataTable({
            responsive : false,
            order      : [[3, 'desc']],
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
                colors: ['#B4771E'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                dataLabels: { enabled: false },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return formatCompactIndian(val);
                        }
                    }
                },
                tooltip: {
                    y: {
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

        const sortedEntries = Object.entries(supplierData).sort((a, b) => b[1] - a[1]);

        let suppliers = [];
        let supplierValues = [];

        if (sortedEntries.length > 5) {
            const top5 = sortedEntries.slice(0, 5);
            const othersSum = sortedEntries.slice(5).reduce((sum, item) => sum + item[1], 0);
            
            suppliers = top5.map(item => item[0]);
            supplierValues = top5.map(item => item[1]);
            
            if (othersSum > 0) {
                suppliers.push('Others');
                supplierValues.push(othersSum);
            }
        } else {
            suppliers = sortedEntries.map(item => item[0]);
            supplierValues = sortedEntries.map(item => item[1]);
        }

        if (supplierChart) {
            supplierChart.destroy();
            supplierChart = null;
        }
        if (suppliers.length > 0) {
            supplierChart = new ApexCharts(document.getElementById('supplierChart'), {
                chart: { type: 'donut', height: 320 },
                series: supplierValues,
                labels: suppliers,
                colors: ['#B4771E', '#28c76f', '#328693', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8'],
                legend: {
                    position: 'bottom',
                    labels: {
                        colors: '#5d596c',
                        fontFamily: 'Public Sans'
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return val.toFixed(1) + '%';
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '{{ currency_symbol() }}' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                },
                noData: { text: 'No data available' }
            });
            supplierChart.render();
        } else {
            $('#supplierChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }
    }

    function initDatePickers() {
        if (typeof $.fn.flatpickr !== 'undefined') {
            const startEl = $('input[name="start_date"]')[0];
            const endEl = $('input[name="end_date"]')[0];
            if (startEl && endEl) {
                if (startEl._flatpickr) startEl._flatpickr.destroy();
                if (endEl._flatpickr) endEl._flatpickr.destroy();

                const startPicker = $(startEl).flatpickr({
                    altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                    onChange: function (selectedDates, dateStr, instance) {
                        $(instance.element).closest('form').trigger('change');
                        if (selectedDates.length) {
                            endPicker.set('minDate', selectedDates[0]);
                        } else {
                            endPicker.set('minDate', null);
                        }
                    }
                });

                const endPicker = $(endEl).flatpickr({
                    altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                    onChange: function (selectedDates, dateStr, instance) {
                        $(instance.element).closest('form').trigger('change');
                        if (selectedDates.length) {
                            startPicker.set('maxDate', selectedDates[0]);
                        } else {
                            startPicker.set('maxDate', 'today');
                        }
                    }
                });

                if (startPicker.selectedDates.length) {
                    endPicker.set('minDate', startPicker.selectedDates[0]);
                }
                if (endPicker.selectedDates.length) {
                    startPicker.set('maxDate', endPicker.selectedDates[0]);
                }
            } else {
                $('.flatpickr').each(function () { if (this._flatpickr) this._flatpickr.destroy(); });
                $('.flatpickr').flatpickr({
                    altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                    onChange: function (selectedDates, dateStr, instance) {
                        $(instance.element).closest('form').trigger('change');
                    }
                });
            }
        }
    }

    $(document).ready(function () {
        // Initial load
        initReport();
        initDatePickers();

        function loadReport(url) {
            $('#report-results').css('opacity', 0.5);
            window.showAjaxLoader && window.showAjaxLoader();

            $.get(url, function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newResults = $(doc).find('#report-results').html();

                $('#report-results').html(newResults);
                initReport();
                initDatePickers();
                updateFilterButtonsVisibility();
            }).always(function () {
                $('#report-results').css('opacity', 1);
                window.hideAjaxLoader && window.hideAjaxLoader();
            });
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

        $(document).on('click', '#applyFiltersBtn', function () {
            const form = $('#filterForm');
            const url = form.attr('action') + '?' + form.serialize();

            loadReport(url);
        });

        $(document).on('click', '#clearFiltersBtn', function () {
            const form = $('#filterForm');

            form[0].reset();
            form.find('.flatpickr').each(function () {
                if (this._flatpickr) {
                    this._flatpickr.clear();
                    this._flatpickr.set('minDate', null);
                    this._flatpickr.set('maxDate', null);
                }
            });
            form.find('input').val('');
            form.find('select').val('').trigger('change.select2');
            updateFilterButtonsVisibility();

            loadReport(form.attr('action'));
        });

        $('#exportExcelBtn').on('click', function () {
            const form = $('#filterForm');
            const url = "{{ route('admin.reports.purchases.export') }}?" + form.serialize();
            window.location.href = url;
        });
    });
    </script>
@endsection
