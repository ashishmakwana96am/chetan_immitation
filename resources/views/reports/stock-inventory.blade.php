@extends('layouts.app')

@section('title', 'Stock Inventory Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <style>
        .variant-toggle {
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            line-height: 1;
        }
        .variant-toggle i {
            font-size: 1rem;
            transition: transform 0.2s ease;
        }
        .variant-toggle.is-open i {
            transform: rotate(90deg);
        }
        #stockTable tr.child td.child {
            padding: 0 !important;
            background-color: #fbfbfc;
        }
        .variant-table {
            margin-bottom: 0;
        }
        .variant-table th,
        .variant-table td {
            font-size: 0.8125rem;
        }
        #stockTableFooterRow td {
            white-space: nowrap !important;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Stock Inventory Report</h4>
        <button type="button" id="exportExcelBtn" class="btn btn-success report-export-btn">
            <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
        </button>
    </div>

    <div id="report-results">
        @include('reports.partials.stock-inventory-results')
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    $(document).ready(function () {
        let table = null;
        const locationCount = {{ $locations->count() }};
        const locationIds = @json($locations->pluck('id'));

        function currentFilters() {
            let minAge = $('#filterAge').val();
            if (minAge === 'custom') {
                minAge = $('#filterAgeCustom').val();
            }
            return {
                category_id : $('#filterCategory').val() || '',
                location_id : $('#filterLocation').val() || '',
                stock       : $('#filterStock').val() || '',
                min_age     : minAge || '',
                sort_by     : $('#sortBy').val() || '',
                from_date   : $('input[name="from_date"]').val() || '',
                to_date     : $('input[name="to_date"]').val() || '',
            };
        }

        function refreshTotals() {
            $.get('{{ route('admin.reports.stock-inventory.totals') }}', currentFilters(), function (res) {
                locationIds.forEach(function (locId) {
                    $('#stockTableFooterRow td[data-loc-total="' + locId + '"]').html(res.location_totals[locId] ?? '0');
                });
                $('#stockTableFooterRow td[data-footer="qty"]').html(res.qty_total);
                $('#stockTableFooterRow td[data-footer="purchase"]').html(res.purchase_total);
                $('#stockTableFooterRow td[data-footer="mrp"]').html(res.mrp_total);

                if (res.product_count !== undefined) {
                    $('#statActiveProductCount').text(res.product_count);
                }
                if (res.qty_total !== undefined) {
                    $('#statTotalStockDisplay').html(res.qty_total);
                }
                if (res.purchase_total !== undefined) {
                    $('#statTotalPurchaseValueDisplay').text(res.purchase_total);
                }
                if (res.mrp_total !== undefined) {
                    $('#statTotalMrpValueDisplay').text(res.mrp_total);
                }
                if (res.soldout_count !== undefined) {
                    $('#statSoldoutProductCount').text(res.soldout_count);
                }
                if (res.location_chart_data && res.stacked_chart_data) {
                    updateCharts(res.location_chart_data, res.stacked_chart_data);
                }
            });
        }

        function renderVariantChild(variants) {
            let html = '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
            html += '<table class="table table-sm mb-0 variant-table">';
            html += '<thead class="table-light"><tr><th style="width: 40px;">#</th><th>Variant</th><th>Last Purchase Date</th>';
            html += '<th class="text-center" colspan="' + locationCount + '">Location Stock</th>';
            html += '<th class="text-center">Total Qty</th><th class="text-end">Purchase Value</th><th class="text-end">MRP Value</th><th class="text-center">Inventory Age</th></tr></thead>';
            html += '<tbody>';
            variants.forEach(function (v) {
                html += '<tr>';
                html += '<td>' + v.index + '</td>';
                html += '<td class="ps-4">' + v.name + '</td>';
                html += '<td>' + v.last_purchase_display + '</td>';
                html += v.loc_badges;
                html += '<td class="text-center">' + v.total_badge + '</td>';
                html += '<td class="text-end fw-semibold">' + v.purchase_value + '</td>';
                html += '<td class="text-end fw-semibold text-success">' + v.mrp_value + '</td>';
                html += '<td class="text-center">' + v.age_badge + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            return html;
        }

        function initReport() {
            if ($.fn.DataTable.isDataTable('#stockTable')) {
                $('#stockTable').DataTable().destroy();
            }

            table = $('#stockTable').DataTable({
                responsive : false,
                serverSide : true,
                processing : true,
                pageLength : 25,
                ordering   : true,
                order      : [[1, 'asc']],
                ajax: {
                    url: '{{ route('admin.reports.stock-inventory.data') }}',
                    data: function (d) {
                        Object.assign(d, currentFilters());
                    }
                },
                columns: [
                    { data: 'index', orderable: false, searchable: false },
                    { data: 'name', name: 'name', orderable: true },
                    { data: 'last_purchase_display', name: 'last_purchase', orderable: true },
                    { data: 'barcode', name: 'barcode', orderable: true },
                    { data: 'category', name: 'category', orderable: true },
                    ...Array.from({ length: locationCount }, function () {
                        return { data: null, defaultContent: '', orderable: false, searchable: false };
                    }),
                    { data: 'total_badge', name: 'total_qty', className: 'text-center', orderable: true },
                    { data: 'purchase_value', name: 'purchase_value', className: 'text-end', orderable: true },
                    { data: 'mrp_value', name: 'mrp_value', className: 'text-end', orderable: true },
                    { data: 'age_badge', name: 'age', className: 'text-center', orderable: true },
                ],
                createdRow: function (row, data) {
                    const $row = $(row);
                    const $placeholders = $row.find('td').slice(5, 5 + locationCount);
                    $placeholders.remove();
                    $($row.find('td').eq(4)).after(data.loc_badges);
                    $row.attr('data-product-id', data.id);
                },
                drawCallback: function () {
                    refreshTotals();
                }
            });

            table.on('xhr.dt draw.dt', function () {
                if (window.hideAjaxLoader) {
                    window.hideAjaxLoader();
                    $('.loader-status').text('Loading');
                }
                $('#stockTable_processing').css('display', 'none');
            });

            $('#stockTable tbody').off('click', '.variant-toggle').on('click', '.variant-toggle', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $btn = $(this);
                const row = table.row($btn.closest('tr'));
                const rowData = row.data();

                if (row.child.isShown()) {
                    row.child.hide();
                    $btn.removeClass('is-open').attr('aria-expanded', 'false');
                } else if (rowData && rowData.variants && rowData.variants.length > 0) {
                    row.child(renderVariantChild(rowData.variants)).show();
                    $btn.addClass('is-open').attr('aria-expanded', 'true');
                }
            });

            $('#filterAge').off('change').on('change', function () {
                $('#customAgeWrapper').toggleClass('d-none', $(this).val() !== 'custom');
            });
        }

        let chartLocation = null;
        let chartStacked = null;

        function updateCharts(locationChartData, stackedChartData) {
            if (chartLocation && locationChartData) {
                chartLocation.updateOptions({
                    xaxis: { categories: locationChartData.map(l => l.name) }
                });
                chartLocation.updateSeries([{ name: 'Total Stock', data: locationChartData.map(l => l.stock) }]);
            }
            if (chartStacked && stackedChartData) {
                const locationNames = @json($locations->pluck('name'));
                const stackedSeries = locationIds.map(function (locId, i) {
                    return {
                        name: locationNames[i],
                        data: stackedChartData.map(p => p[locId] || 0)
                    };
                });
                chartStacked.updateOptions({
                    xaxis: { categories: stackedChartData.map(p => p.name) }
                });
                chartStacked.updateSeries(stackedSeries);
            }
        }

        function initCharts() {
            const locationChartData = JSON.parse(document.getElementById('locationChartData').textContent || '[]');
            const stackedChartData = JSON.parse(document.getElementById('stackedChartData').textContent || '[]');
            const locationNames = @json($locations->pluck('name'));

            chartLocation = new ApexCharts(document.getElementById('locationStockChart'), {
                chart  : { type: 'bar', height: 380, toolbar: { show: false } },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '55%',
                        distributed: true
                    }
                },
                colors  : ['#B4771E', '#28c76f', '#328693', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8'],
                series : [{ name: 'Total Stock', data: locationChartData.map(l => l.stock) }],
                xaxis  : {
                    categories: locationChartData.map(l => l.name),
                    labels: {
                        style: { colors: '#5d596c', fontFamily: 'Public Sans' },
                        formatter: function(val) { return parseInt(val); }
                    }
                },
                yaxis  : {
                    labels: { style: { colors: '#5d596c', fontFamily: 'Public Sans', fontWeight: 500 } }
                },
                dataLabels : {
                    enabled: true,
                    style: { fontSize: '11px', fontFamily: 'Public Sans', fontWeight: '600', colors: ['#fff'] },
                    formatter: function(val) { return parseInt(val); }
                },
                legend: { show: false },
                tooltip: { y: { formatter: function(val) { return val + ' Units'; } } },
                grid: {
                    borderColor: '#e5e5e5',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } },
                    padding: { top: -15, right: 10, bottom: -10, left: 10 }
                }
            });
            chartLocation.render();

            const stackedSeries = locationIds.map(function (locId, i) {
                return {
                    name : locationNames[i],
                    data : stackedChartData.map(p => p[locId] || 0),
                };
            });

            chartStacked = new ApexCharts(document.getElementById('stackedStockChart'), {
                chart  : { type: 'bar', height: 380, stacked: true, toolbar: { show: false } },
                series : stackedSeries,
                xaxis  : {
                    categories: stackedChartData.map(p => p.name),
                    labels: {
                        style: { colors: '#5d596c', fontFamily: 'Public Sans' },
                        formatter: function(val) { return parseInt(val); }
                    }
                },
                yaxis  : {
                    labels: { style: { colors: '#5d596c', fontFamily: 'Public Sans', fontWeight: 500 } }
                },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
                dataLabels : {
                    enabled: true,
                    style: { fontSize: '10px', fontFamily: 'Public Sans', fontWeight: '600', colors: ['#fff'] },
                    formatter: function(val) { return val > 0 ? val : ''; }
                },
                legend     : {
                    position: 'bottom',
                    fontFamily: 'Public Sans',
                    fontSize: '11px',
                    labels: { colors: '#5d596c' },
                    itemMargin: { horizontal: 8, vertical: 4 }
                },
                grid: {
                    borderColor: '#e5e5e5',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } },
                    padding: { top: -15, right: 10, bottom: -10, left: 10 }
                },
                tooltip: { y: { formatter: function(val) { return val + ' Units'; } } }
            });
            chartStacked.render();
        }

        function initDatePickers() {
            if (typeof $.fn.flatpickr === 'undefined') return;

            $('#filterForm .flatpickr').each(function () {
                if (this._flatpickr) this._flatpickr.destroy();
            });
            $('#filterForm .flatpickr').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
            });
        }

        function updateFilterButtonsVisibility() {
            const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                return $(el).val();
            });
            $('#filterActionButtons').toggleClass('d-none', !hasValue);
        }

        $(document).on('input change', '#filterForm', function () {
            updateFilterButtonsVisibility();
        });
        updateFilterButtonsVisibility();

        function reloadStockTable() {
            if (window.showAjaxLoader) {
                $('.loader-status').text('Filtering Stock Report...');
                window.showAjaxLoader();
            }
            if (table) {
                table.ajax.reload(null, false);
            }
        }

        $(document).on('click', '#applyFiltersBtn', function () {
            reloadStockTable();
        });

        $(document).on('click', '#clearFiltersBtn', function() {
            $('#filterCategory').val('').trigger('change.select2');
            $('#filterLocation').val('').trigger('change.select2');
            $('#filterStock').val('').trigger('change.select2');
            $('#filterAge').val('').trigger('change.select2');
            $('#filterAgeCustom').val('');
            $('#customAgeWrapper').addClass('d-none');
            $('#sortBy').val('').trigger('change.select2');
            $('#filterForm .flatpickr').each(function () {
                if (this._flatpickr) this._flatpickr.clear();
            });
            reloadStockTable();
        });

        $(document).on('click', '#exportExcelBtn', function() {
            let params = currentFilters();
            let url = "{{ route('admin.reports.stock-inventory.export-excel') }}";
            let queryArr = [];
            for (let k in params) {
                if (params[k]) {
                    queryArr.push(k + '=' + encodeURIComponent(params[k]));
                }
            }
            if (queryArr.length > 0) {
                url += '?' + queryArr.join('&');
            }

            if (window.showAjaxLoader) {
                $('.loader-status').text('Exporting Excel');
                window.showAjaxLoader();
            }

            $.ajax({
                url: url,
                type: 'GET',
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data, status, xhr) {
                    if (window.hideAjaxLoader) {
                        window.hideAjaxLoader();
                        $('.loader-status').text('Loading');
                    }

                    const disposition = xhr.getResponseHeader('Content-Disposition');
                    let filename = 'stock_inventory_report.xlsx';
                    if (disposition && disposition.indexOf('filename=') !== -1) {
                        const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }

                    const blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    const downloadUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = downloadUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(downloadUrl);
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Excel report downloaded successfully!');
                    }
                },
                error: function () {
                    if (window.hideAjaxLoader) {
                        window.hideAjaxLoader();
                        $('.loader-status').text('Loading');
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to export report. Please try again.');
                    }
                }
            });
        });

        initReport();
        initCharts();
        initDatePickers();
    });
    </script>
@endsection
