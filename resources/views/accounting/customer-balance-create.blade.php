<div class="text-center mb-4">
    <h3 class="mb-2">Add Credit Balance</h3>
    <p class="text-muted">Credit or debit a customer's balance</p>
</div>

<form id="commonModalForm" action="{{ route('admin.accounting.customer-balance.store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="customerBalanceCustomer">Customer <span class="text-danger">*</span></label>
            <select id="customerBalanceCustomer" name="customer_id" class="form-select">
                <option value="">Select Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <input type="hidden" name="source" value="{{ $source }}" />

        <div class="col-12">
            <label class="form-label" for="customerBalanceType">Type <span class="text-danger">*</span></label>
            <select id="customerBalanceType" name="type" class="form-select no-select2">
                <option value="">Select Type</option>
                <option value="credit">Credit</option>
                <option value="debit">Debit</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="customerBalanceAmount">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" id="customerBalanceAmount" name="amount"
                    class="form-control" placeholder="0.00" step="0.01" min="0.01" autofocus />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="customerBalanceNotes">Notes</label>
            <textarea id="customerBalanceNotes" name="notes" class="form-control" rows="3" placeholder="Optional note"></textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Save Entry</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
