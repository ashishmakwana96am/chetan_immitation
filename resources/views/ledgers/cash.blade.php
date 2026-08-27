@extends('layouts.app')

@section('title', 'Cash Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #cashLedgerTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #cashLedgerTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #cashLedgerTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #cashLedgerTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Cash Ledger</h4>
        <div class="d-flex align-items-center gap-2">
            <div id="current-balance-container"></div>
            <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
                <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
            </button>
        </div>
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
                            <span class="text-muted small">Opening</span>
                            <span class="fw-semibold" id="opening-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Sale</span>
                            <span class="fw-semibold text-success" id="sale-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Expense</span>
                            <span class="fw-semibold text-danger" id="expense-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Closing</span>
                            <span class="fw-semibold" id="closing-{{ $loc->id }}"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></span>
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
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
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
            <table class="table border-top" id="cashLedgerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Opening</th>
                        <th>Sale</th>
                        <th>Expense</th>
                        <th>Closing</th>
                        <th>Action</th>
                        <th class="d-none">date_group</th>
                        <th class="d-none">date_sort</th>
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
                    location_id: $('#filter-location').val() || '',
                    start_date:  $('#filter-start-date').val(),
                    end_date:    $('#filter-end-date').val(),
                };
            }

            const table = $('#cashLedgerTable').DataTable({
                responsive: false,
                order: [[7, 'desc']],
                columnDefs: [
                    { targets: 0, orderable: false },
                    { targets: 5, orderable: false },
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
                    url: '{{ route('admin.ledgers.cash.data') }}',
                    cache: false,
                    data: function (d) { Object.assign(d, currentFilters()); },
                    dataSrc: function (json) {
                        if (json.branch_summary) {
                            $.each(json.branch_summary, function (locId, s) {
                                $('#opening-' + locId).text(s.opening).toggleClass('text-danger fw-bold', s.opening.includes('-'));
                                $('#sale-' + locId).text(s.sale);
                                $('#expense-' + locId).text(s.expense);
                                $('#closing-' + locId).text(s.closing).toggleClass('text-danger fw-bold', s.closing.includes('-'));
                            });
                        }
                        if (json.current_balance !== undefined) {
                            const isNeg = json.current_balance.includes('-');
                            $('#current-balance-container').html(
                                '<span class="badge ' + (isNeg ? 'bg-label-danger' : 'bg-label-success') + ' fs-6 fw-bold" style="display: flex; justify-content: center; align-items: center;"><i class="ti ti-cash me-1"></i> Cash Balance: ' + json.current_balance + '</span>'
                            );
                        }
                        return json.data;
                    },
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%' },
                    {
                        data: 'opening',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_opening !== undefined ? parseFloat(row.raw_opening) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return data.includes('-') ? '<span class="text-danger fw-bold">' + data + '</span>' : data;
                        }
                    },
                    {
                        data: 'sale',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_sale !== undefined ? parseFloat(row.raw_sale) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return data;
                        }
                    },
                    {
                        data: 'expense',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_expense !== undefined ? parseFloat(row.raw_expense) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return data;
                        }
                    },
                    {
                        data: 'closing',
                        type: 'num',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_closing !== undefined ? parseFloat(row.raw_closing) : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                            }
                            return data.includes('-') ? '<span class="text-danger fw-bold">' + data + '</span>' : data;
                        }
                    },
                    { data: 'actions', orderable: false },
                    { data: 'date_group', visible: false },
                    { data: 'date_sort', visible: false },
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

                if (typeof window.showAjaxLoader === 'function') {
                    window.showAjaxLoader();
                }

                fetch("{{ route('admin.ledgers.cash.export') }}?" + params.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Export failed');
                    const disposition = response.headers.get('Content-Disposition');
                    let filename = 'cash_ledger_' + new Date().toISOString().slice(0,10) + '.xlsx';
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
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Excel report downloaded successfully!');
                    }
                })
                .catch(error => {
                    console.error('Export error:', error);
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to export Excel. Please try again.');
                    } else {
                        alert('An error occurred while exporting the report.');
                    }
                })
                .finally(() => {
                    if (typeof window.hideAjaxLoader === 'function') {
                        window.hideAjaxLoader();
                    }
                });
            });
        });
    </script>
@endsection
