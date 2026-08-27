@extends('layouts.app')

@section('title', 'Utility Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <style>
        #activityLogTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #activityLogTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #activityLogTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #activityLogTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
        #activityLogTable tbody tr {
            cursor: pointer;
        }
        #activityLogTable tbody tr.group-header {
            cursor: default;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Utility Report</h4>
        <button type="button" class="btn btn-success report-export-btn" data-bs-toggle="offcanvas" data-bs-target="#utilityExportExcelOffcanvas">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-4" id="filterReportCard">
        <div class="card-header">
            <h5 class="mb-0">Filter Report</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3" onsubmit="return false;">
                <div class="col-md-3">
                    <label class="form-label">User</label>
                    <select id="filter-user" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
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
                <div class="col-md-3">
                    <label class="form-label">Module</label>
                    <select id="filter-module" class="form-select">
                        <option value="">All Modules</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}">{{ $module }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Action</label>
                    <select id="filter-action" class="form-select">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}">{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="text" id="filter-start-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="text" id="filter-end-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                    <small class="text-muted">Defaults to the last 30 days when left blank.</small>
                </div>
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
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Activity Logs</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="activityLogTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>User</th>
                        <th>Location</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Details</th>
                        <th class="d-none">Date Sort</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Export Excel Offcanvas Sidepanel -->
    <div class="offcanvas offcanvas-end" id="utilityExportExcelOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 500px; max-width: 100vw;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-semibold">Export Utility Report to Excel</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column" style="overflow: hidden;">
            <form id="utilityExportExcelForm" class="d-flex flex-column h-100 m-0">
                <div class="flex-grow-1 p-4" style="overflow-y: auto;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="utility_export_start_date">Start Date</label>
                        <input type="text" id="utility_export_start_date" name="start_date" class="form-control flatpickr-utility-export" placeholder="DD-MM-YYYY" value="{{ \Carbon\Carbon::now()->subMonth()->format('Y-m-d') }}" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="utility_export_end_date">End Date</label>
                        <input type="text" id="utility_export_end_date" name="end_date" class="form-control flatpickr-utility-export" placeholder="DD-MM-YYYY" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" />
                    </div>
                </div>

                <div class="d-flex p-4 border-top gap-3 mt-auto mb-0">
                    <button type="submit" id="submitUtilityExportBtn" class="btn btn-success flex-fill w-50 m-0">
                        <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
                    </button>
                    <button type="button" class="btn btn-label-secondary flex-fill w-50 m-0" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            const startPicker = $('#filter-start-date').flatpickr({
                altInput   : true,
                altFormat  : 'd-m-Y',
                dateFormat : 'Y-m-d',
                allowInput : false,
                maxDate    : 'today',
                onChange   : function (selectedDates) {
                    if (selectedDates.length) {
                        endPicker.set('minDate', selectedDates[0]);
                    } else {
                        endPicker.set('minDate', null);
                    }
                    updateFilterButtonsVisibility();
                }
            });

            const endPicker = $('#filter-end-date').flatpickr({
                altInput   : true,
                altFormat  : 'd-m-Y',
                dateFormat : 'Y-m-d',
                allowInput : false,
                maxDate    : 'today',
                onChange   : function (selectedDates) {
                    if (selectedDates.length) {
                        startPicker.set('maxDate', selectedDates[0]);
                    } else {
                        startPicker.set('maxDate', 'today');
                    }
                    updateFilterButtonsVisibility();
                }
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
                    user_id: $('#filter-user').val(),
                    location_id: $('#filter-location').val(),
                    module: $('#filter-module').val(),
                    action: $('#filter-action').val(),
                    start_date: $('#filter-start-date').val(),
                    end_date: $('#filter-end-date').val(),
                };
            }

            const table = $('#activityLogTable').DataTable({
                responsive : false,
                processing : false,
                serverSide : true,
                order      : [[9, 'desc']],
                ajax       : {
                    url: '{{ route('admin.reports.utility.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function (d) {
                        Object.assign(d, currentFilters());
                    }
                },
                columns    : [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'created_at',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.date_sort !== undefined ? row.date_sort : data;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'user',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_user_name !== undefined ? row.raw_user_name : String(data).replace(/<[^>]*>/g, '');
                            }
                            return data;
                        }
                    },
                    {
                        data: 'location',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_location_name !== undefined ? row.raw_location_name : String(data).replace(/<[^>]*>/g, '');
                            }
                            return data;
                        }
                    },
                    {
                        data: 'module',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_module !== undefined ? row.raw_module : String(data).replace(/<[^>]*>/g, '');
                            }
                            return data;
                        }
                    },
                    {
                        data: 'action',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_action !== undefined ? row.raw_action : String(data).replace(/<[^>]*>/g, '');
                            }
                            return data;
                        }
                    },
                    {
                        data: 'description',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.raw_description !== undefined ? row.raw_description : String(data).replace(/<[^>]*>/g, '');
                            }
                            return data;
                        }
                    },
                    { data: 'actions', orderable: false, searchable: false },
                    { data: 'date_group', visible: false },
                    { data: 'date_sort', visible: false },
                ],
                columnDefs: [
                    { targets: 0, orderable: false }
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="8"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' log' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
            });

            // Hide processing loader once draw/xhr completes
            table.on('xhr.dt draw.dt', function () {
                window.hideAjaxLoader && window.hideAjaxLoader();
                $('#activityLogTable_processing').css('display', 'none');
            });

            $('#activityLogTable tbody').on('dblclick', 'tr', function (e) {
                if ($(e.target).closest('.dropdown, .table-action-dropdown, button, a').length) {
                    return;
                }
                const data = table.row(this).data();
                if (data && data.view_url) {
                    window.location.href = data.view_url;
                }
            });

            window.refreshTable = function () {
                window.showAjaxLoader && window.showAjaxLoader();
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
                $('#filter-user').val('').trigger('change.select2');
                $('#filter-location').val('').trigger('change.select2');
                $('#filter-module').val('').trigger('change.select2');
                $('#filter-action').val('').trigger('change.select2');
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', 'today');
                endPicker.set('minDate', null);
                endPicker.set('maxDate', 'today');
                updateFilterButtonsVisibility();
                window.refreshTable();
            });

            // Initialize utility export sidepanel date pickers
            if (typeof $.fn.flatpickr !== 'undefined') {
                const expStartEl = $('#utility_export_start_date')[0];
                const expEndEl = $('#utility_export_end_date')[0];
                if (expStartEl && expEndEl) {
                    const expStartPicker = $(expStartEl).flatpickr({
                        altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                        onChange: function (selectedDates) {
                            if (selectedDates.length) {
                                expEndPicker.set('minDate', selectedDates[0]);
                            } else {
                                expEndPicker.set('minDate', null);
                            }
                            expEndPicker.set('maxDate', 'today');
                        }
                    });
                    const expEndPicker = $(expEndEl).flatpickr({
                        altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                        onChange: function (selectedDates) {
                            if (selectedDates.length) {
                                expStartPicker.set('maxDate', selectedDates[0]);
                            } else {
                                expStartPicker.set('maxDate', 'today');
                            }
                        }
                    });
                }
            }

            $(document).on('submit', '#utilityExportExcelForm', function (e) {
                e.preventDefault();
                const filters = currentFilters();
                filters.start_date = $('#utility_export_start_date').val();
                filters.end_date = $('#utility_export_end_date').val();

                const params = new URLSearchParams();
                for (const key in filters) {
                    if (filters[key]) {
                        params.append(key, filters[key]);
                    }
                }

                const offcanvasEl = document.getElementById('utilityExportExcelOffcanvas');
                if (offcanvasEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).hide();
                }

                // Show the branded theme AJAX loader
                if (window.showAjaxLoader) {
                    $('.loader-status').text('Exporting Excel');
                    window.showAjaxLoader();
                }

                const url = "{{ route('admin.reports.utility.export-excel') }}?" + params.toString();

                $.ajax({
                    url: url,
                    type: 'GET',
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function (data, status, xhr) {
                        if (window.hideAjaxLoader) {
                            window.hideAjaxLoader();
                            $('.loader-status').text('Loading');
                        }

                        const disposition = xhr.getResponseHeader('Content-Disposition');
                        let filename = 'utility_report.xlsx';
                        if (disposition && disposition.indexOf('filename=') !== -1) {
                            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                            if (matches != null && matches[1]) {
                                filename = matches[1].replace(/['"]/g, '');
                            }
                        }

                        const blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                        const downloadUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = downloadUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(downloadUrl);
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Excel report downloaded successfully!');
                        }
                    },
                    error: function () {
                        if (window.hideAjaxLoader) {
                            window.hideAjaxLoader();
                            $('.loader-status').text('Loading');
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Failed to export report. Please try again.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
