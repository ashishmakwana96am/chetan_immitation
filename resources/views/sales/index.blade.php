@extends('layouts.app')

@section('title', 'Sales')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #ordersTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #ordersTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #ordersTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #ordersTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
    </style>
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
                        <!-- <th>Location</th> -->
                        <th>Source</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Method</th>
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
                    // { data: 'location' },
                    { data: 'source' },
                    { data: 'final_amount' },
                    { data: 'status',         orderable: false },
                    { data: 'payment_status', orderable: false },
                    { data: 'payment_method' },
                    { data: 'actions',        orderable: false },
                    { data: 'date_group',     visible: false },
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="10"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' sale' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '.change-sale-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const currentStatus = parseInt($(this).data('current'));
                const selectDisabled = [5, 6].includes(currentStatus) ? 'disabled' : '';

                const opt1 = (currentStatus !== 1) ? 'disabled' : '';
                const opt2 = (![1, 2].includes(currentStatus)) ? 'disabled' : '';
                const opt3 = (![2, 3].includes(currentStatus)) ? 'disabled' : '';
                const opt4 = (![3, 4].includes(currentStatus)) ? 'disabled' : '';
                const opt5 = (![4, 5].includes(currentStatus)) ? 'disabled' : '';
                const opt6 = ([5, 6].includes(currentStatus)) ? 'disabled' : '';

                Swal.fire({
                    title: 'Update Sale Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label for="swal-sale-status" class="form-label fw-semibold mb-2">Select Sale Status</label>
                            <select id="swal-sale-status" class="form-select form-select-lg" ${selectDisabled}>
                                <option value="1" ${currentStatus == 1 ? 'selected' : ''} ${opt1}>Pending</option>
                                <option value="2" ${currentStatus == 2 ? 'selected' : ''} ${opt2}>Approve</option>
                                <option value="3" ${currentStatus == 3 ? 'selected' : ''} ${opt3}>Shipped</option>
                                <option value="4" ${currentStatus == 4 ? 'selected' : ''} ${opt4}>Out for delivery</option>
                                <option value="5" ${currentStatus == 5 ? 'selected' : ''} ${opt5}>Delivered</option>
                                <option value="6" ${currentStatus == 6 ? 'selected' : ''} ${opt6}>Decline</option>
                            </select>
                        </div>
                        <div class="mb-3 text-start" id="swal-reason-wrap" style="display:none;">
                            <label for="swal-cancel-reason" class="form-label fw-semibold mb-2">Cancellation Reason <span class="text-danger">*</span></label>
                            <textarea id="swal-cancel-reason" class="form-control" rows="3" maxlength="500" placeholder="Enter the reason for cancellation..."></textarea>
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
                    allowOutsideClick: () => !Swal.isLoading(),
                    didOpen: () => {
                        document.getElementById('swal-sale-status').addEventListener('change', function () {
                            const reasonWrap = document.getElementById('swal-reason-wrap');
                            reasonWrap.style.display = (this.value == '6') ? 'block' : 'none';
                        });
                        if (currentStatus == 6) {
                            document.getElementById('swal-reason-wrap').style.display = 'block';
                        }
                    },
                    preConfirm: () => {
                        const status = document.getElementById('swal-sale-status').value;
                        const reason = document.getElementById('swal-cancel-reason').value.trim();
                        if (status == '6' && !reason) {
                            Swal.showValidationMessage('Please enter a cancellation reason.');
                            return false;
                        }
                        Swal.showLoading();
                        window.showAjaxLoader();
                        return new Promise((resolve, reject) => {
                            $.ajax({
                                url: url,
                                type: 'PATCH',
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content'),
                                    status: status,
                                    cancellation_reason: reason
                                },
                                success: function (res) {
                                    window.hideAjaxLoader();
                                    if (res.status === 'success') {
                                        resolve(res);
                                    } else {
                                        Swal.showValidationMessage(res.message || 'Something went wrong.');
                                        reject();
                                    }
                                },
                                error: function (xhr) {
                                    window.hideAjaxLoader();
                                    const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                                    Swal.showValidationMessage(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                                    reject();
                                }
                            });
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        toastr.success(result.value.message);
                        window.refreshTable();
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
                                <option value="1" ${currentPaymentStatus == 1 ? 'selected' : ''}>Pending</option>
                                <option value="2" ${currentPaymentStatus == 2 ? 'selected' : ''}>Paid</option>
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
                        window.showAjaxLoader();
                        $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                payment_status: result.value
                            },
                            success: function (res) {
                                window.hideAjaxLoader();
                                if (res.status === 'success') {
                                    toastr.success(res.message);
                                    window.refreshTable();
                                }
                            },
                            error: function (xhr) {
                                window.hideAjaxLoader();
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
