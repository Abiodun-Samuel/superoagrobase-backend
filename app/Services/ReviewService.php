<?php

namespace App\Services;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewService
{
    public function build(Request $request)
    {
        $query = Review::query()->with(['user', 'product']);

        if ($request->filled('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        if ($request->filled('per_page')) {
            return $query->paginate($request->integer('per_page'));
        }

        return $query->latest()->get();
    }
}
