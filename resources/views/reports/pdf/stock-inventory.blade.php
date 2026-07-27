<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Inventory Report</title>
    <style>
        @page { size: A4 landscape; margin: 6mm 5mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 7.5px; color: #222; margin: 0; padding: 0; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; border-bottom: 2px solid #333; padding-bottom: 4px; }
        .company-name { font-size: 14px; font-weight: bold; color: #111; letter-spacing: 0.5px; }
        .report-title { font-size: 11px; font-weight: bold; color: #444; text-transform: uppercase; text-align: right; }
        .report-meta { font-size: 7.5px; color: #555; margin-top: 2px; }
        
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .summary-box td { border: 1px solid #bbb; padding: 4px 6px; background: #f8f9fa; }
        .summary-label { font-size: 7px; color: #555; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 9.5px; font-weight: bold; color: #111; margin-top: 1px; }
        
        table.data-table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 10px; word-wrap: break-word; }
        table.data-table th, table.data-table td { border: 1px solid #aaa; padding: 2px 3px; text-align: left; font-size: 7px; vertical-align: middle; word-break: break-all; overflow: hidden; }
        table.data-table th { background-color: #e9ecef; font-weight: bold; text-transform: uppercase; color: #222; border-bottom: 1.5px solid #555; line-height: 1.1; }
        
        .product-img { width: 22px; height: 22px; object-fit: cover; border-radius: 2px; border: 1px solid #ccc; display: block; margin: 0 auto; }
        .variant-indent { padding-left: 6px; color: #555; font-style: italic; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        
        .badge-category { background-color: #e2e3e5; color: #41464b; padding: 1px 3px; border-radius: 2px; font-size: 6.5px; }

        tfoot tr td { background-color: #e9ecef; font-weight: bold; border-top: 1.5px solid #333; border-bottom: 1.5px solid #333; font-size: 7px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">CHETAN IMITATION</div>
            </td>
            <td style="vertical-align: top; text-align: right;">
                <div class="report-title">STOCK INVENTORY REPORT</div>
                <div class="report-meta">Generated Date: {{ date('d-m-Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    @php
        $totalItems = $productsList->count();
        $parentCount = $productsList->where('is_parent', true)->count();
        $totalStockSum = $productsList->where('is_parent', true)->sum('total');
        $totalPurchaseVal = $productsList->where('is_parent', true)->sum('purchase_value');
        $totalMrpVal = $productsList->where('is_parent', true)->sum('mrp_value');

        $totalPairUnits = $productsList->where('is_parent', true)->sum('pair_count');
        $totalLoosePcs  = $productsList->where('is_parent', true)->sum('loose_pcs');

        $reportStockParts = [];
        if ($totalPairUnits > 0) {
            $reportStockParts[] = number_format($totalPairUnits) . ' Pair' . ($totalPairUnits > 1 ? 's' : '');
        }
        if ($totalLoosePcs > 0 || count($reportStockParts) === 0) {
            $reportStockParts[] = number_format($totalLoosePcs) . ' Pcs';
        }
        $reportStockDisplay = implode('<br>', $reportStockParts);

        $locCount = count($locations);
        $locWidth = $locCount > 0 ? (49 / $locCount) : 0;
    @endphp

    <table class="summary-box">
        <tr>
            <td style="width: 25%;">
                <div class="summary-label">Total Products</div>
                <div class="summary-value">{{ $parentCount }} Products</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Stock Quantity</div>
                <div class="summary-value">{!! $reportStockDisplay !!}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total Purchase Value</div>
                <div class="summary-value">{{ format_price($totalPurchaseVal) }}</div>
            </td>
            <td style="width: 25%;">
                <div class="summary-label">Total MRP Value</div>
                <div class="summary-value">{{ format_price($totalMrpVal) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 2%;" class="text-center">#</th>
                <th style="width: 3%;" class="text-center">Img</th>
                <th style="width: 16%;">Product Name / Variant</th>
                <th style="width: 7%;">Barcode</th>
                <th style="width: 7%;">Category</th>
                @foreach($locations as $loc)
                    <th style="width: {{ $locWidth }}%;" class="text-center">{{ $loc->name }}</th>
                @endforeach
                <th style="width: 5%;" class="text-right">Total</th>
                <th style="width: 7%;" class="text-right">Purch Val</th>
                <th style="width: 7%;" class="text-right">MRP Val</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productsList as $index => $item)
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
                    @foreach($locations as $loc)
                        <td class="text-right">{!! $item['formatted_loc_stock'][$loc->id] ?? number_format($item['stock'][$loc->id] ?? 0) !!}</td>
                    @endforeach
                    <td class="text-right fw-bold">{!! $item['formatted_stock'] ?? number_format($item['total']) !!}</td>
                    <td class="text-right">{{ format_price($item['purchase_value']) }}</td>
                    <td class="text-right">{{ format_price($item['mrp_value']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 8 + count($locations) }}" class="text-center" style="padding: 15px;">No inventory items found matching the criteria.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right fw-bold">Grand Total:</td>
                @foreach($locations as $loc)
                    @php
                        $locSum = $productsList->where('is_parent', true)->sum(function($p) use ($loc) { return $p['stock'][$loc->id] ?? 0; });
                    @endphp
                    <td class="text-right fw-bold">{{ number_format($locSum) }}</td>
                @endforeach
                <td class="text-right fw-bold">{{ number_format($totalStockSum) }}</td>
                <td class="text-right fw-bold">{{ format_price($totalPurchaseVal) }}</td>
                <td class="text-right fw-bold">{{ format_price($totalMrpVal) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
