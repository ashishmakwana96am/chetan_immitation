@extends('layouts.app')

@section('title', 'Products')

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
        <h4 class="fw-semibold mb-0">Products List</h4>
        @can('create products')
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Add Product
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="productsTable">
                <thead>
                    <tr>
                        @can('reorder products')<th style="width:36px"></th>@endcan
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Purchase Price</th>
                        <th>Sale Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    @can('reorder products')
    <script src="{{ asset('assets/vendor/libs/sortablejs/sortable.js') }}"></script>
    @endcan
    <script>
        $(document).ready(function () {
            @can('reorder products')
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
                { data: 'index',          width: '5%' },
                { data: 'image',          orderable: false },
                { data: 'name' },
                { data: 'sku' },
                { data: 'category' },
                { data: 'stock' },
                { data: 'purchase_price' },
                { data: 'sale_price' },
                { data: 'status',         orderable: false },
                { data: 'actions',        orderable: false },
            );

            const table = $('#productsTable').DataTable({
                responsive : false,
                ordering   : !canReorder,
                ajax       : { url: '{{ route('admin.products.data') }}', dataSrc: 'data' },
                columns    : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            @can('reorder products')
            let sortableInstance = null;

            table.on('draw', function () {
                const tbody = document.querySelector('#productsTable tbody');
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
                                url         : '{{ route('admin.products.reorder') }}',
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
        });
    </script>
@endsection
