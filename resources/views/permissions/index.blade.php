@extends('layouts.app')

@section('title', 'Permissions')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Permissions List</h4>
        <div class="d-flex gap-2 align-items-center">
            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 280px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-module">Module</label>
                        <select id="filter-module" class="form-select">
                            <option value="">All Modules</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}">{{ $module }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>
            @can('create permissions')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.permissions.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Permission
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="permissionsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Module</th>
                        <th>Assigned Roles</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit permissions') || auth()->user()->can('delete permissions'))
                            <th>Actions</th>
                        @endif
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
                responsive : false,
                order      : [],
                ajax       : {
                    url: '{{ route('admin.permissions.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.module = $('#filter-module').val();
                    }
                },
                columns    : [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'name' },
                    { data: 'module' },
                    { data: 'roles' },
                    { data: 'created_at' },
                    @if(auth()->user()->can('edit permissions') || auth()->user()->can('delete permissions'))
                        { data: 'actions', orderable: false },
                    @endif
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            // Apply Filter
            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });

            // Clear Filter
            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-module').val('');
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });
        });
    </script>
@endsection
