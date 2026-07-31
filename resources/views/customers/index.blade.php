@extends('layouts.app')

@section('title', 'Customers')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Customers List</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 300px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
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
                        <label class="form-label fw-medium text-muted mb-1">Date Range</label>
                        <div class="w-100">
                            <input type="date" id="filter-start-date" class="form-control mb-2" max="{{ now()->format('Y-m-d') }}" />
                            <div class="text-center text-muted small mb-2">to</div>
                            <input type="date" id="filter-end-date" class="form-control" max="{{ now()->format('Y-m-d') }}" />
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>
            @can('create customers')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.customers.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Customer
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="customersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        @if($isSuperAdmin)
                            <th>Branch</th>
                        @endif
                        <th>GST No</th>
                        <th>State</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit customers') || auth()->user()->can('delete customers'))
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
            const table = $('#customersTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : {
                    url: '{{ route('admin.customers.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.status     = $('#filter-status').val();
                        d.start_date = $('#filter-start-date').val();
                        d.end_date   = $('#filter-end-date').val();
                    }
                },
                columns    : [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'name', render: function(data, type, row) {
                        if (type === 'display') {
                            var badge = row.is_credit_customer
                                ? ' <span class="badge bg-label-info ms-1">Credit</span>'
                                : '';
                            return data + badge;
                        }
                        return data;
                    }},
                    { data: 'phone' },
                    { data: 'email' },
                    @if($isSuperAdmin)
                        { data: 'branch' },
                    @endif
                    { data: 'gst_no' },
                    { data: 'state' },
                    { data: 'status',     orderable: false },
                    { data: 'created_at' },
                    @if(auth()->user()->can('edit customers') || auth()->user()->can('delete customers'))
                        { data: 'actions',    orderable: false },
                    @endif
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            // Constrain date range
            $('#filter-start-date').on('change', function () {
                const val = $(this).val();
                $('#filter-end-date').attr('min', val);
            });
            $('#filter-end-date').on('change', function () {
                const val = $(this).val();
                $('#filter-start-date').attr('max', val);
            });

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
                $('#filter-start-date').val('').attr('max', '{{ now()->format('Y-m-d') }}');
                $('#filter-end-date').val('').removeAttr('min');
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });

            $(document).on('change', '.customer-status-toggle', function () {
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

            $(document).on('change', '.customer-credit-toggle', function () {
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
                    error : function (xhr) {
                        toggle.prop('checked', !toggle.prop('checked'));
                        const msg = xhr.responseJSON?.message;
                        toastr.error(typeof msg === 'string' ? msg : 'Something went wrong. Please try again.');
                    }
                });
            });
        });
    </script>
@endsection
