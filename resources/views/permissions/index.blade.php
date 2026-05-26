@extends('layouts.app')

@section('title', 'Permissions')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Permissions List</h4>
        @can('create permissions')
            <button class="btn btn-primary" data-common-modal="{{ route('admin.permissions.create') }}">
                <i class="ti ti-plus me-1"></i> Add Permission
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="permissionsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Assigned Roles</th>
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
            const table = $('#permissionsTable').DataTable({
                responsive : true,
                order      : [],
                ajax       : { url: '{{ route('admin.permissions.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',      width: '5%' },
                    { data: 'name' },
                    { data: 'roles' },
                    { data: 'created_at' },
                    { data: 'actions', orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };
        });
    </script>
@endsection
