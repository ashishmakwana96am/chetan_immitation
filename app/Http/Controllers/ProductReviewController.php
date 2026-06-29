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

    public function data(Request $request)
    {
        $this->authorize('view product reviews');

        $query = ProductReview::with(['product', 'customer'])->orderBy('id', 'desc');

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->get();

        $data = $reviews->map(function ($review, $index) {
            // Stars HTML (filled ★ + empty ☆)
            $rating  = (float) $review->rating;
            $full    = (int) floor($rating);
            $half    = ($rating - $full) >= 0.5 ? 1 : 0;
            $empty   = 5 - $full - $half;
            $stars   = str_repeat('<i class="fa-solid fa-star text-warning" style="font-size:0.85rem;"></i>', $full)
                     . ($half ? '<i class="fa-solid fa-star-half-stroke text-warning" style="font-size:0.85rem;"></i>' : '')
                     . str_repeat('<i class="fa-regular fa-star text-warning" style="font-size:0.85rem;"></i>', $empty);
            $starsHtml = '<span class="d-flex align-items-center gap-1">' . $stars . ' <small class="text-muted ms-1">(' . number_format($rating, 1) . ')</small></span>';

            $commentHtml = '<span class="text-muted">-</span>';
            if ($review->comment) {
                $commentText = e($review->comment);
                if (mb_strlen($commentText) > 80) {
                    $short = mb_substr($commentText, 0, 80);
                    $long = mb_substr($commentText, 80);
                    $commentHtml = '<span>' . $short . '</span>'
                        . '<span class="more-text d-none">' . $long . '</span>'
                        . '<a href="javascript:void(0)" class="toggle-review text-primary ms-1 fw-semibold" style="font-size: 0.8rem;">Show More</a>';
                } else {
                    $commentHtml = '<span>' . $commentText . '</span>';
                }
            }

            return [
                'index'      => $index + 1,
                'product'    => '<span class="fw-semibold">' . e($review->product->name ?? '-') . '</span>'
                              . ($review->product?->sku ? '<br><small class="text-muted">' . e($review->product->sku) . '</small>' : ''),
                'customer'   => e($review->customer->name ?? '-'),
                'rating'     => $starsHtml,
                'comment'    => $commentHtml,
                'created_at' => format_date($review->created_at),
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
