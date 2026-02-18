<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    protected MediaUploadService $uploadService;

    public function __construct(MediaUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function getAllCategories(array $filters = [])
    {
        $query = Category::withCount('products');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('title', 'LIKE', "%{$search}%");
        }

        if (!empty($filters['sort_by'])) {
            $sortBy = $filters['sort_by'];
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest();
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
    protected function handleImageUpload($avatarFile, string $folder): string
    {
        return $this->uploadService->upload($avatarFile, $folder);
    }

    public function createCategory(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $image = $this->handleImageUpload($data['image'], 'superoagrobase_products');
            $data['slug'] = $this->generateUniqueSlug($data['title']);

            return Category::create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'image' => $image ?? null,
            ]);
        });
    }

    public function updateCategory(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            if (isset($data['image']) && !empty($category->image)) {
                $this->uploadService->delete($category->image);
                $data['image'] = $this->handleImageUpload($data['image'], 'superoagrobase_products');
            }
            $updateData = array_filter([
                'title' => $data['title'] ?? null,
                'image' => $data['image'] ?? null,
            ], fn($value) => $value !== null);

            if (isset($data['title']) && $data['title'] !== $category->title) {
                $updateData['slug'] = $this->generateUniqueSlug($data['title']);
            }

            $category->update($updateData);

            return $category->fresh();
        });
    }

    public function deleteCategory(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            $this->uploadService->delete($category->image);
            $category->products()->update(['category_id' => null]);

            return $category->delete();
        });
    }

    public function getCategoryById(int $id): ?Category
    {
        return Category::withCount('products')->find($id);
    }

    private function generateUniqueSlug(string $title): string
    {
        return Str::slug($title) . '-' . now()->timestamp . '-' . Str::random(4);
    }
}
