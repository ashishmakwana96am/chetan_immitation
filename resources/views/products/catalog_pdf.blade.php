<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Catalog</title>
    <style>
        @page {
            margin: 8mm 8mm 10mm 8mm;
            size: A4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 10px;
            background-color: #ffffff;
        }
        .page-container {
            width: 100%;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #B4771E;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-brand {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header-sub {
            font-size: 11px;
            color: #B4771E;
            font-weight: bold;
            margin-top: 2px;
        }
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
        }
        .grid-table tr {
            page-break-inside: avoid;
        }
        .product-cell {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #ffffff;
            padding: 8px 8px;
            text-align: center;
            height: 355px;
            box-sizing: border-box;
        }
        .product-name {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            height: 20px;
            line-height: 10px;
            overflow: hidden;
            margin-bottom: 2px;
            text-align: center;
            text-transform: uppercase;
        }
        .barcode-section {
            margin-bottom: 6px;
            text-align: center;
            font-size: 9px;
            line-height: 12px;
        }
        .barcode-text {
            font-size: 9px;
            font-weight: 600;
            color: #475569;
            font-family: monospace;
            letter-spacing: 0.5px;
        }
        .meta-sep {
            color: #cbd5e1;
            margin: 0 4px;
        }
        .product-category {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .image-container {
            height: 265px;
            line-height: 265px;
            text-align: center;
            vertical-align: middle;
            margin-bottom: 6px;
            background: #ffffff;
            border-radius: 4px;
            overflow: hidden;
        }
        .product-image {
            max-height: 260px;
            max-width: 95%;
            display: inline-block;
            vertical-align: middle;
        }
        .no-image {
            display: inline-block;
            vertical-align: middle;
            color: #94a3b8;
            font-size: 10px;
        }
        .price-section {
            padding-top: 6px;
            border-top: 1px dashed #e2e8f0;
            text-align: center;
            line-height: 16px;
        }
        .price-label {
            font-size: 11px;
            font-weight: bold;
            color: #475569;
            margin-right: 4px;
        }
        .price-mrp-struck {
            font-size: 10px;
            color: #94a3b8;
            text-decoration: line-through;
            margin-right: 5px;
        }
        .price-sale-highlight {
            font-size: 12px;
            font-weight: bold;
            color: #B4771E;
        }
    </style>
</head>
<body>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("Helvetica", "normal");
            $pdf->page_text(520, 820, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 7, array(0.5, 0.5, 0.5));
            $pdf->page_text(25, 820, "Chetan Imitation - Product Catalog", $font, 7, array(0.5, 0.5, 0.5));
        }
    </script>

    @foreach(array_chunk($items, 4) as $pageIndex => $pageItems)
        <div class="page-container" style="{{ !$loop->last ? 'page-break-after: always;' : '' }}">
            <table class="header-table">
                <tr>
                    <td>
                        <div class="header-brand">Chetan Imitation</div>
                        <div class="header-sub">Product Catalog</div>
                    </td>
                </tr>
            </table>

            <table class="grid-table">
                @foreach(array_chunk($pageItems, 2) as $row)
                    <tr>
                        @foreach($row as $item)
                            <td class="product-cell">
                                <div class="product-card">
                                    @if(!empty($item['is_first']))
                                        <div class="product-name">{{ $item['name'] }}</div>
                                        <div class="barcode-section">
                                            <span class="barcode-text">{{ $item['barcode'] }}</span>
                                            @if(!empty($item['sub_category']))
                                                <span class="meta-sep">&bull;</span>
                                                <span class="product-category">{{ $item['sub_category'] }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="product-name" style="visibility: hidden;">&nbsp;</div>
                                        <div class="barcode-section" style="visibility: hidden;">&nbsp;</div>
                                    @endif

                                    <div class="image-container">
                                        @if(!empty($item['image_base64']))
                                            <img src="{{ $item['image_base64'] }}" alt="{{ $item['name'] }}" class="product-image">
                                        @else
                                            <span class="no-image">No Image</span>
                                        @endif
                                    </div>

                                    @if($priceDisplay !== 'none')
                                        <div class="price-section">
                                            <span class="price-label">Price:</span>
                                            @if($priceDisplay === 'both' && ($item['raw_mrp'] ?? 0) > ($item['raw_sale'] ?? 0))
                                                <span class="price-mrp-struck">{{ $item['mrp'] }}</span>
                                                <span class="price-sale-highlight">{{ $item['sale_price'] }}</span>
                                            @elseif($priceDisplay === 'mrp')
                                                <span class="price-sale-highlight">{{ $item['mrp'] }}</span>
                                            @else
                                                <span class="price-sale-highlight">{{ $item['sale_price'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                        @for($i = count($row); $i < 2; $i++)
                            <td class="product-cell" style="border:none; background:transparent;"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</body>
</html>