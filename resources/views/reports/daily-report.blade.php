@extends('layouts.app')

@section('title', 'Daily Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <style>
        #dailySalesTable td code,
        #dailyCollectionsTable td code,
        #dailyCollectionsTable td a code,
        table.dataTable td code,
        table td code {
            color: #e83e8c !important;
        }
        #dailyCollectionsTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #dailyCollectionsTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #dailyCollectionsTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #dailyCollectionsTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Daily Report</h4>
        <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
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
            const dailyTableIds = [
                '#dailySalesTable',
                '#dailyCollectionsTable',
                '#dailyPurchasesTable',
                '#dailyBulkPaymentsTable',
                '#dailyBalanceTransfersTable',
                '#dailyExpensesTable',
                '#dailyPurchaseBillTable'
            ];
            let dailyOverviewChart = null;

            function initDailyTables() {
                if ($.fn.DataTable.isDataTable('#dailySalesTable')) {
                    $('#dailySalesTable').DataTable().destroy();
                }
                $('#dailySalesTable').DataTable({
                    responsive: false,
                    deferRender: true,
                    pageLength: 25,
                    order: [],
                    columnDefs: [
                        { targets: 0, orderable: false, searchable: false },
                        { targets: 5, type: 'num' }
                    ],
                });

                if ($('#dailyCollectionsTable').length) {
                    if ($.fn.DataTable.isDataTable('#dailyCollectionsTable')) {
                        $('#dailyCollectionsTable').DataTable().destroy();
                    }
                    $('#dailyCollectionsTable').DataTable({
                        responsive: false,
                        deferRender: true,
                        pageLength: 25,
                        order: [[2, 'desc']],
                        rowGroup: {
                            dataSrc: 2,
                            startRender: function (rows, group) {
                                const groupTitle = (typeof group === 'object' && group !== null)
                                    ? (group.display || group['@data-order'] || '')
                                    : group;
                                return $('<tr class="group-header"/>')
                                    .append('<td colspan="8"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + groupTitle + '</span><span class="badge bg-label-primary ms-2">' + rows.count() + ' collection' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                            }
                        },
                        columnDefs: [
                            { targets: 0, orderable: false, searchable: false },
                            { targets: 2, visible: false },
                            { targets: 6, type: 'num' },
                            { targets: 8, orderable: false, searchable: false }
                        ],
                    });
                }

                if ($.fn.DataTable.isDataTable('#dailyPurchasesTable')) {
                    $('#dailyPurchasesTable').DataTable().destroy();
                }
                $('#dailyPurchasesTable').DataTable({
                    responsive: false,
                    deferRender: true,
                    pageLength: 25,
                    order: [],
                    columnDefs: [
                        { targets: 0, orderable: false, searchable: false },
                        { targets: 3, type: 'num' }
                    ],
                });

                if ($('#dailyBulkPaymentsTable').length) {
                    if ($.fn.DataTable.isDataTable('#dailyBulkPaymentsTable')) {
                        $('#dailyBulkPaymentsTable').DataTable().destroy();
                    }
                    $('#dailyBulkPaymentsTable').DataTable({
                        responsive: false,
                        deferRender: true,
                        pageLength: 25,
                        order: [],
                        columnDefs: [
                            { targets: 0, orderable: false, searchable: false },
                            { targets: 4, type: 'num' }
                        ],
                    });
                }

                if ($('#dailyBalanceTransfersTable').length) {
                    if ($.fn.DataTable.isDataTable('#dailyBalanceTransfersTable')) {
                        $('#dailyBalanceTransfersTable').DataTable().destroy();
                    }
                    $('#dailyBalanceTransfersTable').DataTable({
                        responsive: false,
                        deferRender: true,
                        pageLength: 25,
                        order: [],
                        columnDefs: [
                            { targets: 0, orderable: false, searchable: false },
                            { targets: 5, type: 'num' }
                        ],
                    });
                }

                if ($.fn.DataTable.isDataTable('#dailyExpensesTable')) {
                    $('#dailyExpensesTable').DataTable().destroy();
                }
                $('#dailyExpensesTable').DataTable({
                    responsive: false,
                    deferRender: true,
                    pageLength: 25,
                    order: [],
                    columnDefs: [
                        { targets: 0, orderable: false, searchable: false },
                        { targets: 3, type: 'num' }
                    ],
                });

                if ($.fn.DataTable.isDataTable('#dailyPurchaseBillTable')) {
                    $('#dailyPurchaseBillTable').DataTable().destroy();
                }
                $('#dailyPurchaseBillTable').DataTable({
                    responsive: false,
                    deferRender: true,
                    pageLength: 25,
                    order: [],
                    columnDefs: [
                        { targets: 0, orderable: false, searchable: false },
                        { targets: 4, type: 'num' },
                        { targets: 5, type: 'num' }
                    ],
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

                const categories = ['Sales', 'Collections', 'Purchases', 'Expenses', 'Balance Transfer', 'Payments Out'];
                const values = [
                    parseFloat(el.getAttribute('data-total-sales') || 0),
                    parseFloat(el.getAttribute('data-total-dues-collected') || 0),
                    parseFloat(el.getAttribute('data-total-purchases') || 0),
                    parseFloat(el.getAttribute('data-total-expenses') || 0),
                    parseFloat(el.getAttribute('data-total-transfers') || 0),
                    parseFloat(el.getAttribute('data-total-payments-out') || 0),
                ];

                dailyOverviewChart = new ApexCharts(document.getElementById('dailyOverviewChart'), {
                    chart: { type: 'bar', height: 320, toolbar: { show: false } },
                    series: [{ name: 'Amount', data: values }],
                    xaxis: { categories: categories },
                    colors: ['#28c76f', '#10b981', '#00cfe8', '#ea5455', '#7367f0', '#ff9f43'],
                    plotOptions: { bar: { borderRadius: 4, columnWidth: '45%', distributed: true } },
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

            $(document).on('click', '#exportExcelBtn', function () {
                const form = $('#filterForm');
                const url = "{{ route('admin.reports.daily-report.export-excel') }}?" + form.serialize();

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
                        let filename = 'daily_report.xlsx';
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

            window.refreshTable = function () {
                submitFilters();
            };

            $(document).on('click', '.delete-collection-btn', function (e) {
                e.preventDefault();
                const btn = $(this);
                const id = btn.data('id');
                const saleNo = btn.data('sale-no');
                const amount = btn.data('amount');

                Swal.fire({
                    title: 'Delete Collection Entry?',
                    text: `Are you sure you want to delete payment collection of ${amount} for Sale #${saleNo}? This will reverse the payment from the sale.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-danger me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (window.showAjaxLoader) {
                            $('.loader-status').text('Deleting Collection');
                            window.showAjaxLoader();
                        }

                        const url = "{{ url('admin/reports/daily-report/collections') }}/" + id;
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                if (window.hideAjaxLoader) window.hideAjaxLoader();
                                toastr.success(res.message || 'Payment collection deleted successfully.');
                                submitFilters();
                            },
                            error: function (xhr) {
                                if (window.hideAjaxLoader) window.hideAjaxLoader();
                                const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                                toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
