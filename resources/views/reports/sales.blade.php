@extends('layouts.app')

@section('title', 'Sale Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Sale Report</h4>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Filter Report</h5></div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.sales') }}" class="row g-3">
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" />
                </div>
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" />
                </div>
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select no-select2">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $paymentStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ $paymentStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-md-2.4 col-sm-6">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select no-select2">
                        <option value="">All Methods</option>
                        <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="online" {{ $paymentMethod === 'online' ? 'selected' : '' }}>Online</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter me-1"></i> Apply Filter
                    </button>
                    <a href="{{ route('admin.reports.sales') }}" class="btn btn-label-secondary">
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
                            <span class="text-muted">Total Sales</span>
                            <h4 class="mb-0 mt-1">{{ format_price($totalSales) }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-chart-line ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Orders</span>
                            <h4 class="mb-0 mt-1">{{ $orderCount }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-shopping-cart ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Avg Order Value</span>
                            <h4 class="mb-0 mt-1">{{ format_price($avgOrderValue) }}</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-calculator ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Payment Split</span>
                            <h5 class="mb-0 mt-2 text-nowrap">
                                <span class="badge bg-label-success">{{ $paidCount }} Paid</span>
                                <span class="badge bg-label-warning">{{ $pendingCount }} Pend</span>
                            </h5>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-wallet ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Sales Trend (Area) -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Sales Trend (Monthly)</h5></div>
                <div class="card-body">
                    <div id="salesTrendChart"></div>
                </div>
            </div>
        </div>

        <!-- Payment Method Distribution (Donut) -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Sales by Payment Method</h5></div>
                <div class="card-body">
                    <div id="paymentMethodChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Table & Top Selling Products Tabs -->
    <div class="card">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-orders" role="tab">
                        <i class="ti ti-shopping-cart me-1"></i> Orders List
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-products" role="tab">
                        <i class="ti ti-box me-1"></i> Top Selling Products
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content p-0">
            <!-- Orders Tab -->
            <div class="tab-pane fade show active" id="tab-orders" role="tabpanel">
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="salesReportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Location</th>
                                <th>Payment Status</th>
                                <th>Method</th>
                                <th class="text-end text-nowrap">Final Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $index => $order)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><code>{{ $order->order_no }}</code></td>
                                    <td>{{ format_date($order->created_at) }}</td>
                                    <td><span class="fw-semibold">{{ $order->customer->name ?? 'Walk-in' }}</span></td>
                                    <td><span class="badge bg-label-secondary">{{ $order->location->name ?? '-' }}</span></td>
                                    <td>
                                        @php
                                            $payColors = [
                                                'pending' => 'bg-label-warning',
                                                'paid'    => 'bg-label-success',
                                            ];
                                            $payLabels = [
                                                'pending' => 'Pending',
                                                'paid'    => 'Paid',
                                            ];
                                            $badgeColor = $payColors[$order->payment_status] ?? 'bg-label-secondary';
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">{{ $payLabels[$order->payment_status] ?? ucfirst($order->payment_status) }}</span>
                                    </td>
                                    <td><span class="text-uppercase small fw-semibold">{{ str_replace('_', ' ', $order->payment_method) }}</span></td>
                                    <td class="text-end text-nowrap fw-semibold">{{ format_price($order->final_amount) }}</td>
                                    <td>
                                        <a href="{{ route('admin.sales.show', $order->id) }}" class="btn btn-sm btn-icon btn-label-secondary" data-bs-toggle="tooltip" title="View">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Selling Products Tab -->
            <div class="tab-pane fade" id="tab-products" role="tabpanel">
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="productsReportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th class="text-end text-nowrap">Qty Sold</th>
                                <th class="text-end text-nowrap">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productSales as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('admin.products.show', $item->product_id) }}" class="fw-semibold">
                                            {{ $item->product->name ?? 'Unknown' }}
                                        </a>
                                    </td>
                                    <td><code>{{ $item->product->sku ?? '-' }}</code></td>
                                    <td class="text-end text-nowrap fw-bold text-info">{{ $item->qty_sold }}</td>
                                    <td class="text-end text-nowrap fw-bold text-success">{{ format_price($item->total_revenue) }}</td>
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
        $('#salesReportTable').DataTable({
            responsive : false,
            order      : [[2, 'desc']],
        });

        $('#productsReportTable').DataTable({
            responsive : false,
            order      : [[3, 'desc']], // Sort by Qty Sold by default!
        });

        // Sales Trend Chart
        const salesTrend = @json($salesTrend);
        const months = Object.keys(salesTrend);
        const values = Object.values(salesTrend);

        if (months.length > 0) {
            new ApexCharts(document.getElementById('salesTrendChart'), {
                chart: { type: 'area', height: 320, toolbar: { show: false } },
                series: [{ name: 'Sales', data: values }],
                xaxis: { categories: months },
                colors: ['#28c76f'],
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.5,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
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
            $('#salesTrendChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }

        // Payment Method Chart
        const paymentMethodData = @json($paymentMethodData);
        const methods = Object.keys(paymentMethodData);
        const methodValues = Object.values(paymentMethodData);

        if (methods.length > 0) {
            new ApexCharts(document.getElementById('paymentMethodChart'), {
                chart: { type: 'donut', height: 320 },
                series: methodValues,
                labels: methods.map(m => m.toUpperCase().replace('_', ' ')),
                legend: { position: 'bottom' },
                dataLabels: { enabled: true },
            }).render();
        } else {
            $('#paymentMethodChart').html('<div class="text-center py-5 text-muted">No data available</div>');
        }
    });
    </script>
@endsection
