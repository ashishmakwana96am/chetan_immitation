@extends('layouts.app')

@section('title', 'Sub Categories')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <style>
        tr.sortable-ghost { opacity: 0.4; background: #e7e3ff !important; }
        tr.sortable-chosen { background: #f0eeff !important; }
    </style>
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
                        @can('reorder sub categories')<th style="width:36px"></th>@endcan
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
    @can('reorder sub categories')
    <script src="{{ asset('assets/vendor/libs/sortablejs/sortable.js') }}"></script>
    @endcan
    <script>
        $(document).ready(function () {
            @can('reorder sub categories')
            const canReorder = true;
            @else
            const canReorder = false;
            @endcan

            const columns = [];
            if (canReorder) {
                columns.push({ data: null, orderable: false, searchable: false, width: '36px',
                    render: () => '<i class="ti ti-grip-vertical text-muted" style="cursor:grab;"></i>' });
            }
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
                ordering   : !canReorder,
                ajax       : { url: '{{ route('admin.sub-categories.data') }}', dataSrc: 'data' },
                columns    : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            @can('reorder sub categories')
            let sortableInstance = null;

            table.on('draw', function () {
                const tbody = document.querySelector('#subCategoriesTable tbody');
                if (tbody && typeof Sortable !== 'undefined') {
                    if (sortableInstance) {
                        sortableInstance.destroy();
                    }

                    sortableInstance = Sortable.create(tbody, {
                        handle      : '.ti-grip-vertical',
                        animation   : 150,
                        ghostClass  : 'sortable-ghost',
                        chosenClass : 'sortable-chosen',
                        onEnd: function () {
                            const orderData = [];
                            let i = 1;
                            $(tbody).find('tr').each(function () {
                                const rowData = table.row(this).data();
                                if (rowData) {
                                    $(this).find('td').eq(1).text(i);
                                    orderData.push({ id: rowData.id, sort_order: i });
                                    i++;
                                }
                            });
                            $.ajax({
                                url         : '{{ route('admin.sub-categories.reorder') }}',
                                type        : 'POST',
                                data        : JSON.stringify({ order: orderData }),
                                headers     : { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                                contentType : 'application/json',
                                success     : function (res) { if (res.status === 'success') toastr.success('Order saved.'); },
                                error       : function () {
                                    toastr.error('Failed to save order.');
                                    table.ajax.reload(null, false);
                                }
                            });
                        }
                    });
                }
            });
            @endcan

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
