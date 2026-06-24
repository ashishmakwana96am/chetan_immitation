<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index()
    {
        $this->authorize('view product reviews');
        return view('product-reviews.index');
    }

    public function data()
    {
        $this->authorize('view product reviews');

        $reviews   = ProductReview::with(['product', 'customer'])->orderBy('id', 'desc')->get();
        $canDelete = auth()->user()->can('delete product reviews');

        $data = $reviews->map(function ($review, $index) use ($canDelete) {

            // Stars HTML (filled ★ + empty ☆)
            $rating  = (float) $review->rating;
            $full    = (int) floor($rating);
            $half    = ($rating - $full) >= 0.5 ? 1 : 0;
            $empty   = 5 - $full - $half;
            $stars   = str_repeat('<i class="ti ti-star-filled text-warning" style="font-size:0.85rem;"></i>', $full)
                     . ($half ? '<i class="ti ti-star-half-filled text-warning" style="font-size:0.85rem;"></i>' : '')
                     . str_repeat('<i class="ti ti-star text-muted" style="font-size:0.85rem;"></i>', $empty);
            $starsHtml = '<span class="d-flex align-items-center gap-1">' . $stars . ' <small class="text-muted ms-1">(' . number_format($rating, 1) . ')</small></span>';

            $actions = '';
            if ($canDelete) {
                $actions = '<div class="dropdown table-action-dropdown">'
                    . '<button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span>Actions</span></button>'
                    . '<div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">'
                    . '<button class="dropdown-item text-danger" data-common-delete="' . route('admin.product-reviews.destroy', $review) . '" data-row-id="review-row-' . $review->id . '"><i class="ti ti-trash me-2"></i>Delete</button>'
                    . '</div></div>';
            }

            return [
                'index'      => $index + 1,
                'product'    => '<span class="fw-semibold">' . e($review->product->name ?? '-') . '</span>'
                              . ($review->product?->sku ? '<br><small class="text-muted">' . e($review->product->sku) . '</small>' : ''),
                'customer'   => e($review->customer->name ?? '-'),
                'rating'     => $starsHtml,
                'comment'    => $review->comment
                    ? '<span class="text-truncate d-inline-block" style="max-width:300px;" title="' . e($review->comment) . '">' . e($review->comment) . '</span>'
                    : '<span class="text-muted">-</span>',
                'created_at' => format_date($review->created_at),
                'actions'    => $actions,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function destroy(ProductReview $productReview)
    {
        $this->authorize('delete product reviews');

        $productReview->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Review deleted successfully.',
        ]);
    }
}
