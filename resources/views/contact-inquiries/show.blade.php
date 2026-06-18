@extends('layouts.app')

@section('title', 'View Contact Inquiry')

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Contact Inquiry Details</h5>
        <a href="{{ route('admin.contact-inquiries.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i> Back to List
        </a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Contact Information</h6>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">Full Name</th>
                            <td>{{ $contactInquiry->full_name }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Email</th>
                            <td><a href="mailto:{{ $contactInquiry->email }}">{{ $contactInquiry->email }}</a></td>
                        </tr>
                        <tr>
                            <th scope="row">Phone</th>
                            <td><a href="tel:{{ $contactInquiry->phone }}">{{ $contactInquiry->phone }}</a></td>
                        </tr>
                        <tr>
                            <th scope="row">Subject</th>
                            <td>{{ $contactInquiry->subject }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Meta Information</h6>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">Submitted At</th>
                            <td>{{ $contactInquiry->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Email Status</th>
                            <td>
                                @if($contactInquiry->emailed_at)
                                    <span class="badge bg-label-success">Sent</span>
                                    <br>
                                    <small class="text-muted">Sent at: {{ $contactInquiry->emailed_at->format('d M Y, h:i A') }}</small>
                                @else
                                    <span class="badge bg-label-warning">Pending</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ID</th>
                            <td>{{ $contactInquiry->id }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <h6 class="text-muted mb-3">Message</h6>
                <div class="p-4 bg-light rounded border">
                    <p class="mb-0 whitespace-pre-wrap">{{ $contactInquiry->message }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection