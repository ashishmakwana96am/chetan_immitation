@extends('layouts.app')

@section('title', 'Roles')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Roles List</h4>
        @can('create roles')
            <button class="btn btn-primary" data-common-modal="{{ route('admin.roles.create') }}" data-size="modal-xl">
                <i class="ti ti-plus me-1"></i> Add Role
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="rolesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Permissions</th>
                        <th>Users</th>
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
            const table = $('#rolesTable').DataTable({
                responsive : true,
                order      : [],
                ajax       : { url: '{{ route('admin.roles.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',       width: '5%' },
                    { data: 'name' },
                    { data: 'permissions' },
                    { data: 'users' },
                    { data: 'created_at' },
                    { data: 'actions', orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            function syncSelectAll() {
                var total   = $('.permission-checkbox').length;
                var checked = $('.permission-checkbox:checked').length;
                $('#selectAllPermissions').prop('checked', total > 0 && total === checked);
            }

            function syncModuleSelectAll(module) {
                var total   = $('.permission-checkbox[data-module="' + module + '"]').length;
                var checked = $('.permission-checkbox[data-module="' + module + '"]:checked').length;
                $('.module-select-all[data-module="' + module + '"]').prop('checked', total === checked);
            }

            $(document).on('change', '#selectAllPermissions', function () {
                var checked = $(this).is(':checked');
                $('.permission-checkbox, .module-select-all').prop('checked', checked);
            });

            $(document).on('change', '.module-select-all', function () {
                var module  = $(this).attr('data-module');
                var checked = $(this).is(':checked');
                $('.permission-checkbox[data-module="' + module + '"]').prop('checked', checked);
                syncSelectAll();
            });

            $(document).on('change', '.permission-checkbox', function () {
                var module = $(this).attr('data-module');
                syncModuleSelectAll(module);
                syncSelectAll();
            });
        });
    </script>
@endsection
