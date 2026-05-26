@extends('layouts.app')

@section('title', 'Suppliers')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Suppliers List</h4>
        @can('create suppliers')
            <button class="btn btn-primary" data-common-modal="{{ route('admin.suppliers.create') }}">
                <i class="ti ti-plus me-1"></i> Add Supplier
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="suppliersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Created By</th>
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
            const table = $('#suppliersTable').DataTable({
                responsive : true,
                order      : [],
                ajax       : { url: '{{ route('admin.suppliers.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',      width: '5%' },
                    { data: 'name' },
                    { data: 'phone' },
                    { data: 'address' },
                    { data: 'status',     orderable: false },
                    { data: 'created_by' },
                    { data: 'created_at' },
                    { data: 'actions',    orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('change', '.supplier-status-toggle', function () {
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
