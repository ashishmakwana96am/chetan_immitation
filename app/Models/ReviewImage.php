<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewImage extends Model
{
    protected $fillable = [
        'product_review_id',
        'image_path',
    ];

    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }

    public function getImageUrlAttribute(): string
    {
        return asset('uploads/'.$this->image_path);
    }
}
