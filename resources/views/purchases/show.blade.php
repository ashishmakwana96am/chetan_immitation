@extends('layouts.app')

@section('title', 'Purchase ' . $purchase->invoice_no)

@section('page-css')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
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
    .card-section-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #B4771E;
        margin-bottom: 4px;
    }
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
</style>
@endsection

@section('content')
    @php
        $statusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
        $statusLabels = [1 => 'Pending', 2 => 'Approve', 3 => 'Decline'];
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Purchase <code>{{ $purchase->invoice_no }}</code> @if($purchase->is_gst) <span class="badge bg-label-success ms-1" style="font-size: 0.8rem;">GST</span> @endif</h4>
            <small class="text-muted">{{ format_date($purchase->created_at) }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <!-- <a href="{{ route('admin.purchases.pdf', $purchase) }}" class="btn btn-label-info" target="_blank">
                <i class="ti ti-file-type-pdf me-1"></i> Download PDF
            </a> -->
            @can('edit purchases')
                @if($purchase->status == 1)
                    <a href="{{ route('admin.purchases.edit', $purchase) }}" class="btn btn-label-info">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                    <button class="btn btn-success"
                        data-common-confirm="{{ route('admin.purchases.status', $purchase) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Approve Purchase"
                        data-confirm-text="Are you sure you want to approve this purchase? Inventory will be updated."
                        data-confirm-btn="Yes, Approve"
                        data-confirm-btn-class="btn-success"
                        data-confirm-data='{"status":2}'>
                        <i class="ti ti-check me-1"></i> Approve
                    </button>
                    <button class="btn btn-label-danger"
                        data-common-confirm="{{ route('admin.purchases.status', $purchase) }}"
                        data-confirm-method="PATCH"
                        data-confirm-title="Decline Purchase"
                        data-confirm-text="Are you sure you want to decline this purchase?"
                        data-confirm-btn="Yes, Decline"
                        data-confirm-btn-class="btn-danger"
                        data-confirm-data='{"status":3}'>
                        <i class="ti ti-x me-1"></i> Decline
                    </button>
                @endif
                @can('edit purchases payment status')
                    @if(($purchase->payment_status ?? 1) != 2 && $purchase->status == 2)
                        <button class="btn btn-success change-purchase-payment-status-btn"
                            data-url="{{ route('admin.purchases.update-payment-status', $purchase) }}"
                            data-history-url="{{ route('admin.purchases.payment-history', $purchase) }}"
                            data-current="{{ $purchase->payment_status ?? 1 }}">
                            <i class="ti ti-currency-rupee me-1"></i> Update Payment Status
                        </button>
                    @endif
                @endcan
            @endcan
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">

        {{-- ══════════════════ LEFT COLUMN ══════════════════ --}}
        <div class="col-lg-4 d-flex flex-column gap-4">

            {{-- Purchase Info --}}
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-receipt-2"></i></span>
                    <h6 class="mb-0 fw-semibold">Purchase Info</h6>
                </div>
                <div class="card-body py-1 px-3">

                    <div class="sale-info-row">
                        <span class="sale-info-label">Purchase No</span>
                        <code class="sale-info-value">{{ $purchase->invoice_no }}</code>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Status</span>
                        <span class="badge {{ $statusColors[$purchase->status] ?? 'bg-label-secondary' }}">
                            {{ $statusLabels[$purchase->status] ?? 'Pending' }}
                        </span>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Payment Status</span>
                        @php
                            $payColors = [1 => 'bg-label-warning', 2 => 'bg-label-info', 3 => 'bg-label-primary'];
                            $payLabels = [1 => 'Pending', 2 => 'Paid', 3 => 'Partially Paid'];
                        @endphp
                        <span class="badge {{ $payColors[$purchase->payment_status ?? 1] ?? 'bg-label-secondary' }}">
                            {{ $payLabels[$purchase->payment_status ?? 1] ?? 'Pending' }}
                        </span>
                    </div>

                    <div class="sale-info-row">
                        <span class="sale-info-label">Total Amount</span>
                        <span class="sale-info-value fw-bold text-primary">{{ format_price($purchase->total_amount) }}</span>
                    </div>

                    @if(($purchase->payment_status ?? 1) != 1)
                        <div class="sale-info-row">
                            <span class="sale-info-label">Paid Amount</span>
                            <span class="sale-info-value fw-semibold text-success">{{ format_price($purchase->paid_amount) }}</span>
                        </div>
                        <div class="sale-info-row">
                            <span class="sale-info-label">Balance Due</span>
                            <span class="sale-info-value fw-semibold text-danger">{{ format_price($purchase->balance_due) }}</span>
                        </div>
                    @endif

                    <div class="sale-info-row">
                        <span class="sale-info-label">Date</span>
                        <span class="sale-info-value">{{ format_date($purchase->created_at) }}</span>
                    </div>

                </div>
            </div>

            @if($purchase->payments->isNotEmpty())
            {{-- Payment History --}}
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-history"></i></span>
                    <h6 class="mb-0 fw-semibold">Payment History</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->payments as $payment)
                                <tr>
                                    <td class="small text-nowrap">{{ format_date($payment->created_at) }}</td>
                                    <td class="text-end small fw-semibold {{ $payment->amount < 0 ? 'text-danger' : 'text-success' }}">{{ format_price($payment->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Supplier --}}
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-user"></i></span>
                    <h6 class="mb-0 fw-semibold">Supplier</h6>
                </div>
                <div class="card-body py-1 px-3">
                    <div class="sale-info-row">
                        <span class="sale-info-label">Name</span>
                        <span class="sale-info-value">{{ $purchase->supplier->name ?? '-' }}</span>
                    </div>
                    @if($purchase->supplier?->phone)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Phone</span>
                        <span class="sale-info-value">{{ $purchase->supplier->phone }}</span>
                    </div>
                    @endif
                    @if($purchase->supplier?->address)
                    <div class="sale-info-row">
                        <span class="sale-info-label">Address</span>
                        <span class="sale-info-value" style="max-width:65%;">{{ $purchase->supplier->address }}</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ══════════════════ RIGHT COLUMN ══════════════════ --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-shopping-bag"></i></span>
                    <h6 class="mb-0 fw-semibold">Purchase Items</h6>
                </div>
                <div class="card-datatable table-responsive p-3">
                    <table class="table mb-0" id="purchaseItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:4%">#</th>
                                <th>Product</th>
                                <th class="text-end">Purchase Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Total</th>
                                <th>Allocations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $index => $item)
                                @php
                                    $displayName = $item->product->name ?? '-';
                                    if ($item->variant) {
                                        $v = $item->variant;
                                        if ($v->attributeValue) {
                                            $displayName .= ' (' . ($v->attributeValue->attribute->name ?? '') . ': ' . ($v->attributeValue->value ?? '') . ')';
                                        }
                                    }

                                    if ($isRestricted && $locationId) {
                                        $myAllocation = $item->allocations->firstWhere('location_id', $locationId);
                                        $displayQty   = $myAllocation ? $myAllocation->quantity : 0;

                                        $subtotal = $item->purchase_price * $displayQty;
                                        $itemDiscAmount = ($item->quantity > 0) ? ($item->discount_amount * ($displayQty / $item->quantity)) : 0;
                                        $displayTotal = $subtotal - $itemDiscAmount;
                                        $displayAllocations = $myAllocation ? collect([$myAllocation]) : collect();
                                    } else {
                                        $displayQty         = $item->quantity;
                                        $displayTotal       = $item->total;
                                        $displayAllocations = $item->allocations;
                                    }
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
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap small">{{ format_price($item->purchase_price) }}</td>
                                    <td class="text-end text-nowrap small">
                                        {{ $displayQty }}
                                        @php
                                            $szVal = $item->custom_size_value ?: ($item->product?->pair_product ? (collect($item->product?->custom_sizes ?? [])->pluck('size')->max() ?: 2) : null);
                                        @endphp
                                        @if($szVal)
                                            <small class="text-muted">&times; {{ rtrim(rtrim(number_format((float) $szVal, 2), '0'), '.') }}pcs</small>
                                        @else
                                            <small class="text-muted">Pcs</small>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap small">
                                        @if($item->discount_value > 0)
                                            @if($item->discount_type === 'flat')
                                                {{ format_price($item->discount_value) }}
                                            @else
                                                {{ number_format($item->discount_value, 0) }}%
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold" style="color:#B4771E;">{{ format_price($displayTotal) }}</td>
                                    <td class="small">
                                        @foreach($displayAllocations as $allocation)
                                            <span class="badge bg-label-info me-1 mb-1">
                                                {{ $allocation->location->name ?? '-' }}: {{ $allocation->quantity }}
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @php
                                $totalSubtotal = 0;
                                $totalItemDiscount = 0;
                                $totalQty = 0;
                                foreach($purchase->items as $item) {
                                    if ($isRestricted && $locationId) {
                                        $myAlloc = $item->allocations->firstWhere('location_id', $locationId);
                                        $dq = $myAlloc ? $myAlloc->quantity : 0;
                                    } else {
                                        $dq = $item->quantity;
                                    }
                                    $totalQty += $dq;
                                    $sub = $item->purchase_price * $dq;
                                    $totalSubtotal += $sub;

                                    if ($isRestricted && $locationId) {
                                        $da = ($item->quantity > 0) ? ($item->discount_amount * ($dq / $item->quantity)) : 0;
                                    } else {
                                        $da = $item->discount_amount;
                                    }
                                    $totalItemDiscount += $da;
                                }

                                // Overall discount
                                $overallDiscAmount = 0;
                                if (!$isRestricted) {
                                    $overallDiscAmount = $purchase->discount_amount;
                                } else {
                                    $totalItemsAmount = $totalSubtotal - $totalItemDiscount;
                                    $purchaseTotalBeforeTax = $purchase->total_amount - $purchase->tax_amount;
                                    $overallDiscAmount = ($purchaseTotalBeforeTax > 0) ? ($purchase->discount_amount * ($totalItemsAmount / $purchaseTotalBeforeTax)) : 0;
                                }
                                $taxableAmount = $totalSubtotal - $totalItemDiscount - $overallDiscAmount;

                                // Tax calculation
                                $taxAmount = 0;
                                if ($purchase->is_gst && $purchase->tax_amount > 0) {
                                    if (!$isRestricted) {
                                        $taxAmount = $purchase->tax_amount;
                                    } else {
                                        $purchaseTotalBeforeTax = $purchase->total_amount - $purchase->tax_amount;
                                        $taxAmount = ($purchaseTotalBeforeTax > 0) ? ($purchase->tax_amount * ($taxableAmount / $purchaseTotalBeforeTax)) : 0;
                                    }
                                }

                                $grandTotal = $taxableAmount + $taxAmount;
                            @endphp

                            <tr>
                                <td colspan="5" class="text-end fw-semibold text-muted">Total Items</td>
                                <td class="text-end fw-semibold text-muted">{{ $totalQty }}</td>
                                <td></td>
                            </tr>
                            @if($totalItemDiscount > 0 || $overallDiscAmount > 0)
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold text-muted">Subtotal</td>
                                    <td class="text-end fw-semibold text-muted">{{ format_price($totalSubtotal) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold text-danger">Discount</td>
                                    <td class="text-end fw-semibold text-danger">-{{ format_price($totalItemDiscount + $overallDiscAmount) }}</td>
                                    <td></td>
                                </tr>
                            @endif

                            @if($purchase->is_gst && $taxAmount > 0)
                                @php
                                    $supplierState = strtolower(trim($purchase->supplier->state ?? ''));
                                    $isLocalGujarat = empty($supplierState) || str_contains($supplierState, 'gujarat') || str_contains($supplierState, 'gujrat');
                                    $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);
                                @endphp
                                @if($isLocalGujarat)
                                    @php
                                        $halfRate = $gstRate / 2;
                                        $halfTax = $taxAmount / 2;
                                    @endphp
                                    <tr>
                                        <td colspan="5" class="text-end fw-semibold text-muted">CGST ({{ $halfRate }}%)</td>
                                        <td class="text-end fw-semibold text-muted">{{ format_price($halfTax) }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end fw-semibold text-muted">SGST ({{ $halfRate }}%)</td>
                                        <td class="text-end fw-semibold text-muted">{{ format_price($halfTax) }}</td>
                                        <td></td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="5" class="text-end fw-semibold text-muted">IGST ({{ $gstRate }}%)</td>
                                        <td class="text-end fw-semibold text-muted">{{ format_price($taxAmount) }}</td>
                                        <td></td>
                                    </tr>
                                @endif
                            @endif

                            <tr style="border-top:2px solid #B4771E;">
                                <td colspan="5" class="text-end fw-bold" style="font-size:1rem; color:#B4771E;">Grand Total</td>
                                <td class="text-end fw-bold" style="font-size:1rem; color:#B4771E;">{{ format_price($grandTotal) }}</td>
                                <td></td>
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
            if ($('#purchaseItemsTable').length) {
                $('#purchaseItemsTable').DataTable({
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
        });

        function buildPaymentHistoryHtml(historyData) {
            if (!historyData || !historyData.payments || historyData.payments.length === 0) {
                return '';
            }

            let rows = historyData.payments.map(function (p) {
                return `<tr><td class="text-nowrap">${p.date}</td><td class="text-end">${p.amount}</td></tr>`;
            }).join('');

            return `
                <div class="mb-3 text-start" style="font-size: 0.8rem;">
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Total: <strong>${historyData.total_amount}</strong></span>
                        <span>Paid: <strong class="text-success">${historyData.paid_amount}</strong></span>
                        <span>Balance: <strong class="text-danger">${historyData.balance_due}</strong></span>
                    </div>
                    <label class="form-label fw-semibold mb-1" style="font-size: 0.8rem;">Payment History</label>
                    <div class="table-responsive border rounded" style="max-height:150px; overflow-y:auto;">
                        <table class="table table-sm mb-0" style="font-size: 0.75rem;">
                            <thead class="table-light"><tr><th>Date</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        $(document).on('click', '.change-purchase-payment-status-btn', function (e) {
            e.preventDefault();
            const url = $(this).data('url');
            const historyUrl = $(this).data('history-url');
            const currentPaymentStatus = $(this).data('current');

            $.get(historyUrl)
                .done(function (res) {
                    openPaymentStatusModal(url, currentPaymentStatus, res.data);
                })
                .fail(function () {
                    openPaymentStatusModal(url, currentPaymentStatus, null);
                });
        });

        function openPaymentStatusModal(url, currentPaymentStatus, historyData) {
            Swal.fire({
                title: 'Update Payment Status',
                html: `
                    ${buildPaymentHistoryHtml(historyData)}
                    <div class="mb-3 text-start">
                        <label for="swal-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                        <select id="swal-payment-status" class="form-select form-select-md">
                            <option value="1" ${currentPaymentStatus == 1 ? 'selected' : 'disabled'}>Pending</option>
                            <option value="3" ${currentPaymentStatus == 3 ? 'selected' : ''}>Partially Paid</option>
                            <option value="2" ${currentPaymentStatus == 2 ? 'selected' : ''}>Paid</option>
                        </select>
                    </div>
                    <div class="mb-3 text-start d-none" id="swal-amount-wrapper">
                        <label for="swal-payment-amount" class="form-label fw-semibold mb-2">Amount Paid Now</label>
                        <input type="number" id="swal-payment-amount" class="form-control form-control-md" min="0.01" step="0.01" placeholder="Enter amount paid" />
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
                didOpen: () => {
                    const statusSelect = document.getElementById('swal-payment-status');
                    const amountWrapper = document.getElementById('swal-amount-wrapper');
                    const toggleAmount = () => {
                        amountWrapper.classList.toggle('d-none', statusSelect.value !== '3');
                    };
                    toggleAmount();
                    statusSelect.addEventListener('change', toggleAmount);
                },
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const status = document.getElementById('swal-payment-status').value;
                        const amount = document.getElementById('swal-payment-amount').value;
                        if (status === '3') {
                            if (!amount || parseFloat(amount) <= 0) {
                                Swal.showValidationMessage('The amount field must be at least 0.01.');
                                return false;
                            }
                            const balanceDue = historyData ? parseFloat(historyData.balance_due_raw) : null;
                            if (balanceDue !== null && !isNaN(balanceDue) && parseFloat(amount) > balanceDue) {
                                Swal.showValidationMessage(`Paid amount cannot be greater than the remaining balance due (${historyData.balance_due}).`);
                                return false;
                            }
                        }

                        return $.ajax({
                            url: url,
                            type: 'PATCH',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                payment_status: status,
                                amount: amount
                            }
                        }).then(function (res) {
                            if (res.status !== 'success') {
                                Swal.showValidationMessage(res.message || 'Something went wrong.');
                                return false;
                            }
                            return res;
                        }).catch(function (xhr) {
                            const resJson = xhr.responseJSON;
                            let msg = 'Something went wrong. Please try again.';
                            if (resJson) {
                                if (typeof resJson.message === 'string' && resJson.message.trim() !== '') {
                                    msg = resJson.message;
                                } else if (resJson.message && typeof resJson.message === 'object') {
                                    const keys = Object.keys(resJson.message);
                                    if (keys.length) {
                                        const val = resJson.message[keys[0]];
                                        msg = Array.isArray(val) ? val[0] : String(val);
                                    }
                                } else if (resJson.errors && typeof resJson.errors === 'object') {
                                    const keys = Object.keys(resJson.errors);
                                    if (keys.length) {
                                        const val = resJson.errors[keys[0]];
                                        msg = Array.isArray(val) ? val[0] : String(val);
                                    }
                                }
                            }
                            Swal.showValidationMessage(msg);
                            return false;
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        toastr.success(result.value.message || 'Payment status updated successfully.');
                        setTimeout(() => location.reload(), 800);
                    }
                });
            }
    </script>
@endsection
