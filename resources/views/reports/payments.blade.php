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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Payment Report</h4>
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
                <div class="card payment-stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Sales</span>
                                <h4 class="mb-0 mt-1 text-success">{{ format_price($totalAmount) }}</h4>
                                <small class="text-muted">{{ $totalCount }} transaction{{ $totalCount != 1 ? 's' : '' }}</small>
                            </div>
                            <span class="badge bg-label-success rounded p-2"><i class="ti ti-trending-up ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card payment-stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Transactions</span>
                                <h4 class="mb-0 mt-1 text-primary">{{ $totalCount }}</h4>
                                <small class="text-muted">{{ format_price($totalAmount) }} total</small>
                            </div>
                            <span class="badge bg-label-primary rounded p-2"><i class="ti ti-credit-card ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card payment-stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Avg Transaction</span>
                                <h4 class="mb-0 mt-1 text-info">{{ format_price($avgAmount) }}</h4>
                                <small class="text-muted">per transaction</small>
                            </div>
                            <span class="badge bg-label-info rounded p-2"><i class="ti ti-calculator ti-sm"></i></span>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Filter Report</h5>
                <button type="button" id="resetFilters" class="btn btn-sm btn-label-secondary">
                    <i class="ti ti-refresh me-1"></i> Reset
                </button>
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
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Location</label>
                        <select name="location_id" class="form-select no-select2">
                            <option value="">All Locations</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Source</label>
                        <select name="source" class="form-select no-select2">
                            <option value="">All Sources</option>
                            @foreach($availableSources as $src)
                                <option value="{{ $src }}" {{ $source === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
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
                </form>
            </div>
        </div>

        {{-- Payments Table --}}
        <div class="card">
            <div class="card-header">
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
                            <th class="d-none">date_group</th>
                            <th class="d-none">date_sort</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php
                                $payment = $order->payment;
                                $sourceVal = strtoupper($order->source ?? 'POS');
                                $normalizedMethod = match ($order->payment_method) {
                                    'razorpay' => 'online',
                                    default    => $order->payment_method,
                                };
                                $methodLabel = match ($normalizedMethod) {
                                    'cod'    => 'COD',
                                    'online' => 'Online',
                                    'cash'   => 'Cash',
                                    default  => ucwords(str_replace('_', ' ', $normalizedMethod ?? '')),
                                };
                                if ($payment) {
                                    $statusLabel = ucfirst($payment->status);
                                    $statusClass = 'status-' . $payment->status;
                                } elseif ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID) {
                                    $statusLabel = 'Paid';
                                    $statusClass = 'status-paid';
                                } else {
                                    $statusLabel = 'Pending';
                                    $statusClass = 'status-pending';
                                }
                            @endphp
                            <tr>
                                <td></td>
                                <td>
                                    <a href="{{ route('admin.sales.show', $order->id) }}">
                                        <code>{{ $order->order_no }}</code>
                                    </a>
                                </td>
                                <td><span class="fw-semibold">{{ $order->customer?->name ?? 'Walk-in' }}</span></td>
                                <td>
                                    <span class="gateway-badge source-{{ strtolower($sourceVal) }}">{{ $sourceVal }}</span>
                                </td>
                                <td>
                                    <span class="gateway-badge method-{{ $normalizedMethod }}">{{ $methodLabel }}</span>
                                </td>
                                <td>
                                    <span class="gateway-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="text-end fw-semibold text-nowrap">{{ format_price($order->final_amount) }}</td>
                                <td class="d-none">{{ $order->created_at->format('d M Y') }}</td>
                                <td class="d-none">{{ $order->created_at->format('Ymd') }}</td>
                            </tr>
                        @endforeach
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
    let trendChart = null;
    let paymentMethodChart = null;
    let sourceChart = null;

    function renderDonutChart(elementId, chartRef, data, colors) {
        if (chartRef) { chartRef.destroy(); chartRef = null; }
        const keys = Object.keys(data);
        const vals = Object.values(data);
        if (keys.length > 0) {
            chartRef = new ApexCharts(document.getElementById(elementId), {
                chart: { type: 'donut', height: 280 },
                series: vals,
                labels: keys,
                legend: { position: 'bottom' },
                dataLabels: { enabled: true },
                colors: colors,
            });
            chartRef.render();
        } else {
            document.getElementById(elementId).innerHTML = '<div class="text-center py-5 text-muted">No data available</div>';
        }
        return chartRef;
    }

    function initCharts() {
        const el = document.getElementById('chart-data');
        const paymentTrend      = JSON.parse(el.getAttribute('data-payment-trend')       || '{}');
        const paymentMethodData = JSON.parse(el.getAttribute('data-payment-method-data') || '{}');
        const sourceData        = JSON.parse(el.getAttribute('data-source-data')         || '{}');

        if (trendChart)  { trendChart.destroy();  trendChart  = null; }
        const months = Object.keys(paymentTrend);
        const values = Object.values(paymentTrend);
        if (months.length > 0) {
            trendChart = new ApexCharts(document.getElementById('paymentTrendChart'), {
                chart: { type: 'area', height: 300, toolbar: { show: false } },
                series: [{ name: 'Amount Received', data: values }],
                xaxis: { categories: months },
                colors: ['#7367f0'],
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] }
                },
                dataLabels: { enabled: false },
                yaxis: {
                    labels: {
                        formatter: val => '{{ currency_symbol() }}' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    }
                }
            });
            trendChart.render();
        } else {
            document.getElementById('paymentTrendChart').innerHTML = '<div class="text-center py-5 text-muted">No data available</div>';
        }

        paymentMethodChart = renderDonutChart('paymentMethodChart', paymentMethodChart, paymentMethodData, ['#0d6efd', '#28c76f', '#ff9f43', '#ea5455']);
        sourceChart        = renderDonutChart('sourceChart', sourceChart, sourceData, ['#5e5873', '#7367f0', '#28c76f']);
    }

    function initTable() {
        if ($.fn.DataTable.isDataTable('#paymentsTable')) {
            $('#paymentsTable').DataTable().destroy();
        }
        $('#paymentsTable').DataTable({
            responsive : false,
            order      : [[8, 'desc']],
            orderFixed : { pre: [[8, 'desc']] },
            columnDefs : [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
                },
                { targets: [7, 8], visible: false }
            ],
            rowGroup   : {
                dataSrc: 7,
                startRender: function (rows, group) {
                    return $('<tr class="group-header"/>')
                        .append('<td colspan="7"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' payment' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                }
            },
            drawCallback: function () {
                const api = this.api();
                api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                    cell.innerHTML = i + 1;
                });
            }
        });
    }

    function initDatePickers() {
        $('.flatpickr').each(function () { if (this._flatpickr) this._flatpickr.destroy(); });
        if (typeof $.fn.flatpickr !== 'undefined') {
            $('.flatpickr').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false,
                onChange: function (selectedDates, dateStr, instance) {
                    $(instance.element).closest('form').trigger('change');
                }
            });
        }
    }

    function loadReport(url) {
        $('#report-results').css('opacity', 0.5);
        $.get(url, function (html) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            $('#report-results').html($(doc).find('#report-results').html());
            initCharts();
            initTable();
            initDatePickers();
        }).always(function () { $('#report-results').css('opacity', 1); });
    }

    $(document).ready(function () {
        initCharts();
        initTable();
        initDatePickers();

        $(document).on('change', '#filterForm', function () {
            loadReport($(this).attr('action') + '?' + $(this).serialize());
        });

        $(document).on('click', '#resetFilters', function () {
            const form = $('#filterForm');
            form[0].reset();
            form.find('.flatpickr').each(function () { if (this._flatpickr) this._flatpickr.clear(); });
            form.find('select').val('');
            loadReport(form.attr('action'));
        });
    });
    </script>
@endsection
