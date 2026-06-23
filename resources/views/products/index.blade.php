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
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Barcode</th>
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

    <script>
        $(document).ready(function () {
            const columns = [];
            columns.push(
                { data: 'index',          width: '5%' },
                { data: 'image',          orderable: false },
                { data: 'name' },
                { data: 'sku' },
                { data: 'barcode',       orderable: false },
                { data: 'category' },
                { data: 'stock' },
                { data: 'purchase_price' },
                { data: 'sale_price' },
                { data: 'status',         orderable: false },
                { data: 'actions',        orderable: false },
            );

            const table = $('#productsTable').DataTable({
                responsive : false,
                ajax       : { url: '{{ route('admin.products.data') }}', dataSrc: 'data' },
                columns    : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            window.viewBarcode = function(barcode, productId) {
                const barcodeUrl = '{{ route('admin.products.barcode', ':id') }}'.replace(':id', productId);
                const modal = `
                    <div class="modal fade" id="barcodeModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Product Barcode</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <p class="fw-bold mb-3">${barcode}</p>
                                    <img src="${barcodeUrl}" alt="Barcode" class="img-fluid" style="max-height: 150px;">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#barcodeModal').remove();
                $('body').append(modal);
                const modalEl = new bootstrap.Modal(document.getElementById('barcodeModal'));
                modalEl.show();
                document.getElementById('barcodeModal').addEventListener('hidden.bs.modal', function () {
                    this.remove();
                });
            };
        });
    </script>
@endsection
