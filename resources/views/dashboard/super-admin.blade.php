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

    <!-- Stats Row 1 — Sales & Products Overview -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">Today's Sales</span>
                            <h4 class="mb-0 mt-1 text-primary">{{ format_price($salesStats['today']) }}</h4>
                            <small class="text-muted d-block mt-1">Credit Balance: <span class="fw-semibold {{ $customerOutstandingBalance < 0 ? 'text-danger' : 'text-info' }}">{{ format_price($customerOutstandingBalance) }}</span></small>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-currency-rupee ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">This Month Sales</span>
                            <h4 class="mb-0 mt-1 text-success">{{ format_price($salesStats['this_month']) }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-trending-up ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">Products</span>
                            <h4 class="mb-0 mt-1">{{ $stats['products'] }}</h4>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-box ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row 2 — Stock & Inventory Overview -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">Total Stock Inventory</span>
                            <div class="h5 mb-0 mt-1 text-info fw-bold lh-sm">
                                @if(!empty($stockStats['stock_parts']))
                                    @foreach($stockStats['stock_parts'] as $part)
                                        <div style="font-size: 0.95rem; line-height: 1.2;">{{ $part }}</div>
                                    @endforeach
                                @else
                                    {{ $stockStats['stock_display'] }}
                                @endif
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-stack ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">Total Purchase Value</span>
                            <h4 class="mb-0 mt-1 text-primary">{{ format_price($stockStats['total_purchase_value']) }}</h4>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ti ti-currency-rupee ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">Total MRP Value</span>
                            <h4 class="mb-0 mt-1 text-success">{{ format_price($stockStats['total_mrp_value']) }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-chart-dots ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row 3 — Entities & Users -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">Customers</span>
                            <h4 class="mb-0 mt-1">{{ $stats['customers'] }}</h4>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-users ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">Suppliers</span>
                            <h4 class="mb-0 mt-1">{{ $stats['suppliers'] }}</h4>
                        </div>
                        <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-truck ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted small text-nowrap">Staff Users</span>
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
                                                <td class="text-end"><span class="badge {{ $lowStockQty == 0 ? 'bg-label-danger' : 'bg-label-warning' }}">{!! $product->formatStockDisplay($lowStockQty) !!}</span></td>
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

        // Sales by Location Pie Chart
        // Locations are already sorted by sales (highest first). A pie reads
        // cleanly up to ~5-6 slices, so beyond that the tail is folded into "Other"
        // instead of drawing a wedge per location.
        var LOCATION_SLICE_LIMIT = 5;
        var topLocations   = salesByLocation.slice(0, LOCATION_SLICE_LIMIT);
        var otherLocations = salesByLocation.slice(LOCATION_SLICE_LIMIT);
        var otherTotal      = otherLocations.reduce((sum, l) => sum + (parseFloat(l.total_sales) || 0), 0);

        var locationLabels = topLocations.map(l => l.name);
        var locationSeries = topLocations.map(l => parseFloat(l.total_sales) || 0);
        if (otherTotal > 0) {
            locationLabels.push('Other (' + otherLocations.length + ')');
            locationSeries.push(otherTotal);
        }

        new ApexCharts(document.getElementById('locationSalesChart'), {
            chart   : { type: 'pie', height: 280 },
            labels  : locationLabels,
            series  : locationSeries,
            colors  : ['#B4771E', '#28c76f', '#328693', '#7367f0', '#ea5455', '#c3c2b7'],
            stroke  : { colors: ['#fff'], width: 2 },
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '11px',
                    fontFamily: 'Public Sans',
                    fontWeight: '600'
                },
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            },
            legend: {
                position: 'bottom',
                fontFamily: 'Public Sans',
                labels: { colors: '#5d596c' },
                markers: { offsetX: -2 },
                itemMargin: { horizontal: 8, vertical: 4 }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '₹' + val.toLocaleString('en-IN');
                    }
                }
            },
            noData  : { text: 'No sales data yet' },
            responsive: [{
                breakpoint: 992,
                options: {
                    chart: { height: 260 },
                    legend: { fontSize: '11px', itemMargin: { horizontal: 6, vertical: 2 } }
                }
            }]
        }).render();

    });
    </script>
@endsection
