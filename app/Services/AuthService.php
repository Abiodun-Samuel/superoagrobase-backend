<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const TOKEN_NAME = 'auth_token';

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'auth_provider' => 'local',
                'email_verified_at' => null,
            ]);

            $user->assignRole($data['role'] ?? 'user');

            $token = $this->createToken($user);

            return [
                'user'  => $user->fresh(),
                'token' => $token,
            ];
        });
    }

    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        $this->ensureUserIsActive($user);

        $token = $this->createToken($user);

        $user->update(['last_login_at' => now()]);

        return [
            'user'  => $user->fresh(),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    private function createToken(User $user): string
    {
        $user->tokens()->delete();
        $expiration = (int) config('sanctum.expiration', 10080);
        $token = $user->createToken(
            self::TOKEN_NAME,
            ['*'],
            now()->addMinutes($expiration)
        );
        return $token->plainTextToken;
    }

    private function ensureUserIsActive(User $user): void
    {
        if (isset($user->status) && $user->status == 'deactivated') {
            Auth::logout();
            throw new AuthenticationException('Your account has been deactivated.');
        }
    }
}
