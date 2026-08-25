@extends('layouts.app')

@section('title', 'Utility Report Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-semibold mb-0">Utility Report Details</h4>
    <a href="{{ route('admin.reports.utility') }}" class="btn btn-label-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Utility Report
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="px-2 py-1">
            @include('utility-reports.partials.show-content')
        </div>
    </div>
</div>
@endsection
