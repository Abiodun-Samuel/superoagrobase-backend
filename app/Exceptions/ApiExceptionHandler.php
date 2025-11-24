<?php

namespace App\Exceptions;

use App\Http\Responses\ApiErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Register exception handlers.
     */
    public function register(Exceptions $exceptions): void
    {
        // Model not found exceptions
        $exceptions->render(
            fn(NotFoundHttpException $e, Request $request) => $this->handleNotFound($e, $request)
        );

        // Authentication exceptions
        $exceptions->render(
            fn(AuthenticationException $e, Request $request) => $this->handleAuthentication($e, $request)
        );

        // Authorization exceptions
        $exceptions->render(
            fn(AuthorizationException $e, Request $request) => $this->handleAuthorization($e, $request)
        );

        $exceptions->render(
            fn(UnauthorizedException $e, Request $request) => $this->handleUnauthorized($e, $request)
        );

        $exceptions->render(
            fn(AccessDeniedHttpException $e, Request $request) => $this->handleAccessDenied($e, $request)
        );

        // Validation exceptions
        $exceptions->render(
            fn(ValidationException $e, Request $request) => $this->handleValidation($e, $request)
        );

        // Generic HTTP exceptions
        $exceptions->render(
            fn(HttpException $e, Request $request) => $this->handleHttpException($e, $request)
        );

        // Fallback for any other exceptions in API routes
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($this->shouldReturnJson($request)) {
                return $this->handleGenericException($e, $request);
            }

            return null; // Let Laravel handle non-API exceptions normally
        });
    }

    /**
     * Handle not found exceptions.
     */
    private function handleNotFound(NotFoundHttpException $e, Request $request): ?Response
    {
        if (!$this->shouldReturnJson($request)) {
            return null;
        }

        $previous = $e->getPrevious();

        if ($previous instanceof ModelNotFoundException) {
            return $this->handleModelNotFound($previous);
        }

        return ApiErrorResponse::create(
            message: $e->getMessage() ?: 'The requested resource was not found.',
            code: Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Handle model not found exceptions.
     */
    private function handleModelNotFound(ModelNotFoundException $e): Response
    {
        $modelName = $this->getModelDisplayName($e->getModel());
        $ids = $e->getIds();

        $message = $this->buildModelNotFoundMessage($modelName, $ids);

        return ApiErrorResponse::create(
            message: $message,
            code: Response::HTTP_NOT_FOUND,
            errors: [
                'model' => $modelName,
                'ids' => $ids,
            ]
        );
    }

    /**
     * Handle authentication exceptions.
     */
    private function handleAuthentication(AuthenticationException $e, Request $request): ?Response
    {
        if (!$this->shouldReturnJson($request)) {
            return null;
        }

        return ApiErrorResponse::create(
            message: 'Authentication required. Please provide valid credentials.',
            code: Response::HTTP_UNAUTHORIZED,
            errors: []
        );
    }

    /**
     * Handle authorization exceptions.
     */
    private function handleAuthorization(AuthorizationException $e, Request $request): ?Response
    {
        if (!$this->shouldReturnJson($request)) {
            return null;
        }

        return ApiErrorResponse::create(
            message: $e->getMessage() ?: 'You are not authorized to perform this action.',
            code: Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Handle Spatie permission unauthorized exceptions.
     */
    private function handleUnauthorized(UnauthorizedException $e, Request $request): ?Response
    {
        if (!$this->shouldReturnJson($request)) {
            return null;
        }

        return ApiErrorResponse::create(
            message: $e->getMessage() ?: 'You do not have the required permissions.',
            code: Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Handle access denied exceptions.
     */
    private function handleAccessDenied(AccessDeniedHttpException $e, Request $request): ?Response
    {
        if (!$this->shouldReturnJson($request)) {
            return null;
        }

        return ApiErrorResponse::create(
            message: $e->getMessage() ?: 'Access to this resource is denied.',
            code: Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Handle validation exceptions.
     */
    private function handleValidation(ValidationException $e, Request $request): ?Response
    {
        if (!$this->shouldReturnJson($request)) {
            return null;
        }

        return ApiErrorResponse::create(
            message: 'The given data was invalid.',
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $e->errors(),
        );
    }

    /**
     * Handle generic HTTP exceptions.
     */
    private function handleHttpException(HttpException $e, Request $request): ?Response
    {
        if (!$this->shouldReturnJson($request)) {
            return null;
        }

        return ApiErrorResponse::create(
            message: $e->getMessage() ?: 'An error occurred.',
            code: $e->getStatusCode()
        );
    }

    /**
     * Handle generic exceptions.
     */
    private function handleGenericException(Throwable $e, Request $request): Response
    {
        $message = app()->hasDebugModeEnabled()
            ? $e->getMessage()
            : 'An unexpected error occurred. Please try again later.';

        $errors = app()->hasDebugModeEnabled() ? [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ] : null;

        return ApiErrorResponse::create(
            message: $message,
            code: Response::HTTP_INTERNAL_SERVER_ERROR,
            errors: $errors
        );
    }

    /**
     * Determine if the request should return JSON.
     */
    private function shouldReturnJson(Request $request): bool
    {
        return $request->is('api/*') ||
            $request->wantsJson() ||
            $request->expectsJson();
    }

    /**
     * Get a human-readable model name.
     */
    private function getModelDisplayName(string $model): string
    {
        return Str::title(Str::snake(class_basename($model), ' '));
    }

    /**
     * Build model not found message.
     */
    private function buildModelNotFoundMessage(string $modelName, array $ids): string
    {
        if (empty($ids)) {
            return "{$modelName} not found.";
        }

        $idList = implode(', ', $ids);
        $plural = count($ids) > 1 ? 's' : '';

        return "{$modelName} with ID{$plural} {$idList} not found.";
    }
}
