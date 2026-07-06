@extends('layouts.app')

@section('title', 'Stock Transfers')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Stock Transfers</h4>
        <div class="d-flex gap-2 align-items-center">
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

            @can('create stock transfers')
                <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Transfer
                </a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="stockTransfersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Transfer No</th>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Created By</th>
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
    <script>
        $(document).ready(function () {
            const table = $('#stockTransfersTable').DataTable({
                responsive: false,
                order: [[9, 'desc']],
                orderFixed: { pre: [[9, 'desc']] },
                ajax: {
                    url: '{{ route('admin.stock-transfers.data') }}',
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
                    { data: 'status', orderable: false },
                    { data: 'created_by' },
                    { data: 'actions', orderable: false },
                    { data: 'date_group', visible: false },
                    { data: 'date_sort', visible: false },
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="9" class="text-center bg-light fw-semibold"><i class="ti ti-calendar-event me-1"></i>' + group + ' <span class="badge bg-label-primary ms-1">' + rows.count() + '</span></td>');
                    }
                },
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $('#btnApplyFilter').on('click', function () {
                window.refreshTable();
                bootstrap.Dropdown.getOrCreateInstance(document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]')).hide();
            });

            $('#btnClearFilter').on('click', function () {
                $('#filter-from-location, #filter-to-location, #filter-status').val('');
                window.refreshTable();
                bootstrap.Dropdown.getOrCreateInstance(document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]')).hide();
            });

            $(document).on('click', '.stock-transfer-action', function () {
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
