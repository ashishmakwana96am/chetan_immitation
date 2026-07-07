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
                        <th>Photo</th>
                        <th>Date</th>
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
                ajax       : {
                    url: '{{ route('admin.product-reviews.data') }}',
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.rating = $('#filter-rating').val();
                    }
                },
                columns    : [
                    { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'product' },
                    { data: 'customer' },
                    { data: 'rating',     orderable: false },
                    { data: 'comment',    orderable: false },
                    { data: 'photo',      orderable: false },
                    { data: 'created_at' }
                ],
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            // Toggle show more/show less review comments
            $(document).on('click', '.toggle-review', function (e) {
                e.preventDefault();
                const link = $(this);
                const moreSpan = link.siblings('.more-text');
                
                if (moreSpan.hasClass('d-none')) {
                    moreSpan.removeClass('d-none');
                    link.text('Show Less');
                } else {
                    moreSpan.addClass('d-none');
                    link.text('Show More');
                }
            });
        });
    </script>
@endsection
