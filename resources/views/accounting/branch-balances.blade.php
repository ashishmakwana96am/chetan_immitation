@extends('layouts.app')

@section('title', 'Opening Balance')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #branchBalancesTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #branchBalancesTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #branchBalancesTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #branchBalancesTable tbody tr.group-header td .group-header-inner span {
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
            <h4 class="fw-semibold mb-0">Opening Balance</h4>
            <small class="text-muted">Overview of balance adjustments for all locations</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-common-modal="{{ route('admin.accounting.opening-balances.transfer') }}">
                <i class="ti ti-arrows-exchange me-1"></i> Transfer Balance
            </button>
            <button class="btn btn-primary" data-common-modal="{{ route('admin.accounting.opening-balances.create') }}">
                <i class="ti ti-plus me-1"></i> Add Opening Balance
            </button>
        </div>
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
                            <span class="text-muted small">Cash Balance</span>
                            <span class="fw-semibold text-success" id="cash-balance-{{ $loc->id }}">{{ format_price($loc->cash_balance) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Bank Balance</span>
                            <span class="fw-semibold text-primary" id="bank-balance-{{ $loc->id }}">{{ format_price($loc->bank_balance) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Standard Accounting Filters Card --}}
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
                    <label class="form-label">Location</label>
                    <select id="filter-location" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Balance Type</label>
                    <select id="filter-balance-type" class="form-select">
                        <option value="">All Balance Types</option>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
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
            <table class="table border-top" id="branchBalancesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>Branch</th>
                        <th>Balance Type</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Notes</th>
                        <th>Done By</th>
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

            // Initialize Flatpickr
            const startPicker = flatpickr("#filter-start-date", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d-m-Y",
                allowInput: false,
                maxDate: "today",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates[0]) {
                        endPicker.set('minDate', selectedDates[0]);
                    }
                }
            });

            const endPicker = flatpickr("#filter-end-date", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d-m-Y",
                allowInput: false,
                maxDate: "today",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates[0]) {
                        startPicker.set('maxDate', selectedDates[0]);
                    } else {
                        startPicker.set('maxDate', 'today');
                    }
                }
            });

            let isFiltered = false;

            function updateFilterButtonsVisibility() {
                const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                    return $(el).val() !== '';
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

            const table = $('#branchBalancesTable').DataTable({
                responsive : false,
                order      : [[10, 'desc']],
                orderFixed : { pre: [[10, 'desc']] },
                columnDefs : [
                    { targets: [9, 10], visible: false }
                ],
                rowGroup   : {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="9"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' transaction' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
                ajax        : {
                    url     : '{{ route('admin.accounting.opening-balances.data') }}',
                    dataSrc : function (json) {
                        if (json.branch_balances) {
                            $.each(json.branch_balances, function (locId, balances) {
                                $('#cash-balance-' + locId).text(balances.cash)
                                    .toggleClass('text-danger', balances.cash.includes('-'))
                                    .toggleClass('text-success', !balances.cash.includes('-'));
                                $('#bank-balance-' + locId).text(balances.bank)
                                    .toggleClass('text-danger', balances.bank.includes('-'))
                                    .toggleClass('text-primary', !balances.bank.includes('-'));
                            });
                        }
                        return json.data;
                    },
                    cache   : false,
                    data    : function(d) {
                        d.start_date   = $('#filter-start-date').val();
                        d.end_date     = $('#filter-end-date').val();
                        d.location_id  = $('#filter-location').val() || '';
                        d.balance_type = $('#filter-balance-type').val() || '';
                    }
                },
                columns     : [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'time' },
                    { data: 'branch_name' },
                    { data: 'balance_type', orderable: false },
                    { data: 'type', orderable: false },
                    { data: 'amount', className: 'fw-semibold' },
                    { data: 'balance_after', className: 'fw-semibold', render: function(d) { return d.includes('-') ? '<span class="text-danger">' + d + '</span>' : d; } },
                    { data: 'notes' },
                    { data: 'created_by' },
                    { data: 'date_group', visible: false },
                    { data: 'date_sort', visible: false },
                ],
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
                $('#filter-start-date').val('');
                $('#filter-end-date').val('');
                $('#filter-location').val('').trigger('change');
                $('#filter-balance-type').val('').trigger('change');
                if (startPicker) startPicker.clear();
                if (endPicker) endPicker.clear();
                updateFilterButtonsVisibility();
                isFiltered = false;
                window.refreshTable();
            });

        });
    </script>
@endsection
