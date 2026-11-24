@extends('layouts.app')

@section('title', 'Video Call Bookings')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <style>
        .btn-join-meet {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 600;
            letter-spacing: 0.3px;
            color: #ffffff !important;
            background: linear-gradient(135deg, #B4771E 0%, #D49B3E 100%);
            border: none;
            border-radius: 6px;
            box-shadow: 0 3px 10px rgba(180, 119, 30, 0.35);
            text-decoration: none !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .btn-join-meet:hover {
            background: linear-gradient(135deg, #966113 0%, #B4771E 100%);
            box-shadow: 0 6px 16px rgba(180, 119, 30, 0.48);
            transform: translateY(-1.5px);
            color: #ffffff !important;
        }
        .btn-join-meet:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(180, 119, 30, 0.3);
        }
        .btn-join-meet i {
            font-size: 15px;
            transition: transform 0.2s ease;
        }
        .btn-join-meet:hover i {
            transform: scale(1.15);
        }
        .badge-completed-meet {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.3px;
            color: #059669 !important;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.08);
            transition: all 0.2s ease;
        }
        .badge-completed-meet:hover {
            background: #d1fae5;
            border-color: #6ee7b7;
            box-shadow: 0 3px 8px rgba(16, 185, 129, 0.16);
            transform: translateY(-1px);
        }
        .badge-completed-meet i {
            font-size: 13px;
        }
        .badge-cancelled-meet {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.3px;
            color: #dc2626 !important;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.08);
            transition: all 0.2s ease;
        }
        .badge-cancelled-meet:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            box-shadow: 0 3px 8px rgba(220, 38, 38, 0.16);
            transform: translateY(-1px);
        }
        .badge-cancelled-meet i {
            font-size: 13px;
        }
        #videoCallsTable .action-dropdown-btn {
            border-radius: 6px;
            font-weight: 600;
            font-size: 12.5px;
            padding: 6px 14px;
            box-shadow: 0 2px 6px rgba(183, 131, 38, 0.12);
            transition: all 0.25s ease;
        }
        #videoCallsTable .action-dropdown-btn:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 5px 12px rgba(183, 131, 38, 0.25);
        }
    </style>
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
                                    if (typeof res.scheduled_count !== 'undefined') {
                                        const badge = document.getElementById('video-call-scheduled-badge');
                                        if (badge) {
                                            badge.textContent = res.scheduled_count;
                                            badge.style.display = res.scheduled_count > 0 ? 'inline-flex' : 'none';
                                        }
                                        const topBadge = document.getElementById('video-call-scheduled-badge-top');
                                        if (topBadge) {
                                            topBadge.textContent = res.scheduled_count;
                                            topBadge.style.display = res.scheduled_count > 0 ? 'inline-flex' : 'none';
                                        }
                                    }
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
