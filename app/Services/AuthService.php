<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Exceptions\Auth\EmailAlreadyVerifiedException;
use App\Exceptions\Auth\InvalidTokenException;
use App\Exceptions\Auth\NoTokenFoundException;
use App\Exceptions\Auth\TokenExpiredException;
use App\Exceptions\Auth\UserNotFoundException;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(protected MailService $mailService) {}
    private const TOKEN_NAME = 'auth_token';

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $verification = $this->buildEmailVerificationPayload($data['email']);

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'auth_provider' => 'local',
                'last_login_at' => now(),
                'email_verified_at' => null,
                'email_verification_token' => $verification['token_hash'],
                'email_verification_expires_at' => $verification['expires_at'],
            ]);

            $user->assignRole(RoleEnum::USER->value);

            $this->mailService->sendWelcomeVerificationEmail([
                'name' => $user->first_name,
                'email' => $user->email,
                'verify_url' => $verification['verify_url'],
            ]);

            $token = $this->createToken($user);

            return [
                'user'  => $user->fresh(),
                'token' => $token,
            ];
        });
    }

    public function sendEmailVerification(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        if ($user->email_verified_at) {
            throw new \Exception('Email already verified', 400);
        }

        $verification = $this->buildEmailVerificationPayload($user->email);

        // Store token in user table
        $user->update([
            'email_verification_token' => $verification['token_hash'],
            'email_verification_expires_at' => $verification['expires_at'],
        ]);

        // Send email
        $this->mailService->sendEmailVerification([
            'name' => $user->first_name,
            'email' => $user->email,
            'verify_url' => $verification['verify_url'],
        ]);

        return [
            'message' => 'Verification link sent to your email',
            'verify_url' => $verification['verify_url'],
            'expires_at' => $verification['expires_at']->toISOString(),
        ];
    }

    protected function buildEmailVerificationPayload(string $email): array
    {
        $token = Str::random(60);
        $expiresAt = now()->addMinutes(60);

        $verifyUrl = rtrim(config('app.frontendUrl'), '/')
            . "/auth/email-verification?token={$token}&email=" . urlencode($email);

        return [
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'verify_url' => $verifyUrl,
        ];
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

    /**
     * Login user with credentials
     *
     * PRODUCTION-SAFE FIX:
     * - Changed from Auth::attempt() to manual validation
     * - Reason: Auth::attempt() doesn't work with Sanctum guard
     * - All existing logic preserved
     *
     * @param array $credentials
     * @return array
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        // ✅ FIXED: Manual credential validation
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // ✅ UNCHANGED: Existing user validation
        $this->ensureUserIsActive($user);

        // ✅ UNCHANGED: Existing token creation
        $token = $this->createToken($user);

        // ✅ UNCHANGED: Existing last login update
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

    /**
     * Ensure user account is active
     *
     * PRODUCTION-SAFE FIX:
     * - Removed Auth::logout() (not needed for token auth)
     * - Changed == to === for strict comparison
     *
     * @param User $user
     * @return void
     * @throws AuthenticationException
     */
    private function ensureUserIsActive(User $user): void
    {
        if (isset($user->status) && $user->status === 'deactivated') {
            throw new AuthenticationException('Your account has been deactivated.');
        }
    }

    public function sendPasswordResetLink(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        $token = Password::createToken($user);

        $resetUrl = config('app.frontendUrl') . "/auth/reset-password?token={$token}&email=" . urlencode($email);

        $this->mailService->sendPasswordResetEmail([
            'name' => $user->first_name,
            'email' => $user->email,
            'reset_url' => $resetUrl,
        ]);

        return [
            'message' => 'Password reset link sent to your email',
        ];
    }

    public function resetPassword(string $email, string $token, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        // Verify token
        if (!Password::tokenExists($user, $token)) {
            throw new \Exception('Invalid or expired reset token', 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($password),
        ]);

        Password::deleteToken($user);

        $this->mailService->sendPasswordResetConfirmation([
            'name' => $user->first_name,
            'email' => $user->email,
        ]);

        return [
            'message' => 'Password reset successfully',
        ];
    }

    public function verifyEmail(string $email, string $token): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        if ($user->email_verified_at) {
            throw new \Exception('Email already verified', 400);
        }

        // Hash the token for comparison
        $hashedToken = hash('sha256', $token);

        // Verify token matches
        if ($user->email_verification_token !== $hashedToken) {
            throw new \Exception('Invalid verification token', 400);
        }

        // Check if token is expired
        if ($user->email_verification_expires_at && Carbon::parse($user->email_verification_expires_at)->isPast()) {
            // Clear expired token
            $user->update([
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
            ]);

            throw new \Exception('Verification token has expired', 400);
        }

        // Verify the email
        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
        ]);

        return [
            'message' => 'Email verified successfully',
            'user' => $user,
        ];
    }

    public function verifyToken(string $email, string $token, string $type): array
    {
        return match ($type) {
            'email_verification' => $this->verifyEmailVerificationToken($email, $token),
            'password_reset' => $this->verifyPasswordResetToken($email, $token),
            default => [
                'valid' => false,
                'message' => 'Invalid token type',
                'error_code' => 'INVALID_TYPE'
            ]
        };
    }

    protected function verifyEmailVerificationToken(string $email, string $token): array
    {
        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new UserNotFoundException();
        }

        // Check if email is already verified
        if ($user->email_verified_at) {
            throw new EmailAlreadyVerifiedException();
        }

        // Check if token exists
        if (!$user->email_verification_token) {
            throw new NoTokenFoundException();
        }

        // Hash the token for comparison
        $hashedToken = hash('sha256', $token);

        // Verify token matches
        if ($user->email_verification_token !== $hashedToken) {
            throw new InvalidTokenException();
        }

        // Check if token is expired
        if ($user->email_verification_expires_at && Carbon::parse($user->email_verification_expires_at)->isPast()) {
            throw new TokenExpiredException(
                Carbon::parse($user->email_verification_expires_at)->toISOString()
            );
        }

        // Token is valid
        return [
            'valid' => true,
            'message' => 'Token is valid',
            'expires_at' => $user->email_verification_expires_at
                ? Carbon::parse($user->email_verification_expires_at)->toISOString()
                : null,
            'time_remaining' => $user->email_verification_expires_at
                ? Carbon::parse($user->email_verification_expires_at)->diffForHumans()
                : null
        ];
    }

    protected function verifyPasswordResetToken(string $email, string $token): array
    {
        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new UserNotFoundException();
        }

        // Check if token exists and is valid
        if (!Password::tokenExists($user, $token)) {
            throw new InvalidTokenException('Invalid or expired reset token');
        }

        // Token is valid
        return [
            'valid' => true,
            'message' => 'Token is valid',
        ];
    }
}
