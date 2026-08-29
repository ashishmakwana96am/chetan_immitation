@extends('layouts.app')

@section('title', 'Customer Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #customerLedgerTable tbody tr:not(.group-header) {
            cursor: pointer;
        }
        #customerLedgerTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #customerLedgerTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #customerLedgerTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #customerLedgerTable tbody tr.group-header td .group-header-inner span {
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
            <h4 class="fw-semibold mb-0">Customer Ledger</h4>
            <small class="text-muted">Company-wide across all branches</small>
        </div>
        <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
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
                            <span class="text-muted small">Total Sales</span>
                            <span class="fw-semibold" id="sales-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Received</span>
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
            <table class="table border-top" id="customerLedgerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Due Amount</th>
                        <th>Action</th>
                        <th>Date Group</th>
                        <th>Date Sort</th>
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

            const table = $('#customerLedgerTable').DataTable({
                responsive: false,
                order: [[4, 'desc']],
                columnDefs: [
                    { targets: [6, 7], visible: false }
                ],
                ajax: {
                    url: '{{ route('admin.ledgers.customer.data') }}',
                    cache: false,
                    data: function (d) { Object.assign(d, currentFilters()); },
                    dataSrc: function (json) {
                        if (json.branch_summary) {
                            $.each(json.branch_summary, function (locId, s) {
                                $('#sales-' + locId).text(s.sales);
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
                        data: 'customer',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_customer !== undefined ? row.raw_customer : String(data).replace(/<[^>]*>/g, '');
                            }
                            return data;
                        }
                    },
                    {
                        data: 'total_amount',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_total_amount !== undefined ? parseFloat(row.raw_total_amount) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return data;
                        }
                    },
                    {
                        data: 'paid_amount',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_paid_amount !== undefined ? parseFloat(row.raw_paid_amount) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return `<span class="text-success fw-semibold">${data}</span>`;
                        }
                    },
                    {
                        data: 'due_amount',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_due_amount !== undefined ? parseFloat(row.raw_due_amount) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
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
                                        <a href="{{ route('admin.ledgers.customer.detail') }}?customer_id=${row.customer_id}${locationQuery}" class="dropdown-item">
                                            <i class="ti ti-eye me-2"></i>View History
                                        </a>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { data: 'date_group' },
                    { data: 'date_sort' },
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

            $(document).on('click', '#exportExcelBtn', function () {
                const params = new URLSearchParams();
                const filters = currentFilters();
                Object.keys(filters).forEach(function (key) {
                    if (filters[key] !== '' && filters[key] !== null && filters[key] !== undefined) {
                        params.append(key, filters[key]);
                    }
                });

                if (window.showAjaxLoader) {
                    window.showAjaxLoader();
                }

                fetch("{{ route('admin.ledgers.customer.export') }}?" + params.toString(), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Export failed');
                    }
                    let filename = 'customer_ledger.xlsx';
                    const disposition = response.headers.get('Content-Disposition');
                    if (disposition && disposition.indexOf('filename=') !== -1) {
                        const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }
                    return response.blob().then(blob => ({ blob, filename }));
                })
                .then(({ blob, filename }) => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);

                    if (window.hideAjaxLoader) {
                        window.hideAjaxLoader();
                    }
                    if (window.toastr) {
                        window.toastr.success('Customer Ledger exported successfully');
                    }
                })
                .catch(error => {
                    if (window.hideAjaxLoader) {
                        window.hideAjaxLoader();
                    }
                    if (window.toastr) {
                        window.toastr.error('Failed to export Customer Ledger');
                    }
                });
            });

            // Double click anywhere on row to navigate to details page
            $('#customerLedgerTable tbody').on('dblclick', 'tr', function (e) {
                if ($(e.target).closest('.dropdown').length || $(e.target).closest('button').length || $(e.target).closest('a').length) {
                    return;
                }
                const data = table.row(this).data();
                if (data) {
                    const locationVal = $('#filter-location').val() || '';
                    const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                    window.location.href = `{{ route('admin.ledgers.customer.detail') }}?customer_id=${data.customer_id}${locationQuery}`;
                }
            });
        });
    </script>
@endsection
