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
            <button type="button" id="resetFilters" class="btn btn-sm btn-label-secondary">
                <i class="ti ti-refresh me-1"></i> Reset
            </button>
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

            initDailyTables();

            if (typeof $.fn.flatpickr !== 'undefined') {
                $('.flatpickr').flatpickr({
                    altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false,
                    onChange: function (selectedDates, dateStr, instance) {
                        submitFilters();
                    }
                });
            }

            $(document).on('change', '#filterForm select', function () {
                submitFilters();
            });

            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                submitFilters();
            });

            $('#resetFilters').on('click', function () {
                const form = $('#filterForm');
                const dateInput = form.find('input[name="date"]')[0];

                if (dateInput && dateInput._flatpickr) {
                    dateInput._flatpickr.setDate('{{ now()->toDateString() }}', false);
                } else if (dateInput) {
                    dateInput.value = '{{ now()->toDateString() }}';
                }

                const branchSelect = form.find('select[name="location_id"]');
                if (branchSelect.length) {
                    // triggering change fires the delegated #filterForm select listener, which reloads once
                    branchSelect.val('').trigger('change.select2');
                } else {
                    submitFilters();
                }
            });
        });
    </script>
@endsection
