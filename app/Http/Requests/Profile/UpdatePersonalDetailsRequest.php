<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonalDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'gender' => ['required', 'string', 'in:Male,Female,Others'],
            'date_of_birth' => ['required', 'date', 'before:today'],
        ];
    }
}
