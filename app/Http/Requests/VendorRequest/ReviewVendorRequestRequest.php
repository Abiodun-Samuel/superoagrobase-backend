<?php

namespace App\Http\Requests\VendorRequest;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewVendorRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(RoleEnum::ADMIN->value);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'Please provide a reason for rejecting this request.',
            'rejection_reason.max' => 'The rejection reason must not exceed 1000 characters.',
        ];
    }
}
