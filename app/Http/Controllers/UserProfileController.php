<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\{
    UpdateBasicInfoRequest,
    UpdatePersonalDetailsRequest,
    UpdateAddressRequest,
    UpdatePreferencesRequest,
    UpdateAvatarRequest
};
use App\Http\Resources\UserResource;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    public function __construct(
        private UserProfileService $profileService
    ) {}

    /**
     * Get authenticated user profile
     */
    public function show(): JsonResponse
    {
        $profile = $this->profileService->getProfile(auth()->user());

        return $this->successResponse(
            new UserResource($profile['user']),
            'Profile retrieved successfully'
        );
    }

    /**
     * Update basic information
     */
    public function updateBasicInfo(UpdateBasicInfoRequest $request): JsonResponse
    {
        $user = $this->profileService->updateBasicInfo(
            auth()->user(),
            $request->validated()
        );

        return $this->successResponse(
            new UserResource($user),
            'Basic information updated successfully'
        );
    }

    /**
     * Update personal details
     */
    public function updatePersonalDetails(UpdatePersonalDetailsRequest $request): JsonResponse
    {
        $user = $this->profileService->updatePersonalDetails(
            auth()->user(),
            $request->validated()
        );

        return $this->successResponse(
            new UserResource($user),
            'Personal details updated successfully'
        );
    }

    /**
     * Update address
     */
    public function updateAddress(UpdateAddressRequest $request): JsonResponse
    {
        $user = $this->profileService->updateAddress(
            auth()->user(),
            $request->validated()
        );

        return $this->successResponse(
            new UserResource($user),
            'Address updated successfully'
        );
    }

    /**
     * Update preferences
     */
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $user = $this->profileService->updatePreferences(
            auth()->user(),
            $request->validated()
        );

        return $this->successResponse(
            new UserResource($user),
            'Preferences updated successfully'
        );
    }

    /**
     * Update avatar
     */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $this->profileService->updateAvatar(
            auth()->user(),
            $request->validated()['avatar']
        );

        return $this->successResponse(
            new UserResource($user),
            'Avatar updated successfully'
        );
    }
}
