@extends('layouts.app')

@section('title', 'Customers')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Customers List</h4>
        @can('create customers')
            <button class="btn btn-primary" data-common-modal="{{ route('admin.customers.create') }}">
                <i class="ti ti-plus me-1"></i> Add Customer
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="customersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created Date</th>
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
            const table = $('#customersTable').DataTable({
                responsive : true,
                order      : [],
                ajax       : { url: '{{ route('admin.customers.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',      width: '5%' },
                    { data: 'name' },
                    { data: 'phone' },
                    { data: 'email' },
                    { data: 'status',     orderable: false },
                    { data: 'created_at' },
                    { data: 'actions',    orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('change', '.customer-status-toggle', function () {
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
