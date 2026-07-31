<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<style>
    .sale-info-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 9px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        font-size: 0.875rem;
    }
    .sale-info-row:last-child { border-bottom: none; }
    .sale-info-label { color: #8592a3; font-size: 0.8rem; flex-shrink: 0; padding-right: 8px; }
    .sale-info-value { font-weight: 500; text-align: right; }
    .card { border: 1px solid rgba(75,70,92,0.1); border-radius: 0.5rem; }
    .card-header {
        background: #fff;
        border-bottom: 1px solid rgba(75,70,92,0.08);
        padding: 0.9rem 1.25rem;
        border-radius: 0.5rem 0.5rem 0 0 !important;
    }
    .card-header .card-title-icon {
        width: 30px; height: 30px;
        background: rgba(180,119,30,0.1);
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .card-header .card-title-icon i { color: #B4771E; font-size: 1rem; }
    .tfoot-label { font-size: 0.82rem; font-weight: 600; color: #5d596c; }
    .tfoot-amount { font-size: 0.82rem; font-weight: 600; }
    .sale-items-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sale-items-table {
        min-width: 700px;
    }
    .sale-items-table th,
    .sale-items-table td {
        vertical-align: middle;
    }
    .sale-items-table .col-index { width: 54px; }
    .sale-items-table .col-product { width: 45%; min-width: 220px; }
</style>

<div class="text-center mb-4 p-4 pb-0">
    <h3 class="mb-0">Sale Details</h3>
    <small class="text-muted">{{ $order->order_no }}</small>
</div>

<div class="p-4 pt-0" style="overflow-y: auto;">
    @php
        $layoutLeftClass  = 'col-12';
        $layoutRightClass = 'col-12';
        $itemsTableId     = 'customerReportSaleItemsTable';
    @endphp
    @include('sales.partials.sale-detail-cards')
</div>
