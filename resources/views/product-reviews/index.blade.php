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

    <!-- View Review Modal -->
    <div class="modal fade" id="viewReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-semibold">Review Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-start">
                        <label class="fw-semibold text-muted d-block small mb-1">Product</label>
                        <span id="modal-review-product" class="fw-bold text-heading" style="font-size: 1.05rem;"></span>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="fw-semibold text-muted d-block small mb-1">User Name</label>
                        <span id="modal-review-customer" class="text-body fw-medium"></span>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="fw-semibold text-muted d-block small mb-1">Rating</label>
                        <div id="modal-review-rating" class="d-flex align-items-center gap-1"></div>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="fw-semibold text-muted d-block small mb-1">Review</label>
                        <div id="modal-review-comment" class="bg-light p-3 rounded text-body" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto; border: 1px solid #ebedf2;"></div>
                    </div>
                    <div class="mb-0 text-start">
                        <label class="fw-semibold text-muted d-block small mb-1">Date</label>
                        <span id="modal-review-date" class="text-body"></span>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 justify-content-center">
                    <button type="button" class="btn text-white px-4" data-bs-dismiss="modal" style="background-color: #B4771E; border-color: #B4771E;">Close</button>
                </div>
            </div>
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
                const product = $(this).attr('data-product');
                const customer = $(this).attr('data-customer');
                const rating = parseFloat($(this).attr('data-rating') || 0);
                const comment = $(this).attr('data-comment');
                const date = $(this).attr('data-date');

                // Generate Stars HTML
                const full = Math.floor(rating);
                const half = (rating - full) >= 0.5 ? 1 : 0;
                const empty = 5 - full - half;

                let stars = '';
                for (let i = 0; i < full; i++) {
                    stars += '<i class="fa-solid fa-star text-warning" style="font-size:1.15rem;"></i>';
                }
                if (half) {
                    stars += '<i class="fa-solid fa-star-half-stroke text-warning" style="font-size:1.15rem;"></i>';
                }
                for (let i = 0; i < empty; i++) {
                    stars += '<i class="fa-regular fa-star text-warning" style="font-size:1.15rem;"></i>';
                }
                const starsHtml = `${stars} <small class="text-muted ms-1">(${rating.toFixed(1)})</small>`;

                // Populate modal
                $('#modal-review-product').text(product);
                $('#modal-review-customer').text(customer);
                $('#modal-review-rating').html(starsHtml);
                $('#modal-review-comment').text(comment);
                $('#modal-review-date').text(date);

                // Show Bootstrap modal
                const myModal = new bootstrap.Modal(document.getElementById('viewReviewModal'));
                myModal.show();
            });
        });
    </script>
@endsection
