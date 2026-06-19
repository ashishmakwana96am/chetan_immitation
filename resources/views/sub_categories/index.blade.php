@extends('layouts.app')

@section('title', 'Sub Categories')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Sub Categories List</h4>
        @can('create sub categories')
            <button class="btn btn-primary" data-common-modal="{{ route('admin.sub-categories.create') }}">
                <i class="ti ti-plus me-1"></i> Add Sub Category
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="subCategoriesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit sub categories') || auth()->user()->can('delete sub categories'))
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
            const columns = [];
            columns.push(
                { data: 'index',      width: '5%' },
                { data: 'name' },
                { data: 'category' },
                { data: 'slug' },
                { data: 'status',     orderable: false },
                { data: 'created_at' },
                @if(auth()->user()->can('edit sub categories') || auth()->user()->can('delete sub categories'))
                { data: 'actions', orderable: false },
                @endif
            );

            const table = $('#subCategoriesTable').DataTable({
                responsive : false,
                ajax       : { url: '{{ route('admin.sub-categories.data') }}', dataSrc: 'data' },
                columns    : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('change', '.sub-category-status-toggle', function () {
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
