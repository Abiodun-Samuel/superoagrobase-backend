<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorResponse extends JsonResponse
{
    /**
     * Create a new API error response.
     */
    public static function create(
        ?string $message = null,
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?array $errors = null,
        ?array $headers = []
    ): self {
        $data = [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];

        // Only include errors if they exist and are not empty
        if ($errors !== null && !empty($errors)) {
            $data['errors'] = $errors;
        }

        // Add error code for easier debugging
        $data['error_code'] = $code;

        // Add timestamp for debugging
        $data['timestamp'] = now()->toISOString();

        return new self($data, $code, $headers);
    }

    /**
     * Create a validation error response.
     */
    public static function validation(array $errors, string $message = 'The given data was invalid.'): self
    {
        return self::create(
            message: $message,
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors
        );
    }

    /**
     * Create an unauthorized error response.
     */
    public static function unauthorized(string $message = 'Authentication required.'): self
    {
        return self::create(
            message: $message,
            code: Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Create a forbidden error response.
     */
    public static function forbidden(string $message = 'You are not authorized to perform this action.'): self
    {
        return self::create(
            message: $message,
            code: Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Create a not found error response.
     */
    public static function notFound(string $message = 'The requested resource was not found.'): self
    {
        return self::create(
            message: $message,
            code: Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Create a server error response.
     */
    public static function serverError(string $message = 'An unexpected error occurred.'): self
    {
        return self::create(
            message: $message,
            code: Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
