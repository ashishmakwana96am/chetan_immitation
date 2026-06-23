<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'   => ['required', 'integer', 'exists:orders,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating'     => ['required', 'numeric', 'min:0.5', 'max:5'],
            'comment'    => ['nullable', 'string', 'max:1000'],
        ]);

        $rating = round((float) $validated['rating'], 1);
        if (abs(($rating * 2) - round($rating * 2)) > 0.001) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Rating must be in half-star steps (0.5, 1, 1.5, ... 5).',
            ], 422);
        }

        $customer = Auth::guard('customer')->user();

        $order = Order::where('id', $request->order_id)
            ->where('customer_id', $customer->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->firstOrFail();

        $productInOrder = $order->items()->where('product_id', $request->product_id)->exists();
        if (!$productInOrder) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This product is not part of the selected order.',
            ], 422);
        }

        $existing = ProductReview::where('customer_id', $customer->id)
            ->where('product_id', $request->product_id)
            ->where('order_id', $order->id)
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You have already reviewed this product for this order.',
            ], 422);
        }

        $review = ProductReview::create([
            'customer_id' => $customer->id,
            'product_id'  => $request->product_id,
            'order_id'    => $order->id,
            'rating'      => $rating,
            'comment'     => trim((string) $request->comment) ?: null,
        ]);

        $review->load('customer');

        return response()->json([
            'status'  => 'success',
            'message' => 'Thank you! Your review has been submitted.',
            'review'  => [
                'rating'       => (float) $review->rating,
                'comment'      => $review->comment,
                'created_at'   => $review->created_at->format('l, F j, Y'),
                'author_name'  => $review->customer->display_name ?: $review->customer->name,
                'author_avatar'=> $review->customer->avatar
                    ? asset($review->customer->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode($review->customer->display_name ?: $review->customer->name) . '&background=B4771E&color=fff&size=120&bold=true',
            ],
        ]);
    }
}
