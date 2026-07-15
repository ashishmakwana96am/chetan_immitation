@extends('layouts.app')

@section('title', 'Supplier Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Supplier Ledger</h4>
            <small class="text-muted">Company-wide across all branches</small>
        </div>
    </div>

    <div class="card mb-4" id="filterReportCard">
        <div class="card-header">
            <h5 class="mb-0">Filter</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3" onsubmit="return false;">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="text" id="filter-start-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="text" id="filter-end-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
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

    <div class="row g-4 mb-4">
        <div class="col-md-4 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Purchase</p>
                    <h5 class="mb-0" id="summaryPurchase">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Payment</p>
                    <h5 class="mb-0 text-success" id="summaryPayment">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Outstanding <small class="text-muted">(Accounts Payable)</small></p>
                    <h5 class="mb-0 text-danger" id="summaryOutstanding">-</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="supplierLedgerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Due Amount</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            const startPicker = $('#filter-start-date').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                onChange: function (selectedDates) { endPicker.set('minDate', selectedDates.length ? selectedDates[0] : null); }
            });
            const endPicker = $('#filter-end-date').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                onChange: function (selectedDates) { startPicker.set('maxDate', selectedDates.length ? selectedDates[0] : 'today'); }
            });

            let isFiltered = false;

            function updateFilterButtonsVisibility() {
                const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                    return $(el).val();
                });
                $('#filterActionButtons').toggleClass('d-none', !hasValue);

                if (!hasValue && isFiltered) {
                    isFiltered = false;
                    window.refreshTable();
                }
            }

            $(document).on('input change', '#filterForm', function () {
                updateFilterButtonsVisibility();
            });
            updateFilterButtonsVisibility();

            function currentFilters() {
                return {
                    start_date: $('#filter-start-date').val(),
                    end_date: $('#filter-end-date').val(),
                };
            }

            const table = $('#supplierLedgerTable').DataTable({
                responsive: false,
                order: [[7, 'desc']],
                columnDefs: [
                    { targets: [7], visible: false }
                ],
                rowGroup: {
                    dataSrc: 'date',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header table-light"/>')
                            .append('<td colspan="7"><div class="group-header-inner d-flex align-items-center py-2 px-3 fw-bold text-heading"><i class="ti ti-calendar me-2"></i><span>' + group + '</span></div></td>');
                    }
                },
                ajax: {
                    url: '{{ route('admin.ledgers.supplier.data') }}',
                    cache: false,
                    data: function (d) { Object.assign(d, currentFilters()); },
                    dataSrc: function (json) {
                        if (json.summary) {
                            $('#summaryPurchase').text(json.summary.purchase);
                            $('#summaryPayment').text(json.summary.payment);
                            $('#summaryOutstanding').text(json.summary.outstanding);
                        }
                        return json.data;
                    },
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%' },
                    { data: 'supplier' },
                    { data: 'date' },
                    { data: 'total_amount' },
                    { data: 'paid_amount' },
                    { data: 'due_amount' },
                    { data: 'payment_status', orderable: false },
                    { data: 'date_raw' },
                ],
                drawCallback: function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });

            window.refreshTable = function () {
                window.showAjaxLoader && window.showAjaxLoader();
                table.ajax.reload(function () {
                    window.hideAjaxLoader && window.hideAjaxLoader();
                }, false);
            };

            $(document).on('click', '#applyFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = true;
                window.refreshTable();
            });

            $(document).on('click', '#clearFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = false;
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', 'today');
                endPicker.set('minDate', null);
                updateFilterButtonsVisibility();
                window.refreshTable();
            });
        });
    </script>
@endsection
