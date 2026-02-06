<?php

namespace App\Http\Requests\VendorRequest;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVendorRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:20'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_phone' => ['required', 'string', 'max:20'],
            'company_address' => ['required', 'string', 'max:500'],
            'company_website' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            // 'email.unique' => 'This email is already associated with an account or pending request.',
            'company_email.required' => 'Company email address is required.',
            'company_phone.required' => 'Company phone number is required.',
            'company_address.required' => 'Company address is required.',
            'company_website.url' => 'Please enter a valid website URL.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'phone_number' => 'phone number',
            'company_name' => 'company name',
            'company_email' => 'company email',
            'company_phone' => 'company phone',
            'company_address' => 'company address',
            'company_website' => 'company website',
        ];
    }
}
