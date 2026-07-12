@extends('layouts.app')

@section('title', 'Daily Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Daily Report</h4>
    </div>

    <div id="report-results">
        @include('reports.partials.daily-report-results')
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
        $(document).ready(function () {
            const dailyTableIds = ['#dailySalesTable', '#dailyPurchasesTable', '#dailyExpensesTable', '#dailyPurchaseBillTable'];
            let dailyOverviewChart = null;

            function initDailyTables() {
                dailyTableIds.forEach(function (id) {
                    if ($.fn.DataTable.isDataTable(id)) {
                        $(id).DataTable().destroy();
                    }
                    $(id).DataTable({
                        responsive: false,
                        order: [],
                        columnDefs: [
                            { targets: 0, orderable: false, searchable: false },
                        ],
                    });
                });
            }

            function initDailyChart() {
                const el = document.getElementById('chart-data');
                if (!el) {
                    return;
                }

                if (dailyOverviewChart) {
                    dailyOverviewChart.destroy();
                    dailyOverviewChart = null;
                }

                const categories = ['Sales', 'Purchases', 'Expenses'];
                const values = [
                    parseFloat(el.getAttribute('data-total-sales') || 0),
                    parseFloat(el.getAttribute('data-total-purchases') || 0),
                    parseFloat(el.getAttribute('data-total-expenses') || 0),
                ];

                dailyOverviewChart = new ApexCharts(document.getElementById('dailyOverviewChart'), {
                    chart: { type: 'bar', height: 300, toolbar: { show: false } },
                    series: [{ name: 'Amount', data: values }],
                    xaxis: { categories: categories },
                    colors: ['#7367f0'],
                    plotOptions: { bar: { borderRadius: 4, columnWidth: '40%', distributed: true } },
                    legend: { show: false },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return '{{ currency_symbol() }}' + parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                return '{{ currency_symbol() }}' + parseFloat(val || 0).toLocaleString('en-IN');
                            }
                        }
                    },
                });
                dailyOverviewChart.render();
            }

            function initDatePickers() {
                if (typeof $.fn.flatpickr === 'undefined') {
                    return;
                }
                $('.flatpickr').each(function () {
                    if (this._flatpickr) {
                        this._flatpickr.destroy();
                    }
                });
                $('.flatpickr').flatpickr({
                    altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                    onChange: function (selectedDates, dateStr, instance) {
                        updateFilterButtonsVisibility();
                    }
                });
            }

            function loadReport(url) {
                $('#report-results').css('opacity', 0.5);
                window.showAjaxLoader && window.showAjaxLoader();

                $.get(url, function (html) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newResults = $(doc).find('#report-results').html();

                    $('#report-results').html(newResults);
                    initDailyChart();
                    initDailyTables();
                    initDatePickers();
                    updateFilterButtonsVisibility();
                }).fail(function () {
                    toastr.error('Failed to load the report. Please try again.');
                }).always(function () {
                    $('#report-results').css('opacity', 1);
                    window.hideAjaxLoader && window.hideAjaxLoader();
                });
            }

            let isFiltered = false;

            function submitFilters() {
                const form = $('#filterForm');
                loadReport(form.attr('action') + '?' + form.serialize());
            }

            function updateFilterButtonsVisibility() {
                const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                    return $(el).val();
                });
                $('#filterActionButtons').toggleClass('d-none', !hasValue);

                if (!hasValue && isFiltered) {
                    isFiltered = false;
                    submitFilters();
                }
            }

            initDailyChart();
            initDailyTables();
            initDatePickers();
            updateFilterButtonsVisibility();

            $(document).on('input change', '#filterForm', function () {
                updateFilterButtonsVisibility();
            });

            $(document).on('submit', '#filterForm', function (e) {
                e.preventDefault();
                isFiltered = true;
                submitFilters();
            });

            $(document).on('click', '#applyFiltersBtn', function () {
                isFiltered = true;
                submitFilters();
            });

            $(document).on('click', '#clearFiltersBtn', function () {
                isFiltered = false;
                const form = $('#filterForm');
                const dateInput = form.find('input[name="date"]')[0];

                if (dateInput && dateInput._flatpickr) {
                    dateInput._flatpickr.setDate('{{ now()->toDateString() }}', false);
                } else if (dateInput) {
                    dateInput.value = '{{ now()->toDateString() }}';
                }

                const branchSelect = form.find('select[name="location_id"]');
                branchSelect.val('').trigger('change.select2');
                updateFilterButtonsVisibility();
                submitFilters();
            });
        });
    </script>
@endsection
