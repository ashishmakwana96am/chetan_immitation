@extends('layouts.app')

@section('title', 'Sale ' . $order->order_no)

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
    .sale-items-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sale-items-table {
        min-width: 750px;
    }
    .sale-items-table th,
    .sale-items-table td {
        vertical-align: middle;
    }
    .sale-items-table .col-index { width: 54px; }
    .sale-items-table .col-product { width: 45%; min-width: 250px; }
    .sale-items-table .col-price { width: 140px; }
    .sale-items-table .col-qty { width: 140px; min-width: 140px; white-space: nowrap; }
    .sale-items-table .col-discount { width: 110px; }
    .sale-items-table .col-total { width: 140px; }
    .sale-items-table .product-name {
        display: block;
        word-break: break-word;
        line-height: 1.35;
    }
    .sale-items-table .product-code {
        display: block;
        margin-top: 0.15rem;
    }
</style>
@endsection

@section('content')
    @php
        $statusColors = [
            1 => 'bg-label-secondary',
            2 => 'bg-label-success',
            3 => 'bg-label-info',
            4 => 'bg-label-warning',
            5 => 'bg-label-success',
            6 => 'bg-label-danger',
        ];
        $statusLabels = [
            1 => 'Pending',
            2 => 'Approve',
            3 => 'Shipped',
            4 => 'Out for delivery',
            5 => 'Delivered',
            6 => 'Cancelled',
        ];
        $paymentColors = [1 => 'bg-label-warning', 2 => 'bg-label-info', 3 => 'bg-label-primary'];
        $paymentLabels = [1 => 'Pending',          2 => 'Paid',         3 => 'Partially Paid'];

        $isOnline          = ($order->source ?? 'POS') === 'ONLINE';

        $mrpSubtotal = 0.00;
        foreach($order->items as $item) {
            $itemMrp = ((float)($item->mrp ?? 0) > 0) ? (float)$item->mrp : ($item->variant?->mrp ?? ($item->product?->mrp ?? 0));
            if ($item->product?->pair_product && $item->custom_size_value && !(float)($item->mrp ?? 0)) {
                $customSizes = collect($item->product->custom_sizes ?? []);
                if ($item->product_variant_id) {
                    $vSizes = collect($item->product->variant_custom_sizes ?? [])->where('product_variant_id', $item->product_variant_id);
                    if ($vSizes->isNotEmpty()) {
                        $customSizes = $vSizes;
                    }
                }
                $matchedSize = $customSizes->firstWhere('size', (string)$item->custom_size_value);
                if ($matchedSize && isset($matchedSize['mrp'])) {
                    $itemMrp = (float) $matchedSize['mrp'];
                }
            }
            $itemBaseMrp = $itemMrp > 0 ? $itemMrp : (float)$item->price;
            $mrpSubtotal += ($itemBaseMrp * (float)$item->quantity);
        }

        $subtotal = $mrpSubtotal;

        $totalDiscountOnMrp = max(0, round($mrpSubtotal - (float)$order->final_amount, 2));
        $totalItemDiscount = $order->items->sum('discount_amount');
        $orderDiscountAmount = 0.0;
        if ($order->order_discount_value > 0) {
            $itemsTotal = $mrpSubtotal - $totalItemDiscount;
            if ($order->order_discount_type === 'flat') {
                $orderDiscountAmount = (float)$order->order_discount_value;
            } else if ($order->order_discount_type === 'percentage') {
                $orderDiscountAmount = $itemsTotal * ((float)$order->order_discount_value / 100);
            }
            $orderDiscountAmount = min($orderDiscountAmount, $itemsTotal);
        }
        $totalDiscount = max($totalDiscountOnMrp, round($totalItemDiscount + $orderDiscountAmount, 2));
    @endphp

    {{-- ── Page header ────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Sale <code>{{ $order->order_no }}</code> @if($order->is_gst) <span class="badge bg-label-success ms-1" style="font-size: 0.8rem;">GST</span> @endif</h4>
            <small class="text-muted">{{ format_date($order->created_at) }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">

            @can('download sales')
                @if($isOnline)
                    <a href="{{ route('admin.sales.pdf', [$order, 'auto_print' => 1]) }}" class="btn btn-label-primary" target="_blank">
                        <i class="ti ti-printer me-1"></i> Invoice
                    </a>
                    <a href="{{ route('admin.sales.label', [$order, 'auto_print' => 1]) }}" class="btn btn-label-success" target="_blank">
                        <i class="ti ti-printer me-1"></i> Print Label
                    </a>
                @else
                    <a href="{{ route('admin.sales.thermal', [$order, 'auto_print' => 1]) }}" class="btn btn-label-primary" target="_blank">
                        <i class="ti ti-printer me-1"></i> Invoice
                    </a>
                    @if($order->is_gst)
                        <a href="{{ route('admin.sales.tax-invoice', [$order, 'auto_print' => 1]) }}" class="btn btn-label-info" target="_blank">
                            <i class="ti ti-file-text me-1"></i> Tax Invoice
                        </a>
                    @endif
                @endif
            @endcan

            @can('edit sales')
                @if(!$isOnline && in_array((int) $order->status, [1, 2], true) && !($order->cancellationRequest && $order->cancellationRequest->status === 'pending') && can_modify_past_date_record($order->created_at))
                    <a href="{{ route('admin.sales.edit', $order) }}" class="btn btn-label-warning">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                @endif
            @endcan

            @can('edit sales payment status')
                @if(($order->payment_status ?? 1) != \App\Models\Order::PAYMENT_STATUS_PAID)
                    <button class="btn btn-success mark-as-paid-btn"
                        data-url="{{ route('admin.sales.status', $order) }}"
                        data-history-url="{{ route('admin.sales.payment-history', $order) }}"
                        data-amount="{{ $order->final_amount }}"
                        data-current="{{ $order->payment_status ?? 1 }}">
                        <i class="ti ti-currency-rupee me-1"></i> Update Payment Status
                    </button>
                @endif
            @endcan

            @can('edit sales status')
                @if(can_modify_past_date_record($order->created_at))
                @php
                    $cs  = (int)$order->status;
                    $isOnline = ($order->source ?? 'POS') === 'ONLINE';
                    
                    if (!$isOnline) {
                        // POS: Only Pending and Approve are possible, and if status is already Approve (2), disable dropdown
                        $dis = ($cs >= 2) ? 'disabled' : '';
                        $o1  = ($cs === 1) ? '' : 'disabled';
                        $o2  = ($cs === 2) ? '' : (($cs === 1) ? '' : 'disabled');
                    } else {
                        // Online: Standard sequential flow
                        $dis = in_array($cs, [5, 6]) ? 'disabled' : '';
                        $o1  = ($cs === 1) ? '' : 'disabled';
                        $o2  = ($cs === 2) ? '' : ((!in_array($cs, [1, 2])) ? 'disabled' : '');
                        $o3  = ($cs === 3) ? '' : ((!in_array($cs, [2, 3])) ? 'disabled' : '');
                        $o4  = ($cs === 4) ? '' : ((!in_array($cs, [3, 4])) ? 'disabled' : '');
                        $o5  = ($cs === 5) ? '' : ((!in_array($cs, [4, 5])) ? 'disabled' : '');
                        $o6  = ($cs === 6) ? '' : ((in_array($cs, [5, 6]))  ? 'disabled' : '');
                    }
                @endphp
                <select id="change-sale-status" class="form-select no-select2" data-current="{{ $order->status }}" {{ $dis }} autocomplete="off" style="min-width:160px;width:auto;">
                    @if(!$isOnline)
                        <option value="1" {{ $order->status==1?'selected':'' }} {{ $o1 }}>Pending</option>
                        <option value="2" {{ $order->status==2?'selected':'' }} {{ $o2 }}>Approve</option>
                    @else
                        <option value="1" {{ $order->status==1?'selected':'' }} {{ $o1 }}>Pending</option>
                        <option value="2" {{ $order->status==2?'selected':'' }} {{ $o2 }}>Approve</option>
                        <option value="3" {{ $order->status==3?'selected':'' }} {{ $o3 }}>Shipped</option>
                        <option value="4" {{ $order->status==4?'selected':'' }} {{ $o4 }}>Out for delivery</option>
                        <option value="5" {{ $order->status==5?'selected':'' }} {{ $o5 }}>Delivered</option>
                        <option value="6" {{ $order->status==6?'selected':'' }} {{ $o6 }}>Cancelled</option>
                    @endif
                </select>
                @endif
            @endcan

            @can('delete sales')
                @if(can_modify_past_date_record($order->created_at))
                <a href="javascript:void(0);" class="btn btn-label-danger" data-common-delete="{{ route('admin.sales.destroy', $order) }}">
                    <i class="ti ti-trash me-1"></i> Delete
                </a>
                @endif
            @endcan

            <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>

        </div>

        {{-- Cancellation reason is now captured via SweetAlert popup --}}
        @can('edit sales status')
        <div id="cancel-reason-wrap" style="display:none !important;"></div>
        @endcan
    </div>

    @if($order->cancellationRequest && $order->cancellationRequest->status === 'pending')
    <div class="alert alert-danger d-flex flex-column gap-3 mb-4" role="alert" id="cancellation-request-alert">
        <div class="d-flex align-items-center">
            <i class="ti ti-alert-triangle me-2 fs-4 text-danger"></i>
            <div>
                <h5 class="alert-heading mb-1 text-danger">Cancellation Request Pending</h5>
                <span>Customer requested to cancel this order. Reason: <strong>{{ $order->cancellationRequest->cancellation_reason }}</strong></span>
            </div>
        </div>
        @can('edit sales status')
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm approve-cancel-btn" data-url="{{ route('admin.sales.cancellation.approve', $order) }}">
                <i class="ti ti-check me-1"></i> Approve & Refund
            </button>
            <button class="btn btn-danger btn-sm reject-cancel-btn" data-url="{{ route('admin.sales.cancellation.reject', $order) }}">
                <i class="ti ti-x me-1"></i> Reject
            </button>
        </div>
        @endcan
    </div>
    @endif

    @include('sales.partials.sale-detail-cards')
@endsection

@section('page-js')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
window.refreshTable = function () {
    window.location.href = "{{ route('admin.sales.index') }}";
};

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent.trim();

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            toastr.success('Copied to clipboard');
        }).catch(() => {
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        toastr.success('Copied to clipboard');
    } catch (err) {
        toastr.error('Failed to copy');
    }

    document.body.removeChild(textArea);
}

function buildSaleShowPaymentHistoryHtml(historyData) {
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
                    <thead class="table-light"><tr><th>DATE</th><th class="text-end">AMOUNT</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        </div>
    `;
}

$(document).on('click', '.mark-as-paid-btn', function (e) {
    e.preventDefault();
    const url = $(this).data('url');
    const historyUrl = $(this).data('history-url');
    const grandTotal = parseFloat($(this).data('amount')) || 0;
    const currentStatus = parseInt($(this).data('current') || 1);

    window.showAjaxLoader ? window.showAjaxLoader() : null;
    $.get(historyUrl)
        .done(function (res) {
            window.hideAjaxLoader ? window.hideAjaxLoader() : null;
            openSaleShowPaymentModal(url, currentStatus, grandTotal, res.data);
        })
        .fail(function () {
            window.hideAjaxLoader ? window.hideAjaxLoader() : null;
            openSaleShowPaymentModal(url, currentStatus, grandTotal, null);
        });
});

function openSaleShowPaymentModal(url, currentStatus, grandTotal, historyData) {
    const optPending = currentStatus === 3 ? 'disabled' : (currentStatus === 1 ? 'selected' : '');
    const optPartial = currentStatus === 3 ? 'selected' : '';
    const optPaid = currentStatus === 2 ? 'selected' : '';

    const remainingDue = historyData ? (historyData.balance_due_raw !== undefined ? parseFloat(historyData.balance_due_raw) : grandTotal) : grandTotal;
    const initialCash = remainingDue;

    Swal.fire({
        title: 'Update Payment Status',
        html: `
            ${buildSaleShowPaymentHistoryHtml(historyData)}
            <div class="mb-3 text-start">
                <label for="swal-payment-status" class="form-label fw-semibold mb-2">Select Payment Status</label>
                <select id="swal-payment-status" class="form-select">
                    <option value="1" ${optPending}>Pending</option>
                    <option value="3" ${optPartial}>Partially Paid</option>
                    <option value="2" ${optPaid}>Paid</option>
                </select>
            </div>
            <div class="text-start d-flex gap-2" id="swal-amounts-wrap">
                <div class="flex-fill">
                    <label class="form-label fw-semibold mb-2">Cash</label>
                    <input type="number" id="swal-paid-cash" class="form-control" value="${initialCash}" min="0" step="0.01">
                </div>
                <div class="flex-fill">
                    <label class="form-label fw-semibold mb-2">Online</label>
                    <input type="number" id="swal-paid-online" class="form-control" value="0" min="0" step="0.01">
                </div>
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
            const amountsWrap = document.getElementById('swal-amounts-wrap');
            const cashInput = document.getElementById('swal-paid-cash');
            const onlineInput = document.getElementById('swal-paid-online');

            const toggleVisibility = () => {
                const val = parseInt(statusSelect.value);
                amountsWrap.style.display = (val === 1) ? 'none' : 'flex';
            };

            const clamp = (source) => {
                const val = parseInt(statusSelect.value);
                if (val === 1) return;

                let cash = parseFloat(cashInput.value) || 0;
                let online = parseFloat(onlineInput.value) || 0;

                if (val === 2) {
                    if (source === 'cash') {
                        cash = Math.min(Math.max(cash, 0), remainingDue);
                        cashInput.value = cash;
                        onlineInput.value = Math.round((remainingDue - cash) * 100) / 100;
                    } else {
                        online = Math.min(Math.max(online, 0), remainingDue);
                        onlineInput.value = online;
                        cashInput.value = Math.round((remainingDue - online) * 100) / 100;
                    }
                } else if (val === 3) {
                    if (source === 'cash') {
                        if (cash > remainingDue) {
                            cash = remainingDue;
                            cashInput.value = cash;
                        }
                        if (cash + online > remainingDue) {
                            online = Math.round((remainingDue - cash) * 100) / 100;
                            onlineInput.value = online;
                        }
                    } else if (source === 'online') {
                        if (online > remainingDue) {
                            online = remainingDue;
                            onlineInput.value = online;
                        }
                        if (cash + online > remainingDue) {
                            cash = Math.round((remainingDue - online) * 100) / 100;
                            cashInput.value = cash;
                        }
                    }
                }
            };

            statusSelect.addEventListener('change', () => {
                toggleVisibility();
                clamp('online');
            });
            cashInput.addEventListener('input', () => clamp('cash'));
            onlineInput.addEventListener('input', () => clamp('online'));
            toggleVisibility();
        },
        preConfirm: () => {
            let status = document.getElementById('swal-payment-status').value;
            const cash = parseFloat(document.getElementById('swal-paid-cash').value) || 0;
            const online = parseFloat(document.getElementById('swal-paid-online').value) || 0;
            const sum = cash + online;

            if (status === '3' && sum >= (remainingDue - 0.001)) {
                status = '2';
            }

            if (status === '3' && sum <= 0) {
                Swal.showValidationMessage('Paid amount must be at least 0.01 for Partially Paid status.');
                return false;
            }
            if (status !== '1' && sum > (remainingDue + 0.01)) {
                Swal.showValidationMessage(`Paid amount cannot be greater than the remaining balance due (₹${remainingDue.toFixed(2)}).`);
                return false;
            }

            return {
                payment_status: status,
                paid_cash_amount: cash,
                paid_online_amount: online,
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            window.showAjaxLoader ? window.showAjaxLoader() : null;
            $.ajax({
                url: url,
                type: 'PATCH',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    payment_status: result.value.payment_status,
                    paid_cash_amount: result.value.paid_cash_amount,
                    paid_online_amount: result.value.paid_online_amount
                },
                success: function (res) {
                    window.hideAjaxLoader ? window.hideAjaxLoader() : null;
                    if (res.status === 'success') {
                        toastr.success(res.message);
                        setTimeout(() => location.reload(), 800);
                    }
                },
                error: function (xhr) {
                    window.hideAjaxLoader ? window.hideAjaxLoader() : null;
                    const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                    toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                }
            });
        }
    });
}

