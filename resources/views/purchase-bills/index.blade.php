@extends('layouts.app')

@section('title', 'Purchase Bill')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Purchase Bill</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 360px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>
                    @if(!$isRestricted)
                        <div class="mb-3">
                            <label class="form-label">Source Location</label>
                            <select id="filter-from-location" class="form-select">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Destination Location</label>
                            <select id="filter-to-location" class="form-select">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1">Pending</option>
                            <option value="2">Accepted</option>
                            <option value="3">Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <select id="filter-payment-status" class="form-select">
                            <option value="">All Payments</option>
                            <option value="1">Pending</option>
                            <option value="2">Paid</option>
                        </select>
                    </div>

                    <div class="mb-3 text-start">
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

                    <div class="dropdown-divider"></div>
                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
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
            if ($('.flatpickr-purchase-bills').length) {
                $('.flatpickr-purchase-bills').flatpickr({
                    dateFormat: 'Y-m-d',
                    allowInput: true
                });
            }

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
                dropdownParent: $('#filterDropdownContainer')
            });

            $('#filterDropdownContainer').on('click', '.select2-container', function (e) {
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
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return parseInt(data, 10) || 0;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'total_amount',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.total_amount_raw !== undefined ? row.total_amount_raw : parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'total_mrp',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.total_mrp_raw !== undefined ? row.total_mrp_raw : parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0;
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

            $('#btnApplyFilter').on('click', function () {
                window.refreshTable();
                bootstrap.Dropdown.getOrCreateInstance(document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]')).hide();
            });

            $('#btnClearFilter').on('click', function () {
                $('#filter-from-location, #filter-to-location, #filter-status, #filter-payment-status, #filter-start-date, #filter-end-date').val('');
                $('#filter-product').val(null).trigger('change');
                window.refreshTable();
                bootstrap.Dropdown.getOrCreateInstance(document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]')).hide();
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

            $(document).on('click', '.change-purchase-bill-payment-status-btn', function (e) {
                e.preventDefault();
                const url = $(this).data('url');
                const currentPaymentStatus = $(this).data('current');

                Swal.fire({
                    title: 'Update Payment Status',
                    html: `
                        <div class="mb-3 text-start">
                            <label for="swal-purchase-bill-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                            <select id="swal-purchase-bill-payment-status" class="form-select form-select-lg">
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
                        return document.getElementById('swal-purchase-bill-payment-status').value;
                    }
                }).then((result) => {
                    if (!result.isConfirmed || !result.value) return;
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
                            toastr.success(res.message);
                            window.refreshTable();
                        },
                        error: function (xhr) {
                            window.hideAjaxLoader();
                            const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                            toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                        }
                    });
                });
            });
        });
    </script>
@endsection
