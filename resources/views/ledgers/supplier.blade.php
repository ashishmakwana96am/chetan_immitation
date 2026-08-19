@extends('layouts.app')

@section('title', 'Supplier Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <style>
        #supplierLedgerTable tbody tr {
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Supplier Ledger</h4>
            <small class="text-muted">Company-wide across all branches</small>
        </div>
        <button type="button" id="exportPdfBtn" class="btn btn-danger report-export-btn">
            <i class="ti ti-file-text me-1"></i> Export to PDF
        </button>
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
                            <span class="text-muted small">Purchase</span>
                            <span class="fw-semibold" id="purchase-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Payment</span>
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
                    <label class="form-label">As on Date</label>
                    <input type="text" id="filter-as-on-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
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
                <div class="col-12 d-flex justify-content-end gap-2 mt-4" id="filterActionButtons">
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
            <table class="table border-top" id="supplierLedgerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Supplier</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Due Amount</th>
                        @if($canManageAdvance)
                            <th>Advance Balance</th>
                        @endif
                        <th>Action</th>
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
            const canManageAdvance = @json($canManageAdvance);
            const asOnDatePicker = $('#filter-as-on-date').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                defaultDate: 'today'
            });

            function currentFilters() {
                return {
                    as_on_date: $('#filter-as-on-date').val(),
                    location_id: $('#filter-location').val() || '',
                };
            }

            const columns = [
                { data: 'index', orderable: false, width: '5%' },
                { data: 'supplier' },
                { data: 'total_amount' },
                { 
                    data: 'paid_amount',
                    render: function (data, type, row) {
                        return `<span class="text-success fw-semibold">${data}</span>`;
                    }
                },
                { 
                    data: 'due_amount',
                    render: function (data, type, row) {
                        return `<span class="text-danger fw-semibold">${data}</span>`;
                    }
                }
            ];

            if (canManageAdvance) {
                columns.push({
                    data: 'advance_balance',
                    render: function (data, type, row) {
                        if (row.raw_advance_balance > 0) {
                            return `<span class="badge bg-label-success fw-bold fs-6">${data}</span>`;
                        }
                        return `<span class="text-muted">${data}</span>`;
                    }
                });
            }

            columns.push({ 
                data: null, 
                orderable: false, 
                render: function (data, type, row) {
                    const locationVal = $('#filter-location').val() || '';
                    const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                    const asOnDate = $('#filter-as-on-date').val() || '';
                    return `
                        <div class="dropdown table-action-dropdown">
                            <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                <span>Actions</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                <a href="{{ route('admin.ledgers.supplier.detail') }}?supplier_id=${row.supplier_id}&as_on_date=${asOnDate}${locationQuery}" class="dropdown-item">
                                    <i class="ti ti-eye me-2"></i>View
                                </a>
                                ${canManageAdvance ? `
                                <a href="{{ route('admin.ledgers.supplier.advance-history') }}?supplier_id=${row.supplier_id}" class="dropdown-item">
                                    <i class="ti ti-history me-2"></i>Advance History
                                </a>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }
            });

            const table = $('#supplierLedgerTable').DataTable({
                responsive: false,
                order: [[4, 'desc']],
                ajax: {
                    url: '{{ route('admin.ledgers.supplier.data') }}',
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
                columns: columns,
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
                window.refreshTable();
            });

            $(document).on('click', '#clearFiltersBtn', function (e) {
                e.preventDefault();
                asOnDatePicker.setDate('today', true);
                $('#filter-location').val(null).trigger('change');
                window.refreshTable();
            });

            $(document).on('click', '#exportPdfBtn', function () {
                const params = new URLSearchParams();
                const filters = currentFilters();
                Object.keys(filters).forEach(function (key) {
                    if (filters[key] !== '' && filters[key] !== null && filters[key] !== undefined) {
                        params.append(key, filters[key]);
                    }
                });
                params.append('auto_print', '1');
                window.open("{{ route('admin.ledgers.supplier.export') }}?" + params.toString(), '_blank');
            });

            // Double click anywhere on row to navigate to details page
            $('#supplierLedgerTable tbody').on('dblclick', 'tr:not(.group-header)', function (e) {
                if ($(e.target).closest('.dropdown').length || $(e.target).closest('button').length || $(e.target).closest('a').length) {
                    return;
                }
                const data = table.row(this).data();
                if (data) {
                    const locationVal = $('#filter-location').val() || '';
                    const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                    const asOnDate = $('#filter-as-on-date').val() || '';
                    window.location.href = `{{ route('admin.ledgers.supplier.detail') }}?supplier_id=${data.supplier_id}&as_on_date=${asOnDate}${locationQuery}`;
                }
            });
        });
    </script>
@endsection
