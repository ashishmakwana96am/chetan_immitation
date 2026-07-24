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
                            <span class="text-muted small">Total Sales</span>
                            <span class="fw-semibold" id="sales-{{ $loc->id }}">-</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Total Received</span>
                            <span class="fw-semibold text-success" id="payment-{{ $loc->id }}">-</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Outstanding</span>
                            <span class="fw-semibold text-danger" id="outstanding-{{ $loc->id }}">-</span>
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
                order: [[7, 'desc']],
                columnDefs: [
                    { targets: [6, 7], visible: false }
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="6"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' entr' + (rows.count() > 1 ? 'ies' : 'y') + '</span></div></td>');
                    }
                },
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
                    { data: 'customer' },
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
                                        <a href="{{ route('admin.ledgers.customer.detail') }}?customer_id=${row.customer_id}&date=${row.date_sort}${locationQuery}" class="dropdown-item">
                                            <i class="ti ti-eye me-2"></i>View
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

            $(document).on('click', '#exportPdfBtn', function () {
                const params = new URLSearchParams();
                const filters = currentFilters();
                Object.keys(filters).forEach(function (key) {
                    if (filters[key] !== '' && filters[key] !== null && filters[key] !== undefined) {
                        params.append(key, filters[key]);
                    }
                });
                params.append('auto_print', '1');
                window.open("{{ route('admin.ledgers.customer.export') }}?" + params.toString(), '_blank');
            });

            // Double click anywhere on row to navigate to details page
            $('#customerLedgerTable tbody').on('dblclick', 'tr:not(.group-header)', function (e) {
                if ($(e.target).closest('.dropdown').length || $(e.target).closest('button').length || $(e.target).closest('a').length) {
                    return;
                }
                const data = table.row(this).data();
                if (data) {
                    const locationVal = $('#filter-location').val() || '';
                    const locationQuery = locationVal ? `&location_id=${locationVal}` : '';
                    window.location.href = `{{ route('admin.ledgers.customer.detail') }}?customer_id=${data.customer_id}&date=${data.date_sort}${locationQuery}`;
                }
            });
        });
    </script>
@endsection
