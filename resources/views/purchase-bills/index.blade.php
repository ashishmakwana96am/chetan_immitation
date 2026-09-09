@extends('layouts.app')

@section('title', 'Purchase Bill')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <style>
        /* Desktop: Standard Dropdown Menu */
        #filterDropdownContainer {
            position: relative;
        }
        @media (min-width: 768px) {
            .purchase-bill-filter-dropdown {
                min-width: 420px;
                width: 420px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 10px;
                padding: 1.5rem;
                z-index: 1060;
            }
            .filter-sidepanel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 1rem;
            }
            .filter-sidepanel-body {
                padding: 0;
            }
            .filter-sidepanel-footer {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid rgba(0, 0, 0, 0.08);
            }
        }

        .filter-action-buttons {
            display: flex;
            width: 100%;
            gap: 0.625rem;
        }

        .filter-action-buttons button {
            flex: 1 1 50%;
            width: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        body.filter-sidepanel-open {
            overflow: hidden !important;
            touch-action: none !important;
        }

        /* Mobile / Phone View: Full Screen Modal Drawer */
        @media (max-width: 767.98px) {
            #filterDropdownContainer .dropdown-menu.purchase-bill-filter-dropdown {
                position: fixed !important;
                inset: 0 !important;
                top: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                height: 100% !important;
                height: 100dvh !important;
                max-height: 100% !important;
                max-height: 100dvh !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                z-index: 1090 !important;
                transform: translateX(100%) !important;
                transition: transform 0.28s ease-in-out, visibility 0.28s !important;
                visibility: hidden !important;
                overscroll-behavior: contain !important;
                background: #fff !important;
            }

            #filterDropdownContainer .dropdown-menu.purchase-bill-filter-dropdown.show {
                transform: translateX(0) !important;
                visibility: visible !important;
            }

            .filter-sidepanel-header {
                padding: 1.15rem 1.25rem;
                margin-bottom: 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.08);
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #fff;
            }

            .filter-sidepanel-body {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                padding: 1.25rem;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior: contain !important;
            }

            .filter-sidepanel-body .mb-3 {
                margin-bottom: 0.85rem !important;
            }

            .filter-sidepanel-footer {
                margin-top: 0;
                padding: 0.85rem 1.25rem;
                padding-bottom: max(0.85rem, env(safe-area-inset-bottom, 0.85rem));
                border-top: 1px solid rgba(0, 0, 0, 0.08);
                background: #fff;
                flex-shrink: 0;
                box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
            }

            .filter-mobile-backdrop {
                position: fixed;
                inset: 0;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100% !important;
                height: 100dvh !important;
                background: rgba(0, 0, 0, 0.45);
                backdrop-filter: blur(1px);
                z-index: 1085;
                opacity: 0;
                transition: opacity 0.25s ease;
                pointer-events: none;
            }

            .filter-mobile-backdrop.show {
                opacity: 1;
                pointer-events: auto;
            }
        }

        .select2-container--open,
        .flatpickr-calendar {
            z-index: 99999 !important;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Purchase Bill</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Filter Dropdown / Side Panel on Mobile --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg purchase-bill-filter-dropdown" id="filterDropdownMenu">
                    <div class="filter-sidepanel-header">
                        <h5 class="dropdown-header px-0 mb-0 fw-semibold fs-5 text-dark">
                            <i class="ti ti-filter me-1 text-primary"></i> Filters
                        </h5>
                        <button type="button" class="btn-close d-md-none" id="btnCloseFilterDropdown" aria-label="Close"></button>
                    </div>

                    <div class="filter-sidepanel-body">
                        @if(!$isRestricted)
                            <div class="mb-3 text-start">
                                <label class="form-label fw-medium text-muted mb-1">Source Location</label>
                                <select id="filter-from-location" class="form-select">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 text-start">
                                <label class="form-label fw-medium text-muted mb-1">Destination Location</label>
                                <select id="filter-to-location" class="form-select">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1">Status</label>
                            <select id="filter-status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="1">Pending</option>
                                <option value="2">Accepted</option>
                                <option value="3">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1">Payment Status</label>
                            <select id="filter-payment-status" class="form-select">
                                <option value="">All Payments</option>
                                <option value="1">Pending</option>
                                <option value="2">Paid</option>
                                <option value="3">Partially Paid</option>
                            </select>
                        </div>

                        <div class="mb-3 text-start position-relative">
                            <label class="form-label fw-medium text-muted mb-1" for="filter-product">Product</label>
                            <select id="filter-product" class="form-select product-search-select" style="width: 100%;">
                                <option value="">All Products</option>
                            </select>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1">Date Range</label>
                            <div class="w-100">
                                <input type="text" id="filter-start-date" class="form-control flatpickr-purchase-bills mb-2" placeholder="Start Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                                <div class="text-center text-muted small mb-2">to</div>
                                <input type="text" id="filter-end-date" class="form-control flatpickr-purchase-bills" placeholder="End Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                            </div>
                        </div>
                    </div>

                    <div class="filter-sidepanel-footer">
                        <div class="filter-action-buttons">
                            <button type="button" class="btn btn-label-secondary btn-sm" id="btnClearFilter">
                                <i class="ti ti-refresh me-1"></i> Clear
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="btnApplyFilter">
                                <i class="ti ti-check me-1"></i> Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @can('export purchase bills')
                <button type="button" id="btnExportPurchaseBills" class="btn btn-success">
                    <i class="ti ti-file-spreadsheet me-1"></i> Export
                </button>
            @endcan

            @can('create purchase bills')
                <a href="{{ route('admin.purchase-bills.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add New Purchase Bill
                </a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="purchaseBillsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bill No</th>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Total Quantity</th>
                        <th>Amount</th>
                        <th>Total MRP</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                        <th class="d-none">Date Group</th>
                        <th class="d-none">Date Sort</th>
                        <th class="d-none">Amount Raw</th>
                        <th class="d-none">MRP Raw</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Total</th>
                        <th id="purchaseBillsTotalAmount"></th>
                        <th id="purchaseBillsTotalMrp"></th>
                        <th colspan="4"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script>
        $(document).ready(function () {
            let flatpickrOpen = false;
            let isSelect2Open = false;
            let isForceClosing = false;

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
                if (isForceClosing) {
                    return true;
                }
                if (flatpickrOpen || isSelect2Open) {
                    e.preventDefault();
                    return false;
                }
                if (e.clickEvent && $(e.clickEvent.target).closest('#filterDropdownContainer, #filterDropdownMenu, .select2-container, .select2-dropdown, .flatpickr-calendar').length) {
                    e.preventDefault();
                    return false;
                }
            });

            $(document).on('mousedown', '.flatpickr-calendar', function (e) {
                e.stopPropagation();
            });

            function closePurchaseBillFilterSidepanel() {
                isForceClosing = true;
                isSelect2Open = false;

                if ($('#filter-product').hasClass('select2-hidden-accessible')) {
                    try {
                        $('#filter-product').select2('close');
                    } catch (err) {}
                }

                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    try {
                        const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl);
                        if (dropdownInstance) {
                            dropdownInstance.hide();
                        }
                    } catch (err) {}
                }

                $('body').removeClass('filter-sidepanel-open');
                $('#filterDropdownContainer').removeClass('show');
                $('#filterDropdownContainer > button[data-bs-toggle="dropdown"]').removeClass('show').attr('aria-expanded', 'false');
                $('#filterDropdownContainer .dropdown-menu').removeClass('show');

                $('.filter-mobile-backdrop').removeClass('show');
                setTimeout(function () {
                    $('.filter-mobile-backdrop').remove();
                    isForceClosing = false;
                }, 280);
            }

            // Handle mobile backdrop and close button
            $('#filterDropdownContainer').on('show.bs.dropdown', function () {
                if (window.innerWidth < 768) {
                    $('body').addClass('filter-sidepanel-open');
                    if ($('.filter-mobile-backdrop').length === 0) {
                        const $backdrop = $('<div class="filter-mobile-backdrop"></div>');
                        $('body').append($backdrop);
                        setTimeout(function () {
                            $backdrop.addClass('show');
                        }, 10);
                    }
                }
            });

            $('#filterDropdownContainer').on('hidden.bs.dropdown', function () {
                $('body').removeClass('filter-sidepanel-open');
                $('.filter-mobile-backdrop').removeClass('show');
                if ($('#filter-product').hasClass('select2-hidden-accessible')) {
                    try {
                        $('#filter-product').select2('close');
                    } catch (err) {}
                }
                setTimeout(function () {
                    $('.filter-mobile-backdrop').remove();
                }, 280);
            });

            $(document).on('click', '#btnCloseFilterDropdown, .filter-mobile-backdrop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                closePurchaseBillFilterSidepanel();
            });

            $('.product-search-select').select2({
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
                dropdownParent: $('#filter-product').parent()
            });

            $('#filter-product').on('select2:open', function () {
                isSelect2Open = true;
                $('.select2-container--open, .select2-dropdown').css('z-index', 99999);
            });
            $('#filter-product').on('select2:close', function () {
                setTimeout(function () {
                    isSelect2Open = false;
                }, 100);
            });
            $('#filter-product').on('select2:unselecting select2:clearing', function (e) {
                isSelect2Open = false;
            });

            $(document).on('click mousedown touchstart pointerdown', '.select2-selection__clear, #filterDropdownContainer .select2-container, #filterDropdownMenu .select2-container, .select2-dropdown, .select2-results, .select2-search', function (e) {
                e.stopPropagation();
            });

            const table = $('#purchaseBillsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                order: [[12, 'desc']],
                ajax: {
                    url: '{{ route('admin.purchase-bills.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function (d) {
                        d.from_location_id = $('#filter-from-location').val();
                        d.to_location_id = $('#filter-to-location').val();
                        d.status = $('#filter-status').val();
                        d.payment_status = $('#filter-payment-status').val();
                        d.product_id = $('#filter-product').val();
                        d.start_date = $('#filter-start-date').val();
                        d.end_date = $('#filter-end-date').val();
                    }
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%', searchable: false },
                    {
                        data: 'transfer_no',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return String(data).replace(/<[^>]*>/g, '');
                            }
                            return data;
                        }
                    },
                    { data: 'from_location' },
                    { data: 'to_location' },
                    {
                        data: 'items_count',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return parseInt(data, 10) || 0;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'total_amount',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.total_amount_raw !== undefined ? parseFloat(row.total_amount_raw) : parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'total_mrp',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.total_mrp_raw !== undefined ? parseFloat(row.total_mrp_raw) : parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0;
                            }
                            return data;
                        }
                    },
                    { data: 'status', orderable: false },
                    { data: 'payment_status', orderable: false },
                    { data: 'created_by' },
                    { data: 'actions', orderable: false },
                    { data: 'date_group', visible: false },
                    { data: 'date_sort', visible: false },
                    { data: 'total_amount_raw', visible: false, searchable: false },
                    { data: 'total_mrp_raw', visible: false, searchable: false },
                ],
                footerCallback: function (row, data, start, end, display) {
                    const json = this.api().ajax.json();
                    if (json && json.grand_total_amount) {
                        $('#purchaseBillsTotalAmount').html('<span style="white-space: nowrap;">' + json.grand_total_amount + '</span>');
                        $('#purchaseBillsTotalMrp').html('<span style="white-space: nowrap;">' + json.grand_total_mrp + '</span>');
                    }
                },
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="11" class="text-center bg-light fw-semibold"><i class="ti ti-calendar-event me-1"></i>' + group + ' <span class="badge bg-label-primary ms-1">' + rows.count() + '</span></td>');
                    }
                },
            });

            table.on('draw.dt', function () {
                $('#purchaseBillsTable_processing').css('display', 'none');
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $('#btnExportPurchaseBills').on('click', function () {
                const params = $.param({
                    from_location_id: $('#filter-from-location').val() || '',
                    to_location_id: $('#filter-to-location').val() || '',
                    status: $('#filter-status').val() || '',
                    payment_status: $('#filter-payment-status').val() || '',
                    product_id: $('#filter-product').val() || '',
                    start_date: $('#filter-start-date').val() || '',
                    end_date: $('#filter-end-date').val() || '',
                });
                window.location.href = '{{ route('admin.purchase-bills.export') }}?' + params;
            });

            $('#btnApplyFilter').on('click', function (e) {
                e.preventDefault();
                window.refreshTable();
                closePurchaseBillFilterSidepanel();
            });

            $('#btnClearFilter').on('click', function (e) {
                e.preventDefault();
                $('#filter-from-location, #filter-to-location, #filter-status, #filter-payment-status').val('');
                $('#filter-product').val(null).trigger('change');
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', null);
                endPicker.set('minDate', null);
                window.refreshTable();
                closePurchaseBillFilterSidepanel();
            });

            $(document).on('click', '.purchase-bill-action', function () {
                const button = $(this);
                Swal.fire({
                    title: button.data('title'),
                    text: button.data('text'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    window.showAjaxLoader();
                    $.ajax({
                        url: button.data('url'),
                        type: button.data('method') || 'PATCH',
                        data: { _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function (res) {
                            window.hideAjaxLoader();
                            toastr.success(res.message);
                            window.refreshTable();
                            if (typeof window.refreshPurchaseBillBadge === 'function') {
                                window.refreshPurchaseBillBadge();
                            }
                        },
                        error: function (xhr) {
                            window.hideAjaxLoader();
                            const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                            toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                        }
                    });
                });
            });

            function buildPurchaseBillPaymentHistoryHtml(historyData) {
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
                                <thead class="table-light"><tr><th>Date</th><th class="text-end">Amount</th></tr></thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            $(document).on('click', '.change-purchase-bill-payment-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const historyUrl = $(this).data('history-url');
                const currentPaymentStatus = $(this).data('current');

                if (historyUrl) {
                    $.get(historyUrl)
                        .done(function (res) {
                            openPurchaseBillPaymentStatusModal(url, currentPaymentStatus, res.data);
                        })
                        .fail(function () {
                            openPurchaseBillPaymentStatusModal(url, currentPaymentStatus, null);
                        });
                } else {
                    openPurchaseBillPaymentStatusModal(url, currentPaymentStatus, null);
                }
            });

            function openPurchaseBillPaymentStatusModal(url, currentPaymentStatus, historyData) {
                Swal.fire({
                    title: 'Update Payment Status',
                    html: `
                        ${buildPurchaseBillPaymentHistoryHtml(historyData)}
                        <div class="mb-3 text-start">
                            <label for="swal-pb-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                            <select id="swal-pb-payment-status" class="form-select form-select-md">
                                <option value="1" ${currentPaymentStatus == 1 ? 'selected' : 'disabled'}>Pending</option>
                                <option value="3" ${currentPaymentStatus == 3 ? 'selected' : ''}>Partially Paid</option>
                                <option value="2" ${currentPaymentStatus == 2 ? 'selected' : ''}>Paid</option>
                            </select>
                        </div>
                        <div class="mb-3 text-start d-none" id="swal-pb-amount-wrapper">
                            <label for="swal-pb-payment-amount" class="form-label fw-semibold mb-2">Amount Paid Now</label>
                            <input type="number" id="swal-pb-payment-amount" class="form-control form-control-md" min="0.01" step="0.01" placeholder="Enter amount paid" />
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
                        const statusSelect = document.getElementById('swal-pb-payment-status');
                        const amountWrapper = document.getElementById('swal-pb-amount-wrapper');
                        const toggleAmount = () => {
                            amountWrapper.classList.toggle('d-none', statusSelect.value !== '3');
                        };
                        toggleAmount();
                        statusSelect.addEventListener('change', toggleAmount);
                    },
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const status = document.getElementById('swal-pb-payment-status').value;
                        const amount = document.getElementById('swal-pb-payment-amount').value;
                        if (status === '3') {
                            if (!amount || parseFloat(amount) <= 0) {
                                Swal.showValidationMessage('The amount field must be at least 0.01.');
                                return false;
                            }
                            const balanceDue = historyData ? parseFloat(historyData.balance_due_raw) : null;
                            if (balanceDue !== null && !isNaN(balanceDue) && parseFloat(amount) > balanceDue) {
                                Swal.showValidationMessage(`Paid amount cannot be greater than the remaining balance due (${historyData.balance_due}).`);
                                return false;
                            }
                        }

                        return $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                payment_status: status,
                                amount: amount
                            }
                        }).then(function (res) {
                            if (res.status === 'success') {
                                toastr.success(res.message);
                                window.refreshTable();
                            } else {
                                Swal.showValidationMessage(res.message || 'Something went wrong.');
                            }
                        }).catch(function (xhr) {
                            const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                            Swal.showValidationMessage(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                        });
                    }
                });
            }
        });
    </script>
@endsection
