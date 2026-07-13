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

            @can('import purchases')
                <button type="button" class="btn btn-outline-primary" id="purchaseImportBtn">
                    <i class="ti ti-file-spreadsheet me-1"></i> Import Purchases
                </button>
            @endcan
            @can('create purchases')
                <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Purchase
                </a>
            @endcan
        </div>
    </div>

    @can('import purchases')
        <div class="offcanvas offcanvas-end" id="purchaseImportOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 600px; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Import Purchases</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column" style="overflow: hidden;" id="purchaseImportOffcanvasBody">
                @include('purchases.purchase_import')
            </div>
        </div>

        <div class="offcanvas offcanvas-end" id="purchaseImportHistoryOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 50vw; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Purchase Import Details & History</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-4" style="overflow-y: auto;">
                <h6 class="fw-semibold mb-3">Import Summary</h6>
                <div class="row g-3 mb-4" id="purchaseImportHistorySummaryCards"></div>

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h6 class="mb-0 fw-semibold">Barcode-wise Report</h6>
                    <div style="width: 250px; max-width: 100%;">
                        <input type="text" id="purchaseImportHistorySearchInput" class="form-control" placeholder="Search Barcode or Product..." />
                    </div>
                </div>

                <div class="table-responsive border rounded-3" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light position-sticky top-0" style="z-index: 1;">
                            <tr>
                                <th>Barcode</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="purchaseImportHistoryTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
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

            // Prevent Bootstrap dropdown from closing when clicking on Select2
            $('#filterDropdownContainer').on('click', '.select2-container', function (e) {
                e.stopPropagation();
            });

            const table = $('#purchasesTable').DataTable({
                responsive : false,
                order      : [[8, 'desc']],
                orderFixed : { pre: [[8, 'desc']] },
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
                    { data: 'invoice_no' },
                    { data: 'supplier' },
                    { data: 'total_amount' },
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

            $(document).on('click', '.change-purchase-payment-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const currentPaymentStatus = $(this).data('current');

                Swal.fire({
                    title: 'Update Payment Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label for="swal-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                            <select id="swal-payment-status" class="form-select form-select-lg">
                                <option value="1" ${currentPaymentStatus == 1 ? 'selected' : ''}>Pending</option>
                                <option value="2" ${currentPaymentStatus == 2 ? 'selected' : ''}>Paid</option>
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
                        return document.getElementById('swal-payment-status').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        window.showAjaxLoader();
                        $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                payment_status: result.value
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
