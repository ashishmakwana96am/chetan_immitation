@extends('layouts.app')

@section('title', 'Discount Coupons')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/typography.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/katex.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Coupons List</h4>
        <div class="d-flex gap-2 align-items-center">
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
                        <label class="form-label fw-medium text-muted mb-1" for="filter-discount-type">Discount Type</label>
                        <select id="filter-discount-type" class="form-select">
                            <option value="">All Types</option>
                            <option value="percentage">Percentage</option>
                            <option value="flat">Flat / Fixed</option>
                        </select>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>
            @can('create coupons')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.coupons.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Coupon
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="couponsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Usage Limit</th>
                        <th>Used Count</th>
                        <th>Validity</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit coupons') || auth()->user()->can('delete coupons'))
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
    <script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
    <script>
        $(document).ready(function () {
            const table = $('#couponsTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : {
                    url: '{{ route('admin.coupons.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.status        = $('#filter-status').val();
                        d.discount_type = $('#filter-discount-type').val();
                    }
                },
                columns    : [
                    { data: 'index',      width: '5%' },
                    { data: 'name' },
                    { data: 'code' },
                    { data: 'discount' },
                    { data: 'usage_limit' },
                    { data: 'usage_count' },
                    { data: 'validity' },
                    { data: 'status',     orderable: false },
                    { data: 'created_at' },
                    @if(auth()->user()->can('edit coupons') || auth()->user()->can('delete coupons'))
                        { data: 'actions',    orderable: false },
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
                $('#filter-discount-type').val('');
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });

            $(document).on('change', '.coupon-status-toggle', function () {
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
