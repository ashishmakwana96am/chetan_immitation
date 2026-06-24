@extends('layouts.app')

@section('title', 'Categories')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    <style>
        tr.dt-drag-handle td:first-child { cursor: grab; color: #adb5bd; }
        tr.dt-drag-handle td:first-child:hover { color: #566a7f; }
        tr.sortable-ghost { opacity: 0.4; background: #e7e3ff !important; }
        tr.sortable-chosen { background: #f0eeff !important; }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Categories List</h4>
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
                        <label class="form-label fw-medium text-muted mb-1" for="filter-featured">Featured</label>
                        <select id="filter-featured" class="form-select">
                            <option value="">All</option>
                            <option value="1">Featured</option>
                            <option value="0">Not Featured</option>
                        </select>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>
            @can('create categories')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.categories.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Category
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="categoriesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Featured</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit categories') || auth()->user()->can('delete categories'))
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        $(document).ready(function () {
            const columns = [];
            columns.push(
                { data: 'index',      width: '5%' },
                { data: 'image',      orderable: false },
                { data: 'name' },
                { data: 'slug' },
                { data: 'is_featured', orderable: false },
                { data: 'status',     orderable: false },
                { data: 'created_at' },
                @if(auth()->user()->can('edit categories') || auth()->user()->can('delete categories'))
                { data: 'actions', orderable: false },
                @endif
            );

            const table = $('#categoriesTable').DataTable({
                responsive : false,
                ajax       : {
                    url: '{{ route('admin.categories.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.status      = $('#filter-status').val();
                        d.is_featured = $('#filter-featured').val();
                    }
                },
                columns    : columns,
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
                $('#filter-featured').val('');
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });



            $(document).on('change', '.category-status-toggle', function () {
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

            $(document).on('change', '.category-featured-toggle', function () {
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
