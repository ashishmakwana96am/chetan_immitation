@extends('layouts.app')

@section('title', 'Sales')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Sales</h4>
        @can('create sales')
            <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> New Sale
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="ordersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sale No</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Method</th>
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
            const table = $('#ordersTable').DataTable({
                responsive : true,
                order      : [],
                ajax       : { url: '{{ route('admin.sales.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',          width: '5%' },
                    { data: 'order_no' },
                    { data: 'customer' },
                    { data: 'location' },
                    { data: 'final_amount' },
                    { data: 'status',         orderable: false },
                    { data: 'payment_status', orderable: false },
                    { data: 'payment_method' },
                    { data: 'created_at' },
                    { data: 'actions',        orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };
        });
    </script>
@endsection
