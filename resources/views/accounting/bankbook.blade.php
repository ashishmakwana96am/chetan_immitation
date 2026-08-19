@extends('layouts.app')

@section('title', 'Bank Book')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #bankBookTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #bankBookTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #bankBookTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #bankBookTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }

        /* ─── Source badges ─────────────────────────────── */
        .source-badge {
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }
        .source-cash         { background-color: #e8f5e9; color: #2e7d32; }
        .source-bank         { background-color: #e3f2fd; color: #1565c0; }
        .source-opening_balance { background-color: #fff8e1; color: #f57f17; }
        .source-expense      { background-color: #fce4ec; color: #c62828; }
        .source-sale         { background-color: #f3e5f5; color: #6a1b9a; }
        .source-purchase     { background-color: #fff3e0; color: #e65100; }
        .source-purchase_bill    { background-color: #e0f7fa; color: #00695c; }
        .source-balance_transfer { background-color: #ede7f6; color: #4527a0; }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Bank Book</h4>
    </div>

    <div class="row g-4 mb-4">
        @foreach($locations as $loc)
            <div class="col-md-6 col-lg-4 branch-card-col" data-location-id="{{ $loc->id }}">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="badge rounded bg-label-primary p-2 me-2">
                                <i class="ti ti-building ti-sm text-primary"></i>
                            </div>
                            <h5 class="card-title mb-0 fw-bold text-truncate" style="max-width: 80%;" title="{{ $loc->name }}">{{ $loc->name }}</h5>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Bank In</span>
                            <span class="fw-semibold text-success" id="credit-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Bank Out</span>
                            <span class="fw-semibold text-danger" id="debit-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Bank Balance</span>
                            <span class="fw-semibold text-primary" id="balance-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Credit Balance</span>
                            <span class="fw-semibold text-info" id="customer-balance-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
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
                @if(auth()->user()->hasRole('super-admin'))
                <div class="col-md-3">
                    <label class="form-label">Location</label>
                    <select id="filter-location" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Source / Type</label>
                    <select id="filter-source" class="form-select">
                        <option value="all">All Sources</option>
                        <option value="opening_balance">Opening Balance Only</option>
                        <option value="expense">Expenses Only</option>
                        <option value="sale">Sales Only</option>
                        <option value="purchase">Purchases Only</option>
                        <option value="purchase_bill">Purchase Bills Only</option>
                        <option value="balance_transfer">Balance Transfers Only</option>
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

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="bankBookTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Source</th>
                        @if(auth()->user()->hasRole('super-admin'))
                            <th>Location</th>
                        @endif
                        <th>Particulars / Details</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Done By</th>
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
            // Track if any flatpickr calendar is open — prevent Bootstrap dropdown from closing
            let flatpickrOpen = false;

            // Initialize Flatpickr for date filters
            const startPicker = $('#filter-start-date').flatpickr({
                altInput   : true,
                altFormat  : 'd-m-Y',
                dateFormat : 'Y-m-d',
                allowInput : false,
                maxDate    : 'today',
                onOpen     : function () { flatpickrOpen = true; },
                onClose    : function (selectedDates) {
                    flatpickrOpen = false;
                    if (selectedDates.length) {
                        endPicker.set('minDate', selectedDates[0]);
                    }
                }
            });

            const endPicker = $('#filter-end-date').flatpickr({
                altInput   : true,
                altFormat  : 'd-m-Y',
                dateFormat : 'Y-m-d',
                allowInput : false,
                maxDate    : 'today',
                onOpen     : function () { flatpickrOpen = true; },
                onClose    : function (selectedDates) {
                    flatpickrOpen = false;
                    if (selectedDates.length) {
                        startPicker.set('maxDate', selectedDates[0]);
                    }
                }
            });

            let isFiltered = false;

            function updateFilterButtonsVisibility() {
                const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                    const val = $(el).val();
                    return val !== '' && val !== null && val !== 'all';
                });
                $('#filterActionButtons').toggleClass('d-none', !hasValue);
                if (!hasValue && isFiltered) {
                    isFiltered = false;
                    window.refreshTable();
                }
            }

            $(document).on('input change select2:select select2:clear', '#filterForm input, #filterForm select', function () {
                updateFilterButtonsVisibility();
            });

            updateFilterButtonsVisibility();

        const sourceIcons = {
            cash:             'ti ti-cash',
            bank:             'ti ti-building-bank',
            opening_balance:  'ti ti-scale',
            expense:          'ti ti-receipt',
            sale:             'ti ti-shopping-cart',
            purchase:         'ti ti-truck-delivery',
            purchase_bill:    'ti ti-file-invoice',
            balance_transfer: 'ti ti-arrows-exchange',
        };
        const sourceLabels = {
            cash: 'Cash', bank: 'Bank', opening_balance: 'Opening Balance', expense: 'Expense',
            sale: 'Sale', purchase: 'Purchase', purchase_bill: 'Purchase Bill', balance_transfer: 'Balance Transfer',
        };

        function sourceBadge(type) {
            const icon  = sourceIcons[type]  || 'ti ti-circle';
            const label = sourceLabels[type] || type;
            return '<span class="source-badge source-' + type + '"><i class="' + icon + ' me-1"></i>' + label + '</span>';
        }

        const table = $('#bankBookTable').DataTable({
            responsive : false,
            order      : [[{{ auth()->user()->hasRole('super-admin') ? 9 : 8 }}, 'desc']],
            columnDefs : [
                { targets: {{ auth()->user()->hasRole('super-admin') ? '[8, 9]' : '[7, 8]' }}, visible: false }
            ],
            rowGroup   : {
                dataSrc: 'date_group',
                startRender: function (rows, group) {
                    return $('<tr class="group-header"/>')
                        .append('<td colspan="{{ auth()->user()->hasRole('super-admin') ? 8 : 7 }}"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' transaction' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                }
            },
            ajax        : {
                url     : '{{ route('admin.accounting.bankbook.data') }}',
                dataSrc : function (json) {
                    if (json.branch_summary) {
                        $.each(json.branch_summary, function (locId, s) {
                            $('#credit-' + locId).text(s.credit);
                            $('#debit-' + locId).text(s.debit);
                            $('#balance-' + locId).text(s.balance)
                                .toggleClass('text-danger', s.balance.includes('-'))
                                .toggleClass('text-primary', !s.balance.includes('-'));
                            $('#customer-balance-' + locId).text(s.customer_balance);
                        });
                    }
                    return json.data;
                },
                cache   : false,
                data    : function(d) {
                    d.start_date  = $('#filter-start-date').val();
                    d.end_date    = $('#filter-end-date').val();
                    d.location_id = $('#filter-location').val() || '';
                }
            },
            columns     : [
                { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'source_type', render: function (data) { return sourceBadge(data); } },
                @if(auth()->user()->hasRole('super-admin'))
                    { data: 'location' },
                @endif
                { data: 'particulars' },
                { data: 'type', render: function (data, type, row) { return type === 'sort' ? data : row.type_badge; } },
                { data: 'amount', className: 'fw-bold text-nowrap', render: function (data, type, row) {
                    if (type === 'sort' || type === 'type') {
                        return row.amount_raw !== undefined ? row.amount_raw : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                    }
                    return row.is_credit ? '<span class="text-success">' + data + '</span>' : '<span class="text-danger">' + data + '</span>';
                } },
                { data: 'balance_after', className: 'fw-bold text-nowrap', render: function(d, type, row) {
                    if (type === 'sort' || type === 'type') {
                        return row.balance_after_raw !== undefined ? row.balance_after_raw : (parseFloat(String(d).replace(/[^0-9.-]+/g, '')) || 0);
                    }
                    return d.includes('-') ? '<span class="text-danger">' + d + '</span>' : d;
                } },
                { data: 'done_by' },
                { data: 'date_group', visible: false },
                { data: 'date_sort', visible: false },
            ],
                drawCallback: function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = api.page.info().start + i + 1;
                    });
                }
            });

            function updateCardVisibility() {
                const selectedLocId = $('#filter-location').val();
                if (selectedLocId) {
                    $('.branch-card-col').hide();
                    $('.branch-card-col[data-location-id="' + selectedLocId + '"]').show();
                } else {
                    $('.branch-card-col').show();
                }
            }

            updateCardVisibility();

            window.refreshTable = function () {
                window.showAjaxLoader && window.showAjaxLoader();
                updateCardVisibility();
                table.ajax.reload(function () {
                    window.hideAjaxLoader && window.hideAjaxLoader();
                }, false);
            };

            // Apply Filters
            $(document).on('click', '#applyFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = true;
                window.refreshTable();
            });

            // Clear Filters
            $(document).on('click', '#clearFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = false;
                startPicker.clear();
                endPicker.clear();
                $('#filter-location').val('').trigger('change');
                $('#filter-source').val('all').trigger('change');
                updateFilterButtonsVisibility();
                window.refreshTable();
            });
        });
    </script>
@endsection
