<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'role',
            'status',
            'search',
            'sort_by',
            'sort_direction',
            'per_page'
        ]);

        $users = $this->userService->getAllUsers($filters);

        return $this->paginatedResponse(
            UserResource::collection($users),
            'Users retrieved successfully'
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return $this->successResponse(
            UserResource::make($user),
            'User created successfully',
            Response::HTTP_CREATED
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles');

        return $this->successResponse(
            UserResource::make($user),
            'User retrieved successfully'
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->updateUser($user, $request->validated());

        return $this->successResponse(
            UserResource::make($user),
            'User updated successfully'
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->deleteUser($user);

        return $this->successResponse(
            null,
            'User deleted successfully'
        );
    }

    public function assignRole(User $user, Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|in:user,vendor,admin,super_admin',
            'roles' => 'required|array',
        ]);

        $user->syncRoles($validated['roles']);

        return $this->successResponse(
            new UserResource($user->load('roles')),
            'Role assigned successfully'
        );
    }
}
