@extends('layouts.app')

@section('title', 'Expenses')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <style>
        #expensesTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #expensesTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Desktop: Standard Dropdown Menu */
        #filterDropdownContainer {
            position: relative;
        }
        @media (min-width: 768px) {
            .expense-filter-dropdown {
                min-width: 380px;
                width: 380px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 10px;
                padding: 1.5rem;
                z-index: 1060;
            }
            .filter-sidepanel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 1rem;
            }
            .filter-sidepanel-body {
                padding: 0;
            }
            .filter-sidepanel-footer {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid rgba(0, 0, 0, 0.08);
            }
        }

        .filter-action-buttons {
            display: flex;
            width: 100%;
            gap: 0.625rem;
        }

        .filter-action-buttons button {
            flex: 1 1 50%;
            width: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        body.filter-sidepanel-open {
            overflow: hidden !important;
            touch-action: none !important;
        }

        /* Mobile / Phone View: Full Screen Modal Drawer */
        @media (max-width: 767.98px) {
            #filterDropdownContainer .dropdown-menu.expense-filter-dropdown {
                position: fixed !important;
                inset: 0 !important;
                top: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                height: 100% !important;
                height: 100dvh !important;
                max-height: 100% !important;
                max-height: 100dvh !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                z-index: 1090 !important;
                transform: translateX(100%) !important;
                transition: transform 0.28s ease-in-out, visibility 0.28s !important;
                visibility: hidden !important;
                overscroll-behavior: contain !important;
                background: #fff !important;
            }

            #filterDropdownContainer .dropdown-menu.expense-filter-dropdown.show {
                transform: translateX(0) !important;
                visibility: visible !important;
            }

            .filter-sidepanel-header {
                padding: 1.15rem 1.25rem;
                margin-bottom: 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.08);
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #fff;
            }

            .filter-sidepanel-body {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                padding: 1.25rem;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior: contain !important;
            }

            .filter-sidepanel-body .mb-3 {
                margin-bottom: 0.85rem !important;
            }

            .filter-sidepanel-footer {
                margin-top: 0;
                padding: 0.85rem 1.25rem;
                padding-bottom: max(0.85rem, env(safe-area-inset-bottom, 0.85rem));
                border-top: 1px solid rgba(0, 0, 0, 0.08);
                background: #fff;
                flex-shrink: 0;
                box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05);
            }

            .filter-mobile-backdrop {
                position: fixed;
                inset: 0;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100% !important;
                height: 100dvh !important;
                background: rgba(0, 0, 0, 0.45);
                backdrop-filter: blur(1px);
                z-index: 1085;
                opacity: 0;
                transition: opacity 0.25s ease;
                pointer-events: none;
            }

            .filter-mobile-backdrop.show {
                opacity: 1;
                pointer-events: auto;
            }
        }

        .flatpickr-calendar {
            z-index: 99999 !important;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Expenses</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Filter Dropdown / Side Panel on Mobile --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg expense-filter-dropdown" id="filterDropdownMenu">
                    <div class="filter-sidepanel-header">
                        <h5 class="dropdown-header px-0 mb-0 fw-semibold fs-5 text-dark">
                            <i class="ti ti-filter me-1 text-primary"></i> Filters
                        </h5>
                        <button type="button" class="btn-close d-md-none" id="btnCloseFilterDropdown" aria-label="Close"></button>
                    </div>

                    <div class="filter-sidepanel-body">
                        @if(!$isRestricted)
                            <div class="mb-3 text-start">
                                <label class="form-label fw-medium text-muted mb-1" for="filter-location">Location</label>
                                <select id="filter-location" class="form-select">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1" for="filter-category">Category</label>
                            <select id="filter-category" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1" for="filter-payment-method">Payment Method</label>
                            <select id="filter-payment-method" class="form-select">
                                <option value="">All Methods</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}">{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1">Date Range</label>
                            <div class="w-100">
                                <input type="text" id="filter-start-date" class="form-control flatpickr-expenses mb-2" placeholder="From Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                                <div class="text-center text-muted small mb-2">to</div>
                                <input type="text" id="filter-end-date" class="form-control flatpickr-expenses" placeholder="To Date" readonly style="width: 100% !important; display: block; margin-left: 0px !important;" />
                            </div>
                        </div>
                    </div>

                    <div class="filter-sidepanel-footer">
                        <div class="filter-action-buttons">
                            <button type="button" class="btn btn-label-secondary btn-sm" id="btnClearFilter">
                                <i class="ti ti-refresh me-1"></i> Clear
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="btnApplyFilter">
                                <i class="ti ti-check me-1"></i> Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @can('create expenses')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.expenses.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Expense
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="expensesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        @if(!$isRestricted)
                            <th>Location</th>
                        @endif
                        <th>Created By</th>
                        @if(auth()->user()->can('edit expenses') || auth()->user()->can('delete expenses'))
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/datatables-rowgroup.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script>
        $(document).ready(function () {
            let flatpickrOpen = false;
            let isForceClosing = false;

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

            $('#filterDropdownContainer').on('hide.bs.dropdown', function (e) {
                if (isForceClosing) {
                    return true;
                }
                if (flatpickrOpen) {
                    e.preventDefault();
                    return false;
                }
                if (e.clickEvent && $(e.clickEvent.target).closest('#filterDropdownContainer, #filterDropdownMenu, .flatpickr-calendar').length) {
                    e.preventDefault();
                    return false;
                }
            });

            $(document).on('mousedown', '.flatpickr-calendar', function (e) {
                e.stopPropagation();
            });

            function closeExpenseFilterSidepanel() {
                isForceClosing = true;

                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    try {
                        const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl);
                        if (dropdownInstance) {
                            dropdownInstance.hide();
                        }
                    } catch (err) {}
                }

                $('body').removeClass('filter-sidepanel-open');
                $('#filterDropdownContainer').removeClass('show');
                $('#filterDropdownContainer > button[data-bs-toggle="dropdown"]').removeClass('show').attr('aria-expanded', 'false');
                $('#filterDropdownContainer .dropdown-menu').removeClass('show');

                $('.filter-mobile-backdrop').removeClass('show');
                setTimeout(function () {
                    $('.filter-mobile-backdrop').remove();
                    isForceClosing = false;
                }, 280);
            }

            // Handle mobile backdrop and close button
            $('#filterDropdownContainer').on('show.bs.dropdown', function () {
                if (window.innerWidth < 768) {
                    $('body').addClass('filter-sidepanel-open');
                    if ($('.filter-mobile-backdrop').length === 0) {
                        const $backdrop = $('<div class="filter-mobile-backdrop"></div>');
                        $('body').append($backdrop);
                        setTimeout(function () {
                            $backdrop.addClass('show');
                        }, 10);
                    }
                }
            });

            $('#filterDropdownContainer').on('hidden.bs.dropdown', function () {
                $('body').removeClass('filter-sidepanel-open');
                $('.filter-mobile-backdrop').removeClass('show');
                setTimeout(function () {
                    $('.filter-mobile-backdrop').remove();
                }, 280);
            });

            $(document).on('click', '#btnCloseFilterDropdown, .filter-mobile-backdrop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                closeExpenseFilterSidepanel();
            });

            const getColCount = function() {
                return $('#expensesTable thead tr th').length;
            };

            const table = $('#expensesTable').DataTable({
                responsive: false,
                order: [[getColCount() + 1, 'desc']],
                ajax: {
                    url: '{{ route('admin.expenses.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function (d) {
                        d.location_id = $('#filter-location').val();
                        d.category = $('#filter-category').val();
                        d.payment_method = $('#filter-payment-method').val();
                        d.start_date = $('#filter-start-date').val();
                        d.end_date = $('#filter-end-date').val();
                    }
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'title' },
                    { data: 'category', render: function (data, type, row) { return type === 'sort' ? (row.raw_category || String(data).replace(/<[^>]*>/g, '')) : data; } },
                    { data: 'amount', render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.raw_amount !== undefined ? row.raw_amount : (parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0);
                        }
                        return data;
                    } },
                    { data: 'payment_method' },
                    @if(!$isRestricted)
                        { data: 'location' },
                    @endif
                    { data: 'created_by' },
                    @if(auth()->user()->can('edit expenses') || auth()->user()->can('delete expenses'))
                        { data: 'actions', orderable: false },
                    @endif
                    { data: 'date_group', visible: false },
                    { data: 'date_sort', visible: false },
                ],
                rowGroup: {
                    dataSrc: 'date_group',
                    startRender: function (rows, group) {
                        const colSpan = getColCount();
                        return $('<tr class="group-header"/>')
                            .append('<td colspan="' + colSpan + '"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' expense' + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                    }
                }
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                closeExpenseFilterSidepanel();
            });

            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-location, #filter-category, #filter-payment-method').val('');
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', null);
                endPicker.set('minDate', null);
                window.refreshTable();
                closeExpenseFilterSidepanel();
            });
        });
    </script>
@endsection
