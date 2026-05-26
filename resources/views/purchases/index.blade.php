@extends('layouts.app')

@section('title', 'Purchase Invoices')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Purchase Invoices</h4>
        @can('create purchases')
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> New Purchase
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="purchasesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invoice No</th>
                        <th>Supplier</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Date</th>
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
            const table = $('#purchasesTable').DataTable({
                responsive : true,
                order      : [],
                ajax       : { url: '{{ route('admin.purchases.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',        width: '5%' },
                    { data: 'invoice_no' },
                    { data: 'supplier' },
                    { data: 'total_amount' },
                    { data: 'status',       orderable: false },
                    { data: 'created_by' },
                    { data: 'created_at' },
                    { data: 'actions',      orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };
        });
    </script>
@endsection
