@extends('layouts.app')

@section('title', 'Branch Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #branchLedgerTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #branchLedgerTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #branchLedgerTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #branchLedgerTable tbody tr.group-header td .group-header-inner span {
            line-height: 1;
            display: flex;
            align-items: center;
            margin-top: 2px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Branch Ledger</h4>
        <div class="d-flex gap-2">
            @can('create balance transfer')
            <button class="btn btn-outline-primary" data-common-modal="{{ route('admin.accounting.opening-balances.transfer') }}">
                <i class="ti ti-arrows-exchange me-1"></i> Transfer Balance
            </button>
            @endcan
            <button type="button" id="exportPdfBtn" class="btn btn-danger report-export-btn">
                <i class="ti ti-file-text me-1"></i> Export to PDF
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Pending Payments Between Branches</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Branch That Owes Payment</th>
                            <th></th>
                            <th>Branch To Be Paid</th>
                            <th class="text-end">Pending Amount</th>
                        </tr>
                    </thead>
                    <tbody id="branchDuesBody">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted fw-semibold">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
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
            <table class="table border-top" id="branchLedgerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bill No</th>
                        <th>From &rarr; To</th>
                        <th>Status</th>
                        <th>Amount</th>
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
                    start_date: $('#filter-start-date').val(),
                    end_date: $('#filter-end-date').val(),
                };
            }

            function renderBranchDues(dues) {
                const body = $('#branchDuesBody');
                body.empty();

                if (!dues || !dues.length) {
                    body.append('<tr><td colspan="5" class="text-center py-4 text-muted">No pending payments between branches for this filter.</td></tr>');
                    return;
                }

                dues.forEach(function (due, index) {
                    const billLabel = due.bills_count + ' bill' + (due.bills_count > 1 ? 's' : '');
                    const row = $('<tr class="branch-due-row" style="cursor: pointer;" title="Double-click to view bills"></tr>')
                        .attr('data-from', due.from_location_id)
                        .attr('data-to', due.to_location_id);

                    row.append('<td>' + (index + 1) + '</td>');
                    row.append('<td class="fw-semibold text-danger">' + due.payable_branch + '</td>');
                    row.append('<td class="text-muted"><i class="ti ti-arrow-right"></i></td>');
                    row.append('<td class="fw-semibold text-success">' + due.receivable_branch + '</td>');
                    row.append('<td class="text-end"><span class="fw-semibold">' + due.amount + '</span> <span class="text-muted small">(' + billLabel + ')</span></td>');

                    body.append(row);
                });
            }

            $(document).on('dblclick', '#branchDuesBody tr.branch-due-row', function () {
                const from = $(this).data('from');
                const to = $(this).data('to');
                window.location.href = '{{ route('admin.ledgers.branch.dues-bills') }}?from_location_id=' + from + '&to_location_id=' + to;
            });

            const table = $('#branchLedgerTable').DataTable({
                responsive: false,
                order: [[7, 'desc']],
                columnDefs: [
                    { targets: [1, 2, 3, 4, 5], orderable: false },
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
                    url: '{{ route('admin.ledgers.branch.data') }}',
                    cache: false,
                    data: function (d) { Object.assign(d, currentFilters()); },
                    dataSrc: function (json) {
                        renderBranchDues(json.branch_dues || []);
                        return json.data;
                    },
                },
                columns: [
                    { data: 'index',       orderable: false, width: '5%' },
                    { data: 'transfer_no', orderable: false },
                    { data: 'branch',      orderable: false },
                    { data: 'status',      orderable: false },
                    { data: 'amount',      orderable: false },
                    { data: 'actions',     orderable: false },
                    { data: 'date_group',  visible: false },
                    { data: 'date_sort',   visible: false },
                ],
                drawCallback: function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });

            function showBranchDuesLoader() {
                $('#branchDuesBody').html('<tr><td colspan="5" class="text-center py-4 text-muted fw-semibold">Loading...</td></tr>');
            }

            window.refreshTable = function () {
                showBranchDuesLoader();
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
                window.open("{{ route('admin.ledgers.branch.export') }}?" + params.toString(), '_blank');
            });

            // Accept Balance Transfer
            $(document).on('click', '.accept-bt-btn', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const acceptUrl = '{{ route("admin.accounting.opening-balances.transfer-accept", ":id") }}'.replace(':id', id);
                Swal.fire({
                    title: 'Accept Balance Transfer?',
                    text: 'This will update cash/bank balances and adjust pending purchase bills.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Accept',
                    customClass: { confirmButton: 'btn btn-success me-3', cancelButton: 'btn btn-label-secondary' },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.showAjaxLoader && window.showAjaxLoader();
                        $.ajax({
                            url: acceptUrl,
                            type: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (res) {
                                window.hideAjaxLoader && window.hideAjaxLoader();
                                toastr.success(res.message || 'Balance transfer accepted successfully.');
                                window.refreshTable();
                                if (typeof window.refreshBranchLedgerBadge === 'function') window.refreshBranchLedgerBadge();
                            },
                            error: function (xhr) {
                                window.hideAjaxLoader && window.hideAjaxLoader();
                                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Action failed.';
                                toastr.error(typeof msg === 'object' ? Object.values(msg).flat().join('<br>') : msg);
                            }
                        });
                    }
                });
            });

            // Reject Balance Transfer
            $(document).on('click', '.reject-bt-btn', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const rejectUrl = '{{ route("admin.accounting.opening-balances.transfer-reject", ":id") }}'.replace(':id', id);
                Swal.fire({
                    title: 'Reject Balance Transfer?',
                    text: 'This transfer request will be rejected.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Reject',
                    customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.showAjaxLoader && window.showAjaxLoader();
                        $.ajax({
                            url: rejectUrl,
                            type: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (res) {
                                window.hideAjaxLoader && window.hideAjaxLoader();
                                toastr.success(res.message || 'Balance transfer rejected.');
                                window.refreshTable();
                                if (typeof window.refreshBranchLedgerBadge === 'function') window.refreshBranchLedgerBadge();
                            },
                            error: function (xhr) {
                                window.hideAjaxLoader && window.hideAjaxLoader();
                                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Action failed.';
                                toastr.error(typeof msg === 'object' ? Object.values(msg).flat().join('<br>') : msg);
                            }
                        });
                    }
                });
            });

            // Edit Balance Transfer (offcanvas form)
            $(document).on('click', '.edit-bt-btn', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const editUrl = '{{ route("admin.accounting.opening-balances.transfer-edit", ":id") }}'.replace(':id', id);
                
                // Trigger standard common modal / offcanvas loader
                $(this).attr('data-common-modal', editUrl);
                if (window.openCommonModal) {
                    window.openCommonModal(editUrl);
                } else {
                    window.location.href = editUrl;
                }
            });

            // Delete Balance Transfer
            $(document).on('click', '.delete-bt-btn', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const deleteUrl = '{{ route("admin.accounting.opening-balances.transfer-destroy", ":id") }}'.replace(':id', id);
                Swal.fire({
                    title: 'Delete Balance Transfer?',
                    text: 'Are you sure you want to delete this balance transfer?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.showAjaxLoader && window.showAjaxLoader();
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (res) {
                                window.hideAjaxLoader && window.hideAjaxLoader();
                                toastr.success(res.message || 'Balance transfer deleted.');
                                window.refreshTable();
                                if (typeof window.refreshBranchLedgerBadge === 'function') window.refreshBranchLedgerBadge();
                            },
                            error: function (xhr) {
                                window.hideAjaxLoader && window.hideAjaxLoader();
                                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Action failed.';
                                toastr.error(typeof msg === 'object' ? Object.values(msg).flat().join('<br>') : msg);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
