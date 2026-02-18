<?php

namespace App\Services;

use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function getReviews(array $filters = [])
    {
        $query = Review::query()->with(['user', 'product'])->select('reviews.*');

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['is_published'])) {
            $query->where('is_published', filter_var($filters['is_published'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['per_page'])) {
            return $query->paginate($filters['per_page'] ?? 20);
        }

        return $query->latest()->get();
    }

    public function createReview(array $data): Review
    {
        return DB::transaction(function () use ($data) {
            $review = Review::create($data);
            return $review->load(['user', 'product']);
        });
    }

    public function updateReview(Review $review, array $data): Review
    {
        return DB::transaction(function () use ($review, $data) {
            $review->update($data);
            return $review->fresh(['user', 'product']);
        });
    }

    public function deleteReview(Review $review): bool
    {
        return DB::transaction(function () use ($review) {
            $deleted = $review->delete();
            return $deleted;
        });
    }

    public function getReview(Review $review): Review
    {
        return $review->load(['user', 'product']);
    }

    public function hasUserReviewedProduct(int $userId, int $productId): bool
    {
        return Review::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }
}
