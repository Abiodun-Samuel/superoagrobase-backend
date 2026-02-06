<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required',
            'current_password.current_password' => 'Current password is incorrect',
            'password.required' => 'New password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'password.different' => 'New password must be different from current password',
        ];
    }
}
