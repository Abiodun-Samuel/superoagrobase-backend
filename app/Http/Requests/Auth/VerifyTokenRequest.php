<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'type' => ['required', 'string', 'in:email_verification,password_reset'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Token is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'type.required' => 'Token type is required',
            'type.in' => 'Invalid token type',
        ];
    }
}
