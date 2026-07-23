@extends('layouts.app')

@section('title', 'Purchase Bill')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
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
                    <div class="dropdown-divider"></div>
                    <div class="d-flex gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply</button>
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
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Total MRP</th>
                        <th>Status</th>
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
                        <th colspan="3"></th>
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
    <script>
        $(document).ready(function () {
            const table = $('#purchaseBillsTable').DataTable({
                responsive: false,
                order: [[11, 'desc']],
                orderFixed: { pre: [[11, 'desc']] },
                ajax: {
                    url: '{{ route('admin.purchase-bills.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function (d) {
                        d.from_location_id = $('#filter-from-location').val();
                        d.to_location_id = $('#filter-to-location').val();
                        d.status = $('#filter-status').val();
                    }
                },
                columns: [
                    {
                        data: null,
                        width: '5%',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { data: 'transfer_no' },
                    { data: 'from_location' },
                    { data: 'to_location' },
                    { data: 'items_count' },
                    { data: 'total_amount' },
                    { data: 'total_mrp' },
                    { data: 'status', orderable: false },
                    { data: 'created_by' },
                    { data: 'actions', orderable: false },
                    { data: 'date_group', visible: false },
                    { data: 'date_sort', visible: false },
                    { data: 'total_amount_raw', visible: false, searchable: false },
                    { data: 'total_mrp_raw', visible: false, searchable: false },
                ],
                footerCallback: function () {
                    const api = this.api();
                    const total = api.column(12, { search: 'applied' }).data().reduce(function (a, b) {
                        return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                    }, 0);
                    const totalMrp = api.column(13, { search: 'applied' }).data().reduce(function (a, b) {
                        return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                    }, 0);
                    $('#purchaseBillsTotalAmount').html('{!! currency_symbol() !!} ' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $('#purchaseBillsTotalMrp').html('{!! currency_symbol() !!} ' + totalMrp.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                },
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="10" class="text-center bg-light fw-semibold"><i class="ti ti-calendar-event me-1"></i>' + group + ' <span class="badge bg-label-primary ms-1">' + rows.count() + '</span></td>');
                    }
                },
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $('#btnExportPurchaseBills').on('click', function () {
                const params = $.param({
                    from_location_id: $('#filter-from-location').val() || '',
                    to_location_id: $('#filter-to-location').val() || '',
                    status: $('#filter-status').val() || '',
                });
                window.location.href = '{{ route('admin.purchase-bills.export') }}?' + params;
            });

            $('#btnApplyFilter').on('click', function () {
                window.refreshTable();
                bootstrap.Dropdown.getOrCreateInstance(document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]')).hide();
            });

            $('#btnClearFilter').on('click', function () {
                $('#filter-from-location, #filter-to-location, #filter-status').val('');
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
        });
    </script>
@endsection
