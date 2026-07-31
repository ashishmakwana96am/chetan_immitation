@extends('layouts.app')

@section('title', 'Customer Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Customer Report</h4>
        <div class="d-flex gap-2">
            @can('manage customer balance')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.accounting.customer-balance.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Credit Balance
                </button>
            @endcan
            <button type="button" id="exportPdfBtn" class="btn btn-danger report-export-btn" target="_blank">
                <i class="ti ti-file-text me-1"></i> Export to PDF
            </button>
        </div>
    </div>

    <div id="report-results">
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Customers</span>
                                <h4 class="mb-0 mt-1">{{ $totalCustomers }}</h4>
                            </div>
                            <span class="badge bg-label-primary rounded p-2"><i class="ti ti-users ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Credit Customers</span>
                                <h4 class="mb-0 mt-1">{{ $totalCreditCustomers }}</h4>
                            </div>
                            <span class="badge bg-label-info rounded p-2"><i class="ti ti-wallet ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Balance</span>
                                <h4 class="mb-0 mt-1 {{ $totalWalletBalance < 0 ? 'text-danger' : 'text-success' }}">{{ format_price($totalWalletBalance) }}</h4>
                            </div>
                            <span class="badge {{ $totalWalletBalance < 0 ? 'bg-label-danger' : 'bg-label-success' }} rounded p-2"><i class="ti ti-currency-rupee ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filter Report</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reports.customer-report') }}" id="filterForm" class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Start Date</label>
                        <input type="text" name="start_date" class="form-control flatpickr" value="{{ $startDate }}" placeholder="DD-MM-YYYY" />
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">End Date</label>
                        <input type="text" name="end_date" class="form-control flatpickr" value="{{ $endDate }}" placeholder="DD-MM-YYYY" />
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Name or phone" />
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Customer Type</label>
                        <select name="credit_only" class="form-select no-select2">
                            <option value="" {{ !$creditOnly ? 'selected' : '' }}>All Customers</option>
                            <option value="1" {{ $creditOnly ? 'selected' : '' }}>Credit Customers Only</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 mt-4 d-none" id="filterActionButtons">
                        <button type="button" id="clearFiltersBtn" class="btn btn-outline-primary">
                            <i class="ti ti-refresh me-1"></i> Clear
                        </button>
                        <button type="button" id="applyFiltersBtn" class="btn btn-primary">
                            <i class="ti ti-filter me-1"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Customers</h5>
            </div>
            <div class="card-datatable table-responsive">
                <table class="table border-top" id="customerReportTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th class="text-end">Total Credit</th>
                            <th class="text-end">Total Debit</th>
                            <th class="text-end">Balance</th>
                            <th style="width: 10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td></td>
                                <td>
                                    <span class="fw-semibold">{{ $customer->name }}</span>
                                    @if($customer->is_credit_customer)
                                        <span class="badge bg-label-success ms-1">Credit</span>
                                    @endif
                                </td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td class="text-end text-success fw-semibold">{{ $customer->is_credit_customer ? format_price($customer->period_credit) : '-' }}</td>
                                <td class="text-end text-danger fw-semibold">{{ $customer->is_credit_customer ? format_price($customer->period_debit) : '-' }}</td>
                                <td class="text-end fw-bold {{ $customer->is_credit_customer && $customer->balance < 0 ? 'text-danger' : 'text-heading' }}">{{ $customer->is_credit_customer ? format_price($customer->balance) : '-' }}</td>
                                <td>
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                            <span>Actions</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                            <a href="{{ route('admin.reports.customer-report.detail') }}?customer_id={{ $customer->id }}" class="dropdown-item">
                                                <i class="ti ti-eye me-2"></i>View
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        function initTable() {
            if ($.fn.DataTable.isDataTable('#customerReportTable')) {
                $('#customerReportTable').DataTable().destroy();
            }
            $('#customerReportTable').DataTable({
                responsive: false,
                order: [[1, 'asc']],
                columnDefs: [
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    }
                ],
                drawCallback: function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = api.page.info().start + i + 1;
                    });
                }
            });
        }

        function initDatePickers() {
            if (typeof $.fn.flatpickr !== 'undefined') {
                const startEl = $('input[name="start_date"]')[0];
                const endEl = $('input[name="end_date"]')[0];
                if (startEl && endEl) {
                    if (startEl._flatpickr) startEl._flatpickr.destroy();
                    if (endEl._flatpickr) endEl._flatpickr.destroy();

                    const startPicker = $(startEl).flatpickr({
                        altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                        onChange: function (selectedDates, dateStr, instance) {
                            $(instance.element).closest('form').trigger('change');
                            if (selectedDates.length) {
                                endPicker.set('minDate', selectedDates[0]);
                            } else {
                                endPicker.set('minDate', null);
                            }
                        }
                    });

                    const endPicker = $(endEl).flatpickr({
                        altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                        onChange: function (selectedDates, dateStr, instance) {
                            $(instance.element).closest('form').trigger('change');
                            if (selectedDates.length) {
                                startPicker.set('maxDate', selectedDates[0]);
                            } else {
                                startPicker.set('maxDate', 'today');
                            }
                        }
                    });

                    if (startPicker.selectedDates.length) {
                        endPicker.set('minDate', startPicker.selectedDates[0]);
                    }
                    if (endPicker.selectedDates.length) {
                        startPicker.set('maxDate', endPicker.selectedDates[0]);
                    }
                }
            }
        }

        let isFiltered = false;

        function updateFilterButtonsVisibility() {
            const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                return $(el).val();
            });
            $('#filterActionButtons').toggleClass('d-none', !hasValue);

            if (!hasValue && isFiltered) {
                isFiltered = false;
                loadReport($('#filterForm').attr('action'));
            }
        }

        function loadReport(url) {
            $('#report-results').css('opacity', 0.5);
            window.showAjaxLoader && window.showAjaxLoader();
            $.get(url, function (html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                $('#report-results').html($(doc).find('#report-results').html());
                initTable();
                initDatePickers();
                updateFilterButtonsVisibility();
            }).always(function () {
                $('#report-results').css('opacity', 1);
                window.hideAjaxLoader && window.hideAjaxLoader();
            });
        }

        $(document).ready(function () {
            initTable();
            initDatePickers();

            $(document).on('input change', '#filterForm', function () {
                updateFilterButtonsVisibility();
            });
            updateFilterButtonsVisibility();

            $(document).on('click', '#applyFiltersBtn', function () {
                isFiltered = true;
                loadReport($('#filterForm').attr('action') + '?' + $('#filterForm').serialize());
            });

            $(document).on('click', '#clearFiltersBtn', function () {
                isFiltered = false;
                const form = $('#filterForm');
                form[0].reset();
                form.find('.flatpickr').each(function () {
                    if (this._flatpickr) {
                        this._flatpickr.clear();
                        this._flatpickr.set('minDate', null);
                        this._flatpickr.set('maxDate', null);
                    }
                });
                updateFilterButtonsVisibility();
                loadReport(form.attr('action'));
            });

            $(document).on('click', '#exportPdfBtn', function () {
                const form = $('#filterForm');
                const url = "{{ route('admin.reports.customer-report.export') }}?" + form.serialize() + "&auto_print=1";
                window.open(url, '_blank');
            });
        });
    </script>
@endsection
