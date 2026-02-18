<?php

namespace App\Services;

use App\Models\User;

class UserProfileService
{
    protected MediaUploadService $uploadService;

    public function __construct(MediaUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    /**
     * Update user basic information
     */
    public function updateBasicInfo(User $user, array $data): User
    {
        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
        ]);

        return $user->fresh();
    }

    /**
     * Update user personal details
     */
    public function updatePersonalDetails(User $user, array $data): User
    {
        $user->update([
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
        ]);

        return $user->fresh();
    }

    /**
     * Update user address
     */
    public function updateAddress(User $user, array $data): User
    {
        $user->update([
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'],
        ]);

        return $user->fresh();
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(User $user, array $data): User
    {
        $user->update([
            'is_marketing_subscribed' => $data['is_marketing_subscribed'] ?? false,
        ]);

        return $user->fresh();
    }

    protected function handleAvatarUpload(User $user, $avatarFile, string $folder): string
    {
        // Delete old avatar if exists
        if (!empty($user->avatar)) {
            $this->uploadService->delete($user->avatar);
        }

        return $this->uploadService->upload($avatarFile, $folder);
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(User $user, string $avatar): User
    {
        $data['avatar'] = $this->handleAvatarUpload($user, $avatar, 'superoagrobase_users');
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Get user profile with completion percentage
     */
    public function getProfile(User $user): array
    {
        return [
            'user' => $user,
            'completion_percentage' => $user->profile_completion_percent,
            'is_completed' => $user->isProfileCompleted(),
        ];
    }
}
