@extends('layouts.app')

@section('title', 'Branch Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Branch Ledger</h4>
    </div>

    <div class="card mb-4" id="filterReportCard">
        <div class="card-header">
            <h5 class="mb-0">Filter</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3" onsubmit="return false;">
                <div class="col-md-3">
                    <label class="form-label">Location</label>
                    <select id="filter-location" class="form-select" @disabled($isRestricted)>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
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

    <div class="row g-4 mb-4">
        <div class="col-md-4 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Transfer In</p>
                    <h5 class="mb-0 text-success" id="summaryTransferIn">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Transfer Out</p>
                    <h5 class="mb-0 text-danger" id="summaryTransferOut">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Outstanding <small class="text-muted">(pending transfers)</small></p>
                    <h5 class="mb-0" id="summaryOutstanding">-</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="branchLedgerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Amount</th>
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
                    location_id: $('#filter-location').val(),
                    start_date: $('#filter-start-date').val(),
                    end_date: $('#filter-end-date').val(),
                };
            }

            const table = $('#branchLedgerTable').DataTable({
                responsive: false,
                order: [],
                ajax: {
                    url: '{{ route('admin.ledgers.branch.data') }}',
                    cache: false,
                    data: function (d) { Object.assign(d, currentFilters()); },
                    dataSrc: function (json) {
                        if (json.summary) {
                            $('#summaryTransferIn').text(json.summary.transfer_in);
                            $('#summaryTransferOut').text(json.summary.transfer_out);
                            $('#summaryOutstanding').text(json.summary.outstanding);
                        }
                        return json.data;
                    },
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%' },
                    { data: 'date' },
                    { data: 'type', orderable: false },
                    { data: 'reference' },
                    { data: 'amount' },
                ],
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
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', 'today');
                endPicker.set('minDate', null);
                updateFilterButtonsVisibility();
                window.refreshTable();
            });
        });
    </script>
@endsection
