@extends('layouts.app')

@section('title', 'Hero Banners')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Hero Banners List</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @can('create banners')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.banners.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Banner
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="bannersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image (1920 x 750)</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit banners') || auth()->user()->can('delete banners'))
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        $(document).ready(function () {
            const columns = [];
            columns.push(
                { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'image', orderable: false },
                { data: 'status', orderable: false },
                { data: 'created_by', orderable: false },
                { data: 'created_at' },
                @if(auth()->user()->can('edit banners') || auth()->user()->can('delete banners'))
                { data: 'actions', orderable: false },
                @endif
            );

            const table = $('#bannersTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : {
                    url: '{{ route('admin.banners.data') }}',
                    dataSrc: 'data',
                    cache: false
                },
                columns : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('change', '.banner-status-toggle', function () {
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
