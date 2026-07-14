@extends('layouts.app')

@section('title', 'Supplier Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #supplierLedgerTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #supplierLedgerTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #supplierLedgerTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #supplierLedgerTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
        #supplierLedgerDetailTable td.text-danger-balance { color: #ea5455; }
        #supplierLedgerDetailTable tr.purchase-row { cursor: pointer; }
        #supplierLedgerDetailTable .purchase-toggle-icon { transition: transform 0.2s ease; display: inline-block; }
        #supplierLedgerDetailTable tr.purchase-row.is-open .purchase-toggle-icon { transform: rotate(90deg); }
        .ledger-child-table th, .ledger-child-table td { font-size: 0.8rem; }
    </style>
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
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select id="filter-supplier" class="form-select">
                        <option value="">All Suppliers (Summary)</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
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

    <div class="row g-4 mb-4">
        <div class="col-md-3 col-6 d-none" id="openingBalanceCard">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Opening Balance</p>
                    <h5 class="mb-0" id="summaryOpening">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6" id="purchaseCardCol">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Purchase</p>
                    <h5 class="mb-0" id="summaryPurchase">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6" id="paymentCardCol">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Payment</p>
                    <h5 class="mb-0 text-success" id="summaryPayment">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6" id="outstandingCardCol">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1" id="outstandingCardLabel">Outstanding <small class="text-muted">(Accounts Payable)</small></p>
                    <h5 class="mb-0 text-danger" id="summaryOutstanding">-</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card" id="summaryTableCard">
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
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="card d-none" id="detailTableCard">
        <div class="card-header">
            <h5 class="mb-0">Transaction Ledger</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="supplierLedgerDetailTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Voucher No</th>
                        <th>Particular</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="fw-bold table-light">
                        <td colspan="4" class="text-end">Totals</td>
                        <td class="text-end" id="footTotalPurchase">-</td>
                        <td class="text-end" id="footTotalPayment">-</td>
                        <td class="text-end" id="footClosingBalance">-</td>
                    </tr>
                </tfoot>
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
                    switchMode('');
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

            let currentMode = 'summary';
            let detailTable = null;

            function buildItemsChildHtml(items) {
                const rows = items.map(function (it) {
                    return '<tr><td>' + it.product + '</td><td>' + it.variant + '</td>'
                        + '<td class="text-end">' + it.quantity + '</td><td class="text-end">' + it.rate + '</td>'
                        + '<td class="text-end">' + it.gst + '</td><td class="text-end">' + it.amount + '</td></tr>';
                }).join('');

                return '<table class="table table-sm ledger-child-table mb-0 bg-label-secondary">'
                    + '<thead><tr><th>Product</th><th>Variant</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">GST</th><th class="text-end">Amount</th></tr></thead>'
                    + '<tbody>' + rows + '</tbody></table>';
            }

            function initDetailTable() {
                detailTable = $('#supplierLedgerDetailTable').DataTable({
                    responsive: false,
                    ordering: false,
                    ajax: {
                        url: '{{ route('admin.ledgers.supplier.detail') }}',
                        cache: false,
                        data: function (d) {
                            Object.assign(d, currentFilters());
                            d.supplier_id = $('#filter-supplier').val();
                        },
                        dataSrc: function (json) {
                            if (json.summary) {
                                $('#summaryOpening').text(json.summary.opening);
                                $('#summaryPurchase').text(json.summary.purchase);
                                $('#summaryPayment').text(json.summary.payment);
                                $('#summaryOutstanding').text(json.summary.closing);
                                $('#footTotalPurchase').text(json.summary.purchase);
                                $('#footTotalPayment').text(json.summary.payment);
                                $('#footClosingBalance').text(json.summary.closing);
                            }
                            return json.data;
                        },
                    },
                    columns: [
                        { data: 'index', orderable: false, width: '5%' },
                        { data: 'date' },
                        { data: 'voucher_no' },
                        {
                            data: 'particular',
                            render: function (data, type, row) {
                                if (type !== 'display') return data;
                                return row.items && row.items.length
                                    ? '<i class="ti ti-chevron-right purchase-toggle-icon me-1"></i>' + data
                                    : data;
                            }
                        },
                        { data: 'debit', className: 'text-end' },
                        { data: 'credit', className: 'text-end' },
                        { data: 'balance', className: 'text-end fw-semibold' },
                    ],
                    createdRow: function (row, data) {
                        if (data.items && data.items.length) {
                            $(row).addClass('purchase-row').data('items', data.items);
                        }
                    },
                    drawCallback: function () {
                        const api = this.api();
                        api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                            cell.innerHTML = i + 1;
                        });
                    }
                });

                $('#supplierLedgerDetailTable tbody').on('click', 'tr.purchase-row', function () {
                    const $tr = $(this);
                    const row = detailTable.row($tr);
                    if (row.child.isShown()) {
                        row.child.hide();
                        $tr.removeClass('is-open');
                    } else {
                        row.child(buildItemsChildHtml($tr.data('items') || [])).show();
                        $tr.addClass('is-open');
                    }
                });
            }

            function switchMode(supplierId) {
                if (supplierId) {
                    currentMode = 'detail';
                    $('#summaryTableCard').addClass('d-none');
                    $('#detailTableCard').removeClass('d-none');
                    $('#openingBalanceCard').removeClass('d-none');
                    $('#outstandingCardLabel').text('Closing Balance');

                    if (!detailTable) {
                        initDetailTable();
                    } else {
                        detailTable.ajax.reload();
                    }
                } else {
                    currentMode = 'summary';
                    $('#detailTableCard').addClass('d-none');
                    $('#summaryTableCard').removeClass('d-none');
                    $('#openingBalanceCard').addClass('d-none');
                    $('#outstandingCardLabel').html('Outstanding <small class="text-muted">(Accounts Payable)</small>');
                    table.ajax.reload();
                }
            }

            const table = $('#supplierLedgerTable').DataTable({
                responsive: false,
                order: [[6, 'desc']],
                orderFixed: { pre: [[6, 'desc']] },
                columnDefs: [
                    { targets: [6], visible: false }
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="6"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' entr' + (rows.count() > 1 ? 'ies' : 'y') + '</span></div></td>');
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
                const activeTable = currentMode === 'detail' ? detailTable : table;
                if (!activeTable) return;

                window.showAjaxLoader && window.showAjaxLoader();
                activeTable.ajax.reload(function () {
                    window.hideAjaxLoader && window.hideAjaxLoader();
                }, false);
            };

            $(document).on('click', '#applyFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = true;
                switchMode($('#filter-supplier').val());
            });

            $(document).on('click', '#clearFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = false;
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', 'today');
                endPicker.set('minDate', null);
                $('#filter-supplier').val('');
                updateFilterButtonsVisibility();
                switchMode('');
            });
        });
    </script>
@endsection
