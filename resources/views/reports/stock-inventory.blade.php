@extends('layouts.app')

@section('title', 'Stock Inventory Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <style>
        .variant-toggle {
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            line-height: 1;
        }
        .variant-toggle i {
            font-size: 1rem;
            transition: transform 0.2s ease;
        }
        .variant-toggle.is-open i {
            transform: rotate(90deg);
        }
        #stockTable tr.child td.child {
            padding: 0 !important;
            background-color: #fbfbfc;
        }
        .variant-table {
            margin-bottom: 0;
        }
        .variant-table th,
        .variant-table td {
            font-size: 0.8125rem;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Stock Inventory Report</h4>
        <button id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
    </div>

    <div id="report-results">
        @include('reports.partials.stock-inventory-results')
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    $(document).ready(function () {
        let table = null;

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
            if ($.fn.DataTable.isDataTable('#stockTable')) {
                $('#stockTable').DataTable().destroy();
            }

            const lastPurchaseColumnIndex = 2;
            const ageColumnIndex = 4 + {{ $locations->count() }} + 4;

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

            $('#stockTable tbody').off('click', '.variant-toggle').on('click', '.variant-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $btn = $(this);
                const productId = $btn.data('product-id');
                const template = document.getElementById('variants-template-' + productId);
                const row = table.row($btn.closest('tr'));

                if (!template) {
                    return;
                }

                if (row.child.isShown()) {
                    row.child.hide();
                    $btn.removeClass('is-open').attr('aria-expanded', 'false');
                } else {
                    row.child(template.innerHTML).show();
                    $btn.addClass('is-open').attr('aria-expanded', 'true');
                }

                table.columns.adjust();
            });

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

            // These filters (category/stock/age/sort) are applied client-side after the
            // partial reloads — the reload re-renders fresh <select> elements with no
            // selection, so without restoring them here the user's choice is silently lost.
            const savedFilters = {
                category  : $('#filterCategory').val(),
                stock     : $('#filterStock').val(),
                age       : $('#filterAge').val(),
                ageCustom : $('#filterAgeCustom').val(),
                sortBy    : $('#sortBy').val(),
            };

            $.get(url, function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newResults = $(doc).find('#report-results').html();

                $('#report-results').html(newResults);

                $('#filterCategory').val(savedFilters.category);
                $('#filterStock').val(savedFilters.stock);
                $('#filterAge').val(savedFilters.age);
                if (savedFilters.age === 'custom') {
                    $('#customAgeWrapper').removeClass('d-none');
                    $('#filterAgeCustom').val(savedFilters.ageCustom);
                }
                $('#sortBy').val(savedFilters.sortBy);

                initReport();
                initDatePickers();
                updateFilterButtonsVisibility();
            }).fail(function () {
                toastr.error('Failed to load the report. Please try again.');
            }).always(function () {
                $('#report-results').css('opacity', 1);
                window.hideAjaxLoader && window.hideAjaxLoader();
            });
        }

        function initDatePickers() {
            if (typeof $.fn.flatpickr === 'undefined') return;

            $('#filterForm .flatpickr').each(function () {
                if (this._flatpickr) this._flatpickr.destroy();
            });
            $('#filterForm .flatpickr').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
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
                loadReport('{{ route('admin.reports.stock-inventory') }}');
            }
        }

        $(document).on('input change', '#filterForm', function () {
            updateFilterButtonsVisibility();
        });
        updateFilterButtonsVisibility();

        $(document).on('click', '#applyFiltersBtn', function () {
            isFiltered = true;
            const form = $('#filterForm');
            loadReport(form.attr('action') + '?' + form.serialize());
        });

        $(document).on('click', '#clearFiltersBtn', function() {
            isFiltered = false;
            $('#filterCategory').val('').trigger('change.select2');
            $('#filterStock').val('').trigger('change.select2');
            $('#filterAge').val('').trigger('change.select2');
            $('#filterAgeCustom').val('');
            $('#customAgeWrapper').addClass('d-none');
            $('#sortBy').val('').trigger('change.select2');
            $('#filterForm .flatpickr').each(function () {
                if (this._flatpickr) this._flatpickr.clear();
            });
            updateFilterButtonsVisibility();

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
