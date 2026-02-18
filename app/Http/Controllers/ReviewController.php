<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}


    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['is_published', 'per_page', 'product_id']);
        $reviews = $this->reviewService->getReviews($filters);
        $data = ReviewResource::collection($reviews);
        return $this->successResponse($data, '');
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        if ($this->reviewService->hasUserReviewedProduct(
            $request->user()->id,
            $request->product_id
        )) {
            return $this->errorResponse('You have already reviewed this product.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $review = $this->reviewService->createReview($request->validated());
        return $this->successResponse(new ReviewResource($review), 'Review created successfully.', Response::HTTP_CREATED);
    }

    public function show(Review $review): JsonResponse
    {
        $review = $this->reviewService->getReview($review);
        return $this->successResponse(new ReviewResource($review), 'Review created successfully.', Response::HTTP_OK);
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $review = $this->reviewService->updateReview($review, $request->validated());
        return $this->successResponse(new ReviewResource($review), 'Review updated successfully.', Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        if (!$request->user()->hasRole([RoleEnum::ADMIN->value])) {
            return $this->errorResponse('Unauthorized to delete this review.', Response::HTTP_UNAUTHORIZED);
        }

        $this->reviewService->deleteReview($review);
        return $this->successResponse(null, 'Review deleted successfully.', Response::HTTP_NO_CONTENT);
    }
}
