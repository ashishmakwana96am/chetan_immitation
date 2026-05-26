@extends('layouts.app')

@section('title', 'Users')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')

    <!-- Stats Cards -->
    <div class="row g-4 mb-4" id="userStatsCards">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Users</span>
                            <h4 class="mb-0 mt-1" id="statTotal">-</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-users ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Active Users</span>
                            <h4 class="mb-0 mt-1" id="statActive">-</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-user-check ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Inactive Users</span>
                            <h4 class="mb-0 mt-1" id="statInactive">-</h4>
                        </div>
                        <span class="badge bg-label-danger rounded p-2"><i class="ti ti-user-off ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Admins</span>
                            <h4 class="mb-0 mt-1" id="statAdmins">-</h4>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ti ti-shield ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Users List</h5>
            @can('create users')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.users.create') }}" data-size="modal-lg">
                    <i class="ti ti-plus me-1"></i> Add User
                </button>
            @endcan
        </div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="usersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
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
            const table = $('#usersTable').DataTable({
                responsive : true,
                order      : [],
                ajax       : {
                    url     : '{{ route('admin.users.data') }}',
                    dataSrc : function (res) {
                        // Update stats cards from response
                        const users    = res.data;
                        const active   = users.filter(u => u.raw_status === 'active').length;
                        const inactive = users.filter(u => u.raw_status === 'inactive').length;
                        const admins   = users.filter(u => u.raw_type === 'admin').length;
                        $('#statTotal').text(users.length);
                        $('#statActive').text(active);
                        $('#statInactive').text(inactive);
                        $('#statAdmins').text(admins);
                        return users;
                    }
                },
                columns    : [
                    { data: 'index',   width: '5%' },
                    { data: 'name' },
                    { data: 'email' },
                    { data: 'phone' },
                    { data: 'role' },
                    { data: 'status',  orderable: false },
                    { data: 'actions', orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('change', '.user-status-toggle', function () {
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
