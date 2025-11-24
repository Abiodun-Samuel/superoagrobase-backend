<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'auth_provider',
        'email_verified_at',
        'avatar',
        'gender',
        'date_of_birth',
        'status',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'is_marketing_subscribed',
        'last_login_at',
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'company_website',
        'billing_details',
        'shipping_details',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'last_login_at' => 'datetime',
            'billing_details' => 'array',
            'shipping_details' => 'array',
            'is_marketing_subscribed' => 'boolean',
        ];
    }
    protected array $completionFields = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'avatar',
        'gender',
        'date_of_birth',
        'address',
        'city',
        'state',
        'country',
        'is_marketing_subscribed',
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'company_website',
        'billing_details',
        'shipping_details',
    ];


    public function isProfileCompleted(): bool
    {
        return collect($this->completionFields)
            ->every(fn($field) => !empty($this->{$field}));
    }
    public function getProfileCompletionPercentAttribute(): int
    {
        $filled = collect($this->completionFields)
            ->filter(fn($field) => !empty($this->{$field}))
            ->count();

        return intval(($filled / count($this->completionFields)) * 100);
    }
}
