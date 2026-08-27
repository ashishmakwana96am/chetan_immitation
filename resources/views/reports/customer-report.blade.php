@extends('layouts.app')

@section('title', 'Customer Credit Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #customerReportTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #customerReportTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #customerReportTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #customerReportTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Customer Credit Report</h4>
        <div class="d-flex gap-2">
            <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
                <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
            </button>
            @can('manage customer balance')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.accounting.customer-balance.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Credit Balance
                </button>
            @endcan
        </div>
    </div>

    <div id="report-results">
        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted small">Total Credit</span>
                                <h5 class="mb-0 mt-1 text-success">{{ format_price($totalCredit) }}</h5>
                            </div>
                            <span class="badge bg-label-success rounded p-2"><i class="ti ti-arrow-down-circle ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted small">Total Debit</span>
                                <h5 class="mb-0 mt-1 text-danger">{{ format_price($totalDebit) }}</h5>
                            </div>
                            <span class="badge bg-label-danger rounded p-2"><i class="ti ti-arrow-up-circle ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted small">Cash Credit Balance</span>
                                <h5 class="mb-0 mt-1 {{ $cashBalance < 0 ? 'text-danger' : 'text-success' }}">{{ format_price($cashBalance) }}</h5>
                            </div>
                            <span class="badge bg-label-success rounded p-2"><i class="ti ti-cash ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted small">Bank Credit Balance</span>
                                <h5 class="mb-0 mt-1 {{ $bankBalance < 0 ? 'text-danger' : 'text-primary' }}">{{ format_price($bankBalance) }}</h5>
                            </div>
                            <span class="badge bg-label-primary rounded p-2"><i class="ti ti-building-bank ti-sm"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-muted small">Total Net Balance</span>
                                <h5 class="mb-0 mt-1 {{ $totalWalletBalance < 0 ? 'text-danger' : 'text-info' }}">{{ format_price($totalWalletBalance) }}</h5>
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
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
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
                            <th>Time</th>
                            <th style="width: 10%">Action</th>
                            <th class="d-none">Date Group</th>
                            <th class="d-none">Date Sort</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr @if($transaction->linked_order) data-modal-url="{{ route('admin.reports.customer-report.sale-products', ['order_id' => $transaction->linked_order->id]) }}" data-modal-size="half" style="cursor: pointer;" @endif>
                                <td></td>
                                <td class="fw-semibold" data-order="{{ $transaction->customer?->name ?? '-' }}">{{ $transaction->customer->name ?? '-' }}</td>
                                <td data-order="{{ $transaction->customer?->phone ?? '-' }}">{{ $transaction->customer->phone ?? '-' }}</td>
                                <td data-order="{{ $transaction->source }}">
                                    @if($transaction->source === 'bank')
                                        <span class="badge bg-label-info">Bank</span>
                                    @else
                                        <span class="badge bg-label-warning">Cash</span>
                                    @endif
                                </td>
                                <td data-order="{{ $transaction->type }}">
                                    @if($transaction->type === 'credit')
                                        <span class="badge bg-label-success">Credit</span>
                                    @else
                                        <span class="badge bg-label-danger">Debit</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}" data-order="{{ (float) $transaction->amount }}">{{ format_price($transaction->amount) }}</td>
                                <td class="text-end fw-semibold {{ $transaction->balance_after < 0 ? 'text-danger' : 'text-heading' }}" data-order="{{ (float) $transaction->balance_after }}">{{ format_price($transaction->balance_after) }}</td>
                                <td data-order="{{ $transaction->notes ?? '-' }}">{{ $transaction->notes ?? '-' }}</td>
                                <td data-order="{{ $transaction->createdBy?->name ?? '-' }}">{{ $transaction->createdBy->name ?? '-' }}</td>
                                <td data-order="{{ $transaction->created_at->format('H:i:s') }}">{{ $transaction->created_at->format('h:i A') }}</td>
                                <td>
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                            <span>Actions</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                            @if($transaction->linked_order)
                                                <a href="javascript:void(0);" class="dropdown-item" data-common-modal="{{ route('admin.reports.customer-report.sale-products', ['order_id' => $transaction->linked_order->id]) }}" data-size="half">
                                                    <i class="ti ti-eye me-2"></i>View
                                                </a>
                                            @endif
                                            @can('manage customer balance')
                                                <a href="{{ route('admin.accounting.customer-balance.thermal', ['transaction' => $transaction->id, 'auto_print' => 1]) }}" class="dropdown-item" target="_blank">
                                                    <i class="ti ti-printer me-2"></i>Receipt
                                                </a>
                                            @endcan
                                            @if(empty($transaction->linked_order) && !preg_match('/Sale #/i', $transaction->notes ?? ''))
                                                @can('edit customer balance')
                                                    <a href="javascript:void(0);" class="dropdown-item" data-common-modal="{{ route('admin.accounting.customer-balance.edit', $transaction->id) }}">
                                                        <i class="ti ti-pencil me-2"></i>Edit
                                                    </a>
                                                @endcan
                                                @can('delete customer balance')
                                                    <a href="javascript:void(0);" class="dropdown-item text-danger" data-common-delete="{{ route('admin.accounting.customer-balance.destroy', $transaction->id) }}">
                                                        <i class="ti ti-trash me-2"></i>Delete
                                                    </a>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none">{{ $transaction->created_at->format('d M Y') }}</td>
                                <td class="d-none">{{ $transaction->created_at->format('YmdHis') }}</td>
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
                deferRender: true,
                pageLength: 25,
                order: [[12, 'desc']],
                columnDefs: [
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { targets: 5, type: 'num' },
                    { targets: 6, type: 'num' },
                    { targets: 10, orderable: false, searchable: false },
                    { targets: [11, 12], visible: false, searchable: false }
                ],
                rowGroup: {
                    dataSrc: 11,
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="11"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' transaction' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
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
            window.refreshTable = function () {
                loadReport($('#filterForm').attr('action') + '?' + $('#filterForm').serialize());
            };

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

            $(document).on('click', '#exportExcelBtn', function () {
                const form = $('#filterForm');
                const url = "{{ route('admin.reports.customer-report.export-excel') }}?" + form.serialize();

                if (window.showAjaxLoader) {
                    $('.loader-status').text('Exporting Excel');
                    window.showAjaxLoader();
                }

                $.ajax({
                    url: url,
                    type: 'GET',
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function (data, status, xhr) {
                        if (window.hideAjaxLoader) {
                            window.hideAjaxLoader();
                            $('.loader-status').text('Loading');
                        }

                        const disposition = xhr.getResponseHeader('Content-Disposition');
                        let filename = 'customer_credit_report.xlsx';
                        if (disposition && disposition.indexOf('filename=') !== -1) {
                            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                            if (matches != null && matches[1]) {
                                filename = matches[1].replace(/['"]/g, '');
                            }
                        }

                        const blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                        const downloadUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = downloadUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(downloadUrl);
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Excel report downloaded successfully!');
                        }
                    },
                    error: function () {
                        if (window.hideAjaxLoader) {
                            window.hideAjaxLoader();
                            $('.loader-status').text('Loading');
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Failed to export report. Please try again.');
                        }
                    }
                });
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
