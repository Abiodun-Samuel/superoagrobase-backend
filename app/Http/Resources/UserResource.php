<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Basic identity
            'id'          => $this->id,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'full_name'   => trim($this->first_name . ' ' . $this->last_name),
            'initials' => $this->generateInitials($this->first_name, $this->last_name),
            'email'       => $this->email,
            'phone_number' => $this->phone_number,

            'profile_completed' => $this->isProfileCompleted(),
            'completion_percent' => $this->profile_completion_percent,

            // Profile
            'avatar'      => $this->avatar,
            'gender'      => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),

            // Authentication
            'auth_provider'    => $this->auth_provider,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),

            // Status
            'status' => $this->status,

            // Location
            'address'     => $this->address,
            'city'        => $this->city,
            'state'       => $this->state,
            'postal_code' => $this->postal_code,
            'country'     => $this->country,

            // E-commerce
            'is_marketing_subscribed' => $this->is_marketing_subscribed,
            'last_login_at'           => $this->last_login_at?->diffForHumans(),

            // Vendor fields (marketplace support)
            'company' => [
                'name'    => $this->company_name,
                'email'   => $this->company_email,
                'phone'   => $this->company_phone,
                'address' => $this->company_address,
                'website' => $this->company_website,
            ],

            // Billing & Shipping (stored JSON)
            'billing_details'  => $this->billing_details ?? [],
            'shipping_details' => $this->shipping_details ?? [],

            // Roles & Permissions
            'roles'        => $this->getRoleNames(),

            // Timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function generateInitials(string $firstName, string $lastName): string
    {
        $firstInitial = Str::upper(Str::substr($firstName, 0, 1));
        $lastInitial = Str::upper(Str::substr($lastName, 0, 1));
        return "$firstInitial$lastInitial";
    }
}
