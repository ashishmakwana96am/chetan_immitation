@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Dashboard</h4>
            <small class="text-muted">Welcome back, {{ auth()->user()->name }}</small>
        </div>
    </div>

    <!-- Stats Row 1 — Business Overview -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Today's Sales</span>
                            <h4 class="mb-0 mt-1 text-primary">{{ format_price($salesStats['today']) }}</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-currency-rupee ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">This Month Sales</span>
                            <h4 class="mb-0 mt-1 text-success">{{ format_price($salesStats['this_month']) }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-trending-up ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Pending Sales</span>
                            <h4 class="mb-0 mt-1 text-warning">{{ $salesStats['pending'] }}</h4>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ti ti-clock ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Purchase (Confirmed)</span>
                            <h4 class="mb-0 mt-1 text-info">{{ format_price($purchaseStats['confirmed']) }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-shopping-cart ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row 2 — Counts -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Products</span>
                            <h4 class="mb-0 mt-1">{{ $stats['products'] }}</h4>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-box ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Customers</span>
                            <h4 class="mb-0 mt-1">{{ $stats['customers'] }}</h4>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-users ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Suppliers</span>
                            <h4 class="mb-0 mt-1">{{ $stats['suppliers'] }}</h4>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-truck ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Staff Users</span>
                            <h4 class="mb-0 mt-1">{{ $stats['users'] }}</h4>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-user ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">

        <!-- Monthly Sales Chart -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sales — Last 6 Months</h5>
                    <span class="badge bg-label-primary">{{ format_price($salesStats['total']) }} Total</span>
                </div>
                <div class="card-body">
                    <div id="monthlySalesChart"></div>
                </div>
            </div>
        </div>

        <!-- Sales by Location -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Sales by Location</h5></div>
                <div class="card-body">
                    <div id="locationSalesChart"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bottom Row -->
    <div class="row g-4">

        <!-- Recent Sales + Recent Contact Inquiries -->
        <div class="col-lg-6">
            <div class="row g-4">

                <!-- Recent Sales -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Sales</h5>
                            <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-label-primary whitespace-nowrap" style="white-space: nowrap;">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sale No</th>
                                        <th>Customer</th>
                                        <th>Location</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentSales as $sale)
                                        <tr>
                                            <td><a href="{{ route('admin.sales.show', $sale) }}"><code>{{ $sale->order_no }}</code></a></td>
                                            <td>{{ $sale->customer->name ?? 'Walk-in' }}</td>
                                            <td>{{ $sale->location->name ?? '-' }}</td>
                                            <td class="text-end fw-semibold text-primary">{{ format_price($sale->final_amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">No sales yet</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @can('view contact inquiries')
                <!-- Recent Contact Inquiries -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Recent Contact Inquiries
                                @if($todayInquiriesCount > 0)
                                    <span class="badge bg-label-warning ms-1">{{ $todayInquiriesCount }} today</span>
                                @endif
                            </h5>
                            <a href="{{ route('admin.contact-inquiries.index') }}" class="btn btn-sm btn-label-primary whitespace-nowrap" style="white-space: nowrap;">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Subject</th>
                                        <th>Received</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentInquiries as $inquiry)
                                        <tr>
                                            <td><a href="{{ route('admin.contact-inquiries.show', $inquiry) }}">{{ $inquiry->full_name }}</a></td>
                                            <td>{{ $inquiry->subject }}</td>
                                            <td>{{ $inquiry->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">No inquiries yet</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endcan

            </div>
        </div>

        <!-- Top Products + Low Stock -->
        <div class="col-lg-6">
            <div class="row g-4">

                <!-- Top Products -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Top Selling Products</h5></div>
                        <div class="card-body p-0">
                          <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Qty Sold</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topProducts as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ $item->product?->primary_image_url ?? asset('website/assets/images/no-image.svg') }}" alt="{{ $item->product->name ?? '' }}" class="rounded me-2 product-thumbnail" style="width: 32px; height: 32px; object-fit: cover;">
                                                    {{ $item->product->name ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="text-end">{{ $item->total_qty }}</td>
                                            <td class="text-end text-primary fw-semibold">{{ format_price($item->total_revenue) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">No data yet</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                           </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-label-warning">
                            <h5 class="mb-0"><i class="ti ti-alert-triangle me-1"></i> Low Stock Alert</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th class="text-end">Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lowStock as $product)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="rounded me-2 product-thumbnail" style="width: 32px; height: 32px; object-fit: cover;">
                                                        <a href="{{ route('admin.products.show', $product->id) }}">{{ $product->name }}</a>
                                                    </div>
                                                </td>
                                                <td>{{ $product->category->name ?? '-' }}</td>
                                                @php($lowStockQty = $product->totalAvailableStock())
                                                <td class="text-end"><span class="badge {{ $lowStockQty == 0 ? 'bg-label-danger' : 'bg-label-warning' }}">{{ $lowStockQty }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-3">All products well stocked</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    $(document).ready(function () {

        const monthlySales = @json($monthlySales);
        const salesByLocation = @json($salesByLocation);

        // Monthly Sales Chart
        new ApexCharts(document.getElementById('monthlySalesChart'), {
            chart   : { type: 'area', height: 250, toolbar: { show: false }, sparkline: { enabled: false } },
            series  : [
                { name: 'Revenue', data: monthlySales.map(m => m.amount) },
                { name: 'Orders',  data: monthlySales.map(m => m.count) },
            ],
            xaxis   : { categories: monthlySales.map(m => m.month) },
            colors  : ['#B4771E', '#28c76f'],
            stroke  : { curve: 'smooth', width: 2 },
            fill    : { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
            dataLabels: { enabled: false },
            legend  : { position: 'top' },
            yaxis   : [
                { title: { text: 'Revenue' } },
                { opposite: true, title: { text: 'Orders' } },
            ],
        }).render();

        // Sales by Location Horizontal Bar Chart
        new ApexCharts(document.getElementById('locationSalesChart'), {
            chart   : { type: 'bar', height: 280, toolbar: { show: false } },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '55%',
                    distributed: true
                }
            },
            colors  : ['#B4771E', '#28c76f', '#328693', '#ff9f43', '#ea5455', '#a873ff', '#4b9bfa', '#ff5c9f', '#ffc107', '#17a2b8'],
            series  : [{
                name: 'Sales',
                data: salesByLocation.map(l => parseFloat(l.total_sales) || 0)
            }],
            xaxis   : {
                categories: salesByLocation.map(l => l.name),
                labels: {
                    style: {
                        colors: '#5d596c',
                        fontFamily: 'Public Sans'
                    },
                    formatter: function(val) {
                        return '₹' + val.toLocaleString('en-IN');
                    }
                }
            },
            yaxis   : {
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
                    return '₹' + val.toLocaleString('en-IN');
                },
                offsetX: 0
            },
            legend: { show: false },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '₹' + val.toLocaleString('en-IN');
                    }
                }
            },
            grid: {
                borderColor: '#e5e5e5',
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } },
                padding: { top: -15, right: 10, bottom: -10, left: 10 }
            },
            noData  : { text: 'No sales data yet' },
        }).render();

    });
    </script>
@endsection
