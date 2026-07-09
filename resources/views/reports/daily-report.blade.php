@extends('layouts.app')

@section('title', 'Daily Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Daily Report</h4>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Filter Report</h5>
            <div class="d-flex gap-2 d-none" id="filterActionButtons">
                <button type="button" id="applyFiltersBtn" class="btn btn-sm btn-primary">
                    <i class="ti ti-filter me-1"></i> Apply
                </button>
                <button type="button" id="clearFiltersBtn" class="btn btn-sm btn-label-secondary">
                    <i class="ti ti-refresh me-1"></i> Clear
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.daily-report') }}" id="filterForm" class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Date</label>
                    <input type="text" name="date" class="form-control flatpickr" value="{{ $date }}" placeholder="DD-MM-YYYY" />
                </div>
                @if($isSuperAdmin)
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label">Branch</label>
                        <select name="location_id" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ (string) $locationId === (string) $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div id="report-results">
        @include('reports.partials.daily-report-results')
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            const dailyTableIds = ['#dailySalesTable', '#dailyPurchasesTable', '#dailyExpensesTable', '#dailyPurchaseBillTable'];

            function initDailyTables() {
                dailyTableIds.forEach(function (id) {
                    if ($.fn.DataTable.isDataTable(id)) {
                        $(id).DataTable().destroy();
                    }
                    $(id).DataTable({
                        responsive: false,
                        order: [],
                        columnDefs: [
                            { targets: 0, orderable: false, searchable: false },
                        ],
                    });
                });
            }

            function loadReport(url) {
                $('#report-results').css('opacity', 0.5);
                window.showAjaxLoader && window.showAjaxLoader();

                $.get(url, function (html) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newResults = $(doc).find('#report-results').html();

                    $('#report-results').html(newResults);
                    initDailyTables();
                }).fail(function () {
                    toastr.error('Failed to load the report. Please try again.');
                }).always(function () {
                    $('#report-results').css('opacity', 1);
                    window.hideAjaxLoader && window.hideAjaxLoader();
                });
            }

            function submitFilters() {
                const form = $('#filterForm');
                loadReport(form.attr('action') + '?' + form.serialize());
            }

            function updateFilterButtonsVisibility() {
                const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                    return $(el).val();
                });
                $('#filterActionButtons').toggleClass('d-none', !hasValue);
            }

            initDailyTables();

            if (typeof $.fn.flatpickr !== 'undefined') {
                $('.flatpickr').flatpickr({
                    altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false,
                    onChange: function (selectedDates, dateStr, instance) {
                        updateFilterButtonsVisibility();
                    }
                });
            }

            $(document).on('input change', '#filterForm', function () {
                updateFilterButtonsVisibility();
            });
            updateFilterButtonsVisibility();

            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                submitFilters();
            });

            $('#applyFiltersBtn').on('click', function () {
                submitFilters();
            });

            $('#clearFiltersBtn').on('click', function () {
                const form = $('#filterForm');
                const dateInput = form.find('input[name="date"]')[0];

                if (dateInput && dateInput._flatpickr) {
                    dateInput._flatpickr.setDate('{{ now()->toDateString() }}', false);
                } else if (dateInput) {
                    dateInput.value = '{{ now()->toDateString() }}';
                }

                const branchSelect = form.find('select[name="location_id"]');
                branchSelect.val('').trigger('change.select2');
                updateFilterButtonsVisibility();
                submitFilters();
            });
        });
    </script>
@endsection
