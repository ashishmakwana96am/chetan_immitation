@extends('layouts.app')

@section('title', 'Contact Inquiries')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Contact Inquiries</h4>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="contactInquiriesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Subject</th>
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
            const table = $('#contactInquiriesTable').DataTable({
                responsive: false,
                ordering: true,
                ajax: { url: '{{ route('admin.contact-inquiries.data') }}', dataSrc: 'data' },
                columns: [
                    { data: 'index', width: '5%' },
                    { data: 'full_name' },
                    { data: 'email' },
                    { data: 'phone' },
                    { data: 'subject' },
                    { data: 'created_at' },
                    { data: 'actions', orderable: false },
                ],
                order: [[5, 'desc']],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    search: "",
                    searchPlaceholder: "Search inquiries...",
                    lengthMenu: "_MENU_ per page"
                }
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };
        });
    </script>
@endsection