<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add proper authorization logic
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:categories,title'],
            'image' => ['nullable', 'string'],
        ];
    }
}
