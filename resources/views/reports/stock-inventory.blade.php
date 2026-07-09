@extends('layouts.app')

@section('title', 'Stock Inventory Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Stock Inventory Report</h4>
        <button id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
    </div>

    <div id="report-results">
        @include('reports.partials.stock-inventory-results')
    </div>

    <!-- Filters -->
    <div class="card mb-4" id="filterReportCard">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Report</h5>
            <div class="d-flex gap-2">
                <button type="button" id="applyFiltersBtn" class="btn btn-sm btn-primary">
                    <i class="ti ti-filter me-1"></i> Apply
                </button>
                <button type="button" id="clearFiltersBtn" class="btn btn-sm btn-label-secondary">
                    <i class="ti ti-refresh me-1"></i> Clear
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.stock-inventory') }}" id="dateFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date <small class="text-muted">(Last Purchase)</small></label>
                    <input type="text" name="from_date" class="form-control flatpickr" value="{{ $fromDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date <small class="text-muted">(Last Purchase)</small></label>
                    <input type="text" name="to_date" class="form-control flatpickr" value="{{ $toDate }}" placeholder="DD-MM-YYYY" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Category</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Stock</label>
                    <select id="filterStock" class="form-select">
                        <option value="">All</option>
                        <option value="in">In Stock</option>
                        <option value="low">Low Stock (≤ 5)</option>
                        <option value="out">SOLD OUT</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Show Products Older Than</label>
                    <select id="filterAge" class="form-select">
                        <option value="">Any Age</option>
                        <option value="30">30 Days</option>
                        <option value="60">60 Days</option>
                        <option value="90">90 Days</option>
                        <option value="180">180 Days</option>
                        <option value="365">365 Days</option>
                        <option value="custom">Custom Days</option>
                    </select>
                </div>
                <div class="col-md-3 d-none" id="customAgeWrapper">
                    <label class="form-label">Custom Days</label>
                    <input type="number" id="filterAgeCustom" class="form-control" min="1" placeholder="e.g. 45" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sort By</label>
                    <select id="sortBy" class="form-select">
                        <option value="">Default</option>
                        <option value="age_desc">Inventory Age (Oldest First)</option>
                        <option value="age_asc">Inventory Age (Newest First)</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    $(document).ready(function () {
        let table = null;
        let $filterCard = null;

        $.fn.dataTable.ext.type.order['null-last-asc'] = function (a, b) {
            if (a === '') return 1;
            if (b === '') return -1;
            return a < b ? -1 : (a > b ? 1 : 0);
        };
        $.fn.dataTable.ext.type.order['null-last-desc'] = function (a, b) {
            if (a === '') return 1;
            if (b === '') return -1;
            return a < b ? 1 : (a > b ? -1 : 0);
        };

        function initReport() {
            if (!$filterCard) {
                $filterCard = $('#filterReportCard');
            }
            $filterCard.insertBefore('#stockDetailCard');

            if ($.fn.DataTable.isDataTable('#stockTable')) {
                $('#stockTable').DataTable().destroy();
            }

            const lastPurchaseColumnIndex = 4 + {{ $locations->count() }} + 2;
            const ageColumnIndex = lastPurchaseColumnIndex + 1;

            const hasDateFilter = !!($('input[name="from_date"]').val() || $('input[name="to_date"]').val());
            const defaultOrder = hasDateFilter ? [lastPurchaseColumnIndex, 'asc'] : [lastPurchaseColumnIndex, 'desc'];

            table = $('#stockTable').DataTable({
                responsive : false,
                order      : [defaultOrder],
                columnDefs : [
                    {
                        targets: 0,
                        orderable: false,
                    },
                    {
                        targets: lastPurchaseColumnIndex,
                        type: 'null-last',
                    }
                ],
            });

            table.on('draw', function () {
                const start = table.page.info().start;
                table.rows({ page: 'current' }).every(function (rowIdx, tableLoop, rowLoop) {
                    $(this.node()).find('td').eq(0).html(start + rowLoop + 1);
                });
            }).draw(false);

            function applyFilters() {
                const cat   = $('#filterCategory').val();
                const stock = $('#filterStock').val();
                let minAge  = $('#filterAge').val();
                if (minAge === 'custom') {
                    minAge = $('#filterAgeCustom').val();
                }
                minAge = minAge ? parseInt(minAge) : null;

                $.fn.dataTable.ext.search = [];
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    const row   = $(table.row(dataIndex).node());
                    const total = parseInt(row.data('total'));
                    const age   = parseInt(row.data('age'));
                    if (cat              && row.data('category-id') != cat) return false;
                    if (hasDateFilter    && total <= 0)                     return false; // Last Purchase filter: only items in stock
                    if (stock === 'in'   && total <= 0)                     return false;
                    if (stock === 'low'  && (total === 0 || total > 5))     return false;
                    if (stock === 'out'  && total > 0)                      return false;
                    if (minAge !== null  && age < minAge)                   return false;
                    return true;
                });
                table.draw();
            }

            $('#filterAge').off('change').on('change', function () {
                $('#customAgeWrapper').toggleClass('d-none', $(this).val() !== 'custom');
            });

            function applySort() {
                const val = $('#sortBy').val();
                if (val === 'age_desc') {
                    table.order([ageColumnIndex, 'desc']).draw();
                } else if (val === 'age_asc') {
                    table.order([ageColumnIndex, 'asc']).draw();
                }
                // blank ("Default") keeps the order already set above (latest/oldest by Last Purchase Date)
            }

            applyFilters();
            applySort();

            initCharts();
        }

        function initCharts() {
            const locations   = @json($locations->pluck('name'));
            const locationIds = @json($locations->pluck('id'));

            const products = JSON.parse(document.getElementById('reportProductsData').textContent || '[]');

            // Only use parent rows for charts — variant rows must NOT be double-counted
            const parentProducts = products.filter(p => p.is_parent);

            const locationTotals = locationIds.map(function (locId) {
                return parentProducts.reduce(function (sum, p) {
                    return sum + (p.stock[locId] || 0);
                }, 0);
            });

            new ApexCharts(document.getElementById('locationStockChart'), {
                chart  : { type: 'bar', height: 380, toolbar: { show: false } },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '55%',
                        distributed: true
                    }
                },
                colors  : ['#B4771E', '#28c76f', '#328693', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8'],
                series : [{ name: 'Total Stock', data: locationTotals }],
                xaxis  : {
                    categories: locations,
                    labels: {
                        style: { colors: '#5d596c', fontFamily: 'Public Sans' },
                        formatter: function(val) { return parseInt(val); }
                    }
                },
                yaxis  : {
                    labels: { style: { colors: '#5d596c', fontFamily: 'Public Sans', fontWeight: 500 } }
                },
                dataLabels : {
                    enabled: true,
                    style: { fontSize: '11px', fontFamily: 'Public Sans', fontWeight: '600', colors: ['#fff'] },
                    formatter: function(val) { return parseInt(val); }
                },
                legend: { show: false },
                tooltip: { y: { formatter: function(val) { return val + ' Units'; } } },
                grid: {
                    borderColor: '#e5e5e5',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } },
                    padding: { top: -15, right: 10, bottom: -10, left: 10 }
                }
            }).render();

            // Top 10 Products — only parent rows, sorted by total stock descending
            const top10 = parentProducts.slice().sort((a, b) => b.total - a.total).slice(0, 10);

            const stackedSeries = locationIds.map(function (locId, i) {
                return {
                    name : locations[i],
                    data : top10.map(p => p.stock[locId] || 0),
                };
            });

            new ApexCharts(document.getElementById('stackedStockChart'), {
                chart  : { type: 'bar', height: 380, stacked: true, toolbar: { show: false } },
                series : stackedSeries,
                xaxis  : {
                    categories: top10.map(p => p.name),
                    labels: {
                        style: { colors: '#5d596c', fontFamily: 'Public Sans' },
                        formatter: function(val) { return parseInt(val); }
                    }
                },
                yaxis  : {
                    labels: { style: { colors: '#5d596c', fontFamily: 'Public Sans', fontWeight: 500 } }
                },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
                dataLabels : {
                    enabled: true,
                    style: { fontSize: '10px', fontFamily: 'Public Sans', fontWeight: '600', colors: ['#fff'] },
                    formatter: function(val) { return val > 0 ? val : ''; }
                },
                legend     : {
                    position: 'bottom',
                    fontFamily: 'Public Sans',
                    fontSize: '11px',
                    labels: { colors: '#5d596c' },
                    itemMargin: { horizontal: 8, vertical: 4 }
                },
                grid: {
                    borderColor: '#e5e5e5',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } },
                    padding: { top: -15, right: 10, bottom: -10, left: 10 }
                },
                tooltip: { y: { formatter: function(val) { return val + ' Units'; } } }
            }).render();
        }

        function loadReport(url) {
            $('#report-results').css('opacity', 0.5);
            window.showAjaxLoader && window.showAjaxLoader();

            if ($filterCard) $filterCard.detach();

            $.get(url, function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newResults = $(doc).find('#report-results').html();

                $('#report-results').html(newResults);
                initReport();
            }).fail(function () {
                toastr.error('Failed to load the report. Please try again.');
                if ($filterCard) $filterCard.insertBefore('#stockDetailCard');
            }).always(function () {
                $('#report-results').css('opacity', 1);
                window.hideAjaxLoader && window.hideAjaxLoader();
            });
        }

        function initDatePickers() {
            if (typeof $.fn.flatpickr === 'undefined') return;

            $('#dateFilterForm .flatpickr').each(function () {
                if (this._flatpickr) this._flatpickr.destroy();
            });
            $('#dateFilterForm .flatpickr').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false,
            });
        }
        
        $('#applyFiltersBtn').on('click', function () {
            const form = $('#dateFilterForm');
            loadReport(form.attr('action') + '?' + form.serialize());
        });

        $('#clearFiltersBtn').on('click', function() {
            $('#filterCategory').val('').trigger('change.select2');
            $('#filterStock').val('').trigger('change.select2');
            $('#filterAge').val('').trigger('change.select2');
            $('#filterAgeCustom').val('');
            $('#customAgeWrapper').addClass('d-none');
            $('#sortBy').val('').trigger('change.select2');
            $('#dateFilterForm .flatpickr').each(function () {
                if (this._flatpickr) this._flatpickr.clear();
            });

            loadReport('{{ route('admin.reports.stock-inventory') }}');
        });

        $('#exportExcelBtn').on('click', function() {
            const cat = $('#filterCategory').val();
            const stock = $('#filterStock').val();

            let url = "{{ route('admin.reports.stock-inventory.export') }}?";
            let params = [];
            if (cat) params.push('category_id=' + cat);
            if (stock) params.push('stock=' + stock);

            window.location.href = url + params.join('&');
        });

        initReport();
        initDatePickers();
    });
    </script>
@endsection
