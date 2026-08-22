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
        .flatpickr-calendar.flatpickr-month-only .flatpickr-monthDropdown-months,
        .flatpickr-calendar.flatpickr-month-only .flatpickr-innerContainer {
            display: none !important;
        }
        .flatpickr-calendar.flatpickr-month-only .flatpickr-months {
            padding-bottom: 0 !important;
        }
        .gst-flatpickr-month-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 8px 10px 10px 10px;
        }
        .gst-flatpickr-month-grid button {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 6px;
            padding: 8px 4px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .gst-flatpickr-month-grid button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f1f5f9 !important;
            color: #94a3b8 !important;
            border-color: #e2e8f0 !important;
        }
        .gst-flatpickr-month-grid button:not(:disabled):hover {
            background: #7367f0 !important;
            color: #fff !important;
            border-color: #7367f0 !important;
        }
        .gst-flatpickr-month-grid button.active {
            background: #7367f0 !important;
            color: #fff !important;
            border-color: #7367f0 !important;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(115, 103, 240, 0.4);
        }
        #confirmGstJsonDownload:focus,
        #confirmGstJsonDownload:active,
        #confirmGstJsonDownload.active {
            box-shadow: none !important;
            outline: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Sales Report</h4>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-warning report-export-btn" data-bs-toggle="offcanvas" data-bs-target="#gstReportOffcanvas">
                <i class="ti ti-file-code me-1"></i> JSON Report
            </button>
            <button type="button" id="exportPdfBtn" class="btn btn-danger report-export-btn" target="_blank">
                <i class="ti ti-file-text me-1"></i> Export to PDF
            </button>
        </div>
    </div>

    <div id="report-results">
        <div id="chart-data"
             data-sales-trend='@json($salesTrend)'
             data-payment-method='@json($paymentMethodData)'>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Sales</span>
                            <h4 class="mb-0 mt-1">{{ format_price($totalSales) }}</h4>
                            <small class="text-muted d-block mt-1">Pending Amount: <span class="fw-semibold text-warning">{{ format_price($totalPendingAmount ?? 0) }}</span></small>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-chart-line ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
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
            <div class="card h-100">
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
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Payment Split</span>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <span class="badge bg-label-success">{{ $paidCount }} Paid</span>
                                @if(($partialCount ?? 0) > 0)
                                    <span class="badge bg-label-info">{{ $partialCount }} Partial</span>
                                @endif
                                <span class="badge bg-label-warning">{{ $pendingCount }} Unpaid</span>
                            </div>
                            <small class="text-muted d-block mt-1">Pending: <span class="fw-semibold text-warning">{{ format_price($totalPendingAmount ?? 0) }}</span></small>
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
    <div class="card mb-4" id="filterReportCard">
        <div class="card-header">
            <h5 class="mb-0">Filter Report</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.sales') }}" id="filterForm" class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Start Date</label>
                    <input type="text" name="start_date" class="form-control flatpickr" value="{{ $startDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">End Date</label>
                    <input type="text" name="end_date" class="form-control flatpickr" value="{{ $endDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-3 col-sm-6">
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
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select no-select2">
                        <option value="">All Statuses</option>
                        <option value="1" {{ $paymentStatus == 1 ? 'selected' : '' }}>Pending</option>
                        <option value="2" {{ $paymentStatus == 2 ? 'selected' : '' }}>Paid</option>
                        <option value="3" {{ $paymentStatus == 3 ? 'selected' : '' }}>Partially Paid</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select no-select2">
                        <option value="">All Methods</option>
                        <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="online" {{ $paymentMethod === 'online' ? 'selected' : '' }}>Online</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Sale Type</label>
                    <select name="is_gst" class="form-select no-select2">
                        <option value="">All Sales</option>
                        <option value="1" {{ (string)$isGst === '1' ? 'selected' : '' }}>GST Sales</option>
                        <option value="0" {{ (string)$isGst === '0' ? 'selected' : '' }}>Non GST Sales</option>
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
                                <th class="d-none">date_sort</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                <th>Barcode</th>
                                <th class="text-end text-nowrap">Qty Sold</th>
                                <th class="text-end text-nowrap">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- GST Report Export Offcanvas Sidepanel -->
    <div class="offcanvas offcanvas-end" id="gstReportOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 500px; max-width: 100vw;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-semibold">Download JSON Report</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column" style="overflow: hidden;">
            <form id="gstReportDownloadForm" class="d-flex flex-column h-100 m-0">
                <div class="flex-grow-1 p-4" style="overflow-y: auto;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Return Period (Month & Year)</label>
                        <input type="text" id="gstJsonMonth" class="form-control" value="{{ date('m-Y') }}" placeholder="MM-YYYY" readonly>
                    </div>
                </div>
                <div class="d-flex p-4 border-top gap-3 mt-auto mb-0">
                    <button type="submit" id="confirmGstJsonDownload" class="btn btn-primary flex-fill w-50 m-0">
                        <i class="ti ti-download me-1"></i> Download JSON
                    </button>
                    <button type="button" class="btn btn-label-secondary flex-fill w-50 m-0" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
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
        if ($.fn.DataTable.isDataTable('#salesReportTable')) {
            $('#salesReportTable').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#productsReportTable')) {
            $('#productsReportTable').DataTable().destroy();
        }

        $('#salesReportTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            pageLength: 25,
            ajax: {
                url: '{{ route("admin.reports.sales.data") }}',
                data: function (d) {
                    d.start_date     = $('input[name="start_date"]').val();
                    d.end_date       = $('input[name="end_date"]').val();
                    d.location_id    = $('select[name="location_id"]').val();
                    d.payment_status = $('select[name="payment_status"]').val();
                    d.payment_method = $('select[name="payment_method"]').val();
                    d.is_gst         = $('select[name="is_gst"]').val();
                }
            },
            columns: [
                { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'invoice_no' },
                { data: 'customer' },
                { data: 'location' },
                { data: 'payment_status' },
                { data: 'payment_method' },
                { data: 'final_amount', className: 'text-end text-nowrap fw-semibold' },
                { data: 'actions', orderable: false, searchable: false },
                { data: 'date_group', visible: false },
                { data: 'date_sort', visible: false }
            ],
            order: [[9, 'desc']],
            rowGroup: {
                dataSrc: 'date_group',
                startRender: function (rows, group) {
                    return $('<tr class="group-header"/>')
                        .append('<td colspan="8"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' sale' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                }
            }
        });

        if ($('#tab-products').hasClass('active')) {
            initProductsReportTable();
        } else {
            $(document).off('shown.bs.tab', 'button[data-bs-target="#tab-products"]').one('shown.bs.tab', 'button[data-bs-target="#tab-products"]', function () {
                initProductsReportTable();
            });
        }

        function initProductsReportTable() {
            if (!$.fn.DataTable.isDataTable('#productsReportTable')) {
                $('#productsReportTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: false,
                    pageLength: 25,
                    ajax: {
                        url: '{{ route("admin.reports.sales.products-data") }}',
                        data: function (d) {
                            d.start_date     = $('input[name="start_date"]').val();
                            d.end_date       = $('input[name="end_date"]').val();
                            d.location_id    = $('select[name="location_id"]').val();
                            d.payment_status = $('select[name="payment_status"]').val();
                            d.payment_method = $('select[name="payment_method"]').val();
                            d.is_gst         = $('select[name="is_gst"]').val();
                        }
                    },
                    columns: [
                        { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                        { data: 'product' },
                        { data: 'barcode' },
                        { data: 'qty_sold', className: 'text-end text-nowrap' },
                        { data: 'total_revenue', className: 'text-end text-nowrap' }
                    ],
                    order: [[3, 'desc']]
                });
            }
        }

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
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return '{{ currency_symbol() }}' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                },
            });
            paymentMethodChart.render();
        } else {
            $('#paymentMethodChart').html('<div class="text-center py-5 text-muted">No data available</div>');
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

            $.get(url, function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newResults = $(doc).find('#report-results').html();

                $('#report-results').html(newResults);
                initReport();
                initDatePickers();
                initGstJsonMonthPicker();
                updateFilterButtonsVisibility();
            }).always(function () {
                $('#report-results').css('opacity', 1);
            });
        }

        let isFiltered = false;

        function updateFilterButtonsVisibility() {
            const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                return $(el).val();
            });
            $('#filterActionButtons').toggleClass('d-none', !hasValue);

            if (!hasValue && isFiltered) {
                isFiltered = false;
                loadReport($('#filterForm').attr('action'));
            }
        }

        $(document).on('input change', '#filterForm', function () {
            updateFilterButtonsVisibility();
        });
        updateFilterButtonsVisibility();

        $(document).on('click', '#applyFiltersBtn', function () {
            isFiltered = true;
            const form = $('#filterForm');
            const url = form.attr('action') + '?' + form.serialize();

            loadReport(url);
        });

        $(document).on('click', '#clearFiltersBtn', function () {
            isFiltered = false;
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

        $('#exportPdfBtn').on('click', function () {
            const form = $('#filterForm');
            const url = "{{ route('admin.reports.sales.export') }}?auto_print=1&" + form.serialize();
            window.open(url, '_blank');
        });

        function initGstJsonMonthPicker() {
            const inputEl = document.getElementById('gstJsonMonth');
            if (!inputEl || typeof $.fn.flatpickr === 'undefined') return;
            if (inputEl._flatpickr) inputEl._flatpickr.destroy();

            const monthsNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const today = new Date();
            const nowYear = today.getFullYear();
            const nowMonthIdx = today.getMonth();

            function updateMonthGridButtons(instance) {
                const calendarContainer = $(instance.calendarContainer);
                const currentVal = instance.input.value || '';
                const selectedMonthIdx = currentVal ? parseInt(currentVal.split('-')[0], 10) - 1 : null;
                const selectedYear = currentVal ? parseInt(currentVal.split('-')[1], 10) : null;
                const activeYear = instance.currentYear;

                calendarContainer.find('.gst-flatpickr-month-grid button').each(function(idx) {
                    const isFuture = (activeYear > nowYear) || (activeYear === nowYear && idx > nowMonthIdx);
                    $(this).prop('disabled', isFuture);
                    $(this).toggleClass('active', activeYear === selectedYear && idx === selectedMonthIdx);
                });

                calendarContainer.find('.flatpickr-next-month').css({
                    'opacity': activeYear >= nowYear ? '0.3' : '1',
                    'pointer-events': activeYear >= nowYear ? 'none' : 'auto'
                });
            }

            $('#gstJsonMonth').flatpickr({
                dateFormat: 'm-Y',
                maxDate: 'today',
                allowInput: false,
                onOpen: function(selectedDates, dateStr, instance) {
                    const calendarContainer = $(instance.calendarContainer);
                    calendarContainer.addClass('flatpickr-month-only');
                    calendarContainer.find('.flatpickr-days, .flatpickr-weekdaycontainer, .flatpickr-innerContainer').hide();
                    
                    calendarContainer.find('.flatpickr-prev-month').off('click.yearOnly').on('click.yearOnly', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        instance.changeYear(instance.currentYear - 1);
                        updateMonthGridButtons(instance);
                    });
                    
                    calendarContainer.find('.flatpickr-next-month').off('click.yearOnly').on('click.yearOnly', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (instance.currentYear < nowYear) {
                            instance.changeYear(instance.currentYear + 1);
                            updateMonthGridButtons(instance);
                        }
                    });

                    if (calendarContainer.find('.gst-flatpickr-month-grid').length === 0) {
                        const grid = $('<div class="gst-flatpickr-month-grid"></div>');

                        monthsNames.forEach((mName, idx) => {
                            const btn = $('<button type="button"></button>').text(mName);
                            
                            btn.on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                if ($(this).is(':disabled')) return;

                                const year = instance.currentYear;
                                const monthStr = String(idx + 1).padStart(2, '0');
                                const selectedVal = monthStr + '-' + year;
                                instance.setDate(selectedVal, true);
                                instance.close();
                            });
                            grid.append(btn);
                        });
                        calendarContainer.append(grid);
                    }
                    updateMonthGridButtons(instance);
                },
                onYearChange: function(selectedDates, dateStr, instance) {
                    updateMonthGridButtons(instance);
                }
            });
        }

        initGstJsonMonthPicker();

        $(document).on('submit', '#gstReportDownloadForm', function (e) {
            e.preventDefault();
            const monthVal = $('#gstJsonMonth').val();
            if (!monthVal) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Please select a return period (Month & Year).');
                } else {
                    alert('Please select a month');
                }
                return;
            }

            const btn = $('#confirmGstJsonDownload');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Downloading...');

            const url = "{{ route('admin.reports.sales.gst-json') }}?month=" + monthVal;

            fetch(url, {
                headers: {
                    'Accept': 'application/json, text/plain, */*'
                }
            })
            .then(async response => {
                if (!response.ok) {
                    let errorMsg = 'No GST sales records found for the selected period.';
                    try {
                        const data = await response.json();
                        if (data && data.message) errorMsg = data.message;
                    } catch (e) {}
                    throw new Error(errorMsg);
                }
                const disposition = response.headers.get('Content-Disposition') || response.headers.get('content-disposition') || '';
                let filename = '';
                if (disposition && disposition.indexOf('filename=') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                if (!filename) {
                    const parts = monthVal.split('-');
                    if (parts.length === 2) {
                        const mNum = parseInt(parts[0], 10);
                        const yNum = parseInt(parts[1], 10);
                        const startYear = mNum >= 4 ? yNum : yNum - 1;
                        filename = `CHETAN IMITATION_${startYear} - ${startYear + 1}_${mNum}.json`;
                    } else {
                        filename = `CHETAN IMITATION_${monthVal}.json`;
                    }
                }
                const blob = await response.blob();
                return { blob, filename };
            })
            .then(({ blob, filename }) => {
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(downloadUrl);
                a.remove();

                if (typeof toastr !== 'undefined') {
                    toastr.success('GST Report JSON downloaded successfully!');
                }

                const offcanvasEl = document.getElementById('gstReportOffcanvas');
                if (offcanvasEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).hide();
                }
            })
            .catch(error => {
                if (typeof toastr !== 'undefined') {
                    toastr.warning(error.message || 'No GST sales data found for the selected period.');
                } else {
                    alert(error.message || 'No GST sales data found.');
                }
            })
            .finally(() => {
                btn.prop('disabled', false).html(originalHtml);
                btn.find('.waves-ripple').remove();
            });
        });

        $('#gstReportOffcanvas').on('hidden.bs.offcanvas', function () {
            const btn = $('#confirmGstJsonDownload');
            btn.prop('disabled', false).removeClass('active focus').blur();
            btn.find('.waves-ripple').remove();
        });
    });
    </script>
@endsection