$(document).ready(function () {

    if ($('#orderItemsTable').length) {
        $('#orderItemsTable').DataTable({
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

    $('#change-sale-status').on('change', function () {
        const status  = $(this).val();
        const current = $(this).data('current');
        const url     = "{{ route('admin.sales.status', $order) }}";

        if (status == '6') {
            Swal.fire({
                title: 'Cancel Order',
                html: `
                    <div class="mb-3 text-start">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="swal-cancel-reason" class="form-label fw-semibold mb-0">Cancellation Reason <span class="text-danger">*</span></label>
                            <small class="text-muted" id="swal-char-counter">0/500</small>
                        </div>
                        <textarea id="swal-cancel-reason" class="form-control" rows="3" maxlength="500" placeholder="Enter the reason for cancellation..."></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel Order',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false,
                didOpen: () => {
                    const reasonInput = document.getElementById('swal-cancel-reason');
                    const charCounter = document.getElementById('swal-char-counter');
                    reasonInput.addEventListener('input', () => {
                        charCounter.textContent = `${reasonInput.value.length}/500`;
                    });
                },
                preConfirm: () => {
                    const reason = document.getElementById('swal-cancel-reason').value.trim();
                    if (!reason) {
                        Swal.showValidationMessage('Please enter a cancellation reason.');
                        return false;
                    }
                    return { reason: reason };
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    window.showAjaxLoader();
                    $.ajax({
                        url: url,
                        type: 'PATCH',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: 6,
                            cancellation_reason: result.value.reason
                        },
                        success: function (res) {
                            window.hideAjaxLoader();
                            if (res.status === 'success') {
                                toastr.success(res.message);
                                if (res.pending_count !== undefined) {
                                    const badge = $('.pending-sales-counter-badge');
                                    if (badge.length > 0) {
                                        badge.text(res.pending_count);
                                        if (res.pending_count > 0) {
                                            badge.attr('style', 'display: inline-block !important;');
                                        } else {
                                            badge.attr('style', 'display: none !important;');
                                        }
                                    }
                                }
                                setTimeout(() => location.reload(), 800);
                            }
                        },
                        error: function (xhr) {
                            window.hideAjaxLoader();
                            const msg = xhr.responseJSON?.message || 'Something went wrong.';
                            toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            $('#change-sale-status').val(current);
                        }
                    });
                } else {
                    $('#change-sale-status').val(current);
                }
            });
            return;
        }

        if (status == '3') {
            Swal.fire({
                title: 'Enter Shipping Details',
                html:
                    '<div class="mb-3 text-start"><label class="form-label small fw-semibold">Shipping Client URL</label><input id="swal-shipped-url" class="form-control" placeholder="https://tracking-url.com"></div>' +
                    '<div class="mb-3 text-start"><label class="form-label small fw-semibold">Tracking ID</label><input id="swal-tracking-id" class="form-control" placeholder="Tracking ID / No."></div>',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Submit & Ship',
                customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
                buttonsStyling: false,
                preConfirm: () => {
                    const urlVal = document.getElementById('swal-shipped-url').value.trim();
                    const trackingVal = document.getElementById('swal-tracking-id').value.trim();
                    if (!urlVal) {
                        Swal.showValidationMessage('Please enter Shipping Client URL');
                        return false;
                    }
                    if (!trackingVal) {
                        Swal.showValidationMessage('Please enter Tracking ID');
                        return false;
                    }
                    return { shipped_client_url: urlVal, tracking_id: trackingVal };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.showAjaxLoader();
                    $.ajax({
                        url: url, type: 'PATCH',
                        data: { 
                            _token: '{{ csrf_token() }}', 
                            status: status, 
                            shipped_client_url: result.value.shipped_client_url, 
                            tracking_id: result.value.tracking_id 
                        },
                        success: function (res) {
                            window.hideAjaxLoader();
                            if (res.status === 'success') {
                                toastr.success(res.message);
                                if (res.pending_count !== undefined) {
                                    const badge = $('.pending-sales-counter-badge');
                                    if (badge.length > 0) {
                                        badge.text(res.pending_count);
                                        if (res.pending_count > 0) {
                                            badge.attr('style', 'display: inline-block !important;');
                                        } else {
                                            badge.attr('style', 'display: none !important;');
                                        }
                                    }
                                }
                                setTimeout(() => location.reload(), 800);
                            }
                        },
                        error: function (xhr) {
                            window.hideAjaxLoader();
                            const msg = xhr.responseJSON?.message || 'Something went wrong.';
                            toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                            $('#change-sale-status').val(current);
                        }
                    });
                } else {
                    $('#change-sale-status').val(current);
                }
            });
            return;
        }

        Swal.fire({
            title: 'Update Sale Status',
            text: 'Are you sure you want to update the status of this sale?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Update',
            customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.showAjaxLoader();
                $.ajax({
                    url: url, type: 'PATCH',
                    data: { _token: '{{ csrf_token() }}', status: status },
                    success: function (res) {
                        window.hideAjaxLoader();
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            if (res.pending_count !== undefined) {
                                const badge = $('.pending-sales-counter-badge');
                                if (badge.length > 0) {
                                    badge.text(res.pending_count);
                                    if (res.pending_count > 0) {
                                        badge.attr('style', 'display: inline-block !important;');
                                    } else {
                                        badge.attr('style', 'display: none !important;');
                                    }
                                }
                            }
                            setTimeout(() => location.reload(), 800);
                        }
                    },
                    error: function (xhr) {
                        window.hideAjaxLoader();
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                        $('#change-sale-status').val(current);
                    }
                });
            } else {
                $('#change-sale-status').val(current);
            }
        });
    });


    $(document).on('click', '.approve-cancel-btn', function (e) {
        e.preventDefault();
        const url = $(this).data('url');

        Swal.fire({
            title: 'Approve Cancellation?',
            text: 'This will cancel the order, restore inventory stock, and automatically process the Razorpay refund if paid online.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve & Refund',
            customClass: { confirmButton: 'btn btn-success me-3', cancelButton: 'btn btn-label-secondary' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.showAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        window.hideAjaxLoader();
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(res.message || 'Failed to approve cancellation.');
                        }
                    },
                    error: function (xhr) {
                        window.hideAjaxLoader();
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                    }
                });
            }
        });
    });

    $(document).on('click', '.reject-cancel-btn', function (e) {
        e.preventDefault();
        const url = $(this).data('url');

        Swal.fire({
            title: 'Reject Cancellation?',
            text: 'This will reject the customer\'s cancellation request and keep the order active.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Reject Request',
            customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.showAjaxLoader();
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        window.hideAjaxLoader();
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(res.message || 'Failed to reject cancellation.');
                        }
                    },
                    error: function (xhr) {
                        window.hideAjaxLoader();
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                    }
                });
            }
        });
    });

    $('#cancel-decline-btn').on('click', function () {
        $('#change-sale-status').val($('#change-sale-status').data('current'));
        $('#cancel-reason-input').val('');
        $('#cancel-reason-wrap').hide();
    });
});
</script>
@endsection
