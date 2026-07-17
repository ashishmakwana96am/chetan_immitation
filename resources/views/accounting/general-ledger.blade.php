@extends('layouts.app')

@section('title', 'General Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        /* ─── Group header rows ─────────────────────────── */
        #generalLedgerTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #generalLedgerTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #generalLedgerTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
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
        .source-expense      { background-color: #fce4ec; color: #c62828; }
        .source-sale         { background-color: #f3e5f5; color: #6a1b9a; }
        .source-purchase     { background-color: #fff3e0; color: #e65100; }
        .source-purchase_bill{ background-color: #e0f7fa; color: #00695c; }

        /* ─── Summary cards ─────────────────────────────── */
        .gl-summary-card .card-body { padding: 1rem 1.25rem; }
        .gl-summary-card .label    { font-size: 0.78rem; color: #6c757d; font-weight: 500; margin-bottom: 4px; }
        .gl-summary-card .value    { font-size: 1.15rem; font-weight: 700; margin: 0; }

        .align-badge{
            display: flex; 
            justify-content: center; 
            align-items: center;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">General Ledger</h4>
            <small class="text-muted">Cash · Bank · Expenses · Sales · Purchases · Transfers</small>
        </div>
    </div>

    {{-- ─── Branch-wise Summary Cards ───────────────────────────────── --}}
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
                            <span class="text-muted small">Total In</span>
                            <span class="fw-semibold text-success" id="credit-{{ $loc->id }}">-</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Out</span>
                            <span class="fw-semibold text-danger" id="debit-{{ $loc->id }}">-</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Cash Balance</span>
                            <span class="fw-semibold text-warning" id="cash-balance-{{ $loc->id }}">-</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Bank Balance</span>
                            <span class="fw-semibold text-info" id="bank-balance-{{ $loc->id }}">-</span>
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
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Start Date</label>
                    <input type="text" id="filter-start-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">End Date</label>
                    <input type="text" id="filter-end-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                </div>
                @if(!$isRestricted)
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Location</label>
                    <select id="filter-location" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Source / Type</label>
                    <select id="filter-source" class="form-select">
                        <option value="all">All Sources</option>
                        <option value="expense">Expenses Only</option>
                        <option value="sale">Sales Only</option>
                        <option value="purchase">Purchases Only</option>
                        <option value="purchase_bill">Purchase Bills Only</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 d-none" id="filterActionButtons">
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
            <table class="table border-top" id="generalLedgerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Source</th>
                        @if(!$isRestricted)
                            <th>Location</th>
                        @endif
                        <th>Particulars / Details</th>
                        <th>Credit (+)</th>
                        <th>Debit (−)</th>
                        <th>Done By</th>
                        {{-- Hidden sort columns --}}
                        <th class="d-none">date_group</th>
                        <th class="d-none">date_sort</th>
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
        let flatpickrOpen = false;

        const startPicker = $('#filter-start-date').flatpickr({
            altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d',
            allowInput: false, maxDate: 'today',
            onOpen:  function () { flatpickrOpen = true; },
            onClose: function (d) {
                flatpickrOpen = false;
                if (d.length) endPicker.set('minDate', d[0]);
            }
        });

        const endPicker = $('#filter-end-date').flatpickr({
            altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d',
            allowInput: false, maxDate: 'today',
            onOpen:  function () { flatpickrOpen = true; },
            onClose: function (d) {
                flatpickrOpen = false;
                if (d.length) startPicker.set('maxDate', d[0]);
            }
        });

        let isFiltered = false;

        function updateFilterButtons() {
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

        $(document).on('input change', '#filterForm', function () {
            updateFilterButtons();
        });

        updateFilterButtons();

        const sourceIcons = {
            cash:         'ti ti-cash',
            bank:         'ti ti-building-bank',
            expense:      'ti ti-receipt',
            sale:         'ti ti-shopping-cart',
            purchase:     'ti ti-truck-delivery',
            purchase_bill:'ti ti-file-invoice',
        };
        const sourceLabels = {
            cash: 'Cash', bank: 'Bank', expense: 'Expense',
            sale: 'Sale', purchase: 'Purchase', purchase_bill: 'Purchase Bill',
        };

        function sourceBadge(type) {
            const icon  = sourceIcons[type]  || 'ti ti-circle';
            const label = sourceLabels[type] || type;
            return '<span class="source-badge source-' + type + '"><i class="' + icon + ' me-1"></i>' + label + '</span>';
        }

        @php
            $isAdmin = !$isRestricted;
        @endphp
        const hasLocation = {{ $isRestricted ? 'false' : 'true' }};
        const dateGroupCol = hasLocation ? 7 : 6;
        const dateSortCol  = hasLocation ? 8 : 7;

        const table = $('#generalLedgerTable').DataTable({
            responsive: false,
            order:      [[dateSortCol, 'desc']],
            orderFixed: { pre: [[dateSortCol, 'desc']] },
            columnDefs: [
                { targets: [dateGroupCol, dateSortCol], visible: false },
            ],
            rowGroup: {
                dataSrc: 'date_group',
                startRender: function (rows, group) {
                    const colspan = hasLocation ? 8 : 7;
                    return $('<tr class="group-header"/>')
                        .append(
                            '<td colspan="' + colspan + '">' +
                            '<div class="group-header-inner">' +
                            '<i class="ti ti-calendar-event"></i>' +
                            '<span>' + group + '</span>' +
                            '<span class="badge bg-label-primary">' + rows.count() + ' entr' + (rows.count() > 1 ? 'ies' : 'y') + '</span>' +
                            '</div></td>'
                        );
                }
            },
            ajax: {
                url:     '{{ route('admin.accounting.general-ledger.data') }}',
                cache:   false,
                data: function (d) {
                    d.start_date  = $('#filter-start-date').val();
                    d.end_date    = $('#filter-end-date').val();
                    d.location_id = $('#filter-location').val() || '';
                    d.source      = $('#filter-source').val() || 'all';
                },
                dataSrc: function (json) {
                    if (json.branch_summary) {
                        $.each(json.branch_summary, function (locId, s) {
                            $('#credit-' + locId).text(s.credit);
                            $('#debit-' + locId).text(s.debit);
                            $('#cash-balance-' + locId).text(s.cash_balance)
                                .toggleClass('text-danger', s.cash_balance.includes('-'))
                                .toggleClass('text-warning', !s.cash_balance.includes('-'));
                            $('#bank-balance-' + locId).text(s.bank_balance)
                                .toggleClass('text-danger', s.bank_balance.includes('-'))
                                .toggleClass('text-info', !s.bank_balance.includes('-'));
                        });
                    }
                    return json.data;
                }
            },
            columns: [
                { data: 'index', orderable: false, width: '4%' },
                { data: 'source_type', orderable: false,
                  render: function (data) { return sourceBadge(data); }
                },
                @if(!$isRestricted)
                { data: 'location' },
                @endif
                { data: 'particulars' },
                { data: 'credit', orderable: false, className: 'text-success fw-bold' },
                { data: 'debit', orderable: false, className: 'text-danger fw-bold' },
                { data: 'done_by' },
                { data: 'date_group', visible: false },
                { data: 'date_sort',  visible: false },
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
            $('#filter-location').val('').trigger('change');
            $('#filter-source').val('all').trigger('change');
            updateFilterButtons();
            window.refreshTable();
        });
    });
    </script>
@endsection
