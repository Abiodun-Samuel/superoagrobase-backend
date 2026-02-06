<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\{VendorRequest, User};
use Illuminate\Support\Facades\{DB, Hash, Password};
use Illuminate\Support\Str;

class VendorRequestService
{
    public function __construct(
        protected MailService $mailService
    ) {}

    public function submitRequest(array $data): VendorRequest
    {
        return DB::transaction(function () use ($data) {
            $email = $data['email'];

            $existingUser = User::where('email', $email)->latest()->first();
            if ($existingUser) {
                throw new \Exception('An account with this email already exists. Please login instead.');
            }

            $existingRequest = VendorRequest::where('email', $email)->first();

            if ($existingRequest && $existingRequest->is_approved) {
                throw new \Exception('Your vendor application has already been approved. Check your email for login credentials.');
            }

            if ($existingRequest) {
                $existingRequest->update([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone_number' => $data['phone_number'],
                    'company_name' => $data['company_name'],
                    'company_email' => $data['company_email'],
                    'company_phone' => $data['company_phone'],
                    'company_address' => $data['company_address'],
                    'company_website' => $data['company_website'] ?? null,

                    'status' => VendorRequest::STATUS_PENDING,
                    'rejection_reason' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);

                $vendorRequest = $existingRequest->fresh();
            } else {
                $vendorRequest = VendorRequest::create($data);
                $emailData = [
                    'admin_url' => config('app.frontendUrl') . '/dashboard/vendor-requests/' . $vendorRequest->id,
                    'name' => $vendorRequest->first_name,
                    'email' => $vendorRequest->email,
                    'phone_number' => $vendorRequest->phone_number,
                    'company_name' => $vendorRequest->company_name,
                ];
                $this->mailService->sendVendorRequestToAdmin($emailData);
            }

            return $vendorRequest;
        });
    }

    public function getAllRequests(array $filters = [])
    {
        $query = VendorRequest::query()->with(['reviewer', 'user']);

        if (isset($filters['status']) && in_array($filters['status'], ['pending', 'approved', 'rejected'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $query->latest();

        return $query->get();
    }

    public function getRequestByEmail(string $email): ?VendorRequest
    {
        return VendorRequest::where('email', $email)
            ->with(['reviewer', 'user'])
            ->latest()
            ->first();
    }

    public function approveRequest(VendorRequest $vendorRequest, User $admin): User
    {
        return DB::transaction(function () use ($vendorRequest, $admin) {
            if ($vendorRequest->is_approved || $vendorRequest->is_rejected) {
                throw new \Exception('This request has already been processed.');
            }

            $existingUser = User::where('email', $vendorRequest->email)->first();
            if ($existingUser) {
                throw new \Exception('A user with this email already exists.');
            }

            $temporaryPassword = Str::random(32);

            $user = User::create([
                'first_name' => $vendorRequest->first_name,
                'last_name' => $vendorRequest->last_name,
                'email' => $vendorRequest->email,
                'phone_number' => $vendorRequest->phone_number,
                'company_name' => $vendorRequest->company_name,
                'company_email' => $vendorRequest->company_email,
                'company_phone' => $vendorRequest->company_phone,
                'company_address' => $vendorRequest->company_address,
                'company_website' => $vendorRequest->company_website,
                'password' => Hash::make($temporaryPassword),
                'email_verified_at' => now(),
                'status' => 'active',
            ]);

            $user->assignRole(RoleEnum::VENDOR->value);

            $vendorRequest->update([
                'status' => VendorRequest::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'user_id' => $user->id,
                'rejection_reason' => null,
            ]);

            $resetToken = Password::createToken($user);
            $resetUrl = config('app.frontendUrl') . '/auth/reset-password?token=' . $resetToken . '&email=' . urlencode($user->email);

            $this->mailService->sendVendorRequestApproved([
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'reset_password_url' => $resetUrl,
            ]);

            return $user;
        });
    }


    public function rejectRequest(VendorRequest $vendorRequest, User $admin, string $reason): VendorRequest
    {
        return DB::transaction(function () use ($vendorRequest, $admin, $reason) {
            if ($vendorRequest->is_approved || $vendorRequest->is_rejected) {
                throw new \Exception('This request has already been processed.');
            }

            $vendorRequest->update([
                'status' => VendorRequest::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $this->mailService->sendVendorRequestRejected([
                'name' => $vendorRequest->first_name . ' ' . $vendorRequest->last_name,
                'email' => $vendorRequest->email,
                'rejection_reason' => $reason,
                'reapply_url' => config('app.frontendUrl') . '/become-a-vendor?email=' . urlencode($vendorRequest->email),
            ]);

            return $vendorRequest;
        });
    }
}
