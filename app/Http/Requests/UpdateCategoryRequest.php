<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add proper authorization logic
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')->id;

        return [
            'title' => ['sometimes', 'string', 'max:255', Rule::unique('categories', 'title')->ignore($categoryId)],
            'image' => ['nullable', 'string'],
        ];
    }
}
