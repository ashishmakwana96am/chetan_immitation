@extends('layouts.app')

@section('title', 'Customer Credit Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Customer Credit Report</h4>
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
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Transactions</span>
                                <h4 class="mb-0 mt-1">{{ $totalTransactions }}</h4>
                            </div>
                            <span class="badge bg-label-primary rounded p-2"><i class="ti ti-receipt ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Credit</span>
                                <h4 class="mb-0 mt-1 text-success">{{ format_price($totalCredit) }}</h4>
                            </div>
                            <span class="badge bg-label-success rounded p-2"><i class="ti ti-arrow-down-circle ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Debit</span>
                                <h4 class="mb-0 mt-1 text-danger">{{ format_price($totalDebit) }}</h4>
                            </div>
                            <span class="badge bg-label-danger rounded p-2"><i class="ti ti-arrow-up-circle ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted">Total Balance</span>
                                <h4 class="mb-0 mt-1 {{ $totalWalletBalance < 0 ? 'text-danger' : 'text-primary' }}">{{ format_price($totalWalletBalance) }}</h4>
                            </div>
                            <span class="badge {{ $totalWalletBalance < 0 ? 'bg-label-danger' : 'bg-label-info' }} rounded p-2"><i class="ti ti-wallet ti-sm"></i></span>
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
                        <label class="form-label">Credit Customer</label>
                        <select name="customer_id" class="form-select">
                            <option value="">All Credit Customers</option>
                            @foreach($creditCustomers as $creditCustomer)
                                <option value="{{ $creditCustomer->id }}" {{ (string) $customerId === (string) $creditCustomer->id ? 'selected' : '' }}>
                                    {{ $creditCustomer->name }}{{ $creditCustomer->phone ? ' (' . $creditCustomer->phone . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Transaction Type</label>
                        <select name="type" class="form-select no-select2">
                            <option value="" {{ !$type ? 'selected' : '' }}>All (Credit & Debit)</option>
                            <option value="credit" {{ $type === 'credit' ? 'selected' : '' }}>Credit Only</option>
                            <option value="debit" {{ $type === 'debit' ? 'selected' : '' }}>Debit Only</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Source</label>
                        <select name="source" class="form-select no-select2">
                            <option value="" {{ !$source ? 'selected' : '' }}>All Sources</option>
                            <option value="cash" {{ $source === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank" {{ $source === 'bank' ? 'selected' : '' }}>Bank</option>
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

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Credit Customer Transactions</h5>
            </div>
            <div class="card-datatable table-responsive">
                <table class="table border-top" id="customerReportTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Source</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance After</th>
                            <th>Notes</th>
                            <th>Done By</th>
                            <th>Date</th>
                            <th style="width: 10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr @if($transaction->linked_order) data-modal-url="{{ route('admin.reports.customer-report.sale-products', ['order_id' => $transaction->linked_order->id]) }}" data-modal-size="half" style="cursor: pointer;" @endif>
                                <td></td>
                                <td class="fw-semibold">{{ $transaction->customer->name ?? '-' }}</td>
                                <td>{{ $transaction->customer->phone ?? '-' }}</td>
                                <td>
                                    @if($transaction->source === 'bank')
                                        <span class="badge bg-label-info">Bank</span>
                                    @else
                                        <span class="badge bg-label-warning">Cash</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->type === 'credit')
                                        <span class="badge bg-label-success">Credit</span>
                                    @else
                                        <span class="badge bg-label-danger">Debit</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">{{ format_price($transaction->amount) }}</td>
                                <td class="text-end fw-semibold {{ $transaction->balance_after < 0 ? 'text-danger' : 'text-heading' }}">{{ format_price($transaction->balance_after) }}</td>
                                <td>{{ $transaction->notes ?? '-' }}</td>
                                <td>{{ $transaction->createdBy->name ?? '-' }}</td>
                                <td>{{ $transaction->created_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    @if($transaction->linked_order)
                                        <div class="dropdown table-action-dropdown">
                                            <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                <span>Actions</span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                                <a href="javascript:void(0);" class="dropdown-item" data-common-modal="{{ route('admin.reports.customer-report.sale-products', ['order_id' => $transaction->linked_order->id]) }}" data-size="half">
                                                    <i class="ti ti-eye me-2"></i>View
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        -
                                    @endif
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
                order: [],
                columnDefs: [
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { targets: 10, orderable: false, searchable: false }
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

            $(document).on('dblclick', '#customerReportTable tbody tr[data-modal-url]', function (e) {
                if ($(e.target).closest('.dropdown').length) {
                    return;
                }
                window.openCommonModal($(this).data('modal-url'), $(this).data('modal-size'));
            });
        });
    </script>
@endsection
