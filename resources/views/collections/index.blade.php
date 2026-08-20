@extends('layouts.app')

@section('title', 'Collections')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Collections List</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 280px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-status">Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>
            @can('create collections')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.collections.create') }}">
                    <i class="ti ti-plus me-1"></i> Add Collection
                </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="collectionsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Collection Name</th>
                        <th>Collection Short Name</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        @if(auth()->user()->can('edit collections') || auth()->user()->can('delete collections'))
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
            const columns = [];
            columns.push(
                { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'name' },
                { data: 'short_name' },
                { data: 'status', orderable: false },
                { data: 'created_at' },
                @if(auth()->user()->can('edit collections') || auth()->user()->can('delete collections'))
                { data: 'actions', orderable: false },
                @endif
            );

            const table = $('#collectionsTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : {
                    url: '{{ route('admin.collections.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.status = $('#filter-status').val();
                    }
                },
                columns: columns,
                drawCallback: function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $('#btnApplyFilter').on('click', function () {
                window.refreshTable();
                bootstrap.Dropdown.getInstance($('#filterDropdownContainer button')[0])?.hide();
            });

            $('#btnClearFilter').on('click', function () {
                $('#filter-status').val('');
                window.refreshTable();
                bootstrap.Dropdown.getInstance($('#filterDropdownContainer button')[0])?.hide();
            });

            $(document).on('change', '.collection-status-toggle', function () {
                const url = $(this).data('url');
                const toggleBtn = $(this);

                $.ajax({
                    url: url,
                    type: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(response.message);
                            }
                        } else {
                            toggleBtn.prop('checked', !toggleBtn.prop('checked'));
                            if (typeof toastr !== 'undefined') {
                                toastr.error(response.message || 'Status update failed.');
                            }
                        }
                    },
                    error: function () {
                        toggleBtn.prop('checked', !toggleBtn.prop('checked'));
                        if (typeof toastr !== 'undefined') {
                            toastr.error('An error occurred while updating status.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
