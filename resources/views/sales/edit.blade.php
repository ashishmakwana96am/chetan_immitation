@extends('layouts.app')

@section('title', 'Edit Sale')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
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
        <div class="row g-4">

            <!-- Main -->
            <div class="col-lg-8">

                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sale Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sale No</label>
                                <input type="text" class="form-control" value="{{ $order->order_no }}" disabled />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                @if(auth()->user()->location_id)
                                    <input type="hidden" name="location_id" value="{{ auth()->user()->location_id }}" />
                                    <input type="text" class="form-control" value="{{ $locations->firstWhere('id', auth()->user()->location_id)?->name ?? '-' }}" disabled />
                                @else
                                    <select name="location_id" class="form-select" id="locationSelect">
                                        <option value="">-- Select Location --</option>
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
                                <label class="form-label">Customer <span class="text-muted">(optional)</span></label>
                                <div class="input-group">
                                    <select name="customer_id" class="form-select" id="customerSelect">
                                        <option value="">-- Walk-in Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ $order->customer_id === $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-label-primary"
                                        data-common-modal="{{ route('admin.customers.create') }}"
                                        title="Add Customer">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select">
                                    <option value="cash"   {{ $order->payment_method === 'cash'   ? 'selected' : '' }}>Cash</option>
                                    <option value="card"   {{ $order->payment_method === 'card'   ? 'selected' : '' }}>Card</option>
                                    <option value="online" {{ $order->payment_method === 'online' ? 'selected' : '' }}>Online</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sale Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                            <i class="ti ti-plus me-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th width="100">Qty</th>
                                        <th width="130">Price</th>
                                        <th width="130">Discount</th>
                                        <th width="130">Total</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-semibold">Items Total</td>
                                        <td class="fw-bold text-primary" id="itemsTotal">{{ format_price($order->total_amount) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="noItemsMsg" class="text-center text-muted py-4 d-none">No items added yet.</div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sale Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Items Total</span>
                            <span id="summaryItemsTotal" class="fw-semibold">{{ format_price($order->total_amount) }}</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sale Discount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="discount" id="orderDiscount"
                                    class="form-control" placeholder="0.00" step="0.01" min="0"
                                    value="{{ $order->discount }}" />
                            </div>
                        </div>
                        <hr />
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">Final Amount</span>
                            <span id="summaryFinal" class="fw-bold text-primary fs-5">{{ format_price($order->final_amount) }}</span>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-device-floppy me-1"></i> Update Sale
                    </button>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary">Cancel</a>
                </div>
            </div>

        </div>
    </form>

    <!-- Item Row Template -->
    <template id="itemRowTemplate">
        <tr class="item-row" data-index="__INDEX__">
            <td>
                <select name="items[__INDEX__][product_id]" class="form-select form-select-sm product-select">
                    <option value="">-- Select Product --</option>
                </select>
                <div class="invalid-feedback"></div>
                <small class="text-muted stock-info-__INDEX__"></small>
            </td>
            <td>
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control form-control-sm item-qty"
                    placeholder="1" min="1" value="1" />
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currency_symbol() }}</span>
                    <input type="number" name="items[__INDEX__][price]"
                        class="form-control form-control-sm item-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currency_symbol() }}</span>
                    <input type="number" name="items[__INDEX__][discount]"
                        class="form-control form-control-sm item-discount"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td>
                <span class="item-total fw-semibold text-nowrap">{{ currency_symbol() }} 0.00</span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-icon btn-label-danger remove-item-btn">
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
    const allProducts = @json($allProducts);
    const existingItems = @json($existingItems);

    window.refreshTable = function () {
        $.get('{{ route('admin.customers.data') }}', function (res) {
            const select  = $('#customerSelect');
            const current = select.val();
            select.find('option:not(:first)').remove();
            res.data.forEach(function (c) {
                select.append($('<option>', { value: c.id, text: c.name + (c.phone !== '-' ? ' - ' + c.phone : '') }));
            });
            select.val(current);
        });
    };

    existingItems.forEach(item => addItemRow(item));

    function addItemRow(data = null) {
        const template = document.getElementById('itemRowTemplate').innerHTML
            .replaceAll('__INDEX__', itemIndex);
        $('#itemsBody').append(template);
        $('#noItemsMsg').addClass('d-none');

        syncProductDropdowns();

        const row = $('#itemsBody .item-row').last();

        if (data) {
            row.find('.product-select').val(data.product_id);
            row.find('.item-price').val(data.price);
            row.find('.item-qty').val(data.quantity);
            row.find('.item-discount').val(data.discount ?? 0);
            updateRowTotal(row);
            updateStockInfo(row);
        }

        itemIndex++;
        updateSummary();
    }

    $('#addItemBtn').on('click', () => addItemRow());

    $(document).on('click', '.remove-item-btn', function () {
        $(this).closest('.item-row').remove();
        if ($('#itemsBody .item-row').length === 0) $('#noItemsMsg').removeClass('d-none');
        syncProductDropdowns();
        updateSummary();
    });

    $(document).on('change', '.product-select', function () {
        const row       = $(this).closest('.item-row');
        const productId = $(this).val();
        const product   = allProducts.find(p => p.id == productId);
        if (product) row.find('.item-price').val(product.price);
        updateRowTotal(row);
        syncProductDropdowns();
        updateStockInfo(row);
    });

    function getLocationId() {
        return $('#locationSelect').val() || $('input[name="location_id"]').val() || '';
    }

    $(document).on('change', '#locationSelect', function () {
        $('#itemsBody .item-row').each(function () { updateStockInfo($(this)); });
    });

    function updateStockInfo(row) {
        const productId  = row.find('.product-select').val();
        const locationId = getLocationId();
        const idx        = row.data('index');
        if (!productId || !locationId) { $('.stock-info-' + idx).text(''); return; }
        $.get('{{ url('admin') }}/inventory/stock', { product_id: productId, location_id: locationId })
            .done(function (res) {
                const qty = res.data?.quantity ?? 0;
                $('.stock-info-' + idx).text('Stock: ' + qty)
                    .removeClass('text-success text-danger')
                    .addClass(qty > 0 ? 'text-success' : 'text-danger');
            });
    }

    function syncProductDropdowns() {
        const selectedMap = {};
        $('.product-select').each(function () {
            const idx = $(this).closest('.item-row').data('index');
            const val = $(this).val();
            if (val) selectedMap[idx] = val;
        });
        const allSelected = Object.values(selectedMap);
        $('.product-select').each(function () {
            const select = $(this), currentVal = select.val();
            select.empty().append('<option value="">-- Select Product --</option>');
            allProducts.forEach(function (p) {
                if (p.id == currentVal || !allSelected.includes(String(p.id))) {
                    select.append($('<option>', { value: p.id, text: p.label, selected: p.id == currentVal }));
                }
            });
        });
    }

    $(document).on('input', '.item-price, .item-qty, .item-discount', function () {
        updateRowTotal($(this).closest('.item-row'));
    });

    $(document).on('input', '#orderDiscount', updateSummary);

    function updateRowTotal(row) {
        const price    = parseFloat(row.find('.item-price').val()) || 0;
        const qty      = parseInt(row.find('.item-qty').val()) || 0;
        const discount = parseFloat(row.find('.item-discount').val()) || 0;
        row.find('.item-total').text(symbol + ' ' + ((price * qty) - discount).toFixed(2));
        updateSummary();
    }

    function updateSummary() {
        let itemsTotal = 0;
        $('#itemsBody .item-row').each(function () {
            itemsTotal += (parseFloat($(this).find('.item-price').val()) || 0)
                        * (parseInt($(this).find('.item-qty').val()) || 0)
                        - (parseFloat($(this).find('.item-discount').val()) || 0);
        });
        const orderDiscount = parseFloat($('#orderDiscount').val()) || 0;
        $('#itemsTotal, #summaryItemsTotal').text(symbol + ' ' + itemsTotal.toFixed(2));
        $('#summaryFinal').text(symbol + ' ' + (itemsTotal - orderDiscount).toFixed(2));
    }

    $('#orderForm').on('submit', function (e) {
        e.preventDefault();
        if ($('#itemsBody .item-row').length === 0) { toastr.error('Please add at least one item.'); return; }
        const form = $(this);
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        $.ajax({
            url     : form.attr('action'),
            type    : 'POST',
            data    : form.serialize(),
            success : function (res) {
                if (res.status === 'success') {
                    toastr.success(res.message);
                    setTimeout(() => window.location.href = '{{ route('admin.sales.index') }}', 800);
                }
            },
            error   : function (xhr) {
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Update Sale');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.message || {};
                    $.each(errors, function (field, messages) { toastr.error(messages[0]); });
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            }
        });
    });

});
</script>
@endsection
