@extends('layouts.app')

@section('title', 'Edit Sale')

@section('page-css')
<style>
    /* Hide spinners in input fields */
    #itemsTable input[type=number]::-webkit-outer-spin-button,
    #itemsTable input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    #itemsTable input[type=number] {
        -moz-appearance: textfield;
    }
    #itemsTable {
        min-width: 920px !important;
    }
    
    /* Column Width Alignments (Hidden Price column, 6 visible columns: 1:Product, 2:Qty, 3:MRP, 4:Price(hidden), 5:Discount, 6:Total, 7:Action) */
    #itemsTable th:nth-child(1), #itemsTable td:nth-child(1) {
        width: 35% !important;
    }
    #itemsTable th:nth-child(2), #itemsTable td:nth-child(2) {
        width: 8% !important;
        min-width: 80px !important;
    }
    #itemsTable th:nth-child(3), #itemsTable td:nth-child(3) {
        width: 14% !important;
        min-width: 105px !important;
    }
    #itemsTable th:nth-child(4), #itemsTable td:nth-child(4) {
        display: none !important;
    }
    #itemsTable th:nth-child(5), #itemsTable td:nth-child(5) {
        width: 25% !important;
        min-width: 190px !important;
    }
    #itemsTable th:nth-child(6), #itemsTable td:nth-child(6) {
        width: 14% !important;
        min-width: 100px !important;
    }
    #itemsTable th:nth-child(7), #itemsTable td:nth-child(7) {
        width: 44px !important;
        min-width: 44px !important;
        max-width: 44px !important;
        padding-left: 0.25rem !important;
        padding-right: 0.25rem !important;
        text-align: center !important;
    }
    
    /* Make inputs look consistent and prevent clipping */
    #itemsTable .item-qty {
        border-radius: 0.375rem !important;
    }
    #itemsTable .input-group {
        flex-wrap: nowrap !important;
        width: fit-content !important;
    }
    #itemsTable .product-sku-display {
        white-space: nowrap !important;
    }
    .compact-entry-layout .card.mb-4 {
        margin-bottom: 0 !important;
    }

    /* Fixed, industry-standard widths for Qty / Price / Discount inputs on every screen size */
    #itemsTable .item-qty {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
        text-align: center;
        margin: 0 auto;
    }
    #itemsTable .item-price-display {
        display: inline-block;
        min-width: 90px;
        text-align: right;
    }
    #itemsTable .item-discount-type {
        width: 110px !important;
        flex: 0 0 110px !important;
    }
    #itemsTable .item-discount-value {
        width: 80px !important;
        min-width: 80px !important;
        max-width: 80px !important;
        flex: 0 0 80px !important;
    }

    /* Prevent large amounts from ever breaking the sidebar layout, on any screen size */
    #summaryColumn .d-flex.justify-content-between,
    #discountColumn .d-flex.justify-content-between {
        flex-wrap: wrap;
        row-gap: 4px;
    }
    #summaryColumn .d-flex.justify-content-between > span,
    #discountColumn .d-flex.justify-content-between > span {
        min-width: 0;
    }
    #summaryColumn .d-flex.justify-content-between > span:last-child {
        overflow-wrap: anywhere;
        word-break: break-word;
        text-align: right;
        flex: 1 1 auto;
    }
    /* Pair type toggle */
    .pair-type-toggle { display: inline-flex; border-radius: 6px; overflow: hidden; border: 1.5px solid #B4771E; }
    .pair-type-toggle .pair-btn {
        padding: 3px 14px; font-size: .8rem; font-weight: 600; border: none; cursor: pointer;
        background: #fff; color: #B4771E; transition: background .15s, color .15s;
    }
    .pair-type-toggle .pair-btn + .pair-btn { border-left: 1.5px solid #B4771E; }
    .pair-type-toggle .pair-btn.active { background: #B4771E; color: #fff; }

    /* Custom size selector */
    .size-toggle { display: inline-flex; border-radius: 6px; overflow: hidden; border: 1.5px solid #B4771E; }
    .size-toggle .size-btn {
        padding: 3px 14px; font-size: .8rem; font-weight: 600; border: none; cursor: pointer;
        background: #fff; color: #B4771E; transition: background .15s, color .15s;
    }
    .size-toggle .size-btn + .size-btn { border-left: 1.5px solid #B4771E; }
    .size-toggle .size-btn.active { background: #B4771E; color: #fff; }
</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Edit Sale <code>{{ $order->order_no }}</code></h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.show', $order) }}" class="btn btn-label-secondary">
                <i class="ti ti-eye me-1"></i> View
            </a>
            <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <form id="orderForm" action="{{ route('admin.sales.update', $order) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3 compact-entry-layout">
            <div class="col-lg-8">
                <div class="row g-3">

            <!-- Sale Details (Full Width) -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sale Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sale No</label>
                                <input type="text" class="form-control" value="{{ $order->order_no }}" disabled />
                            </div>
                            @if(auth()->user()->hasRole('super-admin'))
                            <div class="col-md-6">
                                <label class="form-label">Sale Date <span class="text-danger">*</span></label>
                                <input type="text" name="order_date" id="order_date" class="form-control flatpickr-date" value="{{ $order->created_at ? $order->created_at->format('d-m-Y') : date('d-m-Y') }}" placeholder="DD-MM-YYYY" readonly />
                                <div class="invalid-feedback"></div>
                            </div>
                            @else
                                <input type="hidden" name="order_date" value="{{ $order->created_at ? $order->created_at->format('d-m-Y') : date('d-m-Y') }}" />
                            @endif
                            <div class="col-md-6">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                @if(auth()->user()->location_id)
                                    <input type="hidden" name="location_id" value="{{ auth()->user()->location_id }}" />
                                    <input type="text" class="form-control" value="{{ $locations->firstWhere('id', auth()->user()->location_id)?->name ?? '-' }}" disabled />
                                @else
                                    <select name="location_id" class="form-select" id="locationSelect">
                                        <option value="">Select Location</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}"
                                                {{ $order->location_id == $location->id ? 'selected' : (auth()->user()->location_id == $location->id && !$order->location_id ? 'selected' : '') }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="customer_id" class="form-select" id="customerSelect">
                                        <option value=""></option>
                                        <option value="0" {{ is_null($order->customer_id) ? 'selected' : '' }}>Walk-in Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ $order->customer_id === $customer->id ? 'selected' : '' }}
                                                data-state="{{ $customer->state ?? '' }}"
                                                data-gst="{{ $customer->gst_no ?? '' }}"
                                                data-is-credit="{{ $customer->is_credit_customer ? '1' : '0' }}"
                                                data-credit-balance="{{ (float) $customer->balance }}">
                                                {{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-label-primary"
                                        data-common-modal="{{ route('admin.customers.create') }}"
                                        data-bs-toggle="tooltip"
                                        title="Add Customer">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sales Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select no-select2">
                                    <option value="1" {{ (int) $order->status === 1 ? 'selected' : '' }}>Pending</option>
                                    <option value="2" {{ (int) $order->status === 2 ? 'selected' : '' }}>Approve</option>
                                    <option value="6" {{ (int) $order->status === 6 ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sale Items (Full Width) -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header border-bottom pb-3" style="z-index: 10;">
                        <h5 class="mb-3">Sale Items</h5>
                        <div class="position-relative">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-search"></i></span>
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product by name or barcode..." autocomplete="off">
                            </div>
                            <div id="productSearchResults" class="list-group position-absolute w-100 mt-1 bg-white" style="z-index: 9999; background-color: #ffffff; display: none; max-height: 250px; overflow-y: auto; overflow-x: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 0.375rem;">
                                <!-- Search results will appear here -->
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 {{ $order->items->isEmpty() ? 'd-none' : '' }}" id="itemsTable">
                                     <thead>
                                         <tr class="table-light">
                                             <th style="min-width: 200px;">Product</th>
                                             <th style="width: 80px; min-width: 80px;">Qty</th>
                                             <th style="width: 100px; min-width: 100px;">MRP</th>
                                             <th class="d-none" style="width: 70px; min-width: 70px;">Price</th>
                                             <th style="width: 190px; min-width: 190px;">Discount</th>
                                             <th style="width: 110px; min-width: 110px;">Total</th>
                                             <th style="width: 44px;"></th>
                                         </tr>
                                     </thead>
                                 <tbody id="itemsBody"></tbody>
                                 <tfoot>
                                     <tr class="table-light">
                                         <td colspan="4" class="text-end fw-semibold">Items Total</td>
                                        <td class="fw-bold text-primary text-nowrap" id="itemsTotal">{{ format_price($order->final_amount) }}</td>
                                        <td></td>
                                     </tr>
                                 </tfoot>
                            </table>
                        </div>
                        <div id="noItemsMsg" class="text-center text-muted py-4">No items added yet.</div>
                    </div>
                </div>
            </div>

            <input type="hidden" id="overallDiscountType" name="order_discount_type" value="{{ $order->order_discount_type ?? 'flat' }}" />
            <input type="hidden" id="overallDiscountValue" name="order_discount_value" value="{{ $order->order_discount_value ?? 0 }}" />
                </div>
            </div>

            <div class="col-lg-4">
                <div class="row g-3">

                    <!-- Discount on order -->
                    <div class="col-12" id="discountColumn">
                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0">Discount on order</h5></div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <select id="orderDiscountTypeSelect" class="form-select no-select2">
                                            <option value="percentage" {{ ($order->order_discount_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                            <option value="flat" {{ ($order->order_discount_type ?? 'percentage') === 'flat' ? 'selected' : '' }}>Flat</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <input type="number" id="orderDiscountValueInput" class="form-control" value="{{ (float) ($order->order_discount_value ?? 0) }}" min="0" step="0.01" placeholder="Enter Discount" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax Details -->
                    @if(($order->source ?? 'POS') !== 'ONLINE')
                    <div class="col-12" id="taxColumn" style="display: none;">
                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0">Tax Details</h5></div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_gst_switch" name="is_gst" value="1" {{ $order->is_gst ? 'checked' : '' }} />
                                    <label class="form-check-label" for="is_gst_switch">GST Bill</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Bottom widgets: Summary -->
                    <div class="col-12" id="summaryColumn" style="display: none;">
                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0">Sale Summary</h5></div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Items Total</span>
                                    <span id="summaryItemsTotal" class="fw-semibold">{{ format_price($order->final_amount) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 d-none" id="summaryDiscountRow">
                                    <span class="text-muted">Discount</span>
                                    <span id="summaryDiscountAmount" class="fw-semibold text-danger">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 d-none" id="summaryCGSTRow">
                                    <span class="text-muted" id="summaryCGSTLabel">CGST (1.5%)</span>
                                    <span id="summaryCGSTAmount" class="fw-semibold">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 d-none" id="summarySGSTRow">
                                    <span class="text-muted" id="summarySGSTLabel">SGST (1.5%)</span>
                                    <span id="summarySGSTAmount" class="fw-semibold">0.00</span>
                                </div>
                                <hr />
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">Final Amount</span>
                                    <div class="input-group input-group-sm" style="max-width: 170px;">
                                        <span class="input-group-text fw-bold text-primary px-2">{{ currency_symbol() }}</span>
                                        <input type="number" id="summaryFinalInput" class="form-control text-end fw-bold text-primary fs-5 py-1 px-2" value="{{ (float) $order->final_amount }}" min="0" step="0.01" placeholder="0.00" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

            <!-- Payment Details -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Payment Details</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3 mt-1" id="useCreditBalanceWrapper" style="display: none;">
                            <input type="hidden" name="use_credit_balance" value="0" />
                            <input class="form-check-input" type="checkbox" id="useCreditBalanceSwitch" name="use_credit_balance" value="1" {{ ($order->use_credit_balance ?? true) ? 'checked' : '' }} />
                            <label class="form-check-label fw-semibold" for="useCreditBalanceSwitch">
                                Use Customer Credit Balance
                                <span class="badge bg-label-success ms-1" id="creditBalanceBadge">{{ currency_symbol() }} 0.00</span>
                            </label>
                        </div>
                        <select name="payment_status" id="paymentStatusSelect" class="form-select no-select2">
                            <option value="1" {{ ($order->payment_status ?? 1) == 1 ? 'selected' : '' }} {{ ($order->payment_status ?? 1) == 3 ? 'disabled' : '' }}>Pending</option>
                            <option value="3" {{ ($order->payment_status ?? 1) == 3 ? 'selected' : '' }}>Partially Paid</option>
                            <option value="2" {{ ($order->payment_status ?? 1) == 2 ? 'selected' : '' }}>Paid</option>
                        </select>
                        <div class="row g-2 mt-1" id="paymentSplitWrapper">
                            <div class="col-6">
                                <label class="form-label">Cash</label>
                                <input type="number" name="paid_cash_amount" id="paidCashAmountInput" class="form-control" value="{{ (float) $order->paid_cash_amount }}" min="0" step="0.01">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Online</label>
                                <input type="number" name="paid_online_amount" id="paidOnlineAmountInput" class="form-control" value="{{ (float) $order->paid_online_amount }}" min="0" step="0.01">
                                <div class="invalid-feedback"></div>
                            </div>
                        @php
                            $editPrevCash = (float)($order->paid_cash_amount ?? 0);
                            $editPrevOnline = (float)($order->paid_online_amount ?? 0);
                            $editPrevPaid = $editPrevCash + $editPrevOnline;
                            $editGrandTotal = (float)($order->final_amount ?? 0);
                            $editDue = max(0, $editGrandTotal - $editPrevPaid);

                            $editPayments = $order->salePayments;
                        @endphp

                        @if(in_array(($order->payment_status ?? 1), [2, 3]) && $editPrevPaid > 0)
                            <div class="mt-3 text-start" style="font-size: 0.8rem;">
                                <div class="d-flex justify-content-between text-muted mb-2">
                                    <span>Total: <strong>{{ format_price($editGrandTotal) }}</strong></span>
                                    <span>Paid: <strong class="text-success">{{ format_price($editPrevPaid) }}</strong></span>
                                    <span>Balance: <strong class="text-danger">{{ format_price($editDue) }}</strong></span>
                                </div>
                                <label class="form-label fw-semibold mb-1" style="font-size: 0.8rem;">Payment History</label>
                                <div class="table-responsive border rounded" style="max-height:150px; overflow-y:auto;">
                                    <table class="table table-sm mb-0" style="font-size: 0.75rem;">
                                        <thead class="table-light"><tr><th>DATE</th><th class="text-end">AMOUNT</th></tr></thead>
                                        <tbody>
                                             @if($editPayments->isNotEmpty())
                                                @foreach($editPayments as $p)
                                                    @php
                                                        $epc = (float)($p->cash_amount ?? 0);
                                                        $epo = (float)($p->online_amount ?? 0);
                                                        $epMethodStr = '';
                                                        if ($epc > 0 && $epo > 0) {
                                                            $epMethodStr = ' (Cash: ' . format_price($epc) . ' + Online: ' . format_price($epo) . ')';
                                                        } elseif ($epo > 0) {
                                                            $epMethodStr = ' (Online)';
                                                        } elseif ($epc > 0) {
                                                            $epMethodStr = ' (Cash)';
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td class="text-nowrap">{{ format_date($p->created_at, 'd M Y, h:i A') }}</td>
                                                        <td class="text-end">{{ format_price($p->amount) }}{{ $epMethodStr }}</td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                @php
                                                    $editMethodStr = '';
                                                    if ($editPrevCash > 0 && $editPrevOnline > 0) {
                                                        $editMethodStr = ' (Cash: ' . format_price($editPrevCash) . ' + Online: ' . format_price($editPrevOnline) . ')';
                                                    } elseif ($editPrevOnline > 0) {
                                                        $editMethodStr = ' (Online)';
                                                    } elseif ($editPrevCash > 0) {
                                                        $editMethodStr = ' (Cash)';
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="text-nowrap">{{ $order->updated_at ? format_date($order->updated_at, 'd M Y, h:i A') : format_date($order->created_at, 'd M Y, h:i A') }}</td>
                                                    <td class="text-end">{{ format_price($editPrevPaid) }}{{ $editMethodStr }}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Save and Cancel Buttons -->
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100" id="submitBtnPrint" data-print="1">
                            <i class="ti ti-printer me-1"></i> Save with Print
                        </button>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-label-primary w-100" id="submitBtnNoPrint" data-print="0">
                            <i class="ti ti-device-floppy me-1"></i> Save without Print
                        </button>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary w-100 d-flex align-items-center justify-content-center">Cancel</a>
                    </div>
                </div>
            </div>
                </div>
            </div>

        </div>
    </form>

    <!-- Item Row Template -->
    <template id="itemRowTemplate">
        <tr class="item-row" data-index="__INDEX__">
            <td class="align-middle">
                <div class="d-flex align-items-center">
                    <div class="product-image-container me-3 flex-shrink-0"></div>
                    <div class="d-flex flex-column mb-1">
                        <span class="product-name-display fw-semibold text-heading"></span>
                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                            <small class="product-sku-display text-muted"></small>
                            <span class="badge stock-display text-nowrap"></span>
                        </div>
                        <div class="variant-select-container"></div>
                        <div class="pair-type-container mt-1"></div>
                        <div class="batch-select-container mt-1"></div>
                    </div>
                </div>
                <input type="hidden" name="items[__INDEX__][product_id]" class="product-id-input" value="">
                <input type="hidden" name="items[__INDEX__][pair_type]" class="pair-type-input" value="single">
                <input type="hidden" name="items[__INDEX__][custom_size_value]" class="custom-size-value-input" value="">
                <div class="invalid-feedback"></div>
            </td>
            <td class="align-middle">
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control item-qty"
                    placeholder="1" min="1" value="1" />
            </td>
            <td class="align-middle text-nowrap">
                <span class="fw-semibold text-heading item-mrp-display text-nowrap" style="min-width: 80px; display: inline-block; white-space: nowrap;">₹0.00</span>
            </td>
            <td class="align-middle text-nowrap d-none">
                <span class="fw-semibold text-heading item-price-display text-nowrap" style="min-width: 80px; display: inline-block; white-space: nowrap;">₹0.00</span>
                <input type="hidden" name="items[__INDEX__][price]" class="item-price" value="0" />
            </td>
            <td class="align-middle">
                <div class="input-group flex-nowrap" style="min-width: 190px;">
                    <select name="items[__INDEX__][discount_type]" class="form-select item-discount-type no-select2" style="width: 110px; flex-shrink: 0; flex-grow: 0; padding-left: 8px; padding-right: 18px; background-position: right 4px center;">
                        <option value="percentage" selected>Percentage</option>
                        <option value="flat">Flat</option>
                    </select>
                    <input type="number" name="items[__INDEX__][discount_value]"
                        class="form-control item-discount-value"
                        placeholder="0" min="0" step="0.01" value="0" />
                </div>
            </td>
            <td class="align-middle">
                <input type="number" class="form-control item-total-input"
                    placeholder="0.00" min="0" step="0.01" value="0" style="min-width: 110px;" />
            </td>
            <td class="align-middle">
                <button type="button" class="btn btn-sm btn-icon btn-label-danger remove-item-btn" title="Remove Item">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    </template>
@endsection

@section('page-js')
<script>
$(document).ready(function () {

    let itemIndex = 0;
    const symbol      = '{{ currency_symbol() }}';
    function formatPrice(val) {
        return parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function formatPriceNoDecimals(val) {
        return parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    function setItemPrice(row, price) {
        const val = parseFloat(price) || 0;
        row.find('.item-price').val(val > 0 ? val.toFixed(2) : '0.00');
        row.find('.item-price-display').text(symbol + ' ' + formatPrice(val));
    }
    function getMinAllowedTotal(row) {
        if (row.data('bypass-min-price')) return 0;

        const qty = parseInt(row.find('.item-qty').val()) || 0;
        const product = row.data('product');
        const variantId = row.data('variant-id');
        const isPair = row.find('.pair-type-input').val() === 'pair';
        const itemMrp = parseFloat(row.data('mrp')) || parseFloat(row.find('.item-price').val()) || 0;

        let purchasePrice = parseFloat(row.data('purchase-price'));
        if ((isNaN(purchasePrice) || purchasePrice <= 0) && product && product.purchase_price) {
            purchasePrice = parseFloat(product.purchase_price) || 0;
        }
        if (isNaN(purchasePrice)) purchasePrice = 0;

        if (product && product.pair_product) {
            const effectiveSizes = getEffectiveCustomSizes(product, variantId);
            if (effectiveSizes && effectiveSizes.length) {
                if (!isPair) {
                    purchasePrice = purchasePrice / 2;
                }
            } else if (!isPair) {
                purchasePrice = purchasePrice / 2;
            }
        }

        if (itemMrp > 0 && purchasePrice > itemMrp) {
            purchasePrice = itemMrp / 1.10;
        }

        return purchasePrice > 0 ? qty * purchasePrice * 1.10 : 0;
    }
    function setProductImage(container, product) {
        if (product.image) {
            container.html(`<img src="${product.image}" class="rounded product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;" alt="${product.name || ''}" />`);
        } else {
            container.html(`<div class="rounded bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="ti ti-photo text-muted" style="font-size: 1.25rem;"></i></div>`);
        }
    }
    let allProducts = [];
    let isExistingItemsLoaded = false;

    const locations = @json($locations);
    const existingItems = @json($existingItems);

    loadExistingItems();

    $.getJSON('{{ route("admin.sales.products-json") }}', function(res) {
        allProducts = res || [];
        $('#itemsBody .item-row').each(function () {
            const row = $(this);
            const pId = row.find('.product-id-input').val();
            if (pId) {
                const freshProduct = allProducts.find(p => p.id == pId);
                if (freshProduct) {
                    row.data('product', freshProduct);
                }
            }
            updateStockInfo(row);
        });
    });
    const customerEditUrlTemplate = '{{ route('admin.customers.edit', ['customer' => '__ID__']) }}';
    let pendingGstFixCustomerId = null;
    updateSummary();

    if ($('#order_date').length && typeof $.fn.flatpickr !== 'undefined') {
        $('#order_date').flatpickr({
            dateFormat: 'd-m-Y',
            defaultDate: $('#order_date').val() || 'today'
        });
    }

    window.refreshTable = function (resData) {
        $.get('{{ route('admin.customers.data') }}?_t=' + new Date().getTime(), function (res) {
            const select  = $('#customerSelect');
            let current = select.val();
            if (resData && resData.status === 'success' && resData.data && resData.data.id) {
                current = resData.data.id;
            }
            select.empty();
            select.append('<option value=""></option>');
            select.append('<option value="0">Walk-in Customer</option>');
            res.data.forEach(function (c) {
                const opt = $('<option>', {
                    value: c.id,
                    text: c.name + (c.phone !== '-' ? ' - ' + c.phone : '')
                });
                opt.attr('data-state', c.state_raw || '');
                opt.attr('data-gst', c.gst_no_raw || '');
                select.append(opt);
            });
            select.val(current).trigger('change');

            // After refresh: if GST is on and missing details, open edit modal
            if (pendingGstFixCustomerId) {
                const fixId = pendingGstFixCustomerId;
                pendingGstFixCustomerId = null;
                const opt = select.find('option[value="' + fixId + '"]');
                if (opt.length && (!opt.attr('data-state') || !opt.attr('data-gst'))) {
                    window.openCommonModal(customerEditUrlTemplate.replace('__ID__', fixId));
                }
            }
        });
    };

    // Helper: check if selected customer has GST details when GST bill is enabled
    function checkCustomerGstDetails() {
        return true;
    }

    // Trigger check when GST switch is toggled
    $(document).on('change', '#is_gst_switch', function () {
        if ($(this).is(':checked')) {
            checkCustomerGstDetails();
        }
    });

    // Trigger check when customer changes while GST is already on
    $(document).on('change', '#customerSelect', function () {
        if ($('#is_gst_switch').is(':checked')) {
            checkCustomerGstDetails();
        }
    });

    // -------------------------------------------------------
    // Product Search and Selection
    // -------------------------------------------------------
    const searchInput = $('#productSearchInput');
    const searchResults = $('#productSearchResults');

    function findExactProductMatch(query) {
        const q = query.toLowerCase().trim();
        if (!q) return null;
        const matches = allProducts.filter(p =>
            p.name.toLowerCase() === q ||
            (p.barcode && String(p.barcode).toLowerCase() === q)
        );
        return matches.length === 1 ? matches[0] : null;
    }

    function hasUnselectedVariableProduct() {
        let unselected = false;
        $('.item-row').each(function () {
            const row = $(this);
            const product = row.data('product');
            if (product && product.type === 'variable') {
                const variantVal = row.find('.variant-select').val();
                if (!variantVal || variantVal === '') {
                    unselected = true;
                }
            }
        });
        return unselected;
    }

    function incrementExistingRowQty(product) {
        let updated = false;
        $('.product-id-input').each(function () {
            const row = $(this).closest('.item-row');
            const rowProduct = row.data('product');
            if (rowProduct && rowProduct.type !== 'variable' && $(this).val() == product.id) {
                const qtyInput = row.find('.item-qty');
                qtyInput.val((parseInt(qtyInput.val()) || 0) + 1);
                updateRowTotal(row);
                updated = true;
                return false;
            }
        });
        return updated;
    }

    function selectSearchProduct(product) {
        if (hasUnselectedVariableProduct()) {
            toastr.warning('Please select a variant for the existing variable product before scanning or adding another product.');
            searchInput.val('');
            searchResults.hide().empty();
            return;
        }

        let exists = false;
        if (product.type !== 'variable') {
            $('.product-id-input').each(function() {
                const row = $(this).closest('.item-row');
                const rowProduct = row.data('product');
                if (rowProduct && rowProduct.type !== 'variable' && $(this).val() == product.id) {
                    exists = true;
                }
            });
        }
        if (exists) {
            incrementExistingRowQty(product);
        } else {
            addItemRow(product);
        }
        searchInput.val('');
        searchResults.hide().empty();
        searchInput.focus();
    }

    let scanMatchTimer = null;

    searchInput.on('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            if (scanMatchTimer) { clearTimeout(scanMatchTimer); scanMatchTimer = null; }
            const exactMatch = findExactProductMatch($(this).val());
            if (exactMatch) {
                selectSearchProduct(exactMatch);
                return;
            }
            const firstItem = searchResults.find('.search-result-item').first();
            if (firstItem.length > 0) {
                firstItem.click();
            }
        }
    });

    searchInput.on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        searchResults.empty();

        if (scanMatchTimer) { clearTimeout(scanMatchTimer); scanMatchTimer = null; }

        if (query.length === 0) {
            searchResults.hide();
            return;
        }

        // Barcode scanners (that don't send an Enter/Tab terminator) fire an
        // input event per character. Wait briefly for the burst to finish
        // before treating an exact match as a completed scan, so a shorter
        // barcode that is a prefix of the one being scanned isn't added by mistake.
        scanMatchTimer = setTimeout(function() {
            if (searchInput.val().toLowerCase().trim() !== query) return;
            const exactMatch = findExactProductMatch(query);
            if (exactMatch) selectSearchProduct(exactMatch);
        }, 100);

        const matchedProducts = allProducts.filter(p =>
            p.name.toLowerCase().includes(query) ||
            p.label.toLowerCase().includes(query) ||
            (p.barcode && p.barcode.toLowerCase().includes(query))
        );

        if (matchedProducts.length === 0) {
            searchResults.html('<div class="list-group-item text-muted">No products found</div>');
            searchResults.show();
            return;
        }

        matchedProducts.forEach(p => {
            const isVar = p.type === 'variable';
            const priceBadge = isVar
                ? '<span class="badge bg-label-warning">Variable</span>'
                : '<span class="badge bg-label-primary">' + symbol + ' ' + formatPrice(p.price) + '</span>';
            const imgHtml = p.image 
                ? `<img src="${p.image}" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;" alt="${p.name}" />`
                : `<div class="rounded me-3 bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;"><i class="ti ti-photo text-secondary fs-4"></i></div>`;

            const item = $(`
                <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center search-result-item bg-white" style="background-color: #ffffff;" data-id="${p.id}">
                    <div class="d-flex align-items-center">
                        ${imgHtml}
                        <div>
                            <div class="fw-semibold">${p.name}</div>
                            <small class="text-muted">Barcode: ${p.barcode}</small>
                        </div>
                    </div>
                    ${priceBadge}
                </a>
            `);
            item.data('product', p);
            searchResults.append(item);
        });

        searchResults.show().scrollTop(0);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#productSearchInput, #productSearchResults').length) {
            searchResults.hide();
        }
    });

    $(document).on('click', '.search-result-item', function() {
        selectSearchProduct($(this).data('product'));
    });

    // A variant with its own pack-size list overrides the product's shared list.
    function getEffectiveCustomSizes(product, variantId) {
        if (variantId && product.variants) {
            const variant = product.variants.find(v => v.id == variantId);
            if (variant && variant.custom_sizes && variant.custom_sizes.length) {
                return variant.custom_sizes;
            }
        }
        return product.custom_sizes || [];
    }

    function buildSizeToggleHtml(sizes, defSize) {
        let sizeHtml = `<div class="size-toggle mt-1">`;
        sizes.forEach(cs => {
            const active = defSize && defSize == cs.size ? 'active' : '';
            const csMrp = cs.mrp != null ? cs.mrp : '';
            sizeHtml += `<button type="button" class="size-btn ${active}" data-value="${cs.size}" data-price="${cs.sale_price}" data-mrp="${csMrp}">${cs.size} pcs</button>`;
        });
        sizeHtml += `</div>`;
        return sizeHtml;
    }

    function loadProductBatches(row, selectedBatchId = null) {
        const product = row.data('product');
        if (!product) return;

        const variantId = row.attr('data-variant-id') || row.data('variant-id') || row.find('.variant-select').val() || null;
        const locationId = $('#locationSelect').val() || $('input[name="location_id"]').val() || null;
        const container = row.find('.batch-select-container');

        container.html('<small class="text-muted"><i class="ti ti-loader spinner-border spinner-border-sm me-1"></i>Loading...</small>');

        $.get('{{ route("admin.sales.product-batches") }}', {
            product_id: product.id,
            product_variant_id: variantId,
            location_id: locationId
        }, function(res) {
            if (res.status === 'success' && res.batches && res.batches.length > 0) {
                let html = '<div class="d-flex align-items-center gap-1 mt-1"><span class="badge bg-label-info text-nowrap" style="font-size: 0.7rem;">Purchase Price:</span><select class="form-select form-select-sm batch-select no-select2" style="font-size: 0.78rem; padding-top: 2px; padding-bottom: 2px;">';
                res.batches.forEach(b => {
                    const sel = (selectedBatchId && selectedBatchId == b.purchase_item_id) ? 'selected' : '';
                    html += `<option value="${b.purchase_item_id}" data-purchase-price="${b.purchase_price}" data-available-qty="${b.available_qty}" ${sel}>${b.label}</option>`;
                });
                html += '</select></div>';
                container.html(html);

                const selOpt = container.find('.batch-select option:selected');
                const availAttr = selOpt.attr('data-available-qty');
                if (selOpt.length && availAttr !== undefined && availAttr !== false) {
                    row.data('available-pcs', parseInt(availAttr) || 0);
                    updateStockInfo(row);
                }
            } else {
                const defPrice = symbol + ' ' + (product.purchase_price ? formatPrice(product.purchase_price) : '0.00');
                let html = '<div class="d-flex align-items-center gap-1 mt-1"><span class="badge bg-label-info text-nowrap" style="font-size: 0.7rem;">Purchase Price:</span><select class="form-select form-select-sm batch-select no-select2" style="font-size: 0.78rem; padding-top: 2px; padding-bottom: 2px;">';
                html += `<option value="" data-purchase-price="${product.purchase_price || 0}" data-available-qty="0" selected>${defPrice}</option>`;
                html += '</select></div>';
                container.html(html);

                row.data('available-pcs', 0);
                updateStockInfo(row);
            }
        }).fail(function() {
            container.empty();
        });
    }

    function addItemRow(product, selectedVariantId = null, qty = 1, price = null, discountType = 'percentage', discountValue = 0, pairType = 'single', customSizeValue = null, prependRow = true, existingMrp = null, purchaseItemId = null) {
        const template = document.getElementById('itemRowTemplate').innerHTML
            .replaceAll('__INDEX__', itemIndex);

        if (prependRow) {
            $('#itemsBody').prepend(template);
        } else {
            $('#itemsBody').append(template);
        }
        $('#noItemsMsg').addClass('d-none');
        $('#itemsTable').removeClass('d-none');

        const row = prependRow ? $('#itemsBody .item-row').first() : $('#itemsBody .item-row').last();
        row.find('.product-id-input').val(product.id);
        row.find('.product-name-display').text(product.name);
        setProductImage(row.find('.product-image-container'), product);

        row.data('product', product);
        row.data('index', itemIndex);

        if (product.type === 'variable') {
            // Build variant select dropdown
            const activeLocId = getLocationId();
            let selectHtml = `<select class="form-select form-select-sm variant-select mt-2 no-select2">`;
            selectHtml += `<option value="" disabled ${!selectedVariantId ? 'selected' : ''}>-- Select Variant --</option>`;
            product.variants.forEach(v => {
                const optPrice = v.sale_price != null ? v.sale_price : 0;
                const optMrp = v.mrp != null ? v.mrp : (product.mrp != null ? product.mrp : 0);
                const optPurchasePrice = v.purchase_price != null ? v.purchase_price : 0;
                const selected = selectedVariantId && selectedVariantId == v.id ? 'selected' : '';

                let vStock = 0;
                if (activeLocId && product.stock_by_location && product.stock_by_location[activeLocId] && product.stock_by_location[activeLocId].variants) {
                    vStock = parseInt(product.stock_by_location[activeLocId].variants[v.id]) || 0;
                }
                const isDisabled = (vStock <= 0 && !selected) ? 'disabled' : '';
                const stockLabel = activeLocId ? (vStock > 0 ? ` (${vStock} Pcs)` : ' (Out of stock)') : '';

                selectHtml += `<option value="${v.id}" data-price="${optPrice}" data-mrp="${optMrp}" data-purchase-price="${optPurchasePrice}" data-stock="${vStock}" ${selected} ${isDisabled}>${v.attr_name}: ${v.value_name} (${symbol}${optPrice})${stockLabel}</option>`;
            });
            selectHtml += `</select>`;
            row.find('.variant-select-container').html(selectHtml);

            // Set initial variant
            const selectedOpt = row.find('.variant-select option:selected');
            const initialVariantId = selectedOpt.val() || '';
            const initialPrice = price != null ? price : (selectedOpt.data('price') || 0);
            const initialMrp = existingMrp != null ? existingMrp : (selectedOpt.data('mrp') || product.mrp || 0);

            row.attr('data-variant-id', initialVariantId);
            row.data('variant-id', initialVariantId);
            row.data('mrp', initialMrp);
            row.data('purchase-price', selectedOpt.data('purchase-price') || 0);
            row.data('bypass-min-price', product.bypass_min_price == 1 || product.bypass_min_price === true);
            row.find('.item-mrp-display').text(symbol + ' ' + formatPrice(initialMrp));
            setItemPrice(row, price != null ? price : (initialPrice > 0 ? initialPrice : (selectedOpt.data('price') || 0)));
            row.find('.product-sku-display').text('Barcode: ' + product.barcode);
        } else {
            row.find('.product-sku-display').text('Barcode: ' + product.barcode);
            const itemMrp = existingMrp != null ? existingMrp : (product.mrp != null ? product.mrp : 0);
            row.data('mrp', itemMrp);
            row.data('purchase-price', product.purchase_price != null ? product.purchase_price : 0);
            row.data('bypass-min-price', product.bypass_min_price == 1 || product.bypass_min_price === true);
            row.find('.item-mrp-display').text(symbol + ' ' + formatPrice(itemMrp));
            setItemPrice(row, price != null ? price : (product.price != null ? product.price : 0));
        }

        if (product.pair_product) {
            const effectiveSizes = getEffectiveCustomSizes(product, row.data('variant-id'));
            if (effectiveSizes.length) {
                const defSize = customSizeValue || effectiveSizes[0].size;
                row.find('.pair-type-container').html(buildSizeToggleHtml(effectiveSizes, defSize));
                row.find('.custom-size-value-input').val(defSize);

                const matchedSize = defSize ? effectiveSizes.find(cs => cs.size == defSize) : null;
                if (matchedSize) {
                    if (matchedSize.mrp != null) {
                        row.data('mrp', matchedSize.mrp);
                        row.find('.item-mrp-display').text(symbol + ' ' + formatPrice(matchedSize.mrp));
                    }
                    if (price == null) {
                        setItemPrice(row, matchedSize.sale_price);
                    }
                }
            }
        }

        row.find('.item-qty').val(qty);
        row.find('.item-discount-type').val(discountType);

        // Auto convert MRP vs Sale Price difference into Discount Percentage
        function applySmartDiscount(rowEl, mrpVal, saleVal, defaultDiscType, defaultDiscVal) {
            if (mrpVal > 0 && saleVal > 0 && mrpVal > saleVal) {
                const diff = mrpVal - saleVal;
                const exactPct = (diff / mrpVal) * 100;
                rowEl.find('.item-discount-type').val('percentage');
                rowEl.find('.item-discount-value').val(Math.round(exactPct * 100) / 100);
            } else {
                rowEl.find('.item-discount-type').val(defaultDiscType || 'percentage');
                rowEl.find('.item-discount-value').val(defaultDiscVal || 0);
            }
        }

        const rowMrp = parseFloat(row.data('mrp')) || 0;
        const salePriceVal = parseFloat(row.find('.item-price').val()) || 0;
        applySmartDiscount(row, rowMrp, salePriceVal, discountType, discountValue);

        updateRowTotal(row);
        updateStockInfo(row);
        loadProductBatches(row, purchaseItemId);

        itemIndex++;
        updateSummary();
    }

    $(document).on('change', '.variant-select', function() {
        const row = $(this).closest('.item-row');
        const product = row.data('product');
        const selectedOpt = $(this).find('option:selected');
        const variantId = selectedOpt.val();
        const variantPrice = selectedOpt.data('price') || 0;
        const variantMrp = selectedOpt.data('mrp') || (product ? product.mrp : 0) || 0;

        row.attr('data-variant-id', variantId);
        row.data('variant-id', variantId);
        row.data('mrp', variantMrp);
        row.data('purchase-price', selectedOpt.data('purchase-price'));
        row.find('.item-mrp-display').text(symbol + ' ' + formatPrice(variantMrp));

        function applySmartDisc(rowEl, mrpVal, saleVal) {
            if (mrpVal > 0 && saleVal > 0 && mrpVal > saleVal) {
                const diff = mrpVal - saleVal;
                const exactPct = (diff / mrpVal) * 100;
                rowEl.find('.item-discount-type').val('percentage');
                rowEl.find('.item-discount-value').val(Math.round(exactPct * 100) / 100);
            } else {
                rowEl.find('.item-discount-value').val(0);
            }
        }

        if (product && product.pair_product) {
            const effectiveSizes = getEffectiveCustomSizes(product, variantId);
            if (effectiveSizes.length) {
                const currentSize = parseFloat(row.find('.custom-size-value-input').val());
                const stillValid = effectiveSizes.find(cs => cs.size == currentSize);
                const defSize = stillValid ? currentSize : effectiveSizes[0].size;
                row.find('.pair-type-container').html(buildSizeToggleHtml(effectiveSizes, defSize));
                row.find('.custom-size-value-input').val(defSize);
                const matchedSize = effectiveSizes.find(cs => cs.size == defSize);
                if (matchedSize && matchedSize.mrp != null) {
                    row.data('mrp', matchedSize.mrp);
                    row.find('.item-mrp-display').text(symbol + ' ' + formatPrice(matchedSize.mrp));
                }
                const targetPrice = matchedSize ? matchedSize.sale_price : variantPrice;
                const targetMrp = (matchedSize && matchedSize.mrp != null) ? matchedSize.mrp : variantMrp;
                applySmartDisc(row, targetMrp, targetPrice);
                setItemPrice(row, targetPrice);
            } else {
                applySmartDisc(row, variantMrp, variantPrice);
                setItemPrice(row, variantPrice);
            }
        } else {
            applySmartDisc(row, variantMrp, variantPrice);
            setItemPrice(row, variantPrice);
        }

        updateRowTotal(row);
        updateStockInfo(row);
        updateSummary();
    });

    // Pair type toggle (Single / Pair)
    $(document).on('click', '.pair-btn', function () {
        const toggle = $(this).closest('.pair-type-toggle');
        const row = $(this).closest('.item-row');
        const selected = $(this).data('value');
        toggle.find('.pair-btn').removeClass('active');
        $(this).addClass('active');
        row.find('.pair-type-input').val(selected);
        const price = selected === 'pair'
            ? parseFloat(toggle.data('pair-price'))
            : parseFloat(toggle.data('single-price'));
        setItemPrice(row, price);
        updateRowTotal(row);
        updateStockInfo(row);
        updateSummary();
    });

    // Custom size toggle
    $(document).on('click', '.size-btn', function () {
        const toggle = $(this).closest('.size-toggle');
        const row = $(this).closest('.item-row');
        toggle.find('.size-btn').removeClass('active');
        $(this).addClass('active');
        row.find('.custom-size-value-input').val($(this).data('value'));
        
        const sizePrice = parseFloat($(this).data('price')) || 0;
        const sizeMrp = parseFloat($(this).data('mrp')) || parseFloat(row.data('mrp')) || 0;

        if (sizeMrp > 0) {
            row.data('mrp', sizeMrp);
            row.find('.item-mrp-display').text(symbol + ' ' + formatPrice(sizeMrp));
        }

        if (sizeMrp > 0 && sizePrice > 0 && sizeMrp > sizePrice) {
            const diff = sizeMrp - sizePrice;
            const exactPct = (diff / sizeMrp) * 100;
            row.find('.item-discount-type').val('percentage');
            row.find('.item-discount-value').val(Math.round(exactPct * 100) / 100);
        }
        setItemPrice(row, sizePrice);

        updateRowTotal(row);
        updateStockInfo(row);
        updateSummary();
    });

    $(document).on('change', '.batch-select', function () {
        const row = $(this).closest('.item-row');
        const selOpt = $(this).find('option:selected');
        const availAttr = selOpt.attr('data-available-qty');
        if (selOpt.length && availAttr !== undefined && availAttr !== false) {
            row.data('available-pcs', parseInt(availAttr) || 0);
            updateStockInfo(row);
        }
        updateRowTotal(row);
        updateSummary();
    });

    // -------------------------------------------------------
    // Remove Item Row
    // -------------------------------------------------------
    $(document).on('click', '.remove-item-btn', function () {
        const row = $(this).closest('.item-row');
        row.remove();
        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
        }
        updateSummary();
    });

    // Pre-populate existing items correctly grouping under their parent products
    function loadExistingItems() {
        if (!existingItems || existingItems.length === 0 || isExistingItemsLoaded) return;
        isExistingItemsLoaded = true;

        // Group existing items by product_id
        const grouped = {};
        existingItems.forEach(function(item) {
            if (!grouped[item.product_id]) {
                grouped[item.product_id] = [];
            }
            grouped[item.product_id].push(item);
        });

        // For each unique product_id
        Object.keys(grouped).forEach(function(productId) {
            const itemsForProduct = grouped[productId];
            let product = allProducts.find(p => p.id == productId);
            if (!product && itemsForProduct[0] && itemsForProduct[0].product) {
                product = itemsForProduct[0].product;
            } else if (product && itemsForProduct[0] && itemsForProduct[0].product && itemsForProduct[0].product.stock_by_location) {
                
                if (!product.stock_by_location) {
                    product.stock_by_location = itemsForProduct[0].product.stock_by_location;
                }
            }
            if (!product) return;

            if (product.type === 'variable') {
                const variantItems = itemsForProduct.filter(item => item.product_variant_id != null);
                
                variantItems.forEach(item => {
                    if (parseInt(item.quantity) <= 0) return;
                    
                    let matchedVariant = product.variants.find(v => v.id == item.product_variant_id);
                    
                    if (!matchedVariant) {
                        matchedVariant = product.variants.find(v => parseFloat(v.sale_price) == parseFloat(item.price));
                    }
                    
                    if (!matchedVariant && product.variants.length > 0) {
                        matchedVariant = product.variants[0];
                    }
                    
                    if (matchedVariant) {
                        addItemRow(product, matchedVariant.id, item.quantity, item.price, item.discount_type, item.discount_value, item.pair_type || 'single', item.custom_size_value, false, item.mrp);
                    }
                });
            } else {
                const item = itemsForProduct[0];
                addItemRow(product, null, item.quantity, item.price, item.discount_type, item.discount_value, item.pair_type || 'single', item.custom_size_value, false, item.mrp);
            }
        });
    }

    loadExistingItems();

    // Remove Parent Product Row and all its variants
    $(document).on('click', '.remove-parent-btn', function () {
        const parentRow = $(this).closest('.parent-row');
        const productId = parentRow.data('product-id');
        
        $(`.variant-row[data-parent-id="${productId}"]`).remove();
        parentRow.remove();
        
        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
        }
        updateSummary();
    });

    // Remove single variant row
    $(document).on('click', '.remove-variant-btn', function () {
        const row = $(this).closest('.variant-row');
        row.remove();
        updateSummary();
    });

    function getLocationId() {
        return $('#locationSelect').val() || $('input[name="location_id"]').val() || '';
    }

    $(document).on('change', '#locationSelect, select[name="location_id"]', function () {
        $('#itemsBody .item-row').each(function () {
            loadProductBatches($(this));
            updateStockInfo($(this));
        });
    });

    function updateStockInfo(row) {
        const productId  = row.find('.product-id-input').val();
        const locationId = getLocationId();
        const stockDisplay = row.find('.stock-display');
        const variantId = row.attr('data-variant-id') || row.data('variant-id');
        const product = row.data('product');
        const isPair = row.find('.pair-type-input').val() === 'pair';
        const customSizeValue = parseFloat(row.find('.custom-size-value-input').val()) || 0;
        if (!productId || !product) { stockDisplay.text('').removeAttr('title').css('cursor', '').hide(); return; }

        const stockByLocation = product.stock_by_location || {};

        function rawQtyAt(locId) {
            const locData = stockByLocation[locId] ?? stockByLocation[String(locId)] ?? stockByLocation[Number(locId)];
            if (locData == null) return 0;
            let raw = 0;
            if (typeof locData === 'object' && locData !== null) {
                raw = product.type === 'variable'
                    ? (variantId ? (locData.variants?.[variantId] ?? locData.variants?.[String(variantId)] ?? 0) : (locData.parent ?? 0))
                    : (locData.parent ?? locData.quantity ?? 0);
            } else {
                raw = locData;
            }
            return Math.ceil(parseFloat(raw) || 0);
        }
        function displayQtyAt(locId) {
            const raw = rawQtyAt(locId);
            if (product.pair_product && customSizeValue > 0) {
                return Math.floor(raw / customSizeValue);
            }
            return raw;
        }

        let qty = 0;
        let breakdownText = 'Stock Breakdown:\n';
        let hasStock = false;

        const rowAvailPcs = row.data('available-pcs');
        if (rowAvailPcs !== undefined && rowAvailPcs !== null && rowAvailPcs !== '') {
            qty = parseInt(rowAvailPcs) || 0;
        } else if (locationId) {
            qty = rawQtyAt(locationId);
        } else {
            Object.keys(stockByLocation).forEach(locId => {
                qty += rawQtyAt(locId);
            });
        }

        Object.keys(stockByLocation).forEach(locId => {
            const lQty = rawQtyAt(locId);
            const loc = locations.find(l => l.id == locId);
            const locName = loc ? loc.name : 'Unknown';
            if (lQty > 0) {
                breakdownText += `- ${locName}: ${lQty} Pcs\n`;
                hasStock = true;
            }
        });

        if (!hasStock) {
            breakdownText += 'No stock in any branch';
        }

        const labelPrefix = locationId ? 'Stock: ' : 'Total Stock: ';
        let stockLabelText = 'Out of Stock';
        if (qty > 0) {
            let formattedQty = qty + ' Pcs';
            if (product && product.pair_product) {
                const effectiveSizes = getEffectiveCustomSizes(product, variantId);
                let pairSize = customSizeValue > 0 ? customSizeValue : 0;
                if (!pairSize && effectiveSizes && effectiveSizes.length > 0) {
                    const sizes = effectiveSizes.map(s => typeof s === 'object' && s !== null ? parseFloat(s.size) : parseFloat(s)).filter(s => s > 0);
                    if (sizes.length > 0) pairSize = Math.max(...sizes);
                }
                if (!pairSize) pairSize = 1;

                const pairsCount = Math.floor(qty / pairSize);
                const remPcs = qty % pairSize;
                let parts = [];
                if (pairsCount > 0) parts.push(pairsCount + (pairsCount > 1 ? ' Pairs' : ' Pair'));
                if (remPcs > 0) parts.push(remPcs + ' Pcs');
                formattedQty = parts.length > 0 ? parts.join('<br>') : '0';
            }
            stockLabelText = labelPrefix + formattedQty;
        }

        stockDisplay
            .html(stockLabelText)
            .attr('title', breakdownText.trim())
            .css('cursor', 'help')
            .removeClass('bg-label-success bg-label-danger bg-label-warning text-success text-danger text-warning')
            .addClass(qty > 0 ? (qty < 10 ? 'bg-label-warning' : 'bg-label-success') : 'bg-label-danger')
            .show();
    }

    // -------------------------------------------------------
    // Price / Qty / Discount change
    // -------------------------------------------------------
    $(document).on('input change', '.item-price, .item-qty, .item-discount-value, .item-discount-type', function () {
        const row = $(this).closest('.item-row');
        if (row.length > 0) {
            if ($(this).hasClass('item-price')) {
                const mrpVal = parseFloat(row.data('mrp')) || 0;
                const newPriceVal = parseFloat($(this).val()) || 0;
                if (mrpVal > 0 && newPriceVal > 0 && mrpVal > newPriceVal) {
                    const diff = mrpVal - newPriceVal;
                    const exactPct = (diff / mrpVal) * 100;
                    row.find('.item-discount-type').val('percentage');
                    row.find('.item-discount-value').val(Math.round(exactPct * 100) / 100);
                } else if (mrpVal > 0 && newPriceVal >= mrpVal) {
                    row.find('.item-discount-value').val(0);
                }
            }

            const discType = row.find('.item-discount-type').val();
            const discValueInput = row.find('.item-discount-value');
            if (discType === 'percentage' && parseFloat(discValueInput.val()) > 100) {
                discValueInput.val(100);
            }
            updateRowTotal(row);
        } else {
            updateSummary();
        }
    });

    $(document).on('input change', '.item-total-input', function () {
        const row = $(this).closest('.item-row');
        const enteredTotal = parseFloat($(this).val()) || 0;
        const mrp = parseFloat(row.data('mrp')) || parseFloat(row.find('.item-price').val()) || 0;
        const qty = parseInt(row.find('.item-qty').val()) || 1;
        const subtotal = mrp * qty;

        let diff = subtotal - enteredTotal;
        if (diff < 0) diff = 0;

        if (subtotal > 0 && diff > 0) {
            const exactPct = (diff / subtotal) * 100;
            row.find('.item-discount-type').val('percentage');
            row.find('.item-discount-value').val(Math.round(exactPct * 100) / 100);
        } else {
            row.find('.item-discount-type').val('percentage');
            row.find('.item-discount-value').val(0);
        }

        updateRowTotal(row, true);
    });

    $(document).on('input change', '#orderDiscountTypeSelect, #orderDiscountValueInput', function () {
        const discType = $('#orderDiscountTypeSelect').val();
        const valInput = $('#orderDiscountValueInput');
        if (discType === 'percentage' && parseFloat(valInput.val()) > 100) {
            valInput.val(100);
        }
        $('#overallDiscountType').val(discType);
        $('#overallDiscountValue').val(parseFloat(valInput.val()) || 0);
        updateSummary();
    });

    $('#orderDiscountTypeSelect').trigger('change');

    function updateRowTotal(row, isFromTotalInput = false) {
        const mrp      = parseFloat(row.data('mrp')) || 0;
        const price    = parseFloat(row.find('.item-price').val()) || 0;
        const qty      = parseInt(row.find('.item-qty').val()) || 0;
        const discVal  = parseFloat(row.find('.item-discount-value').val()) || 0;
        const discType = row.find('.item-discount-type').val() || 'flat';

        const basePrice = mrp > 0 ? mrp : price;
        const subtotal = basePrice * qty;
        let discount = 0;
        if (discType === 'flat') {
            discount = discVal;
        } else if (discType === 'percentage') {
            discount = subtotal * (discVal / 100);
        }

        if (discount > subtotal) discount = subtotal;

        let total = subtotal - discount;
        if (discType === 'percentage') {
            total = Math.round(total);
        }
        if (!isFromTotalInput) {
            row.find('.item-total-input').val(total > 0 ? total.toFixed(2) : '0.00');
        }

        let violatesFloor = false;
        if (row.data('bypass-min-price')) {
            violatesFloor = total < 0;
        } else {
            const minTotal = getMinAllowedTotal(row);
            violatesFloor = discVal > 0 && minTotal > 0 && total < minTotal - 0.01;
        }
        row.find('.item-discount-value').toggleClass('is-invalid', violatesFloor);
        if (row.hasClass('parent-row')) {
            row.find('.parent-total').text(symbol + ' ' + formatPrice(total));
        } else {
            row.find('.item-total').text(symbol + ' ' + formatPrice(total));
        }
        updateSummary();
    }

    function updateSummary(isFromFinalInput = false) {
        let subtotalSum = 0;
        let discountSum = 0;
        let minFloorTotal = 0;
        let count       = 0;
        $('#itemsBody .item-row').each(function () {
            const qty      = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) return;

            const mrp      = parseFloat($(this).data('mrp')) || 0;
            const price    = parseFloat($(this).find('.item-price').val()) || 0;
            const discVal  = parseFloat($(this).find('.item-discount-value').val()) || 0;
            const discType = $(this).find('.item-discount-type').val() || 'flat';

            const basePrice = mrp > 0 ? mrp : price;
            const subtotal = basePrice * qty;
            let discount = 0;
            if (discType === 'flat') {
                discount = discVal;
            } else if (discType === 'percentage') {
                discount = Math.round(subtotal * (discVal / 100));
            }

            if (discount > subtotal) discount = subtotal;

            const minTotal = getMinAllowedTotal($(this));
            if (minTotal > 0) {
                minFloorTotal += minTotal;
            }

            subtotalSum += subtotal;
            discountSum += discount;
            count++;
        });

        const itemsTotal = subtotalSum - discountSum;

        const orderDiscType = $('#overallDiscountType').val() || 'flat';
        const orderDiscVal = parseFloat($('#overallDiscountValue').val()) || 0;
        let orderDiscountAmount = 0;

        if (orderDiscVal > 0) {
            if (orderDiscType === 'flat') {
                orderDiscountAmount = orderDiscVal;
            } else if (orderDiscType === 'percentage') {
                orderDiscountAmount = itemsTotal * (orderDiscVal / 100);
            }
        }

        if (orderDiscountAmount > itemsTotal) {
            orderDiscountAmount = itemsTotal;
        }

        const finalAmount = itemsTotal - orderDiscountAmount;
        const orderViolatesFloor = (orderDiscVal > 0 && minFloorTotal > 0 && finalAmount < minFloorTotal - 0.01)
            || (finalAmount < 0);
        $('#orderDiscountValueInput').toggleClass('is-invalid', orderViolatesFloor);
        const totalDiscount = discountSum + orderDiscountAmount;

        // GST Calculation
        const isOnlineOrder = @json(($order->source ?? 'POS') === 'ONLINE');
        const isGst = !isOnlineOrder && $('#is_gst_switch').is(':checked');
        const gstRate = @json(\App\Models\Setting::getValue('purchase_gst_rate', 3));
        let taxAmount = 0;

        if (isGst) {
            const halfRate = gstRate / 2;
            const cgst = finalAmount * (halfRate / 100);
            const sgst = finalAmount * (halfRate / 100);
            taxAmount = cgst + sgst;

            $('#summaryCGSTLabel').text('CGST (' + halfRate + '%)');
            $('#summaryCGSTAmount').text(symbol + ' ' + formatPrice(cgst));
            $('#summarySGSTLabel').text('SGST (' + halfRate + '%)');
            $('#summarySGSTAmount').text(symbol + ' ' + formatPrice(sgst));

            $('#summaryCGSTRow').removeClass('d-none');
            $('#summarySGSTRow').removeClass('d-none');
        } else {
            $('#summaryCGSTRow').addClass('d-none');
            $('#summarySGSTRow').addClass('d-none');
        }

        const grandTotalAmount = Math.round(finalAmount + taxAmount);
        window.currentGrandTotal = grandTotalAmount;
        updatePaymentSplit();

        $('#itemsTotal').text(symbol + ' ' + formatPrice(itemsTotal));
        $('#summaryItemsTotal').text(symbol + ' ' + formatPrice(subtotalSum));
        $('#summaryDiscountAmount').text(symbol + ' ' + formatPrice(totalDiscount));
        $('#summaryFinal').text(symbol + ' ' + formatPriceNoDecimals(grandTotalAmount));
        if (!isFromFinalInput && !$('#summaryFinalInput').is(':focus')) {
            $('#summaryFinalInput').val(grandTotalAmount);
        }

        if (totalDiscount > 0) {
            $('#summaryDiscountAmount').closest('.d-flex').removeClass('d-none');
        } else {
            $('#summaryDiscountAmount').closest('.d-flex').addClass('d-none');
        }

        const totalRows = $('#itemsBody .item-row').length;
        if (totalRows > 0) {
            $('#itemsTotal').closest('tr').show();
            if (!isOnlineOrder) {
                $('#taxColumn').show();
            }
            $('#summaryColumn').show();
            $('#discountColumn').show();
            $('#noItemsMsg').addClass('d-none');
            $('#itemsTable').removeClass('d-none');
        } else {
            $('#itemsTotal').closest('tr').hide();
            $('#taxColumn').hide();
            $('#summaryColumn').hide();
            $('#discountColumn').hide();
            $('#noItemsMsg').removeClass('d-none');
            $('#itemsTable').addClass('d-none');
        }
    }

    $(document).on('input', '#summaryFinalInput', function () {
        let targetGrandTotal = parseFloat($(this).val()) || 0;

        let subtotalSum = 0;
        let discountSum = 0;
        $('#itemsBody .item-row').each(function () {
            const qty = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) return;

            const mrp = parseFloat($(this).data('mrp')) || 0;
            const price = parseFloat($(this).find('.item-price').val()) || 0;
            const discVal = parseFloat($(this).find('.item-discount-value').val()) || 0;
            const discType = $(this).find('.item-discount-type').val() || 'flat';

            const basePrice = mrp > 0 ? mrp : price;
            const subtotal = basePrice * qty;
            let discount = 0;
            if (discType === 'flat') {
                discount = discVal;
            } else if (discType === 'percentage') {
                discount = subtotal * (discVal / 100);
            }
            if (discount > subtotal) discount = subtotal;

            subtotalSum += subtotal;
            discountSum += discount;
        });

        const itemsTotal = subtotalSum - discountSum;
        const isOnlineOrder = @json(($order->source ?? 'POS') === 'ONLINE');
        const isGst = !isOnlineOrder && $('#is_gst_switch').is(':checked');
        const gstRate = @json(\App\Models\Setting::getValue('purchase_gst_rate', 3));
        const taxMultiplier = isGst ? (1 + (parseFloat(gstRate) / 100)) : 1.0;

        const maxAllowedGrandTotal = Math.round(itemsTotal * taxMultiplier);
        if (targetGrandTotal > maxAllowedGrandTotal) {
            targetGrandTotal = maxAllowedGrandTotal;
            $(this).val(maxAllowedGrandTotal);
        }

        const targetNetAmount = targetGrandTotal / taxMultiplier;
        let requiredDiscount = itemsTotal - targetNetAmount;
        if (requiredDiscount < 0) requiredDiscount = 0;
        if (requiredDiscount > itemsTotal) requiredDiscount = itemsTotal;

        $('#orderDiscountTypeSelect').val('flat');
        $('#orderDiscountValueInput').val(requiredDiscount > 0 ? requiredDiscount.toFixed(2) : 0);
        $('#overallDiscountType').val('flat');
        $('#overallDiscountValue').val(requiredDiscount > 0 ? requiredDiscount : 0);

        updateSummary(true);
    });

    $(document).on('change', '#is_gst_switch', function () {
        updateSummary();
    });

    function checkCustomerCreditBalance() {
        const selectedOpt = $('#customerSelect').find('option:selected');
        const isCredit = selectedOpt.data('is-credit') == '1';
        const balance = parseFloat(selectedOpt.data('credit-balance')) || 0;

        if (isCredit && balance > 0) {
            $('#creditBalanceBadge').text(symbol + ' ' + formatPrice(balance));
            $('#useCreditBalanceWrapper').show();
            $('#useCreditBalanceSwitch').prop('disabled', false);
        } else {
            $('#useCreditBalanceWrapper').hide();
            $('#useCreditBalanceSwitch').prop('checked', false).prop('disabled', true);
        }
        updatePaymentSplit();
    }

    $(document).on('change', '#customerSelect', function () {
        checkCustomerCreditBalance();
    });

    $(document).on('change', '#useCreditBalanceSwitch', function () {
        updatePaymentSplit();
    });

    checkCustomerCreditBalance();

    function updatePaymentSplit() {
        const val = $('#paymentStatusSelect').val();
        const isPaid = val === '2';
        const isPartial = val === '3';
        const isPaymentActive = isPaid || isPartial;

        $('#paymentSplitWrapper').toggleClass('d-none', !isPaymentActive);
        $('#paidOnlineAmountInput, #paidCashAmountInput').prop('disabled', !isPaymentActive);
        $('#paidOnlineAmountInput, #paidCashAmountInput').prop('required', isPaymentActive);

        if (!isPaymentActive) {
            $('#paidOnlineAmountInput').val(0);
            $('#paidCashAmountInput').val(0);
            return;
        }

        if (isPaid) {
            syncPaymentSplit('online');
        }
    }

    function syncPaymentSplit(source) {
        const val = $('#paymentStatusSelect').val();
        const isPaid = val === '2';

        const total = window.currentGrandTotal || 0;
        if (total <= 0) {
            return;
        }

        let effectivePayable = total;
        const selectedOpt = $('#customerSelect').find('option:selected');
        const isCredit = selectedOpt.data('is-credit') == '1';
        const balance = parseFloat(selectedOpt.data('credit-balance')) || 0;
        const useCredit = $('#useCreditBalanceSwitch').is(':checked') && isCredit && balance > 0;

        if (useCredit) {
            const creditDeduct = Math.min(balance, total);
            effectivePayable = Math.max(0, total - creditDeduct);
        }

        if (isPaid) {
            if (source === 'cash') {
                let cash = parseFloat($('#paidCashAmountInput').val()) || 0;
                cash = Math.min(Math.max(cash, 0), effectivePayable);
                $('#paidCashAmountInput').val(cash);
                $('#paidOnlineAmountInput').val(Math.round((effectivePayable - cash) * 100) / 100);
            } else {
                let online = parseFloat($('#paidOnlineAmountInput').val()) || 0;
                online = Math.min(Math.max(online, 0), effectivePayable);
                $('#paidOnlineAmountInput').val(online);
                $('#paidCashAmountInput').val(Math.round((effectivePayable - online) * 100) / 100);
            }
        }
    }

    $(document).on('change', '#paymentStatusSelect', updatePaymentSplit);
    $(document).on('input', '#paidOnlineAmountInput', function () { syncPaymentSplit('online'); });
    $(document).on('input', '#paidCashAmountInput', function () { syncPaymentSplit('cash'); });

    // Recomputes the discount floor from scratch (mirrors the server-side check)
    // and returns an error message if the current discount values violate it.
    // Returns null when everything is within the allowed limit.
    function validateDiscounts() {
        let itemsTotal = 0;
        let minFloorTotal = 0;
        let errorMsg = null;

        $('#itemsBody .item-row').each(function () {
            if (errorMsg) return;

            const qty = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) return;

            const mrp      = parseFloat($(this).data('mrp')) || 0;
            const price    = parseFloat($(this).find('.item-price').val()) || 0;
            const discVal  = parseFloat($(this).find('.item-discount-value').val()) || 0;
            const discType = $(this).find('.item-discount-type').val() || 'flat';

            const basePrice = mrp > 0 ? mrp : price;
            const subtotal = basePrice * qty;
            let discount = discType === 'percentage' ? subtotal * (discVal / 100) : discVal;
            if (discount > subtotal) discount = subtotal;

            const itemTotal = subtotal - discount;
            itemsTotal += itemTotal;

            if ($(this).data('bypass-min-price')) {
                if (itemTotal < 0) {
                    $(this).find('.item-discount-value').addClass('is-invalid');
                    errorMsg = 'Item amount cannot be negative.';
                }
                return;
            }

            const itemDiscVal = parseFloat($(this).find('.item-discount-value').val()) || 0;
            const minTotal = getMinAllowedTotal($(this));
            if (minTotal > 0) {
                minFloorTotal += minTotal;
                if (itemDiscVal > 0 && itemTotal < minTotal - 0.01) {
                    $(this).find('.item-discount-value').addClass('is-invalid');
                    errorMsg = 'Discount cannot be applied to this order.';
                }
            }
        });

        if (errorMsg) return errorMsg;

        const orderDiscType = $('#overallDiscountType').val() || 'flat';
        const orderDiscVal = parseFloat($('#overallDiscountValue').val()) || 0;
        let orderDiscountAmount = orderDiscType === 'percentage' ? itemsTotal * (orderDiscVal / 100) : orderDiscVal;
        if (orderDiscountAmount > itemsTotal) orderDiscountAmount = itemsTotal;

        const finalAmount = itemsTotal - orderDiscountAmount;

        if (finalAmount < 0) {
            $('#orderDiscountValueInput').addClass('is-invalid');
            return 'Order total cannot be negative.';
        }

        if (orderDiscVal > 0 && minFloorTotal > 0 && finalAmount < minFloorTotal - 0.01) {
            $('#orderDiscountValueInput').addClass('is-invalid');
            return 'Discount cannot be applied to this order.';
        }

        return null;
    }

    const thermalUrlTemplate = '{{ route('admin.sales.thermal', ['sale' => '__ID__', 'auto_print' => 1]) }}';
    let printAfterSave = false;
    let printWindowRef = null;

    function closePendingPrintTab() {
        if (printWindowRef) {
            printWindowRef.close();
            printWindowRef = null;
        }
    }

    function getClientValidationError() {
        const customerId = $('#customerSelect').val();
        if (customerId === null || customerId === '') {
            $('#customerSelect').next('.select2-container').find('.select2-selection').css('border-color', '#ea5455');
            return 'Please select a customer.';
        }

        if ($('.item-row').length === 0) {
            return 'Please add at least one item to the sale.';
        }

        let invalidQty = false;
        $('.item-row').each(function () {
            const qty = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) {
                invalidQty = true;
                $(this).find('.item-qty').addClass('is-invalid');
            } else {
                $(this).find('.item-qty').removeClass('is-invalid');
            }
        });
        if (invalidQty) {
            return 'Please add at least one item with quantity greater than 0.';
        }

        let variantMissing = false;
        $('.item-row').each(function () {
            const row = $(this);
            const qty = parseInt(row.find('.item-qty').val()) || 0;
            const product = row.data('product');
            if (product && product.type === 'variable') {
                const vVal = row.find('.variant-select').val();
                if (!vVal) {
                    variantMissing = true;
                    row.find('.variant-select').addClass('is-invalid');
                }
            }
        });
        if (variantMissing) {
            return 'Please select a variant for each variable product before saving.';
        }

        let sizeMissing = false;
        $('.item-row').each(function () {
            const row = $(this);
            const qty = parseInt(row.find('.item-qty').val()) || 0;
            const product = row.data('product');
            if (product && product.pair_product && !row.find('.custom-size-value-input').val()) {
                sizeMissing = true;
            }
        });
        if (sizeMissing) {
            return 'Please select a size for each pair product before saving.';
        }

        const payStatus = $('#paymentStatusSelect').val();
        if (payStatus === '3') {
            const cashAmt = parseFloat($('#paidCashAmountInput').val()) || 0;
            const onlineAmt = parseFloat($('#paidOnlineAmountInput').val()) || 0;
            if ((cashAmt + onlineAmt) <= 0) {
                $('#paidCashAmountInput, #paidOnlineAmountInput').addClass('is-invalid');
                return 'Paid amount must be greater than 0 for Partially Paid status.';
            }
        }

        return validateDiscounts();
    }

    let activeSubmitBtn = null;
    $('#submitBtnPrint, #submitBtnNoPrint').on('click', function (e) {
        activeSubmitBtn = $(this);
        printAfterSave = $(this).data('print') == 1;

        const validationError = getClientValidationError();
        if (validationError) {
            e.preventDefault();
            e.stopPropagation();
            toastr.error(validationError);
            closePendingPrintTab();
            return false;
        }

        printWindowRef = printAfterSave ? window.open('', '_blank') : null;
    });

    $('#orderForm').on('submit', function (e) {
        e.preventDefault();

        const validationError = getClientValidationError();
        if (validationError) {
            toastr.error(validationError);
            closePendingPrintTab();
            return;
        }

        // GST customer details check on submit
        if (!checkCustomerGstDetails()) {
            closePendingPrintTab();
            return;
        }

        const dateInput = $('#order_date').val() || '{{ isset($order) && $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format("Y-m-d") : (isset($order) && $order->created_at ? $order->created_at->format("Y-m-d") : date("Y-m-d")) }}';
        if (dateInput && typeof window.checkAndConfirmDateSubmission === 'function') {
            const customerName = $('#customer_id option:selected').text() || 'Customer';
            const finalAmount = $('#grandTotalText').text() || ('₹' + $('#grandTotal').val());

            const confirmed = window.checkAndConfirmDateSubmission(this, dateInput, {
                module: 'Sale Invoice',
                partyLabel: 'Customer:',
                partyName: customerName,
                amount: finalAmount,
                dateFormatted: dateInput,
                submitBtn: activeSubmitBtn || $('#submitBtnPrint')
            });

            if (!confirmed) {
                closePendingPrintTab();
                return;
            }
        }

        const form = $(this);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.select2-container .select2-selection').css('border-color', '');
        form.find('.invalid-feedback').text('').hide();

        $('#hiddenSubmitContainer').remove();

        const hiddenContainer = $('<div id="hiddenSubmitContainer" style="display: none;"></div>');
        form.append(hiddenContainer);

        const visibleInputs = $('#itemsTable').find('input, select');
        visibleInputs.prop('disabled', true);

        let submitIdx = 0;
        $('.item-row').each(function() {
            const row = $(this);
            const product = row.data('product');
            const qty = parseInt(row.find('.item-qty').val()) || 0;
            const variantId = row.data('variant-id') || '';
            const mrp = parseFloat(row.data('mrp')) || 0;
            const price = parseFloat(row.find('.item-price').val()) || 0;
            const discountType = row.find('.item-discount-type').val() || 'flat';
            const discountValue = parseFloat(row.find('.item-discount-value').val()) || 0;

            const batchSelect = row.find('.batch-select');
            const purchaseItemId = batchSelect.length ? (batchSelect.val() || '') : '';
            const purchasePrice = batchSelect.length ? (batchSelect.find('option:selected').data('purchase-price') || '') : '';

            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="${variantId}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][purchase_item_id]" value="${purchaseItemId}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][purchase_price]" value="${purchasePrice}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][pair_type]" value="${row.find('.pair-type-input').val() || 'single'}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][custom_size_value]" value="${row.find('.custom-size-value-input').val() || ''}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][mrp]" value="${mrp}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][quantity]" value="${qty}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][price]" value="${price}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][discount_type]" value="${discountType}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][discount_value]" value="${discountValue}">`);
            submitIdx++;
        });

        const submitBtnPrint = $('#submitBtnPrint');
        const submitBtnNoPrint = $('#submitBtnNoPrint');
        submitBtnPrint.prop('disabled', true);
        submitBtnNoPrint.prop('disabled', true);
        (printAfterSave ? submitBtnPrint : submitBtnNoPrint)
            .html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url     : form.attr('action'),
            type    : 'POST',
            data    : form.serialize(),
            success : function (res) {
                visibleInputs.prop('disabled', false);
                hiddenContainer.remove();
                if (res.status === 'success') {
                    toastr.success(res.message);
                    if (printAfterSave && res.id) {
                        const printUrl = thermalUrlTemplate.replace('__ID__', res.id);
                        if (printWindowRef) {
                            printWindowRef.location.href = printUrl;
                        } else {
                            window.open(printUrl, '_blank');
                        }
                        window.location.href = '{{ route('admin.sales.index') }}';
                    } else {
                        setTimeout(() => window.location.href = '{{ route('admin.sales.index') }}', 800);
                    }
                }
            },
            error   : function (xhr) {
                visibleInputs.prop('disabled', false);
                hiddenContainer.remove();
                submitBtnPrint.prop('disabled', false).html('<i class="ti ti-printer me-1"></i> Save with Print');
                submitBtnNoPrint.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save without Print');
                closePendingPrintTab();

                const responseJSON = xhr.responseJSON;
                const errors = responseJSON?.errors || responseJSON?.message;

                if (xhr.status === 422 && errors && typeof errors === 'object') {
                    let shownToastr = false;
                    $.each(errors, function (field, messages) {
                        if (field === 'items' || field.startsWith('items.')) {
                            if (!shownToastr) {
                                toastr.error(Array.isArray(messages) ? messages[0] : messages);
                                shownToastr = true;
                            }
                            return;
                        }
                        let input = form.find('[name="' + field + '"], [name="' + field + '[]"]');
                        if (input.length > 0) {
                            input.addClass('is-invalid');
                            if (input.hasClass('select2-hidden-accessible')) {
                                input.next('.select2-container').find('.select2-selection').css('border-color', '#ea5455');
                            }
                            let container = input.closest('.input-group');
                            if (container.length > 0) {
                                container.siblings('.invalid-feedback').text(messages[0]).show();
                            } else {
                                input.siblings('.invalid-feedback').text(messages[0]).show();
                            }
                        }
                    });
                    if (!shownToastr && Object.keys(errors).length === 0) {
                        toastr.error('Something went wrong. Please try again.');
                    }
                } else {
                    const errorMsg = typeof errors === 'string' 
                        ? errors 
                        : (typeof responseJSON?.message === 'string' ? responseJSON.message : 'Something went wrong. Please try again.');
                    toastr.error(errorMsg);
                }
            }
        });
    });

});
</script>
@endsection
