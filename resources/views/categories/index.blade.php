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
        @can('create categories')
            <button class="btn btn-primary" data-common-modal="{{ route('admin.categories.create') }}">
                <i class="ti ti-plus me-1"></i> Add Category
            </button>
        @endcan
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
                ajax       : { url: '{{ route('admin.categories.data') }}', dataSrc: 'data' },
                columns    : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };



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
