@extends('layouts.app')

@section('title', 'Purchase Bill ' . $transfer->transfer_no)

@section('page-css')
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
</style>
@endsection

@section('content')
    @php
        $statusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
        $statusLabels = [1 => 'Pending', 2 => 'Accepted', 3 => 'Rejected'];
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Purchase Bill <code>{{ $transfer->transfer_no }}</code></h4>
            <small class="text-muted">{{ format_date($transfer->created_at) }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @if($transfer->status == \App\Models\PurchaseBill::STATUS_PENDING)
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
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th>Product</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandTotal = 0; @endphp
                            @foreach($transfer->items as $index => $item)
                                @php
                                    $displayName = $item->product->name ?? '-';
                                    if ($item->variant && $item->variant->attributeValue) {
                                        $displayName .= ' (' . ($item->variant->attributeValue->attribute->name ?? 'Variant') . ': ' . ($item->variant->attributeValue->value ?? '') . ')';
                                    }
                                    $price = $item->variant->purchase_price ?? $item->product->purchase_price ?? 0;
                                    $lineTotal = $price * $item->quantity;
                                    $grandTotal += $lineTotal;
                                @endphp
                                <tr>
                                    <td class="text-muted small">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $item->product?->primary_image_url ?? asset('website/assets/images/no-image.svg') }}" alt="{{ $displayName }}" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <span class="fw-semibold">{{ $displayName }}</span>
                                                @if($item->product?->barcode)
                                                    <br><small class="text-muted">{{ $item->product->barcode }}</small>
                                                @endif
                                                @if($item->custom_size_value)
                                                    <br><small class="text-muted">Size: {{ rtrim(rtrim(number_format((float)$item->custom_size_value, 2), '0'), '.') }} pcs</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ $item->quantity }}
                                        <small class="text-muted">
                                            @if($item->custom_size_value)
                                                Packs
                                            @elseif(($item->pair_type ?? 'single') === 'pair')
                                                Pairs
                                            @else
                                                Pcs
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-end">{{ currency_symbol() }} {{ number_format($price, 2) }}</td>
                                    <td class="text-end fw-semibold">{{ currency_symbol() }} {{ number_format($lineTotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-end fw-bold">Total Qty</td>
                                <td class="text-end fw-bold text-primary">{{ $transfer->items->sum('quantity') }}</td>
                                <td class="text-end fw-bold">Total Amount</td>
                                <td class="text-end fw-bold text-primary">{{ currency_symbol() }} {{ number_format($grandTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
<script>
$(document).ready(function () {
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
