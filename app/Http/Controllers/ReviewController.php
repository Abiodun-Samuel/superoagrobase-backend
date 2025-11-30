<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}


    public function index(Request $request): JsonResponse
    {
        $reviews = $this->reviewService->build($request);
        $data = ReviewResource::collection($reviews);
        return $this->successResponse($data, '');
    }
}
