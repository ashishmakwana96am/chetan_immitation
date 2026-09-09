@extends('layouts.app')

@section('title', 'Users')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <style>
        /* Desktop: Standard Dropdown Menu */
        #filterDropdownContainer {
            position: relative;
        }
        @media (min-width: 768px) {
            .user-filter-dropdown {
                min-width: 360px;
                width: 360px;
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
            #filterDropdownContainer .dropdown-menu.user-filter-dropdown {
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

            #filterDropdownContainer .dropdown-menu.user-filter-dropdown.show {
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
    </style>
@endsection

@section('content')

    <!-- Stats Cards -->
    <div class="row g-4 mb-4" id="userStatsCards">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Users</span>
                            <h4 class="mb-0 mt-1" id="statTotal"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-users ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Active Users</span>
                            <h4 class="mb-0 mt-1" id="statActive"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-user-check ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Inactive Users</span>
                            <h4 class="mb-0 mt-1" id="statInactive"><span class="spinner-border spinner-border-sm text-secondary" style="width: 0.75rem; height: 0.75rem;" role="status"></span></h4>
                        </div>
                        <span class="badge bg-label-danger rounded p-2"><i class="ti ti-user-off ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0">Users List</h5>
            <div class="d-flex gap-2 align-items-center">
                {{-- Filter Dropdown / Side Panel on Mobile --}}
                <div class="dropdown d-inline-block" id="filterDropdownContainer">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                        <i class="ti ti-filter me-1"></i> Filter
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg user-filter-dropdown" id="filterDropdownMenu">
                        <div class="filter-sidepanel-header">
                            <h5 class="dropdown-header px-0 mb-0 fw-semibold fs-5 text-dark">
                                <i class="ti ti-filter me-1 text-primary"></i> Filters
                            </h5>
                            <button type="button" class="btn-close d-md-none" id="btnCloseFilterDropdown" aria-label="Close"></button>
                        </div>

                        <div class="filter-sidepanel-body">
                            <div class="mb-3 text-start">
                                <label class="form-label fw-medium text-muted mb-1" for="filter-role">Role</label>
                                <select id="filter-role" class="form-select">
                                    <option value="">All Roles</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 text-start">
                                <label class="form-label fw-medium text-muted mb-1" for="filter-status">Status</label>
                                <select id="filter-status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
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
                @can('create users')
                    <button class="btn btn-primary" data-common-modal="{{ route('admin.users.create') }}" data-size="modal-lg">
                        <i class="ti ti-plus me-1"></i> Add User
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="usersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        @if(auth()->user()->hasRole('super-admin'))
                            <th>Location</th>
                        @endif
                        <th>Status</th>
                        @if(auth()->user()->can('edit users') || auth()->user()->can('delete users') || auth()->user()->can('change users password'))
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
    <script>
        $(document).ready(function () {
            let isForceClosing = false;

            function closeUserFilterSidepanel() {
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

            $('#filterDropdownContainer').on('hide.bs.dropdown', function (e) {
                if (isForceClosing) {
                    return true;
                }
                if (e.clickEvent && $(e.clickEvent.target).closest('#filterDropdownContainer, #filterDropdownMenu').length) {
                    e.preventDefault();
                    return false;
                }
            });

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
                closeUserFilterSidepanel();
            });

            const table = $('#usersTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : {
                    url     : '{{ route('admin.users.data') }}',
                    cache   : false,
                    data    : function(d) {
                        d.role_id = $('#filter-role').val();
                        d.status  = $('#filter-status').val();
                    },
                    dataSrc : function (res) {
                        // Update stats cards from response
                        const users    = res.data;
                        const active   = users.filter(u => u.raw_status == 1).length;
                        const inactive = users.filter(u => u.raw_status == 2).length;
                        $('#statTotal').text(users.length);
                        $('#statActive').text(active);
                        $('#statInactive').text(inactive);
                        return users;
                    }
                },
                columns    : [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'name' },
                    { data: 'email' },
                    { data: 'phone' },
                    { data: 'role' },
                    @if(auth()->user()->hasRole('super-admin'))
                        { data: 'location' },
                    @endif
                    { data: 'status',  orderable: false },
                    @if(auth()->user()->can('edit users') || auth()->user()->can('delete users') || auth()->user()->can('change users password'))
                        { data: 'actions', orderable: false },
                    @endif
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            // Apply Filter
            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                closeUserFilterSidepanel();
            });

            // Clear Filter
            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-role').val('');
                $('#filter-status').val('');
                window.refreshTable();
                closeUserFilterSidepanel();
            });

            $(document).on('change', '.user-status-toggle', function () {
                const toggle = $(this);
                const url    = toggle.attr('data-url');

                $.ajax({
                    url  : url,
                    type : 'PATCH',
                    data : { _token: $('meta[name="csrf-token"]').attr('content') },
                    success : function (res) {
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            window.refreshTable();
                        }
                    },
                    error : function () {
                        toggle.prop('checked', !toggle.prop('checked'));
                        toastr.error('Something went wrong. Please try again.');
                    }
                });
            });
        });
    </script>
@endsection
