@extends('layouts.app')

@section('title', 'Products')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
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
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Category</th>
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
    <script>
        $(document).ready(function () {
            const table = $('#productsTable').DataTable({
                responsive : true,
                order      : [],
                ajax       : { url: '{{ route('admin.products.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',          width: '5%' },
                    { data: 'image',          orderable: false },
                    { data: 'name' },
                    { data: 'sku' },
                    { data: 'category' },
                    { data: 'purchase_price' },
                    { data: 'sale_price' },
                    { data: 'status',         orderable: false },
                    { data: 'actions',        orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };
        });
    </script>
@endsection
