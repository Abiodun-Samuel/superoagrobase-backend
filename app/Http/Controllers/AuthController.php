<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse(
            [
                'token' => $result['token'],
                'user'  => new UserResource($result['user']),
            ],
            'Registration successful',
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

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse(
            ['user' => UserResource::make($user)],
            'User details retrieved successfully',
            Response::HTTP_OK
        );
    }
}
