@extends('layouts.app')

@section('title', 'Profit & Loss Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <style>
        .ledger-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            border-bottom: 1px dashed #e6e6e6;
        }
        .ledger-line.total-line {
            font-weight: 700;
            border-top: 2px solid #5d596c;
            border-bottom: 2px solid #5d596c;
            background-color: #f8f7fa;
            margin-top: 10px;
        }
        .ledger-line.net-profit-line {
            font-weight: 700;
            font-size: 1.15rem;
            color: #28c76f;
            border-top: 2px solid #28c76f;
            border-bottom: 4px double #28c76f;
            background-color: rgba(40, 199, 111, 0.08);
            margin-top: 15px;
        }
        .ledger-line.net-loss-line {
            font-weight: 700;
            font-size: 1.15rem;
            color: #ea5455;
            border-top: 2px solid #ea5455;
            border-bottom: 4px double #ea5455;
            background-color: rgba(234, 84, 85, 0.08);
            margin-top: 15px;
        }
        .ledger-header {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: #a1acb8;
            margin-top: 15px;
            margin-bottom: 5px;
            padding-left: 12px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Profit & Loss Report</h4>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Report</h5>
            <a href="{{ route('admin.reports.profit-loss') }}" class="btn btn-sm btn-label-secondary">
                <i class="ti ti-refresh me-1"></i> Reset
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.profit-loss') }}" id="filterForm" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" />
                </div>
                <div class="col-md-4">
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

            </form>
        </div>
    </div>

    <div id="report-results">
        <div id="chart-data" 
             data-monthly-revenue='@json($monthlyRevenue)' 
             data-monthly-cogs='@json($monthlyCogs)'>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Revenue</span>
                            <h4 class="mb-0 mt-1 text-success">{{ format_price($totalRevenue) }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-arrow-up-right ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Cost of Goods Sold (COGS)</span>
                            <h4 class="mb-0 mt-1 text-danger">{{ format_price($totalCogs) }}</h4>
                        </div>
                        <span class="badge bg-label-danger rounded p-2"><i class="ti ti-arrow-down-left ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Net Profit</span>
                            <h4 class="mb-0 mt-1 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ format_price($netProfit) }}
                            </h4>
                        </div>
                        <span class="badge {{ $netProfit >= 0 ? 'bg-label-success' : 'bg-label-danger' }} rounded p-2">
                            <i class="ti ti-presentation ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Profit Margin</span>
                            <h4 class="mb-0 mt-1">{{ round($profitMargin, 1) }}%</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-percentage ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger & Chart Row -->
    <div class="row g-4 mb-4">
        <!-- P&L Operating Ledger Statement -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">P&L Operating Statement</h5>
                    <span class="badge bg-label-secondary small">Ledger View</span>
                </div>
                <div class="card-body pt-2">
                    <!-- Operating Revenue -->
                    <div class="ledger-header">Operating Revenue</div>
                    <div class="ledger-line">
                        <span>Gross Sales Revenue</span>
                        <span>{{ format_price($totalRevenue) }}</span>
                    </div>
                    <div class="ledger-line total-line">
                        <span>Total Revenue</span>
                        <span>{{ format_price($totalRevenue) }}</span>
                    </div>

                    <!-- Cost of Sales -->
                    <div class="ledger-header">Cost of Sales</div>
                    <div class="ledger-line">
                        <span>Cost of Goods Sold (COGS)</span>
                        <span>{{ format_price($totalCogs) }}</span>
                    </div>
                    <div class="ledger-line total-line">
                        <span>Total Cost of Sales</span>
                        <span>{{ format_price($totalCogs) }}</span>
                    </div>

                    <!-- Net Income -->
                    <div class="ledger-header">Operating Income</div>
                    <div class="ledger-line {{ $netProfit >= 0 ? 'net-profit-line' : 'net-loss-line' }}">
                        <span>{{ $netProfit >= 0 ? 'Net Operating Profit' : 'Net Operating Loss' }}</span>
                        <span>{{ format_price($netProfit) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue vs COGS Chart -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Revenue vs COGS (Monthly)</h5></div>
                <div class="card-body">
                    <div id="revenueCogsChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Itemized Profitability analysis -->
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Product Profitability Breakdown</h5></div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="profitabilityTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-end">Qty Sold</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Purchase Cost</th>
                        <th class="text-end">Net Profit</th>
                        <th class="text-end">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    @foreach($productProfitability as $prodId => $data)
                        @php
                            $prodProfit = $data['total_revenue'] - $data['total_cost'];
                            $prodMargin = $data['total_revenue'] > 0 ? ($prodProfit / $data['total_revenue']) * 100 : 0;
                        @endphp
                        <tr>
                            <td>{{ $counter++ }}</td>
                            <td>
                                <a href="{{ route('admin.products.show', $prodId) }}" class="fw-semibold">
                                    {{ $data['name'] }}
                                </a>
                            </td>
                            <td><code>{{ $data['sku'] }}</code></td>
                            <td class="text-end fw-semibold">{{ $data['qty_sold'] }}</td>
                            <td class="text-end text-success fw-semibold">{{ format_price($data['total_revenue']) }}</td>
                            <td class="text-end text-danger fw-semibold">{{ format_price($data['total_cost']) }}</td>
                            <td class="text-end {{ $prodProfit >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                {{ format_price($prodProfit) }}
                            </td>
                            <td class="text-end">
                                <span class="badge {{ $prodProfit >= 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ round($prodMargin, 1) }}%
                                </span>
                            </td>
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
    let revenueCogsChart = null;

    function initReport() {
        // Initialize DataTables (destroy first if already exists)
        if ($.fn.DataTable.isDataTable('#profitabilityTable')) {
            $('#profitabilityTable').DataTable().destroy();
        }

        $('#profitabilityTable').DataTable({
            responsive : false,
            order      : [[6, 'desc']], // Order by Net Profit by default
        });

        // Fetch Chart Data from DOM attributes to bypass jQuery cache
        const chartDataEl = $('#chart-data');
        const monthlyRevenue = JSON.parse(chartDataEl.attr('data-monthly-revenue') || '{}');
        const monthlyCogs = JSON.parse(chartDataEl.attr('data-monthly-cogs') || '{}');

        const months = Object.keys(monthlyRevenue);
        const revenueValues = Object.values(monthlyRevenue);
        const cogsValues = Object.values(monthlyCogs);

        if (revenueCogsChart) {
            revenueCogsChart.destroy();
            revenueCogsChart = null;
        }
        if (months.length > 0) {
            revenueCogsChart = new ApexCharts(document.getElementById('revenueCogsChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [
                    { name: 'Revenue', data: revenueValues },
                    { name: 'COGS (Cost)', data: cogsValues }
                ],
                xaxis: { categories: months },
                colors: ['#28c76f', '#ea5455'],
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
            revenueCogsChart.render();
        } else {
            $('#revenueCogsChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }
    }

    $(document).ready(function () {
        // Initial load
        initReport();

        // AJAX Filtering on form field changes
        $('#filterForm').on('change', 'input, select', function () {
            const form = $('#filterForm');
            const url = form.attr('action') + '?' + form.serialize();

            // Set loading opacity
            $('#report-results').css('opacity', 0.5);

            $.get(url, function (html) {
                // Parse and update the results container
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newResults = $(doc).find('#report-results').html();
                
                $('#report-results').html(newResults);
                
                // Re-initialize charts and tables
                initReport();
                
                $('#report-results').css('opacity', 1);
            });
        });
    });
    </script>
@endsection
