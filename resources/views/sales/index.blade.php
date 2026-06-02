@extends('layouts.app')

@section('title', 'Sales')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Sales</h4>
        @can('create sales')
            <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> New Sale
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="ordersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sale No</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Method</th>
                        <th>Date</th>
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
            const table = $('#ordersTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : { url: '{{ route('admin.sales.data') }}', dataSrc: 'data', cache: false },
                columns    : [
                    { data: 'index',          width: '5%' },
                    { data: 'order_no' },
                    { data: 'customer' },
                    { data: 'location' },
                    { data: 'final_amount' },
                    { data: 'status',         orderable: false },
                    { data: 'payment_status', orderable: false },
                    { data: 'payment_method' },
                    { data: 'created_at' },
                    { data: 'actions',        orderable: false },
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '.change-sale-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const currentStatus = $(this).data('current');

                Swal.fire({
                    title: 'Update Sale Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label for="swal-sale-status" class="form-label fw-semibold mb-2">Select Sale Status</label>
                            <select id="swal-sale-status" class="form-select form-select-lg">
                                <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="approve" ${currentStatus === 'approve' ? 'selected' : ''}>Approve</option>
                                <option value="decline" ${currentStatus === 'decline' ? 'selected' : ''}>Decline</option>
                            </select>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Update',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        return document.getElementById('swal-sale-status').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                status: result.value
                            },
                            success: function (res) {
                                if (res.status === 'success') {
                                    toastr.success(res.message);
                                    window.refreshTable();
                                }
                            },
                            error: function (xhr) {
                                const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                                toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.change-payment-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const currentPaymentStatus = $(this).data('current');

                Swal.fire({
                    title: 'Update Payment Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label for="swal-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                            <select id="swal-payment-status" class="form-select form-select-lg">
                                <option value="pending" ${currentPaymentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="paid" ${currentPaymentStatus === 'paid' ? 'selected' : ''}>Paid</option>
                            </select>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Update',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        return document.getElementById('swal-payment-status').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                payment_status: result.value
                            },
                            success: function (res) {
                                if (res.status === 'success') {
                                    toastr.success(res.message);
                                    window.refreshTable();
                                }
                            },
                            error: function (xhr) {
                                const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                                toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
