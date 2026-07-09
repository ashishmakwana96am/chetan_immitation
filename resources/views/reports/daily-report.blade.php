@extends('layouts.app')

@section('title', 'Daily Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Daily Report</h4>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Report</h5>
            <button type="button" id="resetFilters" class="btn btn-sm btn-label-secondary">
                <i class="ti ti-refresh me-1"></i> Reset
            </button>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.daily-report') }}" id="filterForm" class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Date</label>
                    <input type="text" name="date" class="form-control flatpickr" value="{{ $date }}" placeholder="DD-MM-YYYY" />
                </div>
                @if($isSuperAdmin)
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Branch</label>
                        <select name="location_id" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ (string) $locationId === (string) $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div id="dailyReportResults">
        @include('reports.partials.daily-report-results')
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            const dataUrl = '{{ route('admin.reports.daily-report.data') }}';

            const dailyTableIds = ['#dailySalesTable', '#dailyPurchasesTable', '#dailyExpensesTable', '#dailyPurchaseBillTable'];

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

            function loadReport() {
                window.showAjaxLoader && window.showAjaxLoader();
                $.get(dataUrl, $('#filterForm').serialize())
                    .done(function (res) {
                        if (res.status !== 'success') return;
                        renderResults(res);
                    })
                    .fail(function () {
                        toastr.error('Failed to load the report. Please try again.');
                    })
                    .always(function () {
                        window.hideAjaxLoader && window.hideAjaxLoader();
                    });
            }

            function money(val) {
                const symbol = '{{ currency_symbol() }}';
                return symbol + ' ' + parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function renderResults(res) {
                $('#totalSalesAmount').text(money(res.totalSales));
                $('#totalSalesCount').text(res.totalSalesCount + ' order' + (res.totalSalesCount == 1 ? '' : 's'));
                $('#totalPurchasesAmount').text(money(res.totalPurchases));
                $('#totalPurchasesCount').text(res.totalPurchasesCount + ' invoice' + (res.totalPurchasesCount == 1 ? '' : 's'));
                $('#totalExpensesAmount').text(money(res.totalExpenses));
                $('#totalExpensesCount').text(res.totalExpensesCount + ' entr' + (res.totalExpensesCount == 1 ? 'y' : 'ies'));
                $('#totalTransfersCount').text(res.totalTransfersCount);
                $('#totalTransfersQty').text(res.totalTransfersQty + ' unit' + (res.totalTransfersQty == 1 ? '' : 's'));

                // Branch-wise breakdown
                const $branchBody = $('#branchBreakdownBody');
                const $branchCard = $('#branchBreakdownCard');
                if (res.branchRows.length > 1) {
                    let rows = '';
                    res.branchRows.forEach(function (row) {
                        rows += '<tr>' +
                            '<td class="fw-semibold">' + row.location_name + '</td>' +
                            '<td class="text-end">' + money(row.sales_amount) + ' <small class="text-muted">(' + row.sales_count + ')</small></td>' +
                            '<td class="text-end">' + money(row.purchase_amount) + ' <small class="text-muted">(' + row.purchase_count + ')</small></td>' +
                            '<td class="text-end">' + money(row.expense_amount) + ' <small class="text-muted">(' + row.expense_count + ')</small></td>' +
                            '<td class="text-end">' + row.transfer_count + ' <small class="text-muted">(' + row.transfer_qty + ' units)</small></td>' +
                            '</tr>';
                    });
                    $branchBody.html(rows);
                    $branchCard.show();
                } else {
                    $branchCard.hide();
                }

                // Sales
                let salesRows = '';
                res.salesRows.forEach(function (row) {
                    salesRows += '<tr>' +
                        '<td>' + row.index + '</td>' +
                        '<td><code>' + row.sale_no + '</code></td>' +
                        '<td>' + row.customer + '</td>' +
                        '<td>' + row.location + '</td>' +
                        '<td>' + row.source + '</td>' +
                        '<td class="text-end">' + money(row.amount) + '</td>' +
                        '<td>' + row.status + '</td>' +
                        '<td>' + row.payment_status + '</td>' +
                        '<td>' + row.method + '</td>' +
                        '</tr>';
                });
                $('#dailySalesBody').html(salesRows);

                // Purchases
                let purchaseRows = '';
                res.purchaseRows.forEach(function (row) {
                    purchaseRows += '<tr>' +
                        '<td>' + row.index + '</td>' +
                        '<td><code>' + row.purchase_no + '</code></td>' +
                        '<td>' + row.supplier + '</td>' +
                        '<td class="text-end">' + money(row.total_amount) + '</td>' +
                        '<td>' + row.status + '</td>' +
                        '<td>' + row.payment_status + '</td>' +
                        '</tr>';
                });
                $('#dailyPurchasesBody').html(purchaseRows);

                // Expenses
                let expenseRows = '';
                res.expenseRows.forEach(function (row) {
                    expenseRows += '<tr>' +
                        '<td>' + row.index + '</td>' +
                        '<td>' + row.title + '</td>' +
                        '<td>' + row.category + '</td>' +
                        '<td class="text-end">' + money(row.amount) + '</td>' +
                        '<td>' + row.payment_method + '</td>' +
                        '<td>' + row.location + '</td>' +
                        '<td>' + row.expense_date + '</td>' +
                        '<td>' + row.created_by + '</td>' +
                        '</tr>';
                });
                $('#dailyExpensesBody').html(expenseRows);

                // Purchase Bill
                let billRows = '';
                res.purchaseBillRows.forEach(function (row) {
                    billRows += '<tr>' +
                        '<td>' + row.index + '</td>' +
                        '<td><code>' + row.bill_no + '</code></td>' +
                        '<td>' + row.source + '</td>' +
                        '<td>' + row.destination + '</td>' +
                        '<td>' + row.items_count + '</td>' +
                        '<td class="text-end">' + money(row.amount) + '</td>' +
                        '<td>' + row.status + '</td>' +
                        '<td>' + row.created_by + '</td>' +
                        '</tr>';
                });
                $('#dailyPurchaseBillBody').html(billRows);

                initDailyTables();
            }

            initDailyTables();

            if (typeof $.fn.flatpickr !== 'undefined') {
                $('.flatpickr').flatpickr({
                    altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false,
                    onChange: function (selectedDates, dateStr, instance) {
                        $(instance.element).closest('form').trigger('change');
                    }
                });
            }

            $(document).on('change', '#filterForm', function (e) {
                e.preventDefault();
                loadReport();
            });

            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                loadReport();
            });

            $('#resetFilters').on('click', function () {
                const form = $('#filterForm');
                const dateInput = form.find('input[name="date"]')[0];

                if (dateInput && dateInput._flatpickr) {
                    dateInput._flatpickr.setDate('{{ now()->toDateString() }}', false);
                } else if (dateInput) {
                    dateInput.value = '{{ now()->toDateString() }}';
                }
                form.find('select[name="location_id"]').val('').trigger('change.select2');

                loadReport();
            });
        });
    </script>
@endsection
