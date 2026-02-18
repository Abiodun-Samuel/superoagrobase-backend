<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add proper authorization logic
    }

    public function rules(): array
    {
        $subcategoryId = $this->route('subcategory')->id;

        return [
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'title' => ['sometimes', 'string', 'max:255', Rule::unique('subcategories', 'title')->ignore($subcategoryId)],
            'image' => ['nullable', 'string'],
        ];
    }
}
