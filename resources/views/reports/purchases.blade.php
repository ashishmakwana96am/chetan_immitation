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
        .report-tabs-header {
            padding: 0 1.25rem !important;
            background-color: #fff;
        }
        .report-tabs-header .nav-tabs,
        .report-tabs-header .nav-tabs.card-header-tabs {
            border-bottom: none !important;
            margin: 0 !important;
            padding-left: 0 !important;
        }
        .report-tabs-header .nav-tabs .nav-link,
        .report-tabs-header .nav-tabs.card-header-tabs .nav-link {
            padding: 0.65rem 1rem !important;
            margin-top: 0 !important;
            margin-bottom: -1px !important;
            font-weight: 500;
            border: none !important;
            border-bottom: 2px solid transparent !important;
            border-radius: 0 !important;
            background: transparent !important;
        }
        .report-tabs-header .nav-tabs .nav-link.active,
        .report-tabs-header .nav-tabs.card-header-tabs .nav-link.active {
            font-weight: 600;
            color: #B4771E !important;
            border-bottom: 2px solid #B4771E !important;
            background: transparent !important;
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
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Purchases</span>
                            <h4 class="mb-0 mt-1">{{ format_price($totalPurchases) }}</h4>
                            <small class="text-muted d-block mt-1">Pending Amount: <span class="fw-semibold text-warning">{{ format_price($totalPendingAmount ?? 0) }}</span></small>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-currency-rupee ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100">
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
            <div class="card h-100">
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
        <div class="card-header">
            <h5 class="mb-0">Filter Report</h5>
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
                <div class="col-md-3">
                    <label class="form-label">Purchase Type</label>
                    <select name="is_gst" class="form-select no-select2">
                        <option value="">All Purchases</option>
                        <option value="1" {{ (string)$isGst === '1' ? 'selected' : '' }}>GST Purchases</option>
                        <option value="0" {{ (string)$isGst === '0' ? 'selected' : '' }}>Non GST Purchases</option>
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

    <!-- Detail Table & Top Purchased Products Tabs -->
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <h5 class="mb-0 fw-semibold">Purchase Details</h5>
        </div>
        <div class="border-bottom report-tabs-header">
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
        if ($.fn.DataTable.isDataTable('#purchasesReportTable')) {
            $('#purchasesReportTable').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#purchasedProductsTable')) {
            $('#purchasedProductsTable').DataTable().destroy();
        }

        $('#purchasesReportTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            pageLength: 25,
            ajax: {
                url: '{{ route("admin.reports.purchases.data") }}',
                data: function (d) {
                    d.start_date  = $('input[name="start_date"]').val();
                    d.end_date    = $('input[name="end_date"]').val();
                    d.supplier_id = $('select[name="supplier_id"]').val();
                    d.is_gst      = $('select[name="is_gst"]').val();
                }
            },
            columns: [
                { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'invoice_no' },
                { data: 'supplier' },
                { data: 'status' },
                { data: 'total_amount', className: 'text-end text-nowrap fw-semibold' },
                { data: 'actions', orderable: false, searchable: false },
                { data: 'date_group', visible: false },
                { data: 'date_sort', visible: false }
            ],
            order: [[7, 'desc']],
            rowGroup: {
                dataSrc: 'date_group',
                startRender: function (rows, group) {
                    return $('<tr class="group-header"/>')
                        .append('<td colspan="6"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' purchase</span></div></td>');
                }
            }
        });

        if ($('#tab-products').hasClass('active')) {
            initProductsTable();
        } else {
            $(document).off('shown.bs.tab', 'button[data-bs-target="#tab-products"]').one('shown.bs.tab', 'button[data-bs-target="#tab-products"]', function () {
                initProductsTable();
            });
        }

        function initProductsTable() {
            if (!$.fn.DataTable.isDataTable('#purchasedProductsTable')) {
                $('#purchasedProductsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: false,
                    pageLength: 25,
                    ajax: {
                        url: '{{ route("admin.reports.purchases.products-data") }}',
                        data: function (d) {
                            d.start_date  = $('input[name="start_date"]').val();
                            d.end_date    = $('input[name="end_date"]').val();
                            d.supplier_id = $('select[name="supplier_id"]').val();
                            d.is_gst      = $('select[name="is_gst"]').val();
                        }
                    },
                    columns: [
                        { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                        { data: 'product' },
                        { data: 'barcode' },
                        { data: 'qty_purchased', className: 'text-end text-nowrap' },
                        { data: 'total_cost', className: 'text-end text-nowrap' }
                    ],
                    order: [[3, 'desc']]
                });
            }
        }

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

        $('#exportExcelBtn').on('click', function () {
            const form = $('#filterForm');
            const url = "{{ route('admin.reports.purchases.export-excel') }}?" + form.serialize();

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
                    let filename = 'purchase_report.xlsx';
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
    });
    </script>
@endsection
