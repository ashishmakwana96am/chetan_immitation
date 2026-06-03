@extends('layouts.app')

@section('title', 'Modules')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Modules List</h4>
        @can('create permissions')
            <button class="btn btn-primary" data-common-modal="{{ route('admin.modules.create') }}">
                <i class="ti ti-plus me-1"></i> Add Module
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="modulesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Parent Module</th>
                        <th>Icon</th>
                        <th>Route Name</th>
                        <th>Active Pattern</th>
                        <th>Permission</th>
                        <th>Sort Order</th>
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
            const table = $('#modulesTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : { url: '{{ route('admin.modules.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',      width: '5%' },
                    { data: 'name' },
                    { data: 'parent' },
                    { data: 'icon' },
                    { data: 'route' },
                    { data: 'active_pattern' },
                    { data: 'permission' },
                    { data: 'sort_order' },
                    @if(auth()->user()->can('edit permissions') || auth()->user()->can('delete permissions'))
                        { data: 'actions', orderable: false },
                    @endif
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };
        });
    </script>
@endsection
