<?php

namespace App\Services;

use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubcategoryService
{
    protected MediaUploadService $uploadService;

    public function __construct(MediaUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    protected function handleImageUpload($avatarFile, string $folder): string
    {
        return $this->uploadService->upload($avatarFile, $folder);
    }

    public function getAllSubcategories()
    {
        $query = Subcategory::withCount('products')->with('category')->latest()->get();
        return $query;
    }

    public function createSubcategory(array $data): Subcategory
    {
        return DB::transaction(function () use ($data) {
            $image = $this->handleImageUpload($data['image'], 'superoagrobase_products');

            $data['slug'] = $this->generateUniqueSlug($data['title']);

            return Subcategory::create([
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'image' => $image ?? null,
            ]);
        });
    }

    public function updateSubcategory(Subcategory $subcategory, array $data): Subcategory
    {
        return DB::transaction(function () use ($subcategory, $data) {

            if (isset($data['image']) && !empty($subcategory->image)) {
                $this->uploadService->delete($subcategory->image);
                $data['image'] = $this->handleImageUpload($data['image'], 'superoagrobase_products');
            }

            $updateData = array_filter([
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'] ?? null,
                'image' => $data['image'] ?? null,
            ], fn($value) => $value !== null);

            if (isset($data['title']) && $data['title'] !== $subcategory->title) {
                $updateData['slug'] = $this->generateUniqueSlug($data['title']);
            }

            $subcategory->update($updateData);

            return $subcategory->fresh();
        });
    }

    public function deleteSubcategory(Subcategory $subcategory): bool
    {
        return DB::transaction(function () use ($subcategory) {
            $this->uploadService->delete($subcategory->image);
            $subcategory->products()->update(['subcategory_id' => null]);

            return $subcategory->delete();
        });
    }

    public function getSubcategoryById(int $id): ?Subcategory
    {
        return Subcategory::withCount('products')->find($id);
    }

    private function generateUniqueSlug(string $title): string
    {
        return Str::slug($title) . '-' . now()->timestamp . '-' . Str::random(4);
    }
}
