<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with(['subcategory', 'products'])->get();
        $data = CategoryResource::collection($categories);
        return $this->successResponse($data, '');
    }
}
