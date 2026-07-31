@extends('layouts.app')

@section('title', 'Customer Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <style>
        #customerReportTable tbody tr {
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Customer Report</h4>
            <small class="text-muted">Customer list with credit customer wallet balance</small>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Total Customers</span>
                        <span class="fw-semibold text-primary" id="summary-total-customers">-</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Credit Customers</span>
                        <span class="fw-semibold text-primary" id="summary-total-credit-customers">-</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Total Wallet Balance</span>
                        <span class="fw-semibold text-success" id="summary-total-balance">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="filter-credit-only" />
                <label class="form-check-label" for="filter-credit-only">Show credit customers only</label>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="customerReportTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th class="text-end">Total Credit</th>
                        <th class="text-end">Total Debit</th>
                        <th class="text-end">Wallet Balance</th>
                        <th style="width: 10%">Action</th>
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
            const table = $('#customerReportTable').DataTable({
                responsive: false,
                order: [[1, 'asc']],
                ajax: {
                    url: '{{ route('admin.reports.customer-report.data') }}',
                    cache: false,
                    data: function (d) {
                        d.credit_only = $('#filter-credit-only').is(':checked') ? 1 : 0;
                    },
                    dataSrc: function (json) {
                        if (json.summary) {
                            $('#summary-total-customers').text(json.summary.total_customers);
                            $('#summary-total-credit-customers').text(json.summary.total_credit_customers);
                            $('#summary-total-balance').text(json.summary.total_balance);
                        }
                        return json.data;
                    },
                },
                columns: [
                    { data: 'index', orderable: false },
                    { data: 'name' },
                    { data: 'phone' },
                    { data: 'credit_badge', orderable: false },
                    { data: 'total_credit', className: 'text-end' },
                    { data: 'total_debit', className: 'text-end' },
                    { data: 'balance', className: 'text-end fw-semibold text-heading' },
                    {
                        data: null,
                        orderable: false,
                        render: function (data, type, row) {
                            if (!row.is_credit_customer) {
                                return '<span class="text-muted">-</span>';
                            }
                            return `<a href="{{ route('admin.reports.customer-report.detail') }}?customer_id=${row.customer_id}" class="btn btn-sm btn-label-primary">
                                        <i class="ti ti-eye me-1"></i>View
                                    </a>`;
                        }
                    },
                ],
                drawCallback: function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = api.page.info().start + i + 1;
                    });
                }
            });

            window.refreshTable = function () {
                table.ajax.reload();
            };

            $(document).on('change', '#filter-credit-only', function () {
                window.refreshTable();
            });

            $('#customerReportTable tbody').on('dblclick', 'tr', function () {
                const data = table.row(this).data();
                if (data && data.is_credit_customer) {
                    window.location.href = `{{ route('admin.reports.customer-report.detail') }}?customer_id=${data.customer_id}`;
                }
            });
        });
    </script>
@endsection
