@extends('layouts.app')

@section('title', 'Categories')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    <style>
        tr.dt-drag-handle td:first-child { cursor: grab; color: #adb5bd; }
        tr.dt-drag-handle td:first-child:hover { color: #566a7f; }
        tr.sortable-ghost { opacity: 0.4; background: #e7e3ff !important; }
        tr.sortable-chosen { background: #f0eeff !important; }

        /* Desktop: Standard Dropdown Menu */
        #filterDropdownContainer {
            position: relative;
        }
        @media (min-width: 768px) {
            .category-filter-dropdown {
                min-width: 320px;
                width: 320px;
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

        body.filter-sidepanel-open {
            overflow: hidden !important;
            touch-action: none !important;
        }

        /* Mobile / Phone View: Full Screen Modal Drawer */
        @media (max-width: 767.98px) {
            #filterDropdownContainer .dropdown-menu.category-filter-dropdown {
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

            #filterDropdownContainer .dropdown-menu.category-filter-dropdown.show {
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

            .filter-action-buttons {
                display: flex;
                width: 100%;
                gap: 0.5rem;
            }

            .filter-action-buttons button {
                flex: 1;
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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Categories List</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Filter Dropdown / Side Panel on Mobile --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg category-filter-dropdown" id="filterDropdownMenu">
                    <div class="filter-sidepanel-header">
                        <h5 class="dropdown-header px-0 mb-0 fw-semibold fs-5 text-dark">
                            <i class="ti ti-filter me-1 text-primary"></i> Filters
                        </h5>
                        <button type="button" class="btn-close d-md-none" id="btnCloseFilterDropdown" aria-label="Close"></button>
                    </div>
                    <div class="filter-sidepanel-body">
                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1" for="filter-status">Status</label>
                            <select id="filter-status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1" for="filter-featured">Featured</label>
                            <select id="filter-featured" class="form-select">
                                <option value="">All</option>
                                <option value="1">Featured</option>
                                <option value="0">Not Featured</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-sidepanel-footer">
                        <div class="d-flex justify-content-end gap-2 filter-action-buttons">
                            <button type="button" class="btn btn-label-secondary btn-sm px-3" id="btnClearFilter">
                                <i class="ti ti-refresh me-1"></i> Clear Filter
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4" id="btnApplyFilter">
                                <i class="ti ti-check me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @can('create categories')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.categories.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Category
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="categoriesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Featured</th>
                        <th>Status</th>
                        <th>Low Stock Threshold</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit categories') || auth()->user()->can('delete categories'))
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        $(document).ready(function () {
            const columns = [];
            columns.push(
                { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'image',      orderable: false },
                { data: 'name' },
                { data: 'is_featured', orderable: false },
                { data: 'status',     orderable: false },
                { data: 'low_stock_threshold', type: 'num' },
                { data: 'created_at' },
                @if(auth()->user()->can('edit categories') || auth()->user()->can('delete categories'))
                { data: 'actions', orderable: false },
                @endif
            );

            const table = $('#categoriesTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : {
                    url: '{{ route('admin.categories.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.status      = $('#filter-status').val();
                        d.is_featured = $('#filter-featured').val();
                    }
                },
                columns    : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            function closeCategoryFilterSidepanel() {
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
                closeCategoryFilterSidepanel();
            });

            // Apply Filter
            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                closeCategoryFilterSidepanel();
            });

            // Clear Filter
            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-status').val('');
                $('#filter-featured').val('');
                window.refreshTable();
                closeCategoryFilterSidepanel();
            });



            $(document).on('change', '.category-status-toggle', function () {
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

            $(document).on('change', '.category-featured-toggle', function () {
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
