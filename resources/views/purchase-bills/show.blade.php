@extends('layouts.app')

@section('title', 'Purchase Bill ' . $transfer->transfer_no)

@section('page-css')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
<style>
    .info-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 9px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        font-size: 0.875rem;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #8592a3; flex-shrink: 0; }
    .info-value { font-weight: 500; text-align: right; }
    .card-title-icon {
        width: 30px;
        height: 30px;
        background: rgba(180,119,30,0.1);
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .card-title-icon i { color: #B4771E; }
    .purchase-bill-items-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .purchase-bill-items-table {
        min-width: 980px;
    }
    .purchase-bill-items-table th,
    .purchase-bill-items-table td {
        vertical-align: middle;
    }
    .purchase-bill-items-table .col-index { width: 54px; }
    .purchase-bill-items-table .col-product { width: 35%; min-width: 250px; }
    .purchase-bill-items-table .col-qty { width: 110px; }
    .purchase-bill-items-table .col-money { width: 130px; }
    .purchase-bill-items-table .money-cell,
    .purchase-bill-items-table .qty-cell,
    .purchase-bill-items-table .total-label {
        white-space: nowrap !important;
    }
    .purchase-bill-items-table .product-name {
        display: block;
        word-break: break-word;
        line-height: 1.35;
    }
    .purchase-bill-items-table .product-code {
        display: block;
        margin-top: 0.15rem;
    }
    .purchase-bill-items-table tfoot td {
        background: #fff;
    }
</style>
@endsection

@section('content')
    @php
        $statusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
        $statusLabels = [1 => 'Pending', 2 => 'Accepted', 3 => 'Rejected'];
        $paymentStatusColors = [1 => 'bg-label-warning', 2 => 'bg-label-success'];
        $paymentStatusLabels = [1 => 'Pending', 2 => 'Paid'];
        $currentPaymentStatus = (int) ($transfer->payment_status ?? 1);
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Purchase Bill <code>{{ $transfer->transfer_no }}</code></h4>
            <small class="text-muted">{{ format_date($transfer->created_at) }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @if($transfer->status == \App\Models\PurchaseBill::STATUS_PENDING)
                @can('edit purchase bills')
                    <a href="{{ route('admin.purchase-bills.edit', $transfer) }}" class="btn btn-label-primary">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                @endcan
                @can('accept purchase bills')
                    <button class="btn btn-success purchase-bill-action"
                        data-url="{{ route('admin.purchase-bills.accept', $transfer) }}"
                        data-title="Accept Purchase Bill"
                        data-text="Stock will move from source to destination location.">
                        <i class="ti ti-check me-1"></i> Accept
                    </button>
                @endcan
                @can('reject purchase bills')
                    <button class="btn btn-label-danger purchase-bill-action"
                        data-url="{{ route('admin.purchase-bills.reject', $transfer) }}"
                        data-title="Reject Purchase Bill"
                        data-text="No inventory stock will be changed.">
                        <i class="ti ti-x me-1"></i> Reject
                    </button>
                @endcan
            @endif
            @can('edit purchase bills payment status')
                @if($transfer->status == \App\Models\PurchaseBill::STATUS_ACCEPTED && $currentPaymentStatus !== \App\Models\PurchaseBill::PAYMENT_STATUS_PAID)
                    <button class="btn btn-label-primary change-purchase-bill-payment-status-btn"
                        data-url="{{ route('admin.purchase-bills.update-payment-status', $transfer) }}"
                        data-current="{{ $currentPaymentStatus }}">
                        <i class="ti ti-credit-card me-1"></i> Update Payment Status
                    </button>
                @endif
            @endcan
            <a href="{{ route('admin.purchase-bills.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-transfer-out"></i></span>
                    <h6 class="mb-0 fw-semibold">Transfer Info</h6>
                </div>
                <div class="card-body py-1 px-3">
                    <div class="info-row">
                        <span class="info-label">Bill No</span>
                        <code class="info-value">{{ $transfer->transfer_no }}</code>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="badge {{ $statusColors[$transfer->status] ?? 'bg-label-secondary' }}">{{ $statusLabels[$transfer->status] ?? 'Pending' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Source</span>
                        <span class="info-value">{{ $transfer->fromLocation->name ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Destination</span>
                        <span class="info-value">{{ $transfer->toLocation->name ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method</span>
                        <span class="info-value">{{ ucwords($transfer->payment_method ?? 'cash') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status</span>
                        <span class="badge {{ $paymentStatusColors[$currentPaymentStatus] ?? 'bg-label-warning' }}">{{ $paymentStatusLabels[$currentPaymentStatus] ?? 'Pending' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created By</span>
                        <span class="info-value">{{ $transfer->createdBy->name ?? '-' }}</span>
                    </div>
                    @if($transfer->acceptedBy)
                        <div class="info-row">
                            <span class="info-label">Updated By</span>
                            <span class="info-value">{{ $transfer->acceptedBy->name ?? '-' }}</span>
                        </div>
                    @endif
                    @if($transfer->accepted_at)
                        <div class="info-row">
                            <span class="info-label">Updated At</span>
                            <span class="info-value">{{ format_date($transfer->accepted_at) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($transfer->remarks)
                <div class="card">
                    <div class="card-header"><h6 class="mb-0 fw-semibold">Remarks</h6></div>
                    <div class="card-body">
                        <p class="mb-0">{{ $transfer->remarks }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-box"></i></span>
                    <h6 class="mb-0 fw-semibold">Transfer Items</h6>
                </div>
                <div class="card-datatable table-responsive purchase-bill-items-wrap p-3">
                    <table class="table mb-0 purchase-bill-items-table" id="transferItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="col-index">#</th>
                                <th class="col-product">Product</th>
                                <th class="text-end col-qty">Qty</th>
                                <th class="text-end col-money">Price</th>
                                <th class="text-end col-money">Total</th>
                                <th class="text-end col-money">Unit MRP</th>
                                <th class="text-end col-money">Total MRP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfer->items as $index => $item)
                                @php
                                    $displayName = $item->product->name ?? '-';
                                    if ($item->variant && $item->variant->attributeValue) {
                                        $displayName .= ' (' . ($item->variant->attributeValue->attribute->name ?? 'Variant') . ': ' . ($item->variant->attributeValue->value ?? '') . ')';
                                    }
                                    $price = $item->calculated_unit_amount ?? 0;
                                    $lineTotal = $item->calculated_line_amount ?? 0;
                                    $unitMrp = $item->calculated_unit_mrp ?? 0;
                                    $lineMrp = $item->calculated_line_mrp ?? 0;
                                @endphp
                                <tr>
                                    <td class="text-muted small">{{ $index + 1 }}</td>
                                     <td>
                                         <div class="d-flex align-items-center">
                                             <img src="{{ $item->product?->primary_image_url ?? asset('website/assets/images/no-image.svg') }}" alt="{{ $displayName }}" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                             <div class="min-w-0">
                                                 <span class="fw-semibold product-name">{{ $displayName }}</span>
                                                 @if($item->product?->barcode)
                                                     <small class="text-muted product-code">{{ $item->product->barcode }}</small>
                                                 @endif
                                             </div>
                                         </div>
                                     </td>
                                     <td class="text-end fw-semibold qty-cell">
                                        {{ $item->quantity }}
                                        @php
                                            $szVal = ($item->calculated_multiplier ?? 1) > 1 ? $item->calculated_multiplier : null;
                                        @endphp
                                        @if($szVal)
                                            <span class="small text-muted">&times; {{ rtrim(rtrim(number_format((float) $szVal, 2), '0'), '.') }}pcs</span>
                                        @elseif(($item->pair_type ?? 'single') === 'pair')
                                            <span class="small text-muted">&times; 2pcs</span>
                                        @else
                                            <span class="small text-muted">Pcs</span>
                                        @endif
                                    </td>
                                    <td class="text-end money-cell">{{ currency_symbol() }} {{ number_format($price, 2) }}</td>
                                    <td class="text-end fw-semibold money-cell">{{ currency_symbol() }} {{ number_format($lineTotal, 2) }}</td>
                                    <td class="text-end money-cell">{{ currency_symbol() }} {{ number_format($unitMrp, 2) }}</td>
                                    <td class="text-end fw-semibold money-cell">{{ currency_symbol() }} {{ number_format($lineMrp, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-end fw-bold total-label">Total Qty</td>
                                <td class="text-end fw-bold text-primary">{{ $transfer->items->sum('quantity') }}</td>
                                <td class="text-end fw-bold total-label">Total Amount</td>
                                <td class="text-end fw-bold text-primary money-cell">{{ currency_symbol() }} {{ number_format($totalAmount, 2) }}</td>
                                <td class="text-end fw-bold total-label">Total MRP</td>
                                <td class="text-end fw-bold text-primary money-cell">{{ currency_symbol() }} {{ number_format($totalMrp, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
$(document).ready(function () {

    if ($('#transferItemsTable').length) {
        $('#transferItemsTable').DataTable({
            paging: true,
            searching: true,
            ordering: false,
            info: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                search: '',
                searchPlaceholder: 'Search item...'
            }
        });
    }
    $(document).on('click', '.change-purchase-bill-payment-status-btn', function (e) {
        e.preventDefault();
        const url = $(this).data('url');
        const currentPaymentStatus = $(this).data('current');

        Swal.fire({
            title: 'Update Payment Status',
            html: `
                <div class="mb-3 text-start">
                    <label for="swal-purchase-bill-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                    <select id="swal-purchase-bill-payment-status" class="form-select form-select-lg">
                        <option value="1" ${currentPaymentStatus == 1 ? 'selected' : ''}>Pending</option>
                        <option value="2" ${currentPaymentStatus == 2 ? 'selected' : ''}>Paid</option>
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false,
            preConfirm: () => {
                return document.getElementById('swal-purchase-bill-payment-status').value;
            }
        }).then((result) => {
            if (!result.isConfirmed || !result.value) return;
            window.showAjaxLoader();
            $.ajax({
                url: url,
                type: 'PATCH',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    payment_status: result.value
                },
                success: function (res) {
                    window.hideAjaxLoader();
                    toastr.success(res.message);
                    setTimeout(() => window.location.reload(), 600);
                },
                error: function (xhr) {
                    window.hideAjaxLoader();
                    toastr.error(xhr.responseJSON?.message || 'Something went wrong. Please try again.');
                }
            });
        });
    });

    $(document).on('click', '.purchase-bill-action', function () {
        const button = $(this);
        Swal.fire({
            title: button.data('title'),
            text: button.data('text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Confirm',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false,
        }).then((result) => {
            if (!result.isConfirmed) return;
            window.showAjaxLoader();
            $.ajax({
                url: button.data('url'),
                type: 'PATCH',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    window.hideAjaxLoader();
                    toastr.success(res.message);
                    setTimeout(() => window.location.reload(), 600);
                },
                error: function (xhr) {
                    window.hideAjaxLoader();
                    toastr.error(xhr.responseJSON?.message || 'Something went wrong. Please try again.');
                }
            });
        });
    });
});
</script>
@endsection
