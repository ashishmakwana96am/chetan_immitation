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
            width: 232.44pt !important; /* 2.7cm + 2.7cm + 2.8cm = 8.2cm = 232.44pt */
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        .label-row {
            height: 34.02pt !important; /* 1.2cm height */
            max-height: 34.02pt !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .zone-front-td {
            width: 76.54pt !important; /* 2.7cm */
            height: 34.02pt !important;
            padding: 1pt 3pt !important;
            vertical-align: middle;
            border: none !important;
        }
        .zone-back-td {
            width: 76.54pt !important; /* 2.7cm */
            height: 34.02pt !important;
            padding: 1pt 2pt !important;
            text-align: center;
            vertical-align: middle;
            border: none !important;
        }
        .zone-tail-td {
            width: 79.38pt !important; /* 2.8cm */
            height: 34.02pt !important;
            padding: 0 !important;
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
            height: 16pt !important;
            overflow: hidden;
            text-align: left;
        }
        .barcode-img {
            max-width: 100% !important;
            height: 16pt !important;
            display: block;
        }
    </style>
</head>
<body>
    <table class="labels-container-table" cellpadding="0" cellspacing="0">
        @foreach($printItems as $item)
            @for($i = 0; $i < $item['qty']; $i++)
                <tr class="label-row">
                    <td class="zone-front-td" style="text-align: left;">
                        <div class="code-line" style="text-align: left; margin-bottom: 2pt; padding-left: 2pt;">{{ $item['barcodeText'] }}</div>
                        <div class="barcode-container" style="text-align: left;">
                            <img class="barcode-img" src="{{ $item['barcodeBase64'] }}" style="margin: 0;" />
                        </div>
                        <div class="category-line" style="text-align: left; margin-top: 2pt; padding-left: 2pt;">{{ $item['category'] }}</div>
                    </td>
                    <td class="zone-back-td">
                        <div class="mrp-line" style="text-align: left; margin: 0; padding-left: 8pt;">MRP : {!! $item['salePrice'] !!}</div>
                    </td>
                    <td class="zone-tail-td">
                        <!-- Tail section -->
                    </td>
                </tr>
            @endfor
        @endforeach
    </table>
</body>
</html>
