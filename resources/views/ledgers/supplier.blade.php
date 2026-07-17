@extends('layouts.app')

@section('title', 'Supplier Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #supplierLedgerTable tbody tr:not(.group-header) {
            cursor: pointer;
        }
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
                @if(!$isRestricted)
                <div class="col-md-3">
                    <label class="form-label">Location</label>
                    <select id="filter-location" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
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
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Purchase</span>
                            <h4 class="mb-0 mt-1" id="summaryPurchase">-</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-shopping-cart ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Payment</span>
                            <h4 class="mb-0 mt-1 text-success" id="summaryPayment">-</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-credit-card ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Outstanding <small class="text-muted">(Accounts Payable)</small></span>
                            <h4 class="mb-0 mt-1 text-danger" id="summaryOutstanding">-</h4>
                        </div>
                        <span class="badge bg-label-danger rounded p-2"><i class="ti ti-alert-circle ti-sm"></i></span>
                    </div>
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
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Due Amount</th>
                        <th>Action</th>
                        <th>Date Group</th>
                        <th>Date Sort</th>
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
                    location_id: $('#filter-location').val() || '',
                };
            }

            const table = $('#supplierLedgerTable').DataTable({
                responsive: false,
                order: [[7, 'desc']],
                columnDefs: [
                    { targets: [6, 7], visible: false }
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
                    { data: 'total_amount' },
                    { data: 'paid_amount' },
                    { 
                        data: 'due_amount',
                        render: function (data, type, row) {
                            return `<span class="text-danger fw-semibold">${data}</span>`;
                        }
                    },
                    { 
                        data: null, 
                        orderable: false, 
                        render: function (data, type, row) {
                            const locationVal = $('#filter-location').val() || '';
                            const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                            return `
                                <div class="dropdown table-action-dropdown">
                                    <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                        <span>Actions</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                        <a href="${window.location.origin}/admin/ledgers/supplier/detail?supplier_id=${row.supplier_id}&date=${row.date_sort}${locationQuery}" class="dropdown-item">
                                            <i class="ti ti-eye me-2"></i>View
                                        </a>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { data: 'date_group' },
                    { data: 'date_sort' },
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
                $('#filter-location').val(null).trigger('change');
                updateFilterButtonsVisibility();
                window.refreshTable();
            });

            // Double click anywhere on row to navigate to details page
            $('#supplierLedgerTable tbody').on('dblclick', 'tr:not(.group-header)', function (e) {
                if ($(e.target).closest('.dropdown').length || $(e.target).closest('button').length || $(e.target).closest('a').length) {
                    return;
                }
                const data = table.row(this).data();
                if (data) {
                    const locationVal = $('#filter-location').val() || '';
                    const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                    window.location.href = `${window.location.origin}/admin/ledgers/supplier/detail?supplier_id=${data.supplier_id}&date=${data.date_sort}${locationQuery}`;
                }
            });
        });
    </script>
@endsection
