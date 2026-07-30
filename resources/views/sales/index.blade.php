@extends('layouts.app')

@section('title', 'Sales')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <style>
        #ordersTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #ordersTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #ordersTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #ordersTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }

        .stock-warning-tooltip .tooltip-inner {
            max-width: 340px;
            width: 340px;
            padding: 14px 16px;
            text-align: left;
            background-color: #2b2c40;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        .stock-warning-tooltip .tooltip-arrow::before {
            border-top-color: #2b2c40 !important;
        }
        .stock-warning-tooltip .sw-item + .sw-item {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }
        .stock-warning-tooltip .sw-title {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 6px;
        }
        .stock-warning-tooltip .sw-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #ff8a8a;
            background: rgba(255, 90, 90, 0.15);
            border-radius: 20px;
            padding: 2px 10px;
            margin-bottom: 8px;
        }
        .stock-warning-tooltip .sw-subtitle {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #b6b7cf;
            margin-bottom: 4px;
        }
        .stock-warning-tooltip .sw-empty {
            font-size: 0.78rem;
            color: #b6b7cf;
            font-style: italic;
        }
        .stock-warning-tooltip .sw-branch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 0.82rem;
            color: #e6e6f0;
            padding: 3px 0;
        }
        .stock-warning-tooltip .sw-qty {
            background: #3fb950;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            border-radius: 20px;
            padding: 1px 10px;
            line-height: 1.4;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Sales</h4>
        <div class="d-flex gap-2 align-items-center">
            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>
                    
                    @if($isSuperAdmin)
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

                    {{-- Status --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-status">Sale Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1">Pending</option>
                            <option value="2">Approved</option>
                            <option value="3">Shipped</option>
                            <option value="4">Out for delivery</option>
                            <option value="5">Delivered</option>
                            <option value="6">Cancelled</option>
                        </select>
                    </div>

                    {{-- Payment Status --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-payment-status">Payment Status</label>
                        <select id="filter-payment-status" class="form-select">
                            <option value="">All Payments</option>
                            <option value="1">Pending</option>
                            <option value="3">Partially Paid</option>
                            <option value="2">Paid</option>
                        </select>
                    </div>

                    {{-- Source --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-source">Source</label>
                        <select id="filter-source" class="form-select">
                            <option value="">All Sources</option>
                            <option value="POS">POS</option>
                            <option value="ONLINE">ONLINE</option>
                        </select>
                    </div>

                    {{-- Product --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-product">Product</label>
                        <select id="filter-product" class="form-select product-search-select" style="width: 100%;">
                            <option value="">All Products</option>
                        </select>
                    </div>

                    {{-- Date Range --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1">Date Range</label>
                        <div class="w-100">
                            <input type="text" id="filter-start-date" class="form-control flatpickr-sales mb-2" placeholder="Start Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                            <div class="text-center text-muted small mb-2">to</div>
                            <input type="text" id="filter-end-date" class="form-control flatpickr-sales" placeholder="End Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>

            @can('create sales')
                <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add New Bill
                </a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="ordersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sale No</th>
                        <th>Customer</th>
                        @if($isSuperAdmin)
                        <th>Location</th>
                        @endif
                        <th>Source</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Method</th>
                        <th>Actions</th>
                        <th class="d-none">Date Group</th>
                        <th class="d-none">Date Sort</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script>
        $(document).ready(function () {
            let flatpickrOpen = false;
            const isSuperAdmin = {{ $isSuperAdmin ? 'true' : 'false' }};

            const startPicker = $('#filter-start-date').flatpickr({
                altInput   : true,
                altFormat  : 'd-m-Y',
                dateFormat : 'Y-m-d',
                allowInput : false,
                maxDate    : 'today',
                onOpen     : function () { flatpickrOpen = true; },
                onClose    : function (selectedDates) {
                    flatpickrOpen = false;
                    if (selectedDates.length) {
                        endPicker.set('minDate', selectedDates[0]);
                    }
                }
            });

            const endPicker = $('#filter-end-date').flatpickr({
                altInput   : true,
                altFormat  : 'd-m-Y',
                dateFormat : 'Y-m-d',
                allowInput : false,
                maxDate    : 'today',
                onOpen     : function () { flatpickrOpen = true; },
                onClose    : function (selectedDates) {
                    flatpickrOpen = false;
                    if (selectedDates.length) {
                        startPicker.set('maxDate', selectedDates[0]);
                    }
                }
            });

            $('#filterDropdownContainer').on('hide.bs.dropdown', function (e) {
                if (flatpickrOpen) {
                    e.preventDefault();
                    return false;
                }
            });

            $(document).on('mousedown', '.flatpickr-calendar', function (e) {
                e.stopPropagation();
            });
            $('#filter-product').select2({
                ajax: {
                    url: '{{ route('admin.products.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: 'Search products...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#filterDropdownContainer')
            });

            $('#filterDropdownContainer').on('click', '.select2-container', function (e) {
                e.stopPropagation();
            });

            $(document).on('mousedown', '.flatpickr-calendar', function (e) {
                e.stopPropagation();
            });

            const dateSortColIndex = isSuperAdmin ? 11 : 10;

            const table = $('#ordersTable').DataTable({
                responsive : false,
                order      : [[dateSortColIndex, 'desc']],
                orderFixed : { pre: [[dateSortColIndex, 'desc']] },
                ajax       : {
                    url: '{{ route('admin.sales.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.status = $('#filter-status').val();
                        d.payment_status = $('#filter-payment-status').val();
                        d.source = $('#filter-source').val();
                        d.product_id = $('#filter-product').val();
                        d.start_date = $('#filter-start-date').val();
                        d.end_date = $('#filter-end-date').val();
                        if (isSuperAdmin) {
                            d.location_id = $('#filter-location').val();
                        }
                    }
                },
                columns    : [
                    {
                        data: null,
                        width: '5%',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            const rowNumber = meta.row + meta.settings._iDisplayStart + 1;
                            return '<span class="d-inline-flex align-items-center gap-1 text-nowrap">' + rowNumber + (row.stock_warning || '') + (row.cancellation_warning || '') + '</span>';
                        }
                    },
                    { data: 'order_no' },
                    { data: 'customer' },
                    ...(isSuperAdmin ? [{ data: 'location' }] : []),
                    { data: 'source' },
                    { data: 'final_amount' },
                    { data: 'status',         orderable: false },
                    { data: 'payment_status', orderable: false },
                    { data: 'payment_method' },
                    { data: 'actions',        orderable: false },
                    { data: 'date_group',     visible: false },
                    { data: 'date_sort',      visible: false },
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        const colCount = isSuperAdmin ? 11 : 10;
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="' + colCount + '"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' sale' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
                createdRow: function (row, data) {
                    if (data.stock_warning) {
                        $(row).addClass('table-warning');
                    }
                    if (data.cancellation_requested) {
                        $(row).addClass('table-danger');
                    }
                },
                drawCallback: function () {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                }
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '.change-sale-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const currentStatus = parseInt($(this).data('current'));
                const source = $(this).data('source') || 'POS';
                const isOnline = source === 'ONLINE';
                const existingShippedUrl = $(this).data('shipped-url') || '';
                const existingTrackingId = $(this).data('tracking-id') || '';
                const existingCancelReason = $(this).data('cancel-reason') || '';

                let selectDisabled = '';
                let optionsHtml = '';

                if (!isOnline) {
                    // POS Order: only Pending and Approve
                    selectDisabled = (currentStatus >= 2) ? 'disabled' : '';
                    const opt1 = (currentStatus !== 1) ? 'disabled' : '';
                    const opt2 = (currentStatus === 2) ? '' : ((currentStatus === 1) ? '' : 'disabled');
                    
                    optionsHtml = `
                        <option value="1" ${currentStatus == 1 ? 'selected' : ''} ${opt1}>Pending</option>
                        <option value="2" ${currentStatus == 2 ? 'selected' : ''} ${opt2}>Approve</option>
                    `;
                } else {
                    // ONLINE Order: all options, sequential disabling
                    selectDisabled = [5, 6].includes(currentStatus) ? 'disabled' : '';
                    const opt1 = (currentStatus !== 1) ? 'disabled' : '';
                    const opt2 = (![1, 2].includes(currentStatus)) ? 'disabled' : '';
                    const opt3 = (![2, 3].includes(currentStatus)) ? 'disabled' : '';
                    const opt4 = (![3, 4].includes(currentStatus)) ? 'disabled' : '';
                    const opt5 = (![4, 5].includes(currentStatus)) ? 'disabled' : '';
                    const opt6 = ([5, 6].includes(currentStatus)) ? 'disabled' : '';
                    
                    optionsHtml = `
                        <option value="1" ${currentStatus == 1 ? 'selected' : ''} ${opt1}>Pending</option>
                        <option value="2" ${currentStatus == 2 ? 'selected' : ''} ${opt2}>Approve</option>
                        <option value="3" ${currentStatus == 3 ? 'selected' : ''} ${opt3}>Shipped</option>
                        <option value="4" ${currentStatus == 4 ? 'selected' : ''} ${opt4}>Out for delivery</option>
                        <option value="5" ${currentStatus == 5 ? 'selected' : ''} ${opt5}>Delivered</option>
                        <option value="6" ${currentStatus == 6 ? 'selected' : ''} ${opt6}>Cancelled</option>
                    `;
                }

                Swal.fire({
                    title: 'Update Sale Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label for="swal-sale-status" class="form-label fw-semibold mb-2">Select Sale Status</label>
                            <select id="swal-sale-status" class="form-select form-select-lg" ${selectDisabled}>
                                ${optionsHtml}
                            </select>
                        </div>
                        <div class="mb-3 text-start" id="swal-reason-wrap" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="swal-cancel-reason" class="form-label fw-semibold mb-0">Cancellation Reason <span class="text-danger">*</span></label>
                                <small class="text-muted" id="swal-char-counter">0/500</small>
                            </div>
                            <textarea id="swal-cancel-reason" class="form-control" rows="3" maxlength="500" placeholder="Enter the reason for cancellation..."></textarea>
                        </div>
                        <div class="mb-3 text-start" id="swal-shipping-wrap" style="display:none;">
                            <div class="mb-3">
                                <label for="swal-shipped-url" class="form-label fw-semibold mb-2">Shipping Client URL <span class="text-danger">*</span></label>
                                <input id="swal-shipped-url" class="form-control" placeholder="https://tracking-url.com">
                            </div>
                            <div>
                                <label for="swal-tracking-id" class="form-label fw-semibold mb-2">Tracking ID <span class="text-danger">*</span></label>
                                <input id="swal-tracking-id" class="form-control" placeholder="Tracking ID / No.">
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Update',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    didOpen: () => {
                        const reasonInput = document.getElementById('swal-cancel-reason');
                        const charCounter = document.getElementById('swal-char-counter');
                        
                        const updateCount = () => {
                            charCounter.textContent = `${reasonInput.value.length}/500`;
                        };
                        
                        reasonInput.addEventListener('input', updateCount);

                        document.getElementById('swal-sale-status').addEventListener('change', function () {
                            const reasonWrap = document.getElementById('swal-reason-wrap');
                            const shippingWrap = document.getElementById('swal-shipping-wrap');
                            reasonWrap.style.display = (this.value == '6') ? 'block' : 'none';
                            // Show shipping fields only when selecting Shipped from a different status
                            shippingWrap.style.display = (this.value == '3' && currentStatus != 3) ? 'block' : 'none';
                        });
                        
                        if (currentStatus == 6) {
                            document.getElementById('swal-reason-wrap').style.display = 'block';
                            if (existingCancelReason) {
                                reasonInput.value = existingCancelReason;
                                updateCount();
                            }
                        }
                        // Do NOT show shipping fields if already Shipped — fields not needed again
                    },
                    preConfirm: () => {
                        const status = document.getElementById('swal-sale-status').value;
                        const reason = document.getElementById('swal-cancel-reason').value.trim();
                        const shippedUrl = document.getElementById('swal-shipped-url') ? document.getElementById('swal-shipped-url').value.trim() : '';
                        const trackingId = document.getElementById('swal-tracking-id') ? document.getElementById('swal-tracking-id').value.trim() : '';

                        if (status == '6' && !reason) {
                            Swal.showValidationMessage('Please enter a cancellation reason.');
                            return false;
                        }
                        // Only validate shipping fields when transitioning TO Shipped (not already shipped)
                        if (status == '3' && currentStatus != 3) {
                            if (!shippedUrl) {
                                Swal.showValidationMessage('Please enter Shipping Client URL');
                                return false;
                            }
                            if (!trackingId) {
                                Swal.showValidationMessage('Please enter Tracking ID');
                                return false;
                            }
                        }
                        return { status: status, reason: reason, shipped_client_url: shippedUrl, tracking_id: trackingId };
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const { status, reason, shipped_client_url, tracking_id } = result.value;
                        window.showAjaxLoader();
                        $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                status: status,
                                cancellation_reason: reason,
                                shipped_client_url: shipped_client_url,
                                tracking_id: tracking_id
                            },
                            success: function (res) {
                                window.hideAjaxLoader();
                                if (res.status === 'success') {
                                    toastr.success(res.message);
                                    if (res.pending_count !== undefined) {
                                        const badge = $('.pending-sales-counter-badge');
                                        if (badge.length > 0) {
                                            badge.text(res.pending_count);
                                            if (res.pending_count > 0) {
                                                badge.attr('style', 'display: inline-block !important;');
                                            } else {
                                                badge.attr('style', 'display: none !important;');
                                            }
                                        }
                                    }
                                    window.refreshTable();
                                } else {
                                    toastr.error(res.message || 'Something went wrong.');
                                }
                            },
                            error: function (xhr) {
                                window.hideAjaxLoader();
                                const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                                toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            }
                        });
                    }
                });
            });

            function buildSalePaymentHistoryHtml(historyData) {
                if (!historyData || !historyData.payments || historyData.payments.length === 0) {
                    return '';
                }

                let rows = historyData.payments.map(function (p) {
                    return `<tr><td class="text-nowrap">${p.date}</td><td class="text-end">${p.amount}</td></tr>`;
                }).join('');

                return `
                    <div class="mb-3 text-start" style="font-size: 0.8rem;">
                        <div class="d-flex justify-content-between text-muted mb-2">
                            <span>Total: <strong>${historyData.total_amount}</strong></span>
                            <span>Paid: <strong class="text-success">${historyData.paid_amount}</strong></span>
                            <span>Balance: <strong class="text-danger">${historyData.balance_due}</strong></span>
                        </div>
                        <label class="form-label fw-semibold mb-1" style="font-size: 0.8rem;">Payment History</label>
                        <div class="table-responsive border rounded" style="max-height:150px; overflow-y:auto;">
                            <table class="table table-sm mb-0" style="font-size: 0.75rem;">
                                <thead class="table-light"><tr><th>DATE</th><th class="text-end">AMOUNT</th></tr></thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            $(document).on('click', '.change-payment-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const historyUrl = $(this).data('history-url');
                const grandTotal = parseFloat($(this).data('amount')) || 0;
                const currentStatus = parseInt($(this).data('current') || 1);

                window.showAjaxLoader();
                $.get(historyUrl)
                    .done(function (res) {
                        window.hideAjaxLoader();
                        openSalePaymentModal(url, currentStatus, grandTotal, res.data);
                    })
                    .fail(function () {
                        window.hideAjaxLoader();
                        openSalePaymentModal(url, currentStatus, grandTotal, null);
                    });
            });

            function openSalePaymentModal(url, currentStatus, grandTotal, historyData) {
                const optPending = currentStatus === 3 ? 'disabled' : (currentStatus === 1 ? 'selected' : '');
                const optPartial = currentStatus === 3 ? 'selected' : '';
                const optPaid = currentStatus === 2 ? 'selected' : '';

                const remainingDue = historyData ? (historyData.balance_due_raw !== undefined ? parseFloat(historyData.balance_due_raw) : grandTotal) : grandTotal;
                const initialCash = remainingDue;

                Swal.fire({
                    title: 'Update Payment Status',
                    html: `
                        ${buildSalePaymentHistoryHtml(historyData)}
                        <div class="mb-3 text-start">
                            <label for="swal-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                            <select id="swal-payment-status" class="form-select">
                                <option value="1" ${optPending}>Pending</option>
                                <option value="3" ${optPartial}>Partially Paid</option>
                                <option value="2" ${optPaid}>Paid</option>
                            </select>
                        </div>
                        <div class="text-start d-flex gap-2" id="swal-amounts-wrap">
                            <div class="flex-fill">
                                <label class="form-label fw-semibold mb-2">Cash</label>
                                <input type="number" id="swal-paid-cash" class="form-control" value="${initialCash}" min="0" step="0.01">
                            </div>
                            <div class="flex-fill">
                                <label class="form-label fw-semibold mb-2">Online</label>
                                <input type="number" id="swal-paid-online" class="form-control" value="0" min="0" step="0.01">
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Update',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    didOpen: () => {
                        const statusSelect = document.getElementById('swal-payment-status');
                        const amountsWrap = document.getElementById('swal-amounts-wrap');
                        const cashInput = document.getElementById('swal-paid-cash');
                        const onlineInput = document.getElementById('swal-paid-online');

                        const toggleVisibility = () => {
                            const val = parseInt(statusSelect.value);
                            amountsWrap.style.display = (val === 1) ? 'none' : 'flex';
                        };

                        const clamp = (source) => {
                            const val = parseInt(statusSelect.value);
                            if (val === 1) return;

                            let cash = parseFloat(cashInput.value) || 0;
                            let online = parseFloat(onlineInput.value) || 0;

                            if (val === 2) {
                                if (source === 'cash') {
                                    cash = Math.min(Math.max(cash, 0), remainingDue);
                                    cashInput.value = cash;
                                    onlineInput.value = Math.round((remainingDue - cash) * 100) / 100;
                                } else {
                                    online = Math.min(Math.max(online, 0), remainingDue);
                                    onlineInput.value = online;
                                    cashInput.value = Math.round((remainingDue - online) * 100) / 100;
                                }
                            } else if (val === 3) {
                                if (source === 'cash') {
                                    if (cash > remainingDue) {
                                        cash = remainingDue;
                                        cashInput.value = cash;
                                    }
                                    if (cash + online > remainingDue) {
                                        online = Math.round((remainingDue - cash) * 100) / 100;
                                        onlineInput.value = online;
                                    }
                                } else if (source === 'online') {
                                    if (online > remainingDue) {
                                        online = remainingDue;
                                        onlineInput.value = online;
                                    }
                                    if (cash + online > remainingDue) {
                                        cash = Math.round((remainingDue - online) * 100) / 100;
                                        cashInput.value = cash;
                                    }
                                }
                            }
                        };

                        statusSelect.addEventListener('change', () => {
                            toggleVisibility();
                            clamp('online');
                        });
                        cashInput.addEventListener('input', () => clamp('cash'));
                        onlineInput.addEventListener('input', () => clamp('online'));
                        toggleVisibility();
                    },
                    preConfirm: () => {
                        const status = document.getElementById('swal-payment-status').value;
                        const cash = parseFloat(document.getElementById('swal-paid-cash').value) || 0;
                        const online = parseFloat(document.getElementById('swal-paid-online').value) || 0;
                        const sum = cash + online;

                        if (status === '3' && sum <= 0) {
                            Swal.showValidationMessage('Paid amount must be at least 0.01 for Partially Paid status.');
                            return false;
                        }
                        if (status !== '1' && sum > (remainingDue + 0.01)) {
                            Swal.showValidationMessage(`Paid amount cannot be greater than the remaining balance due (₹${remainingDue.toFixed(2)}).`);
                            return false;
                        }

                        return {
                            payment_status: status,
                            paid_cash_amount: cash,
                            paid_online_amount: online,
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        window.showAjaxLoader();
                        $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                payment_status: result.value.payment_status,
                                paid_cash_amount: result.value.paid_cash_amount,
                                paid_online_amount: result.value.paid_online_amount
                            },
                            success: function (res) {
                                window.hideAjaxLoader();
                                if (res.status === 'success') {
                                    toastr.success(res.message);
                                    window.refreshTable();
                                }
                            },
                            error: function (xhr) {
                                window.hideAjaxLoader();
                                const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                                toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            }
                        });
                    }
                });
            }



            // Apply Filter button handler
            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                
                // Close the dropdown after applying
                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl) || new bootstrap.Dropdown(dropdownToggleEl);
                    dropdownInstance.hide();
                }
            });

            // Clear Filter button handler
            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-status').val('');
                $('#filter-payment-status').val('');
                $('#filter-source').val('');
                $('#filter-product').val('').trigger('change');
                if (isSuperAdmin) {
                    $('#filter-location').val('');
                }
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', null);
                endPicker.set('minDate', null);
                window.refreshTable();
                
                // Close the dropdown after clearing
                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl) || new bootstrap.Dropdown(dropdownToggleEl);
                    dropdownInstance.hide();
                }
            });
        });
    </script>
@endsection
