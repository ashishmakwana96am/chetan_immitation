<div class="text-center mb-4">
    <h3 class="mb-2">Add New Expense</h3>
    <p class="text-muted">Fill in the details to record a new expense</p>
</div>

<form id="commonModalForm" action="{{ route('admin.expenses.store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="expenseTitle">Title <span class="text-danger">*</span></label>
            <input type="text" id="expenseTitle" name="title"
                class="form-control" placeholder="Enter Expense Title" autofocus />
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="expenseCategory">Category <span class="text-danger">*</span></label>
            <select id="expenseCategory" name="category" class="form-select">
                <option value="">Select Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="expenseAmount">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" id="expenseAmount" name="amount"
                    class="form-control" placeholder="0.00" step="0.01" min="0.01" />
            </div>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="expenseDate">Expense Date <span class="text-danger">*</span></label>
            <input type="text" id="expenseDate" name="expense_date" class="form-control flatpickr" value="{{ date('Y-m-d') }}" data-min-date="{{ can_modify_past_date_record() ? '' : date('Y-m-d') }}" data-max-date="today" placeholder="DD-MM-YYYY" readonly />
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="expensePaymentMethod">Payment Method <span class="text-danger">*</span></label>
            <select id="expensePaymentMethod" name="payment_method" class="form-select">
                <option value="">Select Payment Method</option>
                @foreach($paymentMethods as $method)
                    <option value="{{ $method }}">{{ $method }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label">Location <span class="text-danger">*</span></label>
            @if($isRestricted)
                <input type="text" class="form-control" value="{{ $locations->first()->name ?? '-' }}" disabled />
                <input type="hidden" name="location_id" value="{{ $locations->first()->id ?? '' }}" />
            @else
                <select id="expenseLocation" name="location_id" class="form-select">
                    <option value="">Select Location</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            @endif
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="expenseNotes">Notes</label>
            <textarea id="expenseNotes" name="notes" class="form-control" rows="2" placeholder="Optional note"></textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Create Expense</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
