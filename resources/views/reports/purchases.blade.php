@extends('layouts.app')

@section('title', 'Purchase Reports')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Purchase Reports</h4>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Filter Report</h5></div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.purchases') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Invoice Status</label>
                    <select name="status" class="form-select no-select2">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approve" {{ $status === 'approve' ? 'selected' : '' }}>Approve</option>
                        <option value="decline" {{ $status === 'decline' ? 'selected' : '' }}>Decline</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter me-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('admin.reports.purchases') }}" class="btn btn-label-secondary">
                        <i class="ti ti-refresh me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Purchases</span>
                            <h4 class="mb-0 mt-1">{{ format_price($totalPurchases) }}</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-currency-dollar ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Invoices</span>
                            <h4 class="mb-0 mt-1">{{ $invoiceCount }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-file-text ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Confirmed Invoices</span>
                            <h4 class="mb-0 mt-1">{{ $confirmedCount }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-circle-check ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Draft Invoices</span>
                            <h4 class="mb-0 mt-1">{{ $draftCount }}</h4>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ti ti-file-pencil ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Monthly Purchases (Bar) -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Purchases Trend (Monthly)</h5></div>
                <div class="card-body">
                    <div id="purchasesTrendChart"></div>
                </div>
            </div>
        </div>

        <!-- Supplier Distribution (Donut) -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Purchases by Supplier</h5></div>
                <div class="card-body">
                    <div id="supplierChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Table & Top Purchased Products Tabs -->
    <div class="card">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-invoices" role="tab">
                        <i class="ti ti-file-text me-1"></i> Invoices List
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-products" role="tab">
                        <i class="ti ti-box me-1"></i> Top Purchased Products
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content p-0">
            <!-- Invoices Tab -->
            <div class="tab-pane fade show active" id="tab-invoices" role="tabpanel">
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="purchasesReportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th class="text-end text-nowrap">Total Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $index => $invoice)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><code>{{ $invoice->invoice_no }}</code></td>
                                    <td>{{ format_date($invoice->created_at) }}</td>
                                    <td><span class="fw-semibold">{{ $invoice->supplier->name ?? 'Unknown' }}</span></td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-label-secondary',
                                                'approve' => 'bg-label-success',
                                                'decline' => 'bg-label-danger',
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Pending',
                                                'approve' => 'Approve',
                                                'decline' => 'Decline',
                                            ];
                                            $badgeColor = $statusColors[$invoice->status] ?? 'bg-label-secondary';
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">{{ $statusLabels[$invoice->status] ?? ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold">{{ format_price($invoice->total_amount) }}</td>
                                    <td>
                                        <a href="{{ route('admin.purchases.show', $invoice->id) }}" class="btn btn-sm btn-icon btn-label-secondary">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Purchased Products Tab -->
            <div class="tab-pane fade" id="tab-products" role="tabpanel">
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="purchasedProductsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th class="text-end text-nowrap">Qty Purchased</th>
                                <th class="text-end text-nowrap">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productPurchases as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('admin.products.show', $item->product_id) }}" class="fw-semibold">
                                            {{ $item->product->name ?? 'Unknown' }}
                                        </a>
                                    </td>
                                    <td><code>{{ $item->product->sku ?? '-' }}</code></td>
                                    <td class="text-end text-nowrap fw-bold text-info">{{ $item->qty_purchased }}</td>
                                    <td class="text-end text-nowrap fw-bold text-success">{{ format_price($item->total_cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    $(document).ready(function () {
        $('#purchasesReportTable').DataTable({
            responsive : false,
            order      : [[2, 'desc']],
        });

        $('#purchasedProductsTable').DataTable({
            responsive : false,
            order      : [[3, 'desc']], // Sort by Qty Purchased descending by default!
        });

        // Purchases Trend Chart
        const purchasesTrend = @json($purchasesTrend);
        const months = Object.keys(purchasesTrend);
        const values = Object.values(purchasesTrend);

        if (months.length > 0) {
            new ApexCharts(document.getElementById('purchasesTrendChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{ name: 'Purchases', data: values }],
                xaxis: { categories: months },
                colors: ['#7367f0'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                dataLabels: { enabled: false },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return '{{ currency_symbol() }}' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            }).render();
        } else {
            $('#purchasesTrendChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }

        // Supplier Horizontal Bar Chart
        const supplierData = @json($supplierData);
        const suppliers = Object.keys(supplierData);
        const supplierValues = Object.values(supplierData);

        if (suppliers.length > 0) {
            new ApexCharts(document.getElementById('supplierChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '55%',
                        distributed: true
                    }
                },
                colors: ['#7367f0', '#28c76f', '#00cfe8', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8'],
                series: [{
                    name: 'Purchases',
                    data: supplierValues
                }],
                xaxis: {
                    categories: suppliers,
                    labels: {
                        style: {
                            colors: '#5d596c',
                            fontFamily: 'Public Sans'
                        },
                        formatter: function(val) {
                            return '₹' + parseFloat(val).toLocaleString('en-IN');
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#5d596c',
                            fontFamily: 'Public Sans',
                            fontWeight: 500
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '11px',
                        fontFamily: 'Public Sans',
                        fontWeight: '600',
                        colors: ['#fff']
                    },
                    formatter: function(val) {
                        return '₹' + parseFloat(val).toLocaleString('en-IN');
                    },
                    offsetX: 0
                },
                legend: { show: false },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '₹' + parseFloat(val).toLocaleString('en-IN');
                        }
                    }
                },
                grid: {
                    borderColor: '#e5e5e5',
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: false } },
                    padding: { top: -15, right: 10, bottom: -10, left: 10 }
                },
                noData: { text: 'No data available' }
            }).render();
        } else {
            $('#supplierChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }
    });
    </script>
@endsection
