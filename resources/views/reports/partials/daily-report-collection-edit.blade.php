<div class="text-center mb-4">
    <h3 class="mb-2">Edit Payment Collection</h3>
    <p class="text-muted">Update collection details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.reports.daily-report.collections.update', $salePayment) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="edit_payment_method">Payment Method <span class="text-danger">*</span></label>
            <select class="form-select no-select2" id="edit_payment_method" name="payment_method" required>
                <option value="cash" {{ $salePayment->payment_method === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="online" {{ $salePayment->payment_method === 'online' ? 'selected' : '' }}>Online</option>
                <option value="online_cash" {{ $salePayment->payment_method === 'online_cash' ? 'selected' : '' }}>Cash + Online</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12" id="editCashCol">
            <label class="form-label" for="edit_cash_amount">Cash Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" step="0.01" min="0" max="{{ $maxAllowed }}" class="form-control" id="edit_cash_amount" name="cash_amount" value="{{ (float) $salePayment->cash_amount > 0 ? $salePayment->cash_amount : '' }}" placeholder="0.00" />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12" id="editOnlineCol">
            <label class="form-label" for="edit_online_amount">Online Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" step="0.01" min="0" max="{{ $maxAllowed }}" class="form-control" id="edit_online_amount" name="online_amount" value="{{ (float) $salePayment->online_amount > 0 ? $salePayment->online_amount : '' }}" placeholder="0.00" />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="edit_notes">Notes / Reference</label>
            <textarea class="form-control" id="edit_notes" name="notes" rows="3" placeholder="Cheque no, UPI ref, etc...">{{ $salePayment->notes }}</textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
    (function () {
        const maxAllowed = {{ (float) $maxAllowed }};
        const maxCents = Math.round(maxAllowed * 100);
        const $form = $('#commonModalForm');
        const $method = $form.find('#edit_payment_method');
        const $cash = $form.find('#edit_cash_amount');
        const $online = $form.find('#edit_online_amount');

        function validateAmounts() {
            const cashCents = $cash.prop('disabled') ? 0 : Math.round((Number($cash.val()) || 0) * 100);
            const onlineCents = $online.prop('disabled') ? 0 : Math.round((Number($online.val()) || 0) * 100);
            const totalCents = cashCents + onlineCents;
            let message = '';

            if (totalCents > maxCents) {
                message = @json('Total collected amount cannot exceed ' . format_price($maxAllowed) . '.');
            } else if (totalCents <= 0) {
                message = 'Total collected amount must be greater than 0.';
            }

            $cash[0].setCustomValidity('');
            $online[0].setCustomValidity('');
            const activeInput = $cash.prop('disabled') ? $online[0] : $cash[0];
            activeInput.setCustomValidity(message);
            return message === '';
        }

        function syncMethodFields() {
            const method = $method.val();
            $form.find('#editCashCol').toggle(method !== 'online');
            $form.find('#editOnlineCol').toggle(method !== 'cash');
            $cash.prop('disabled', method === 'online');
            $online.prop('disabled', method === 'cash');
            validateAmounts();
        }

        $method.on('change', syncMethodFields);
        $cash.add($online).on('input', validateAmounts);
        $form.on('submit', function (event) {
            if (!validateAmounts()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                this.reportValidity();
            }
        });

        syncMethodFields();
    })();
</script>
