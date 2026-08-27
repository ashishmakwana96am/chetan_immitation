@extends('layouts.app')

@section('title', 'Payment Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <style>
        .payment-stat-card .badge { font-size: 1rem; padding: 0.6rem; }
        .gateway-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .method-cod    { background: #fff3cd; color: #856404; }
        .method-cash   { background: #e6f9f0; color: #28c76f; }
        .method-online { background: #e8f4fd; color: #0d6efd; }
        .source-pos    { background: #f0f0f5; color: #5e5873; }
        .source-online { background: #ede7f6; color: #7367f0; }
        .status-captured  { background: #e6f9f0; color: #28c76f; }
        .status-paid      { background: #e6f9f0; color: #28c76f; }
        .status-partial   { background: #e7f3ff; color: #7367f0; }
        .status-pending   { background: #fff3cd; color: #856404; }
        .status-refunded  { background: #fff3cd; color: #856404; }
        .status-failed    { background: #fde8e8; color: #ea5455; }
        #paymentsTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #paymentsTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #paymentsTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #paymentsTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Payment Report</h4>
        <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
    </div>

    <div id="report-results">
        <div id="chart-data"
             data-payment-trend='@json($paymentTrend)'
             data-payment-method-data='@json($paymentMethodData)'
             data-source-data='@json($sourceData)'>
        </div>

        {{-- Stats Cards --}}
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card payment-stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Sales</span>
                                <h4 class="mb-0 mt-1 text-success" id="statTotalAmount">{{ format_price($totalAmount) }}</h4>
                            </div>
                            <span class="badge bg-label-success rounded p-2"><i class="ti ti-trending-up ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card payment-stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Pending Amount</span>
                                <h4 class="mb-0 mt-1 text-warning" id="statPendingAmount">{{ format_price($pendingAmount) }}</h4>
                            </div>
                            <span class="badge bg-label-warning rounded p-2"><i class="ti ti-wallet ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card payment-stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Refunds</span>
                                <h4 class="mb-0 mt-1 text-danger" id="statRefundAmount">{{ format_price($refundAmount) }}</h4>
                            </div>
                            <span class="badge bg-label-danger rounded p-2"><i class="ti ti-receipt-refund ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">Payment Volume (Monthly)</h5></div>
                    <div class="card-body">
                        <div id="paymentTrendChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">By Payment Method</h5></div>
                    <div class="card-body">
                        <div id="paymentMethodChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card h-100">
                    <div class="card-header"><h5 class="mb-0">By Source</h5></div>
                    <div class="card-body">
                        <div id="sourceChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filter Report</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reports.payments') }}" id="filterForm" class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Start Date</label>
                        <input type="text" name="start_date" class="form-control flatpickr" value="{{ $startDate }}" placeholder="DD-MM-YYYY" />
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">End Date</label>
                        <input type="text" name="end_date" class="form-control flatpickr" value="{{ $endDate }}" placeholder="DD-MM-YYYY" />
                    </div>
                    @if($isSuperAdmin)
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Location</label>
                        <select name="location_id" class="form-select no-select2">
                            <option value="">All Locations</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Source</label>
                        <select name="source" class="form-select no-select2">
                            <option value="">All Sources</option>
                            @foreach($availableSources as $src)
                                <option value="{{ $src }}" {{ $source === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select no-select2">
                            <option value="">All Methods</option>
                            @foreach($availablePaymentMethods as $method)
                                <option value="{{ $method }}" {{ $paymentMethod === $method ? 'selected' : '' }}>
                                    {{ $method === 'cod' ? 'COD' : ucfirst($method) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" id="filterPaymentStatus" class="form-select no-select2">
                            <option value="">All Statuses</option>
                            <option value="{{ \App\Models\Order::PAYMENT_STATUS_PENDING }}" {{ $paymentStatus == \App\Models\Order::PAYMENT_STATUS_PENDING ? 'selected' : '' }}>Pending</option>
                            <option value="{{ \App\Models\Order::PAYMENT_STATUS_PARTIAL }}" {{ $paymentStatus == \App\Models\Order::PAYMENT_STATUS_PARTIAL ? 'selected' : '' }}>Partially Paid</option>
                            <option value="{{ \App\Models\Order::PAYMENT_STATUS_PAID }}" {{ $paymentStatus == \App\Models\Order::PAYMENT_STATUS_PAID ? 'selected' : '' }}>Paid</option>
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

        {{-- Payments Table --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">All Payment Transactions</h5>
            </div>
            <div class="card-datatable table-responsive">
                <table class="table border-top" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Order No</th>
                            <th>Customer</th>
                            <th>Source</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Refund Amount</th>
                            <th class="d-none">date_group</th>
                            <th class="d-none">date_sort</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    let paymentTrendChart  = null;
    let paymentMethodChart = null;
    let sourceChart        = null;

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

    function renderDonutChart(containerId, chartInstance, dataObj, colors) {
        const labels = Object.keys(dataObj || {});
        const values = Object.values(dataObj || {});

        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        if (labels.length > 0 && values.some(v => v > 0)) {
            chartInstance = new ApexCharts(document.getElementById(containerId), {
                chart: { type: 'donut', height: 320 },
                series: values,
                labels: labels,
                colors: colors,
                legend: { position: 'bottom', labels: { colors: '#5d596c', fontFamily: 'Public Sans' } },
                dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + '%' },
                tooltip: {
                    y: {
                        formatter: (val) => '{{ currency_symbol() }}' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    }
                },
                noData: { text: 'No data available' }
            });
            chartInstance.render();
        } else {
            $('#' + containerId).html('<div class="text-center py-5 text-muted">No data available</div>');
        }
        return chartInstance;
    }

    function updateCharts(chartsObj) {
        if (!chartsObj) return;

        const paymentTrend      = chartsObj.paymentTrend || {};
        const paymentMethodData = chartsObj.paymentMethodData || {};
        const sourceData        = chartsObj.sourceData || {};

        const months = Object.keys(paymentTrend);
        const values = Object.values(paymentTrend);

        if (paymentTrendChart) {
            paymentTrendChart.destroy();
            paymentTrendChart = null;
        }

        if (months.length > 0) {
            paymentTrendChart = new ApexCharts(document.getElementById('paymentTrendChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{ name: 'Payments', data: values }],
                xaxis: { categories: months },
                colors: ['#7367f0'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                dataLabels: { enabled: false },
                yaxis: {
                    labels: {
                        formatter: (val) => formatCompactIndian(val)
                    }
                },
                tooltip: {
                    y: {
                        formatter: (val) => '{{ currency_symbol() }}' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    }
                }
            });
            paymentTrendChart.render();
        } else {
            $('#paymentTrendChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }

        paymentMethodChart = renderDonutChart('paymentMethodChart', paymentMethodChart, paymentMethodData, ['#0d6efd', '#28c76f', '#ff9f43', '#ea5455']);
        sourceChart        = renderDonutChart('sourceChart', sourceChart, sourceData, ['#5e5873', '#7367f0', '#28c76f']);
    }

    function initCharts() {
        const chartDataEl = $('#chart-data');
        const paymentTrend      = JSON.parse(chartDataEl.attr('data-payment-trend') || '{}');
        const paymentMethodData = JSON.parse(chartDataEl.attr('data-payment-method-data') || '{}');
        const sourceData        = JSON.parse(chartDataEl.attr('data-source-data') || '{}');

        updateCharts({
            paymentTrend: paymentTrend,
            paymentMethodData: paymentMethodData,
            sourceData: sourceData
        });
    }

    function initReport() {
        initCharts();

        if ($.fn.DataTable.isDataTable('#paymentsTable')) {
            $('#paymentsTable').DataTable().destroy();
        }
        $('#paymentsTable').DataTable({
            processing : true,
            serverSide : true,
            responsive : false,
            pageLength : 25,
            ajax: {
                url: '{{ route("admin.reports.payments.data") }}',
                data: function (d) {
                    d.start_date     = $('input[name="start_date"]').val();
                    d.end_date       = $('input[name="end_date"]').val();
                    d.location_id    = $('select[name="location_id"]').val();
                    d.source         = $('select[name="source"]').val();
                    d.payment_method = $('select[name="payment_method"]').val();
                    d.payment_status = $('select[name="payment_status"]').val();
                },
                dataSrc: function (json) {
                    if (json.stats) {
                        $('#statTotalAmount').text(json.stats.totalAmount || '{{ currency_symbol() }}0.00');
                        $('#statPendingAmount').text(json.stats.pendingAmount || '{{ currency_symbol() }}0.00');
                        $('#statRefundAmount').text(json.stats.refundAmount || '{{ currency_symbol() }}0.00');
                    }
                    if (json.charts) {
                        updateCharts(json.charts);
                    }
                    return json.data || [];
                }
            },
            columns: [
                { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                {
                    data: 'invoice_no',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.raw_invoice_no !== undefined ? row.raw_invoice_no : String(data).replace(/<[^>]*>/g, '');
                        }
                        return data;
                    }
                },
                {
                    data: 'customer',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.raw_customer !== undefined ? row.raw_customer : String(data).replace(/<[^>]*>/g, '');
                        }
                        return data;
                    }
                },
                {
                    data: 'source',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.raw_source !== undefined ? row.raw_source : String(data).replace(/<[^>]*>/g, '');
                        }
                        return data;
                    }
                },
                {
                    data: 'payment_method',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.raw_payment_method !== undefined ? row.raw_payment_method : String(data).replace(/<[^>]*>/g, '');
                        }
                        return data;
                    }
                },
                {
                    data: 'status',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.raw_status !== undefined ? row.raw_status : String(data).replace(/<[^>]*>/g, '');
                        }
                        return data;
                    }
                },
                {
                    data: 'final_amount',
                    type: 'num',
                    className: 'text-end fw-semibold text-nowrap',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.raw_final_amount !== undefined ? parseFloat(row.raw_final_amount) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'refund_amount',
                    type: 'num',
                    className: 'text-end fw-semibold text-nowrap text-danger',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.raw_refund_amount !== undefined ? parseFloat(row.raw_refund_amount) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                        }
                        return data;
                    }
                },
                { data: 'date_group', visible: false },
                { data: 'date_sort', visible: false }
            ],
            columnDefs: [
                { targets: 0, orderable: false }
            ],
            order: [[9, 'desc']],
            rowGroup: {
                dataSrc: 'date_group',
                startRender: function (rows, group) {
                    return $('<tr class="group-header"/>')
                        .append('<td colspan="8"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' payment' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                }
            }
        });
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
                    altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today'
                });
            }
        }
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

    $(document).ready(function () {
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
                updateFilterButtonsVisibility();
            }).always(function () {
                $('#report-results').css('opacity', 1);
            });
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

        $(document).on('click', '#exportExcelBtn', function () {
            const form = $('#filterForm');
            const url = "{{ route('admin.reports.payments.export-excel') }}?" + form.serialize();

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
                    let filename = 'payment_report.xlsx';
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
