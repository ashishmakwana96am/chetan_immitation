<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Catalog</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 7mm;
        }

        body {
            margin: 0;
            color: #28211e;
            font: 9pt "DejaVu Sans", sans-serif;
        }

        .page-break {
            page-break-after: always;
        }

        .catalog-header,
        .catalog-grid,
        .catalog-footer {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .catalog-header {
            margin-bottom: 3mm;
            border-bottom: 1pt solid #a4773e;
        }

        .catalog-header td {
            height: 19mm;
            padding: 0;
            vertical-align: middle;
        }

        .catalog-logo {
            display: block;
            width: 30mm;
            height: auto;
        }

        .catalog-title {
            color: #805a2e;
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 1pt;
            text-align: right;
            text-transform: uppercase;
        }

        .product-cell {
            width: 49.25%;
            padding: 0;
            vertical-align: top;
        }

        .column-gap {
            width: 1.5%;
            padding: 0;
        }

        .catalog-grid + .catalog-grid {
            margin-top: 3mm;
        }

        .product-slot {
            height: 125mm;
        }

        .product-card {
            text-align: center;
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .product-card td {
            border: 0.85pt solid #b89e7c;
        }

        .image-area {
            height: 103mm;
            padding: 0;
            vertical-align: middle;
            text-align: center;
            font-size: 8pt;
            line-height: 1;
            color: #70665d;
        }

        .no-price .image-area {
            height: 109mm;
        }

        .product-image {
            max-width: 93mm;
            max-height: 101mm;
            vertical-align: middle;
        }

        .no-price .product-image {
            max-height: 107mm;
        }

        .product-details {
            padding: 1.5mm 3mm;
            background-color: #faf6ef;
        }

        .product-name {
            height: 8.5mm;
            overflow-wrap: break-word;
            font-size: 9pt;
            font-weight: bold;
            line-height: 4mm;
        }

        .product-meta {
            height: 3.5mm;
            overflow: hidden;
            white-space: nowrap;
            color: #6d6055;
            font-size: 7pt;
            line-height: 3.5mm;
        }

        .meta-separator {
            margin: 0 1mm;
            color: #9f7545;
        }

        .price-section {
            height: 6mm;
            overflow: hidden;
            white-space: nowrap;
            line-height: 5mm;
        }

        .previous-price {
            margin-right: 1mm;
            color: #6d6055;
            font-size: 8pt;
            text-decoration: line-through;
        }

        .product-price {
            color: #774d21;
            font-size: 11pt;
            font-weight: bold;
        }

        .catalog-footer {
            margin-top: 2mm;
            border-top: 0.5pt solid #dccfbe;
            color: #6d6055;
            font-size: 7pt;
        }

        .catalog-footer td {
            padding: 1mm 0 0;
        }

        .page-number {
            text-align: right;
        }
    </style>
</head>
<body>
    @php($pages = array_chunk($items, 4) ?: [[]])

    @foreach($pages as $pageIndex => $pageItems)
        <div class="{{ $loop->last ? '' : 'page-break' }}">
            <table class="catalog-header">
                <tr>
                    <td>
                        <img src="{{ public_path('assets/img/logo.png') }}" alt="Chetan Imitation" class="catalog-logo">
                    </td>
                    <td class="catalog-title">Product Catalog</td>
                </tr>
            </table>

            @foreach(array_chunk(array_pad($pageItems, 4, null), 2) as $row)
                <table class="catalog-grid{{ $priceDisplay === 'none' ? ' no-price' : '' }}">
                    <tr>
                        @foreach($row as $item)
                            @unless($loop->first)
                                <td class="column-gap"></td>
                            @endunless
                            <td class="product-cell">
                                <div class="product-slot">
                                    @if($item !== null)
                                        <table class="product-card">
                                            <tr>
                                                <td class="image-area">
                                                    @if(!empty($item['image_base64']))
                                                        <img src="{{ $item['image_base64'] }}" alt="{{ $item['name'] }}" class="product-image">
                                                    @else
                                                        No image available
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="product-details">
                                                    <div class="product-name">
                                                        {{ mb_strimwidth($item['name'], 0, 52, '…') }}
                                                    </div>
                                                    <div class="product-meta">
                                                        {{ $item['barcode'] }}
                                                        @if(!empty($item['sub_category']))
                                                            <span class="meta-separator">&bull;</span>
                                                            {{ $item['sub_category'] }}
                                                        @endif
                                                    </div>

                                                    @if($priceDisplay !== 'none')
                                                        <div class="price-section">
                                                            @if($priceDisplay === 'both' && ($item['raw_mrp'] ?? 0) > ($item['raw_sale'] ?? 0))
                                                                <span class="previous-price">{{ $item['mrp'] }}</span>
                                                            @endif
                                                            <span class="product-price">{{ $priceDisplay === 'mrp' ? $item['mrp'] : $item['sale_price'] }}</span>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    @elseif($pageItems === [] && $loop->parent->first && $loop->first)
                                        No products available
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endforeach

            <table class="catalog-footer">
                <tr>
                    <td>Chetan Imitation</td>
                    <td class="page-number">Page {{ $pageIndex + 1 }} of {{ count($pages) }}</td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
