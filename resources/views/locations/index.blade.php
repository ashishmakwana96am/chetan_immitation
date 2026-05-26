@extends('layouts.app')

@section('title', 'Locations')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Locations List</h4>
        @can('create locations')
            <button class="btn btn-primary"
                data-common-modal="{{ route('admin.locations.create') }}">
                <i class="ti ti-plus me-1"></i> Add Location
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="locationsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Address</th>
                        <th>Default</th>
                        <th>Status</th>
                        <th>Created By</th>
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

            const table = $('#locationsTable').DataTable({
                responsive  : true,
                order       : [],
                ajax        : {
                    url     : '{{ route('admin.locations.data') }}',
                    dataSrc : 'data',
                },
                columns     : [
                    { data: 'index',      width: '5%' },
                    { data: 'name' },
                    { data: 'slug' },
                    { data: 'address' },
                    { data: 'is_default' },
                    { data: 'status',  orderable: false },
                    { data: 'created_by' },
                    { data: 'actions', orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('change', '.location-status-toggle', function () {
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
