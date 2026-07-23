@extends('layouts.app')

@section('title', 'Due Bills History')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Due Bills History</h4>
            <small class="text-muted">
                <span class="fw-semibold text-danger">{{ $fromLocation->name }}</span>
                <i class="ti ti-arrow-right mx-1"></i>
                <span class="fw-semibold text-success">{{ $toLocation->name }}</span>
                &mdash; pending purchase bills (accepted, payment not yet marked Paid)
            </small>
        </div>
        <a href="{{ route('admin.ledgers.branch') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back to Branch Ledger
        </a>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bill No</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills as $index => $bill)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><code>{{ $bill->transfer_no }}</code></td>
                            <td>{{ format_date($bill->accepted_at) }}</td>
                            <td class="text-end">
                                <span class="fw-semibold">{{ format_price($bill->computed_amount) }}</span>
                                <span class="text-muted small">({{ $bill->computed_qty_text }})</span>
                            </td>
                            <td>
                                <div class="dropdown table-action-dropdown">
                                    <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                        <span>Actions</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                        <a href="{{ route('admin.purchase-bills.show', $bill->id) }}" class="dropdown-item">
                                            <i class="ti ti-eye me-2"></i>View
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No pending bills between these branches.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($bills->count())
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">{{ format_price($total) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
