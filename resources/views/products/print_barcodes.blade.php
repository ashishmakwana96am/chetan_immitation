<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Labels</title>
    <style>
        @page {
            size: 232.44pt {{ $pdfHeight }}pt;
            margin: 0px !important;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif !important;
            color: #000;
            line-height: 1 !important;
        }
        html, body {
            margin: 0px !important;
            padding: 0px !important;
            width: 232.44pt !important;
            height: {{ $pdfHeight }}pt !important;
            background: #fff;
            overflow: hidden !important;
        }
        .labels-container-table {
            width: 187.08pt !important; /* 37mm + 29mm = 66mm = 187.08pt */
            border-collapse: separate !important;
            border-spacing: 0 2pt !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        .label-row {
            height: 28.35pt !important; /* 10mm height */
            max-height: 28.35pt !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .zone-front-td {
            width: 104.88pt !important; /* 37mm */
            height: 28.35pt !important;
            padding: 1pt 3pt !important;
            vertical-align: middle;
            border: none !important;
        }
        .zone-back-td {
            width: 82.2pt !important; /* 29mm */
            height: 28.35pt !important;
            padding: 1pt 2pt !important;
            text-align: center;
            vertical-align: middle;
            border: none !important;
        }
        .mrp-line {
            font-size: 7.5pt !important;
            font-weight: bold !important;
            line-height: 1 !important;
            white-space: nowrap;
            overflow: hidden;
            margin-bottom: 2pt !important;
        }
        .rupee-symbol {
            font-family: "DejaVu Sans", sans-serif !important;
            font-weight: normal !important;
        }
        .code-line {
            font-size: 5pt !important;
            font-weight: normal !important;
            line-height: 1 !important;
            white-space: nowrap;
            overflow: hidden;
        }
        .variations-line {
            font-size: 5pt !important;
            font-weight: normal !important;
            line-height: 1 !important;
            white-space: nowrap;
            overflow: hidden;
            margin-bottom: 1.5pt !important;
        }
        .category-line {
            font-size: 5pt !important;
            font-weight: normal !important;
            line-height: 1 !important;
            white-space: nowrap;
            overflow: hidden;
            margin-top: 1.5pt !important;
        }
        .barcode-container {
            width: 100% !important;
            height: 8pt !important;
            overflow: hidden;
            text-align: center;
        }
        .barcode-img {
            width: 100% !important;
            height: 8pt !important;
            display: block;
        }
    </style>
</head>
<body>
    <table class="labels-container-table" cellpadding="0" cellspacing="0">
        @foreach($printItems as $item)
            @for($i = 0; $i < $item['qty']; $i++)
                <tr class="label-row">
                    <td class="zone-front-td">
                        <div class="mrp-line">MRP : {!! $item['salePrice'] !!}</div>
                        <div class="code-line">{{ $item['barcodeText'] }}</div>
                    </td>
                    <td class="zone-back-td">
                        <div class="variations-line">{!! !empty($item['variations']) ? e($item['variations']) : '&nbsp;' !!}</div>
                        <div class="barcode-container">
                            <img class="barcode-img" src="{{ $item['barcodeBase64'] }}" />
                        </div>
                        <div class="category-line">{{ $item['category'] }}</div>
                    </td>
                </tr>
            @endfor
        @endforeach
    </table></body></html>
