<div class="text-center mb-4">
    <h3 class="mb-2">Add Balance Entry</h3>
    <p class="text-muted">Credit or debit the cash / bank balance for {{ $location->name }}</p>
</div>

<form id="commonModalForm" action="{{ route('admin.locations.balance.store', $location) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="balanceType">Balance Type <span class="text-danger">*</span></label>
            <select id="balanceType" name="balance_type" class="form-select no-select2">
                <option value="">Select Balance Type</option>
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="entryType">Entry Type <span class="text-danger">*</span></label>
            <select id="entryType" name="type" class="form-select no-select2">
                <option value="">Select Entry Type</option>
                <option value="credit">Credit (Add)</option>
                <option value="debit">Debit (Deduct)</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="entryAmount">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" id="entryAmount" name="amount"
                    class="form-control" placeholder="0.00" step="0.01" min="0.01" autofocus />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="entryNotes">Notes</label>
            <textarea id="entryNotes" name="notes" class="form-control" rows="3" placeholder="Optional note"></textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Save Entry</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
