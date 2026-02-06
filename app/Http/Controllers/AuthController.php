<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Auth\VerifyTokenRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return $this->successResponse(
            ['token' => $result['token'], 'user'  => new UserResource($result['user']),],
            'Registration successful. A verification email has been sent to your email address. The link will expire in 60 minutes.',
            Response::HTTP_CREATED
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            $request->clearRateLimit();

            return $this->successResponse(
                [
                    'token' => $result['token'],
                    'user'  => new UserResource($result['user']),
                ],
                'Login successful'
            );
        } catch (ValidationException $ex) {
            return $this->errorResponse(
                $ex->getMessage() ?? 'Error, unable to login',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $ex) {
            return $this->errorResponse(
                $ex->getMessage() ?? 'Error, unable to login',
            );
        }
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout(auth()->user());
        return $this->successResponse(
            null,
            'Logout successful',
            Response::HTTP_NO_CONTENT
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->sendPasswordResetLink($request->validated('email'));

            return $this->successResponse(null, $result['message'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->authService->resetPassword(
                $validated['email'],
                $validated['token'],
                $validated['password']
            );

            return $this->successResponse(
                null,
                $result['message'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->authService->verifyEmail(
                $validated['email'],
                $validated['token']
            );

            return $this->successResponse(null, $result['message'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->sendEmailVerification($request->validated('email'));

            return $this->successResponse(null, $result['message'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function verifyToken(VerifyTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->authService->verifyToken(
            $validated['email'],
            $validated['token'],
            $validated['type']
        );

        return $this->successResponse(
            [
                'valid' => $result['valid'],
                'message' => $result['message'],
                'expires_at' => $result['expires_at'] ?? null,
                'time_remaining' => $result['time_remaining'] ?? null,
            ],
            'Token is valid',
            Response::HTTP_OK
        );
    }

    // public function me(Request $request): JsonResponse
    // {
    //     $user = $request->user();

    //     return $this->successResponse(
    //         ['user' => UserResource::make($user)],
    //         'User details retrieved successfully',
    //         Response::HTTP_OK
    //     );
    // }

    // public function changePassword(ChangePasswordRequest $request): JsonResponse
    // {
    //     try {
    //         $result = $this->authService->changePassword(
    //             $request->user(),
    //             $request->validated('password')
    //         );

    //         return $this->successResponse(
    //             null,
    //             $result['message'],
    //             Response::HTTP_OK
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(
    //             $e->getMessage(),
    //             $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
    //         );
    //     }
    // }

    // public function updateProfile(UpdateProfileRequest $request): JsonResponse
    // {
    //     try {
    //         $user = $this->authService->updateProfile(
    //             $request->user(),
    //             $request->validated()
    //         );

    //         return $this->successResponse(
    //             new UserResource($user),
    //             'Profile updated successfully',
    //             Response::HTTP_OK
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(
    //             $e->getMessage(),
    //             $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
    //         );
    //     }
    // }

    // public function getProfile(): JsonResponse
    // {
    //     try {
    //         $user = auth()->user();

    //         return $this->successResponse(
    //             new UserResource($user),
    //             'Profile retrieved successfully',
    //             Response::HTTP_OK
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(
    //             $e->getMessage(),
    //             Response::HTTP_INTERNAL_SERVER_ERROR
    //         );
    //     }
    // }
}
