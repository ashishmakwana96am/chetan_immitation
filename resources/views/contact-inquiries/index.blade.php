@extends('layouts.app')

@section('title', 'Contact Inquiries')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Contact Inquiries</h4>
        {{-- Filter Dropdown --}}
        <div class="dropdown d-inline-block" id="filterDropdownContainer">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                <i class="ti ti-filter me-1"></i> Filter
            </button>
            <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 300px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>
                <div class="mb-3 text-start">
                    <label class="form-label fw-medium text-muted mb-1" for="filter-emailed">Email Status</label>
                    <select id="filter-emailed" class="form-select">
                        <option value="">All</option>
                        <option value="1">Sent</option>
                        <option value="0">Pending</option>
                    </select>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-medium text-muted mb-1">Date Range</label>
                    <div class="w-100">
                        <input type="date" id="filter-start-date" class="form-control mb-2" />
                        <div class="text-center text-muted small mb-2">to</div>
                        <input type="date" id="filter-end-date" class="form-control" />
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="d-flex justify-content-between gap-2 pt-2">
                    <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                    <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                </div>
            </div>
        </div>
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
                ajax: {
                    url: '{{ route('admin.contact-inquiries.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.emailed     = $('#filter-emailed').val();
                        d.start_date  = $('#filter-start-date').val();
                        d.end_date    = $('#filter-end-date').val();
                    }
                },
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
                $('#filter-emailed').val('');
                $('#filter-start-date').val('');
                $('#filter-end-date').val('');
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });
        });
    </script>
@endsection