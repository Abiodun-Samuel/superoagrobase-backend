<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:newsletter_subscribers,email',
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already subscribed to our newsletter.',
        ];
    }
}
