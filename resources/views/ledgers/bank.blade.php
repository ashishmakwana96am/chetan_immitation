@extends('layouts.app')

@section('title', 'Bank Ledger')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Bank Ledger</h4>
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
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Opening</p>
                    <h5 class="mb-0" id="summaryOpening">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Receipt</p>
                    <h5 class="mb-0 text-success" id="summaryReceipt">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Payment</p>
                    <h5 class="mb-0 text-danger" id="summaryPayment">-</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Closing</p>
                    <h5 class="mb-0" id="summaryClosing">-</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="bankLedgerTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Opening</th>
                        <th>Receipt</th>
                        <th>Payment</th>
                        <th>Closing</th>
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

            const table = $('#bankLedgerTable').DataTable({
                responsive: false,
                order: [],
                ajax: {
                    url: '{{ route('admin.ledgers.bank.data') }}',
                    cache: false,
                    data: function (d) { Object.assign(d, currentFilters()); },
                    dataSrc: function (json) {
                        if (json.summary) {
                            $('#summaryOpening').text(json.summary.opening);
                            $('#summaryReceipt').text(json.summary.receipt);
                            $('#summaryPayment').text(json.summary.payment);
                            $('#summaryClosing').text(json.summary.closing);
                        }
                        return json.data;
                    },
                },
                columns: [
                    { data: 'index', orderable: false, width: '5%' },
                    { data: 'date' },
                    { data: 'opening' },
                    { data: 'receipt' },
                    { data: 'payment' },
                    { data: 'closing' },
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
