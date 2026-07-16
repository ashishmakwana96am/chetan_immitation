@extends('layouts.app')

@section('title', 'Cash Book')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #cashBookTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #cashBookTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #cashBookTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #cashBookTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Cash Book</h4>
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
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted d-block mb-1">Total Cash In (Receipts)</span>
                            <h4 class="mb-0 mt-1 text-success" id="summaryTotalCredit">₹0.00</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-trending-up ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted d-block mb-1">Total Cash Out (Payments)</span>
                            <h4 class="mb-0 mt-1 text-danger" id="summaryTotalDebit">₹0.00</h4>
                        </div>
                        <span class="badge bg-label-danger rounded p-2"><i class="ti ti-trending-down ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted d-block mb-1">Cash Balance</span>
                            <h4 class="mb-0 mt-1 text-primary" id="summaryCurrentBalance">₹0.00</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-cash ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="cashBookTable">
                <thead>
                    <tr>
                        <th>#</th>
                        @if(auth()->user()->hasRole('super-admin'))
                            <th>Location</th>
                        @endif
                        <th>Particulars / Details</th>
                        <th>Credit (+)</th>
                        <th>Debit (-)</th>
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

            const table = $('#cashBookTable').DataTable({
                responsive : false,
                order      : [[{{ auth()->user()->hasRole('super-admin') ? 8 : 7 }}, 'desc']],
                orderFixed : { pre: [[{{ auth()->user()->hasRole('super-admin') ? 8 : 7 }}, 'desc']] },
                columnDefs : [
                    { targets: {{ auth()->user()->hasRole('super-admin') ? '[7, 8]' : '[6, 7]' }}, visible: false }
                ],
                rowGroup   : {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="{{ auth()->user()->hasRole('super-admin') ? 7 : 6 }}"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' transaction' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
                ajax        : {
                    url     : '{{ route('admin.accounting.cashbook.data') }}',
                    dataSrc : function (json) {
                        if (json.summary) {
                            $('#summaryTotalCredit').text(json.summary.total_credit);
                            $('#summaryTotalDebit').text(json.summary.total_debit);
                            $('#summaryCurrentBalance').text(json.summary.current_balance)
                                .toggleClass('text-danger', json.summary.current_balance.includes('-'))
                                .toggleClass('text-primary', !json.summary.current_balance.includes('-'));
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
                    @if(auth()->user()->hasRole('super-admin'))
                        { data: 'location' },
                    @endif
                    { data: 'particulars' },
                    { data: 'credit', className: 'text-success fw-bold' },
                    { data: 'debit', className: 'text-danger fw-bold' },
                    { data: 'balance_after', className: 'fw-bold', render: function(d) { return d.includes('-') ? '<span class="text-danger">' + d + '</span>' : d; } },
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

            window.refreshTable = function () {
                window.showAjaxLoader && window.showAjaxLoader();
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
                updateFilterButtonsVisibility();
                window.refreshTable();
            });
        });
    </script>
@endsection
