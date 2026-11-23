@extends('layouts.app')

@section('title', 'Video Call Bookings')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Video Call Bookings</h4>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="videoCallsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Booking No</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Date & Slot</th>
                        <th>Google Meet</th>
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
            const table = $('#videoCallsTable').DataTable({
                responsive: false,
                order: [],
                ajax: {
                    url: '{{ route('admin.video-call-bookings.data') }}',
                    dataSrc: 'data',
                    cache: false
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'booking_no' },
                    { data: 'customer_name' },
                    { data: 'customer_phone' },
                    { data: 'customer_email' },
                    { data: 'slot_info' },
                    { data: 'google_meet', orderable: false },
                    { data: 'actions', orderable: false }
                ]
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '.btn-cancel-booking', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const no = $(this).data('booking-no');

                Swal.fire({
                    title: 'Cancel Booking?',
                    text: 'Are you sure you want to cancel booking #' + no + '? The calendar meeting will also be cancelled.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Cancel it',
                    cancelButtonText: 'No, Keep it',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/video-call-bookings/' + id + '/cancel',
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                if (res.status === 'success') {
                                    toastr.success(res.message);
                                    window.refreshTable();
                                } else {
                                    toastr.error(res.message || 'Failed to cancel booking.');
                                }
                            },
                            error: function (xhr) {
                                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Something went wrong.';
                                toastr.error(msg);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
