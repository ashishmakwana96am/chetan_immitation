<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products Report</title>
    <style>
        @page { size: A4 landscape; margin: 8mm 6mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #222; margin: 0; padding: 0; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        .company-name { font-size: 15px; font-weight: bold; color: #111; letter-spacing: 0.5px; }
        .report-title { font-size: 12px; font-weight: bold; color: #444; text-transform: uppercase; text-align: right; }
        .report-meta { font-size: 8px; color: #555; margin-top: 2px; }
        
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary-box td { border: 1px solid #bbb; padding: 5px 8px; background: #f8f9fa; }
        .summary-label { font-size: 7.5px; color: #555; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 10px; font-weight: bold; color: #111; margin-top: 1px; }
        
        table.data-table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 10px; word-wrap: break-word; }
        table.data-table th, table.data-table td { border: 1px solid #aaa; padding: 3px 4px; text-align: left; font-size: 7.5px; vertical-align: middle; word-break: break-all; overflow: hidden; }
        table.data-table th { background-color: #e9ecef; font-weight: bold; text-transform: uppercase; color: #222; border-bottom: 1.5px solid #555; }
        
        .product-img { width: 24px; height: 24px; object-fit: cover; border-radius: 2px; border: 1px solid #ccc; display: block; margin: 0 auto; }
        .variant-indent { padding-left: 8px; color: #555; font-style: italic; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        
        .badge-active { background-color: #d1e7dd; color: #0f5132; padding: 1px 4px; border-radius: 2px; font-size: 7px; font-weight: bold; }
        .badge-inactive { background-color: #f8d7da; color: #842029; padding: 1px 4px; border-radius: 2px; font-size: 7px; font-weight: bold; }
        .badge-category { background-color: #e2e3e5; color: #41464b; padding: 1px 4px; border-radius: 2px; font-size: 7px; }

        tfoot tr td { background-color: #e9ecef; font-weight: bold; border-top: 1.5px solid #333; border-bottom: 1.5px solid #333; font-size: 8px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">CHETAN IMITATION</div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="report-title">PRODUCTS REPORT</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    @php
        $totalItems = $productsList->count();
        $parentCount = $productsList->where('is_parent', true)->count();
        $totalStockSum = $productsList->where('is_parent', true)->sum('total_stock');
        $totalValuation = $productsList->sum(function($p) { return $p['is_parent'] ? ($p['total_stock'] * $p['purchase_price']) : 0; });

        $totalPairUnits = $productsList->where('is_parent', true)->sum('pair_count');
        $totalLoosePcs  = $productsList->where('is_parent', true)->sum('loose_pcs');

        $reportStockParts = [];
        if ($totalPairUnits > 0) {
            $reportStockParts[] = number_format($totalPairUnits) . ' Pair' . ($totalPairUnits > 1 ? 's' : '');
        }
        if ($totalLoosePcs > 0 || count($reportStockParts) === 0) {
            $reportStockParts[] = number_format($totalLoosePcs) . ' Pcs';
        }
        $reportStockDisplay = implode(', ', $reportStockParts);
    @endphp

    <table class="summary-box">
        <tr>
            <td style="width: 50%;">
                <div class="summary-label">Total Products</div>
                <div class="summary-value">{{ $parentCount }} Products</div>
            </td>
            <td style="width: 50%;">
                <div class="summary-label">Total Stock Quantity</div>
                <div class="summary-value">{{ $reportStockDisplay }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">#</th>
                <th style="width: 4%;" class="text-center">Img</th>
                <th style="width: 27%;">Product Name / Variant</th>
                <th style="width: 11%;">Barcode</th>
                <th style="width: 12%;">Category</th>
                <th style="width: 10%;" class="text-right">Purchase Price</th>
                <th style="width: 10%;" class="text-right">Sale Price</th>
                <th style="width: 7%;" class="text-right">Margin</th>
                <th style="width: 8%;" class="text-right">Stock</th>
                <th style="width: 8%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productsList as $index => $item)
                @php
                    $margin = $item['sale_price'] - $item['purchase_price'];
                    $marginPct = $item['purchase_price'] > 0 ? round(($margin / $item['purchase_price']) * 100, 1) : 0;
                @endphp
                <tr style="{{ !$item['is_parent'] ? 'background-color: #fafafa;' : '' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center" style="padding: 1px;">
                        @if(!empty($item['image_base64']))
                            <img src="{{ $item['image_base64'] }}" class="product-img" alt="">
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($item['is_parent'])
                            <span class="fw-bold">{{ $item['name'] }}</span>
                        @else
                            <div class="variant-indent">↳ {{ $item['variant_name'] }}</div>
                        @endif
                    </td>
                    <td><code>{{ $item['barcode'] ?: '-' }}</code></td>
                    <td><span class="badge-category">{{ $item['category'] }}</span></td>
                    <td class="text-right">{{ format_price($item['purchase_price']) }}</td>
                    <td class="text-right">{{ format_price($item['sale_price']) }}</td>
                    <td class="text-right">{{ $marginPct }}%</td>
                    <td class="text-right fw-bold">{{ $item['formatted_stock'] ?? $item['total_stock'] }}</td>
                    <td class="text-center">
                        @if($item['status'] == 1)
                            <span class="badge-active">Active</span>
                        @else
                            <span class="badge-inactive">Inactive</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px;">No products found matching the criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
