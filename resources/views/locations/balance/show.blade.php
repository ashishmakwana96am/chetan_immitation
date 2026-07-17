@extends('layouts.app')

@section('title', $location->name . ' - Balance')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">{{ $location->name }} - Balance</h4>
            <small class="text-muted">Cash &amp; bank balance ledger for this branch</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('admin.locations.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Locations
            </a>
            <button class="btn btn-primary" data-common-modal="{{ route('admin.locations.balance.create', $location) }}">
                <i class="ti ti-plus me-1"></i> Add Entry
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Cash Balance</p>
                        <h4 class="mb-0" id="cashBalanceValue">{{ format_price($location->cash_balance) }}</h4>
                    </div>
                    <div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                        <i class="ti ti-cash fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Bank Balance</p>
                        <h4 class="mb-0" id="bankBalanceValue">{{ format_price($location->bank_balance) }}</h4>
                    </div>
                    <div class="rounded-circle bg-label-info d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                        <i class="ti ti-building-bank fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="balanceTransactionsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Balance Type</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Notes</th>
                        <th>Created By</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            const table = $('#balanceTransactionsTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : {
                    url     : '{{ route('admin.locations.balance.data', $location) }}',
                    dataSrc : 'data',
                    cache   : false,
                },
                columns    : [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'created_at' },
                    { data: 'balance_type', orderable: false },
                    { data: 'type', orderable: false },
                    { data: 'amount' },
                    { data: 'balance_after' },
                    { data: 'notes' },
                    { data: 'created_by' },
                ],
            });

            window.refreshTable = function (res) {
                table.ajax.reload(null, false);
                if (res && res.cash_balance) {
                    $('#cashBalanceValue').text(res.cash_balance);
                }
                if (res && res.bank_balance) {
                    $('#bankBalanceValue').text(res.bank_balance);
                }
            };
        });
    </script>
@endsection
