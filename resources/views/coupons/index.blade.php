@extends('layouts.app')

@section('title', 'Discount Coupons')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/typography.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/katex.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Coupons List</h4>
        @can('create coupons')
            <button class="btn btn-primary" data-common-modal="{{ route('admin.coupons.create') }}">
                <i class="ti ti-plus me-1"></i> Add Coupon
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="couponsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Usage Limit</th>
                        <th>Validity</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit coupons') || auth()->user()->can('delete coupons'))
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
    <script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
    <script>
        $(document).ready(function () {
            const table = $('#couponsTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : { url: '{{ route('admin.coupons.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',      width: '5%' },
                    { data: 'name' },
                    { data: 'code' },
                    { data: 'discount' },
                    { data: 'usage_limit' },
                    { data: 'validity' },
                    { data: 'status',     orderable: false },
                    { data: 'created_at' },
                    @if(auth()->user()->can('edit coupons') || auth()->user()->can('delete coupons'))
                        { data: 'actions',    orderable: false },
                    @endif
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('change', '.coupon-status-toggle', function () {
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
