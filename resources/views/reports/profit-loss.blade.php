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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Profit & Loss Report</h4>
        <button type="button" id="exportPdfBtn" class="btn btn-danger report-export-btn" target="_blank">
            <i class="ti ti-file-text me-1"></i> Export to PDF
        </button>
    </div>

    <div id="report-results">
        <div id="chart-data" 
             data-monthly-revenue='@json($monthlyRevenue)' 
             data-monthly-cogs='@json($monthlyCogs)'
             data-monthly-expenses='@json($monthlyExpenses)'>
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
                            <span class="text-muted">Expenses</span>
                            <h4 class="mb-0 mt-1 text-warning">{{ format_price($totalExpenses) }}</h4>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ti ti-receipt ti-sm"></i></span>
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

                    <!-- Operating Expenses -->
                    <div class="ledger-header">Operating Expenses</div>
                    <div class="ledger-line">
                        <span>Total Expenses</span>
                        <span class="text-danger">-{{ format_price($totalExpenses) }}</span>
                    </div>
                    <div class="ledger-line total-line">
                        <span>Total Operating Expenses</span>
                        <span class="text-danger">{{ format_price($totalExpenses) }}</span>
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

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Report</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.profit-loss') }}" id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="text" name="start_date" class="form-control flatpickr" value="{{ $startDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="text" name="end_date" class="form-control flatpickr" value="{{ $endDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-3">
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

    <!-- Itemized Profitability analysis -->
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Product Profitability Breakdown</h5></div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="profitabilityTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Barcode</th>
                        <th class="text-end">Qty Sold</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Purchase Cost</th>
                        <th class="text-end">Net Profit</th>
                        <th class="text-end">Margin</th>
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
    let revenueCogsChart = null;

    function initReport() {
        if ($.fn.DataTable.isDataTable('#profitabilityTable')) {
            $('#profitabilityTable').DataTable().destroy();
        }

        $('#profitabilityTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            pageLength: 25,
            ajax: {
                url: '{{ route("admin.reports.profit-loss.data") }}',
                data: function (d) {
                    d.start_date  = $('input[name="start_date"]').val();
                    d.end_date    = $('input[name="end_date"]').val();
                    d.location_id = $('select[name="location_id"]').val();
                }
            },
            columns: [
                { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'product' },
                { data: 'barcode' },
                { data: 'qty_sold', className: 'text-end' },
                { data: 'total_revenue', className: 'text-end' },
                { data: 'total_cost', className: 'text-end' },
                { data: 'profit', className: 'text-end' },
                { data: 'margin', className: 'text-end' }
            ],
            order: []
        });

        const chartDataEl = $('#chart-data');
        const monthlyRevenue = JSON.parse(chartDataEl.attr('data-monthly-revenue') || '{}');
        const monthlyCogs = JSON.parse(chartDataEl.attr('data-monthly-cogs') || '{}');
        const monthlyExpenses = JSON.parse(chartDataEl.attr('data-monthly-expenses') || '{}');

        const months = Object.keys(monthlyRevenue);
        const revenueValues = Object.values(monthlyRevenue);
        const cogsValues = Object.values(monthlyCogs);
        const expensesValues = Object.values(monthlyExpenses);

        if (revenueCogsChart) {
            revenueCogsChart.destroy();
            revenueCogsChart = null;
        }
        if (months.length > 0) {
            revenueCogsChart = new ApexCharts(document.getElementById('revenueCogsChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [
                    { name: 'Revenue', data: revenueValues },
                    { name: 'COGS (Cost)', data: cogsValues },
                    { name: 'Expenses', data: expensesValues }
                ],
                xaxis: { categories: months },
                colors: ['#28c76f', '#ea5455', '#ff9f43'],
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
            const url = "{{ route('admin.reports.profit-loss.export') }}?" + form.serialize() + "&auto_print=1";
            window.open(url, '_blank');
        });
    });
    </script>
@endsection
