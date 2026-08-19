@extends('layouts.app')

@section('title', 'Outstanding Payables')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #outstandingPayablesTable tbody tr:not(.group-header) {
            cursor: pointer;
        }
        #outstandingPayablesTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #outstandingPayablesTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #outstandingPayablesTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #outstandingPayablesTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Outstanding Payables</h4>
            <small class="text-muted">Consolidated outstanding amounts due to suppliers</small>
        </div>
        <div class="d-flex gap-2">
            @php
                $isMainBranchUser = auth()->user()->hasRole('super-admin') || !auth()->user()->location_id;
                $canViewPaymentHistory = $isMainBranchUser && (auth()->user()->hasRole('super-admin') || auth()->user()->can('view purchase payments'));
                $canMakePayment = $isMainBranchUser && (auth()->user()->hasRole('super-admin') || auth()->user()->can('create purchase payment'));
            @endphp
            @if($canViewPaymentHistory)
                <a href="{{ route('admin.accounting.outstanding-payables.payment-history') }}" class="btn btn-outline-primary">
                    <i class="ti ti-history me-1"></i> Payment History
                </a>
            @endif
            @if($canMakePayment)
                <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#bulkPayOffcanvas">
                    <i class="ti ti-cash me-1"></i> Make Payment
                </button>
            @endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach($locations as $loc)
            <div class="col-md-6 col-lg-4 branch-card-col" data-location-id="{{ $loc->id }}">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="badge rounded bg-label-primary p-2 me-2">
                                <i class="ti ti-building ti-sm text-primary"></i>
                            </div>
                            <h5 class="card-title mb-0 fw-bold text-truncate" style="max-width: 80%;" title="{{ $loc->name }}">{{ $loc->name }}</h5>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Purchases</span>
                            <span class="fw-semibold" id="purchase-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Paid</span>
                            <span class="fw-semibold text-success" id="payment-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Outstanding</span>
                            <span class="fw-semibold text-danger" id="outstanding-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4" id="filterReportCard">
        <div class="card-header">
            <h5 class="mb-0">Filter</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3" onsubmit="return false;">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="text" id="filter-start-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="text" id="filter-end-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                </div>
                @if(!$isRestricted)
                <div class="col-md-3">
                    <label class="form-label">Location</label>
                    <select id="filter-location" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
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

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="outstandingPayablesTable">
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th>Supplier</th>
                        <th class="text-end">Total Purchase</th>
                        <th class="text-end">Total Paid</th>
                        <th class="text-end">Outstanding Amount</th>
                        <th style="width: 10%">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Make Payment Offcanvas Sidepanel -->
    <div class="offcanvas offcanvas-end" id="bulkPayOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 500px; max-width: 100vw;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="bulkPayOffcanvasLabel">Make Payment</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column" style="overflow: hidden;">
            <form id="bulkPayForm" class="d-flex flex-column h-100 m-0">
                @csrf
                <div class="flex-grow-1 p-4" style="overflow-y: auto;">
                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Select Supplier</label>
                        <select id="bulk-pay-supplier-id" name="supplier_id" class="form-select form-select-lg no-select2" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Enter Amount to Pay (₹)</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0.01" id="bulk-pay-amount" name="amount" class="form-control form-control-lg" placeholder="e.g. 500000" required autofocus />
                        </div>
                        <small class="text-muted d-block mt-2" id="bulk-pay-max-hint">Payable balance: <strong class="text-danger" id="bulk-pay-max-val">₹ 0.00</strong></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Payment Method</label>
                        <select id="bulk-pay-payment-method" name="payment_method" class="form-select form-select-lg no-select2" required>
                            <option value="cash" selected>Cash</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex p-4 border-top gap-3 mt-auto mb-0">
                    <button type="submit" id="bulkPaySubmitBtn" class="btn btn-primary flex-fill w-50 m-0">
                        <i class="ti ti-check me-1"></i> Make Payment
                    </button>
                    <button type="button" class="btn btn-label-secondary flex-fill w-50 m-0" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            const startPicker = $('#filter-start-date').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                onChange: function (selectedDates) { endPicker.set('minDate', selectedDates.length ? selectedDates[0] : null); }
            });
            const endPicker = $('#filter-end-date').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                onChange: function (selectedDates) { startPicker.set('maxDate', selectedDates.length ? selectedDates[0] : 'today'); }
            });

            let isFiltered = false;

            function updateFilterButtonsVisibility() {
                const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                    return $(el).val();
                });
                $('#filterActionButtons').toggleClass('d-none', !hasValue);

                if (!hasValue && isFiltered) {
                    isFiltered = false;
                    window.refreshTable();
                }
            }

            $(document).on('input change', '#filterForm', function () {
                updateFilterButtonsVisibility();
            });
            updateFilterButtonsVisibility();

            function currentFilters() {
                return {
                    start_date: $('#filter-start-date').val(),
                    end_date: $('#filter-end-date').val(),
                    location_id: $('#filter-location').val() || '',
                };
            }

            const table = $('#outstandingPayablesTable').DataTable({
                responsive: false,
                order: [[1, 'asc']],
                ajax: {
                    url: '{{ route('admin.accounting.outstanding-payables.data') }}',
                    cache: false,
                    data: function (d) { Object.assign(d, currentFilters()); },
                    dataSrc: function (json) {
                        if (json.branch_summary) {
                            $.each(json.branch_summary, function (locId, s) {
                                $('#purchase-' + locId).text(s.purchase);
                                $('#payment-' + locId).text(s.payment);
                                $('#outstanding-' + locId).text(s.outstanding);
                            });
                        }
                        return json.data;
                    },
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%' },
                    { 
                        data: 'supplier',
                        render: function (data, type, row) {
                            const locationVal = $('#filter-location').val() || '';
                            const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                            return `<a href="{{ route('admin.accounting.outstanding-payables.detail') }}?supplier_id=${row.supplier_id}${locationQuery}" class="fw-semibold text-body">${data}</a>`;
                        }
                    },
                    { 
                        data: 'total_amount', 
                        className: 'text-end fw-semibold text-heading',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_total_amount !== undefined ? row.raw_total_amount : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'paid_amount', 
                        className: 'text-end fw-semibold text-heading',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_paid_amount !== undefined ? row.raw_paid_amount : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return `<span class="text-success fw-semibold">${data}</span>`;
                        }
                    },
                    { 
                        data: 'due_amount', 
                        className: 'text-end fw-semibold text-heading',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_due_amount !== undefined ? row.raw_due_amount : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return `<span class="text-danger fw-semibold">${data}</span>`;
                        }
                    },
                    { 
                        data: null, 
                        orderable: false, 
                        render: function (data, type, row) {
                            const locationVal = $('#filter-location').val() || '';
                            const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                            return `
                                <div class="dropdown table-action-dropdown">
                                    <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                        <span>Actions</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                        <a href="{{ route('admin.accounting.outstanding-payables.detail') }}?supplier_id=${row.supplier_id}${locationQuery}" class="dropdown-item">
                                             <i class="ti ti-eye me-2"></i>View
                                         </a>
                                     </div>
                                 </div>
                            `;
                        }
                    },
                ],
                drawCallback: function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });

            function updateCardVisibility() {
                const selectedLocId = $('#filter-location').val();
                if (selectedLocId) {
                    $('.branch-card-col').hide();
                    $('.branch-card-col[data-location-id="' + selectedLocId + '"]').show();
                } else {
                    $('.branch-card-col').show();
                }
            }

            updateCardVisibility();

            window.refreshTable = function () {
                window.showAjaxLoader && window.showAjaxLoader();
                updateCardVisibility();
                table.ajax.reload(function () {
                    window.hideAjaxLoader && window.hideAjaxLoader();
                }, false);
            };

            $(document).on('click', '#applyFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = true;
                window.refreshTable();
            });

            $(document).on('click', '#clearFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = false;
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', 'today');
                endPicker.set('minDate', null);
                $('#filter-location').val(null).trigger('change');
                updateFilterButtonsVisibility();
                window.refreshTable();
            });

            function updateBulkPayMax() {
                const selectedSupplierId = $('#bulk-pay-supplier-id').val();
                let totalDue = 0.0;
                if (typeof table !== 'undefined' && table.rows) {
                    table.rows({ filter: 'applied' }).data().each(function (row) {
                        if (!selectedSupplierId || String(row.supplier_id) === String(selectedSupplierId)) {
                            if (row.due_amount) {
                                const raw = parseFloat(String(row.due_amount).replace(/[^0-9.-]+/g, ''));
                                if (!isNaN(raw)) totalDue += raw;
                            }
                        }
                    });
                }
                totalDue = Math.round(totalDue * 100) / 100;

                const formatted = '₹ ' + totalDue.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $('#bulk-pay-max-val').text(formatted);
                $('#bulk-pay-max-hint').show();

                if (totalDue > 0) {
                    $('#bulk-pay-amount').attr('max', totalDue);
                } else {
                    $('#bulk-pay-amount').removeAttr('max');
                }
            }

            $('#bulkPayOffcanvas').on('show.bs.offcanvas', function () {
                const locationId = $('#filter-location').val() || '';
                $('#bulk-pay-location-id').val(locationId);
                $('#bulk-pay-amount').val('');
                $('#bulk-pay-supplier-id').val('');
                updateBulkPayMax();
            });

            $(document).on('change', '#bulk-pay-supplier-id', function () {
                updateBulkPayMax();
            });

            // Handle Bulk Pay Submit
            $('#bulkPayForm').on('submit', function (e) {
                e.preventDefault();

                const amountVal = parseFloat($('#bulk-pay-amount').val()) || 0;
                const maxVal = parseFloat($('#bulk-pay-amount').attr('max')) || 0;

                if (maxVal <= 0) {
                    const msg = 'There are no pending payable balances to process.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: msg, customClass: { confirmButton: 'btn btn-primary' } });
                    } else {
                        alert(msg);
                    }
                    return false;
                }

                if (amountVal > maxVal) {
                    const msg = 'Payment amount cannot exceed the total outstanding balance due (' + $('#bulk-pay-max-val').text() + ').';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: msg, customClass: { confirmButton: 'btn btn-primary' } });
                    } else {
                        alert(msg);
                    }
                    return false;
                }

                const $btn = $('#bulkPaySubmitBtn');
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...');

                $.ajax({
                    url: '{{ route('admin.accounting.outstanding-payables.bulk-pay') }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (res) {
                        const offcanvasEl = document.getElementById('bulkPayOffcanvas');
                        if (offcanvasEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                            bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).hide();
                        } else {
                            $('#bulkPayOffcanvas').removeClass('show');
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message);
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: res.message,
                                customClass: { confirmButton: 'btn btn-primary' }
                            });
                        } else {
                            alert(res.message);
                        }
                        window.refreshTable();
                    },
                    error: function (xhr) {
                        let errorMsg = 'Failed to process bulk payment.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            if (typeof xhr.responseJSON.message === 'string') {
                                errorMsg = xhr.responseJSON.message;
                            } else if (typeof xhr.responseJSON.message === 'object') {
                                const keys = Object.keys(xhr.responseJSON.message);
                                if (keys.length) {
                                    const val = xhr.responseJSON.message[keys[0]];
                                    errorMsg = Array.isArray(val) ? val[0] : String(val);
                                }
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const keys = Object.keys(xhr.responseJSON.errors);
                            if (keys.length) {
                                const val = xhr.responseJSON.errors[keys[0]];
                                errorMsg = Array.isArray(val) ? val[0] : String(val);
                            }
                        }

                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMsg,
                                customClass: { confirmButton: 'btn btn-primary' }
                            });
                        } else {
                            alert(errorMsg);
                        }
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Make Payment');
                    }
                });
            });

            // Double click anywhere on row to navigate to details page
            $('#outstandingPayablesTable tbody').on('dblclick', 'tr:not(.group-header)', function (e) {
                if ($(e.target).closest('.dropdown').length || $(e.target).closest('button').length || $(e.target).closest('a').length) {
                    return;
                }
                const data = table.row(this).data();
                if (data && data.supplier_id) {
                    const locationVal = $('#filter-location').val() || '';
                    const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                    window.location.href = `{{ route('admin.accounting.outstanding-payables.detail') }}?supplier_id=${data.supplier_id}${locationQuery}`;
                }
            });
        });
    </script>
@endsection
