<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogController extends Controller
{
    public function __construct(
        protected BlogService $blogService
    ) {}

    /**
     * Get all blogs (Admin only - with filters)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'category' => $request->input('category'),
            'is_featured' => $request->input('is_featured'),
            'search' => $request->input('search'),
            'per_page' => $request->input('per_page', 10),
        ];

        $blogs = $this->blogService->getAllBlogs($filters);
        return $this->paginatedResponse(BlogResource::collection($blogs), '');
    }

    /**
     * Get published blogs (Public - for blog page)
     */
    public function published(Request $request): JsonResponse
    {
        $filters = [
            'category' => $request->input('category'),
            'search' => $request->input('search'),
            'per_page' => $request->input('per_page', 10),
        ];

        $blogs = $this->blogService->getPublishedBlogs($filters);
        return $this->paginatedResponse(BlogResource::collection($blogs), '');
    }

    /**
     * Get featured blogs (Public - for landing page, limited to 4)
     */
    public function featured(): JsonResponse
    {
        $blogs = $this->blogService->getFeaturedBlogs();

        return $this->successResponse(
            BlogResource::collection($blogs)
        );
    }

    /**
     * Get a single blog by slug (Public & Admin)
     * Route model binding automatically resolves by slug
     */
    public function show(Blog $blog): JsonResponse
    {
        if ($blog->status === 'published') {
            $blog->incrementViews();
        }

        $blog->load('author');

        return $this->successResponse(
            new BlogResource($blog)
        );
    }

    /**
     * Create a new blog (Admin only)
     */
    public function store(StoreBlogRequest $request): JsonResponse
    {
        $blog = $this->blogService->createBlog(
            $request->validated(),
            $request->user()->id
        );

        return $this->successResponse(
            new BlogResource($blog->load('author')),
            'Blog created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * Update an existing blog (Admin only)
     */
    public function update(UpdateBlogRequest $request, Blog $blog): JsonResponse
    {
        $updatedBlog = $this->blogService->updateBlog($blog, $request->validated());

        return $this->successResponse(
            new BlogResource($updatedBlog),
            'Blog updated successfully.'
        );
    }

    /**
     * Delete a blog (Admin only)
     */
    public function destroy(Blog $blog): JsonResponse
    {
        $this->blogService->deleteBlog($blog);

        return $this->successResponse(
            null,
            'Blog deleted successfully.'
        );
    }
}
