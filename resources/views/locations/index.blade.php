@extends('layouts.app')

@section('title', 'Locations')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Locations List</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 280px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-status">Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-default">Default</label>
                        <select id="filter-default" class="form-select">
                            <option value="">All</option>
                            <option value="1">Default</option>
                            <option value="0">Non-Default</option>
                        </select>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>
            @can('create locations')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.locations.create') }}"><i class="ti ti-plus me-1"></i> Add Location</button>
            @endcan
        </div>
    </div>

    @if(auth()->user()->hasRole('super-admin'))
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-muted d-block mb-1">Total Cash Balance</span>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-0 me-2 text-success" id="summaryTotalCash">₹0.00</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-success p-2 rounded">
                                <i class="ti ti-cash ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-muted d-block mb-1">Total Bank Balance</span>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-0 me-2 text-primary" id="summaryTotalBank">₹0.00</h4>
                                </div>
                            </div>
                            <span class="badge bg-label-primary p-2 rounded">
                                <i class="ti ti-building-bank ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="locationsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>GST Number</th>
                        @if(auth()->user()->hasRole('super-admin'))
                            <th>Cash Balance</th>
                            <th>Bank Balance</th>
                        @endif
                        <th>Status</th>
                        @if(auth()->user()->can('edit locations') || auth()->user()->can('delete locations') || (auth()->user()->hasRole('super-admin') && auth()->user()->can('manage branch balances')))
                            <th>Actions</th>
                        @endif
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

            const table = $('#locationsTable').DataTable({
                responsive : false,
                order       : [],
                ajax        : {
                    url     : '{{ route('admin.locations.data') }}',
                    dataSrc : function (json) {
                        if (json.summary) {
                            $('#summaryTotalCash').text(json.summary.total_cash);
                            $('#summaryTotalBank').text(json.summary.total_bank);
                        }
                        return json.data;
                    },
                    cache   : false,
                    data    : function(d) {
                        d.status     = $('#filter-status').val();
                        d.is_default = $('#filter-default').val();
                    }
                },
                columns     : [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'name' },
                    { data: 'address' },
                    { data: 'phone' },
                    { data: 'gst_number' },
                    @if(auth()->user()->hasRole('super-admin'))
                        { data: 'cash_balance' },
                        { data: 'bank_balance' },
                    @endif
                    { data: 'status',  orderable: false },
                    @if(auth()->user()->can('edit locations') || auth()->user()->can('delete locations') || (auth()->user()->hasRole('super-admin') && auth()->user()->can('manage branch balances')))
                        { data: 'actions', orderable: false },
                    @endif
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            // Apply Filter
            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });

            // Clear Filter
            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-status').val('');
                $('#filter-default').val('');
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });

            $(document).on('change', '.location-status-toggle', function () {
                const toggle = $(this);
                const url    = toggle.attr('data-url');

                $.ajax({
                    url  : url,
                    type : 'PATCH',
                    data : { _token: $('meta[name="csrf-token"]').attr('content') },
                    success : function (res) {
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            window.refreshTable();
                        }
                    },
                    error : function () {
                        toggle.prop('checked', !toggle.prop('checked'));
                        toastr.error('Something went wrong. Please try again.');
                    }
                });
            });

        });
    </script>
@endsection
