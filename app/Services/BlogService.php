<?php

namespace App\Services;

use App\Models\Blog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogService
{
    protected MediaUploadService $uploadService;

    public function __construct(MediaUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    protected function handleImageUpload($imageFile): string
    {
        return $this->uploadService->upload($imageFile, 'superoagrobase/blogs');
    }

    public function getAllBlogs(array $filters = []): LengthAwarePaginator
    {
        $query = Blog::with('author:id,first_name,last_name,email,avatar')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filter by featured
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        // Search by title or content
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('excerpt', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->paginate($perPage);
    }

    /**
     * Get published blogs for public viewing
     */
    public function getPublishedBlogs(array $filters = []): LengthAwarePaginator
    {
        $query = Blog::with('author')
            ->published()
            ->orderBy('published_at', 'desc');

        // Filter by category
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('excerpt', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->paginate($perPage);
    }

    /**
     * Get featured blogs for landing page (limited to 4)
     */
    public function getFeaturedBlogs(): Collection
    {
        return Blog::with('author')
            ->published()
            ->featured()
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get();
    }

    /**
     * Create a new blog
     */
    public function createBlog(array $data, int $authorId): Blog
    {
        return DB::transaction(function () use ($data, $authorId) {
            // Ensure slug uniqueness
            if (isset($data['featured_image'])) {
                $data['featured_image'] = $this->handleImageUpload($data['featured_image'],);
            }
            $data['slug'] = $this->ensureUniqueSlug($data['title']);

            // Set author
            $data['author_id'] = $authorId;

            // Auto-set published_at if status is published and published_at is not set
            if ($data['status'] === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            return Blog::create($data);
        });
    }

    /**
     * Update an existing blog
     */
    public function updateBlog(Blog $blog, array $data): Blog
    {
        return DB::transaction(function () use ($blog, $data) {
            if (!empty($blog->featured_image)) {
                $this->uploadService->delete($blog->featured_image);
            }

            if (isset($data['featured_image'])) {
                $data['featured_image'] = $this->handleImageUpload($data['featured_image']);
            }
            // Generate slug if title changed and slug not provided
            if (isset($data['title']) && $data['title'] !== $blog->title && empty($data['slug'])) {
                $data['slug'] = $this->ensureUniqueSlug($data['title']);
            }

            // Auto-set published_at if status changed to published and published_at is not set
            if (isset($data['status']) && $data['status'] === 'published' && empty($blog->published_at) && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $blog->update($data);
            return $blog->fresh('author');
        });
    }

    /**
     * Delete a blog
     */
    public function deleteBlog(Blog $blog): bool
    {
        $this->uploadService->delete($blog->featured_image);
        return $blog->delete();
    }

    private function ensureUniqueSlug(string $title): string
    {
        return Str::slug($title) . '-' . now()->timestamp . '-' . Str::random(4);
    }
}
