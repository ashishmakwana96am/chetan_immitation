@extends('layouts.app')

@section('title', 'Expenses')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Expenses</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 320px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>

                    @if(!$isRestricted)
                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1" for="filter-location">Location</label>
                            <select id="filter-location" class="form-select">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-category">Category</label>
                        <select id="filter-category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-payment-method">Payment Method</label>
                        <select id="filter-payment-method" class="form-select">
                            <option value="">All Methods</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-start-date">From Date</label>
                        <input type="date" id="filter-start-date" class="form-control" max="{{ now()->format('Y-m-d') }}" />
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-end-date">To Date</label>
                        <input type="date" id="filter-end-date" class="form-control" max="{{ now()->format('Y-m-d') }}" />
                    </div>

                    <div class="dropdown-divider"></div>

                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>

            @can('create expenses')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.expenses.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Expense
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="expensesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        @if(!$isRestricted)
                            <th>Location</th>
                        @endif
                        <th>Expense Date</th>
                        <th>Created By</th>
                        @if(auth()->user()->can('edit expenses') || auth()->user()->can('delete expenses'))
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
    <script>
        $(document).ready(function () {
            const table = $('#expensesTable').DataTable({
                responsive: false,
                order: [],
                ajax: {
                    url: '{{ route('admin.expenses.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function (d) {
                        d.location_id = $('#filter-location').val();
                        d.category = $('#filter-category').val();
                        d.payment_method = $('#filter-payment-method').val();
                        d.start_date = $('#filter-start-date').val();
                        d.end_date = $('#filter-end-date').val();
                    }
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'title' },
                    { data: 'category', orderable: false },
                    { data: 'amount' },
                    { data: 'payment_method' },
                    @if(!$isRestricted)
                        { data: 'location' },
                    @endif
                    { data: 'expense_date' },
                    { data: 'created_by' },
                    @if(auth()->user()->can('edit expenses') || auth()->user()->can('delete expenses'))
                        { data: 'actions', orderable: false },
                    @endif
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });

            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-location, #filter-category, #filter-payment-method, #filter-start-date, #filter-end-date').val('');
                window.refreshTable();
                const btn = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (btn) { (bootstrap.Dropdown.getInstance(btn) || new bootstrap.Dropdown(btn)).hide(); }
            });
        });
    </script>
@endsection
