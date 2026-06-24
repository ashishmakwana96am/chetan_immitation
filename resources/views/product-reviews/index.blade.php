@extends('layouts.app')

@section('title', 'Product Reviews')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Product Reviews</h4>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="reviewsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>User Name</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Actions</th>
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
            const table = $('#reviewsTable').DataTable({
                responsive : false,
                order      : [],
                ajax       : { url: '{{ route('admin.product-reviews.data') }}', dataSrc: 'data' },
                columns    : [
                    { data: 'index',      width: '5%' },
                    { data: 'product' },
                    { data: 'customer' },
                    { data: 'rating',     orderable: false },
                    { data: 'comment',    orderable: false },
                    { data: 'created_at' },
                    { data: 'actions',    orderable: false }
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '.view-review-btn', function (e) {
                e.preventDefault();
                const product = $(this).data('product');
                const customer = $(this).data('customer');
                const ratingHtml = $(this).data('rating');
                const comment = $(this).data('comment');
                const date = $(this).data('date');

                Swal.fire({
                    title: 'Review Details',
                    html: `
                        <div class="text-start">
                            <div class="mb-3">
                                <label class="fw-semibold text-muted d-block small mb-1">Product</label>
                                <span class="fw-bold">${product}</span>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold text-muted d-block small mb-1">User Name</label>
                                <span>${customer}</span>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold text-muted d-block small mb-1">Rating</label>
                                <div>${ratingHtml}</div>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold text-muted d-block small mb-1">Review</label>
                                <div class="bg-light p-3 rounded" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto; border: 1px solid #ebedf2;">${comment}</div>
                            </div>
                            <div class="mb-0">
                                <label class="fw-semibold text-muted d-block small mb-1">Date</label>
                                <span>${date}</span>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Close',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            });
        });
    </script>
@endsection
