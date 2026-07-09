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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Utility Report</h4>
        <div class="d-flex gap-2 align-items-center">
            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 380px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-user">User</label>
                        <select id="filter-user" class="form-select">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-location">Location</label>
                        <select id="filter-location" class="form-select">
                            <option value="">All Locations</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-module">Module</label>
                        <select id="filter-module" class="form-select">
                            <option value="">All Modules</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}">{{ $module }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-action">Action</label>
                        <select id="filter-action" class="form-select">
                            <option value="">All Actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}">{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1">Date Range</label>
                        <div class="w-100">
                            <input type="text" id="filter-start-date" class="form-control flatpickr-log mb-2" placeholder="Start Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                            <div class="text-center text-muted small mb-2">to</div>
                            <input type="text" id="filter-end-date" class="form-control flatpickr-log" placeholder="End Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                        </div>
                        <small class="text-muted">Defaults to the last 30 days when left blank.</small>
                    </div>

                    <div class="dropdown-divider"></div>

                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
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
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            let flatpickrOpen = false;

            const startPicker = $('#filter-start-date').flatpickr({
                altInput   : true,
                altFormat  : 'd-m-Y',
                dateFormat : 'Y-m-d',
                allowInput : false,
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
                onOpen     : function () { flatpickrOpen = true; },
                onClose    : function (selectedDates) {
                    flatpickrOpen = false;
                    if (selectedDates.length) {
                        startPicker.set('maxDate', selectedDates[0]);
                    }
                }
            });

            $('#filterDropdownContainer').on('hide.bs.dropdown', function (e) {
                if (flatpickrOpen) {
                    e.preventDefault();
                    return false;
                }
            });

            $(document).on('mousedown', '.flatpickr-calendar', function (e) {
                e.stopPropagation();
            });

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
                order      : [[9, 'desc']],
                orderFixed : { pre: [[9, 'desc']] },
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
                    { data: 'created_at' },
                    { data: 'user' },
                    { data: 'location' },
                    { data: 'module' },
                    { data: 'action' },
                    { data: 'description' },
                    { data: 'actions', orderable: false, searchable: false },
                    { data: 'date_group', visible: false },
                    { data: 'date_sort', visible: false },
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="8"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' log' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                },
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
                table.ajax.reload(null, false);
            };

            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl) || new bootstrap.Dropdown(dropdownToggleEl);
                    dropdownInstance.hide();
                }
            });

            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-user').val('');
                $('#filter-location').val('');
                $('#filter-module').val('');
                $('#filter-action').val('');
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', null);
                endPicker.set('minDate', null);
                window.refreshTable();
                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl) || new bootstrap.Dropdown(dropdownToggleEl);
                    dropdownInstance.hide();
                }
            });
        });
    </script>
@endsection
