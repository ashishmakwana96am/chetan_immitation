@extends('layouts.app')

@section('title', 'Purchases')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <style>
        #purchasesTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #purchasesTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #purchasesTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #purchasesTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
        @media (max-width: 991.98px) {
            #purchaseImportHistoryOffcanvas { width: 100vw !important; }
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Purchases</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>

                    {{-- Supplier --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-supplier">Supplier</label>
                        <select id="filter-supplier" class="form-select">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-status">Purchase Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1">Pending</option>
                            <option value="2">Approved</option>
                            <option value="3">Declined</option>
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
                            <input type="text" id="filter-start-date" class="form-control flatpickr-purchases mb-2" placeholder="Start Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                            <div class="text-center text-muted small mb-2">to</div>
                            <input type="text" id="filter-end-date" class="form-control flatpickr-purchases" placeholder="End Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>

            @can('create purchases')
                <button type="button" class="btn btn-outline-primary" id="purchaseImportBtn">
                    <i class="ti ti-file-spreadsheet me-1"></i> Import
                </button>
            @endcan
            @can('create purchases')
                <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Purchase
                </a>
            @endcan
        </div>
    </div>

    @can('create purchases')
        <div class="offcanvas offcanvas-end" id="purchaseImportOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 600px; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Import Purchases</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column" style="overflow: hidden;" id="purchaseImportOffcanvasBody">
                @include('purchases.purchase_import')
            </div>
        </div>

        <div class="offcanvas offcanvas-end" id="purchaseImportHistoryOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 55vw; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Purchase Import Details & History</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-4" style="overflow-y: auto; overflow-x: hidden;">
                <h6 class="fw-semibold mb-3">Import Summary</h6>
                <div class="row g-3 mb-3" id="purchaseImportHistorySummaryCards"></div>

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h6 class="fw-semibold mb-0">Barcode-wise Report</h6>
                    <div id="purchaseImportPrintBarcodeWrapper" class="d-none">
                        <button type="button" id="purchaseImportPrintBarcodeBtn" class="btn btn-label-primary btn-sm">
                            <i class="ti ti-printer me-1"></i> Print Barcode
                        </button>
                    </div>
                </div>
                <div class="card-datatable table-responsive">
                    <table class="table table-hover border-top" id="purchaseImportHistoryTable" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="purchaseImportHistoryTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="offcanvas offcanvas-end" id="purchaseImportPreviewOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 65vw; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Review Before Import</h5>
                <button type="button" class="btn-close text-reset" id="purchaseImportPreviewCloseBtn" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-4 d-flex flex-column" style="overflow-y: auto; overflow-x: hidden;">
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="ti ti-info-circle me-2 fs-5"></i>
                    <div>Nothing has been saved yet. Review the details below, then click <strong>Confirm &amp; Import</strong> to actually create the purchase(s).</div>
                </div>

                <h6 class="fw-semibold mb-3">Summary</h6>
                <div class="row g-3 mb-4" id="purchaseImportPreviewSummaryCards"></div>

                <div id="purchaseImportPreviewNewProductsWrap" class="mb-4 d-none">
                    <h6 class="fw-semibold mb-2"><i class="ti ti-package me-1"></i> New Products <span class="badge bg-label-info" id="purchaseImportPreviewNewProductsCount"></span></h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Barcode</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th class="text-end">Purchase Price</th>
                                    <th class="text-end">Sale Price</th>
                                    <th class="text-end">MRP</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseImportPreviewNewProductsBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="purchaseImportPreviewUpdatedProductsWrap" class="mb-4 d-none">
                    <h6 class="fw-semibold mb-2"><i class="ti ti-refresh me-1"></i> Existing Products Being Reused <span class="badge bg-label-warning" id="purchaseImportPreviewUpdatedProductsCount"></span></h6>
                    <small class="text-muted d-block mb-2">These products already exist (matched by barcode). Fields below will be overwritten with the Excel's values.</small>
                    <div id="purchaseImportPreviewUpdatedProductsBody"></div>
                </div>

                <div id="purchaseImportPreviewPurchasesWrap" class="mb-4 d-none">
                    <h6 class="fw-semibold mb-2"><i class="ti ti-shopping-cart me-1"></i> Purchases to be Created</h6>
                    <div class="card-datatable table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="purchaseImportPreviewPurchasesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Supplier</th>
                                    <th class="text-center">Items</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th>Status</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseImportPreviewPurchasesBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="purchaseImportPreviewFailuresWrap" class="mb-4 d-none">
                    <h6 class="fw-semibold mb-2 text-danger"><i class="ti ti-alert-triangle me-1"></i> Rows That Will Be Skipped</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Row</th>
                                    <th>Product</th>
                                    <th>Barcode</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseImportPreviewFailuresBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="d-flex p-4 border-top gap-3 mt-auto mb-0">
                <button type="button" class="btn btn-primary flex-fill w-50 m-0" id="purchaseImportPreviewConfirmBtn">
                    <i class="ti ti-check me-1"></i> Confirm &amp; Import
                </button>
                <button type="button" class="btn btn-label-secondary flex-fill w-50 m-0" id="purchaseImportPreviewCancelBtn">Cancel</button>
            </div>
        </div>
    @endcan

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="purchasesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Purchase No</th>
                        <th>Supplier</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Payment Status</th>
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
        // ─── Purchase Barcode Print (reuses admin.products.print-barcodes as-is) ──
        const purchaseBarcodeItemsUrlTemplate = '{{ route("admin.purchases.barcode-items", ["purchase" => "__ID__"]) }}';

        window.openPurchaseBarcodeModal = function (purchaseIds) {
            const ids = Array.isArray(purchaseIds) ? purchaseIds : [purchaseIds];
            if (!ids.length) return;

            Promise.all(ids.map(function (id) {
                return $.get(purchaseBarcodeItemsUrlTemplate.replace('__ID__', id));
            })).then(function (responses) {
                let itemsMap = {};
                responses.forEach(function (res) {
                    if (res && res.status === 'success' && res.items) {
                        res.items.forEach(function (item) {
                            const key = `${item.id}_${item.selected_variant_id || ''}_${item.custom_size_value || ''}`;
                            if (itemsMap[key]) {
                                itemsMap[key].quantity += item.quantity;
                            } else {
                                itemsMap[key] = Object.assign({}, item);
                            }
                        });
                    }
                });

                const items = Object.values(itemsMap);

                if (items.length === 0) {
                    toastr.warning('No barcoded products found for this purchase.');
                    return;
                }

                $('#purchaseBarcodeModal').remove();

                let listHtml = '';
                items.forEach(function (item) {
                    let customSizeSelectHtml = '';
                    if (item.pair_product && item.pair_mode === 'custom_size' && item.custom_sizes && item.custom_sizes.length) {
                        customSizeSelectHtml = '<div class="mt-1 d-flex align-items-center gap-1"><small class="text-secondary fw-medium">Size:</small><select class="form-select form-select-sm purchase-bulk-item-custom-size" style="width: auto; min-width: 90px;">';
                        item.custom_sizes.forEach(function(cs) {
                            const sizeVal = typeof cs === 'object' && cs !== null ? cs.size : cs;
                            const sizeNum = parseFloat(sizeVal);
                            const label = String(sizeVal).includes('pcs') ? sizeVal : sizeVal + ' pcs';
                            const isSelected = item.custom_size_value && Math.abs(parseFloat(item.custom_size_value) - sizeNum) < 0.001;
                            customSizeSelectHtml += `<option value="${label}" ${isSelected ? 'selected' : ''}>${label}</option>`;
                        });
                        customSizeSelectHtml += '</select></div>';
                    }

                    // Variant: auto-selected from purchase
                    let variantHtml = '';
                    if (item.variant_label) {
                        variantHtml = `<div class="mt-1 d-flex align-items-center gap-1"><small class="text-secondary fw-medium">Variant:</small><span class="badge bg-label-info">${item.variant_label}</span></div>`;
                    }

                    listHtml += `
                        <tr class="purchase-bulk-item-row" data-id="${item.id}" data-barcode="${item.barcode}" data-variant-id="${item.selected_variant_id || ''}">
                            <td>
                                <div class="fw-semibold text-dark">${item.name}</div>
                                ${customSizeSelectHtml}
                                ${variantHtml}
                            </td>
                            <td><code>${item.barcode}</code></td>
                            <td><input type="number" class="form-control form-control-sm purchase-bulk-item-qty" value="${item.quantity}" min="1" max="1000"></td>
                        </tr>
                    `;
                });

                const modalHtml = `
                    <div class="modal fade" id="purchaseBarcodeModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-bottom">
                                    <h5 class="modal-title fw-semibold">Print Barcode</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3 d-flex align-items-center gap-2 bg-light p-2 rounded">
                                        <label for="purchaseBulkDefaultQty" class="form-label mb-0 fw-medium small text-secondary">Set Qty for All:</label>
                                        <input type="number" id="purchaseBulkDefaultQty" class="form-control form-control-sm w-25" placeholder="Qty" min="1">
                                        <button type="button" id="applyPurchaseBulkDefaultQty" class="btn btn-sm btn-primary">Apply</button>
                                    </div>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Barcode</th>
                                                    <th style="width: 100px;">Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>${listHtml}</tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer border-top-0 pt-0">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="startPurchaseBarcodePrintBtn">
                                        <i class="ti ti-printer me-1"></i> Print Barcodes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('body').append(modalHtml);
                new bootstrap.Modal(document.getElementById('purchaseBarcodeModal')).show();
            }).catch(function () {
                toastr.error('Failed to load purchase items for barcode printing.');
            });
        };

        $(document).on('click', '.purchase-print-barcode-btn', function () {
            const ids = String($(this).data('purchase-ids')).split(',').map(Number).filter(Boolean);
            window.openPurchaseBarcodeModal(ids);
        });

        $(document).on('click', '#applyPurchaseBulkDefaultQty', function () {
            const val = parseInt($('#purchaseBulkDefaultQty').val()) || 1;
            $('.purchase-bulk-item-qty').val(val);
        });

        $(document).on('click', '#startPurchaseBarcodePrintBtn', function () {
            const items = [];
            $('.purchase-bulk-item-row').each(function () {
                const id = $(this).data('id');
                const qty = parseInt($(this).find('.purchase-bulk-item-qty').val()) || 1;
                const item = { id: id, qty: qty };
                const $sizeSelect = $(this).find('.purchase-bulk-item-custom-size');
                if ($sizeSelect.length > 0 && $sizeSelect.val()) {
                    item.selected_size = $sizeSelect.val();
                }
                const variantId = $(this).data('variant-id');
                if (variantId) {
                    item.selected_variant_id = variantId;
                }
                items.push(item);
            });

            window.startBarcodePrint(items);

            bootstrap.Modal.getInstance(document.getElementById('purchaseBarcodeModal')).hide();
        });

        $(document).ready(function () {
            // Track if any flatpickr calendar is open — prevent Bootstrap dropdown from closing
            let flatpickrOpen = false;

            // Initialize Flatpickr for date filters
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

            // Block Bootstrap dropdown from closing while flatpickr calendar is open
            $('#filterDropdownContainer').on('hide.bs.dropdown', function (e) {
                if (flatpickrOpen) {
                    e.preventDefault();
                    return false;
                }
            });

            // Also stop mousedown propagation from flatpickr calendar (extra safety)
            $(document).on('mousedown', '.flatpickr-calendar', function (e) {
                e.stopPropagation();
            });

            // Initialize Select2 for product search
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

            const table = $('#purchasesTable').DataTable({
                responsive : false,
                order      : [[8, 'desc']],
                ajax       : {
                    url: '{{ route('admin.purchases.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.supplier_id = $('#filter-supplier').val();
                        d.status = $('#filter-status').val();
                        d.payment_status = $('#filter-payment-status').val();
                        d.product_id = $('#filter-product').val();
                        d.start_date = $('#filter-start-date').val();
                        d.end_date = $('#filter-end-date').val();
                    }
                },
                columns    : [
                    {
                        data: null,
                        width: '5%',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'invoice_no',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_invoice_no !== undefined ? row.raw_invoice_no : String(data).replace(/<[^>]*>/g, '');
                            }
                            return data;
                        }
                    },
                    { data: 'supplier' },
                    {
                        data: 'total_amount',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_total_amount !== undefined ? parseFloat(row.raw_total_amount) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return data;
                        }
                    },
                    { data: 'status',         orderable: false },
                    { data: 'payment_status', orderable: false },
                    { data: 'actions',        orderable: false },
                    { data: 'date_group',     visible: false },
                    { data: 'date_sort',      visible: false },
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="8"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' purchase' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '.change-purchase-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const currentStatus = $(this).data('current');

                Swal.fire({
                    title: 'Update Purchase Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label for="swal-purchase-status" class="form-label fw-semibold mb-2">Select Purchase Status</label>
                            <select id="swal-purchase-status" class="form-select form-select-lg">
                                <option value="1" ${currentStatus == 1 ? 'selected' : ''}>Pending</option>
                                <option value="2" ${currentStatus == 2 ? 'selected' : ''}>Approve</option>
                                <option value="3" ${currentStatus == 3 ? 'selected' : ''}>Decline</option>
                            </select>
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
                    preConfirm: () => {
                        return document.getElementById('swal-purchase-status').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        window.showAjaxLoader();
                        $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                status: result.value
                            },
                            success: function (res) {
                                window.hideAjaxLoader();
                                if (res.status === 'success') {
                                    toastr.success(res.message);
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

            function buildPaymentHistoryHtml(historyData) {
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

            $(document).on('click', '.change-purchase-payment-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const historyUrl = $(this).data('history-url');
                const currentPaymentStatus = $(this).data('current');

                $.get(historyUrl)
                    .done(function (res) {
                        openPaymentStatusModal(url, currentPaymentStatus, res.data);
                    })
                    .fail(function () {
                        openPaymentStatusModal(url, currentPaymentStatus, null);
                    });
            });

            function openPaymentStatusModal(url, currentPaymentStatus, historyData) {
                Swal.fire({
                    title: 'Update Payment Status',
                    html: `
                        ${buildPaymentHistoryHtml(historyData)}
                        <div class="mb-3 text-start">
                            <label for="swal-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                            <select id="swal-payment-status" class="form-select form-select-md">
                                <option value="1" ${currentPaymentStatus == 1 ? 'selected' : 'disabled'}>Pending</option>
                                <option value="3" ${currentPaymentStatus == 3 ? 'selected' : ''}>Partially Paid</option>
                                <option value="2" ${currentPaymentStatus == 2 ? 'selected' : ''}>Paid</option>
                            </select>
                        </div>
                        <div class="mb-3 text-start d-none" id="swal-amount-wrapper">
                            <label for="swal-payment-amount" class="form-label fw-semibold mb-2">Amount Paid Now</label>
                            <input type="number" id="swal-payment-amount" class="form-control form-control-md" min="0.01" step="0.01" placeholder="Enter amount paid" />
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
                        const amountWrapper = document.getElementById('swal-amount-wrapper');
                        const toggleAmount = () => {
                            amountWrapper.classList.toggle('d-none', statusSelect.value !== '3');
                        };
                        toggleAmount();
                        statusSelect.addEventListener('change', toggleAmount);
                    },
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const status = document.getElementById('swal-payment-status').value;
                        const amount = document.getElementById('swal-payment-amount').value;
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
                            if (res.status !== 'success') {
                                Swal.showValidationMessage(res.message || 'Something went wrong.');
                                return false;
                            }
                            return res;
                        }).catch(function (xhr) {
                            const resJson = xhr.responseJSON;
                            let msg = 'Something went wrong. Please try again.';
                            if (resJson) {
                                if (typeof resJson.message === 'string' && resJson.message.trim() !== '') {
                                    msg = resJson.message;
                                } else if (resJson.message && typeof resJson.message === 'object') {
                                    const keys = Object.keys(resJson.message);
                                    if (keys.length) {
                                        const val = resJson.message[keys[0]];
                                        msg = Array.isArray(val) ? val[0] : String(val);
                                    }
                                } else if (resJson.errors && typeof resJson.errors === 'object') {
                                    const keys = Object.keys(resJson.errors);
                                    if (keys.length) {
                                        const val = resJson.errors[keys[0]];
                                        msg = Array.isArray(val) ? val[0] : String(val);
                                    }
                                }
                            }
                            Swal.showValidationMessage(msg);
                            return false;
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        toastr.success(result.value.message || 'Payment status updated successfully.');
                        window.refreshTable();
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
                $('#filter-supplier').val('');
                $('#filter-status').val('');
                $('#filter-payment-status').val('');
                $('#filter-product').val('').trigger('change');
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

            // Import Purchases — side panel (content is already server-rendered
            // in the DOM, so opening it is instant with no AJAX fetch/spinner delay)
            $(document).on('click', '#purchaseImportBtn', function () {
                const offcanvasEl = document.getElementById('purchaseImportOffcanvas');
                bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).show();
            });
        });
    </script>
@endsection
