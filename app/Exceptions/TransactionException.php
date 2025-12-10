<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransactionException extends Exception
{
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * Render exception as JSON response
     *
     * @param $request
     * @return JsonResponse
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $this->getMessage(),
        ], $this->code);
    }

    /**
     * Payment initialization failed
     *
     * @param string|null $message
     * @param int $code
     * @return static
     */
    public static function initializationFailed(
        string $message = null,
        int $code = Response::HTTP_BAD_REQUEST
    ): self {
        $exception = new self(
            $message ?? 'Failed to initialize payment',
            $code
        );
        $exception->code = $code;
        return $exception;
    }

    /**
     * Order already paid exception
     *
     * @param string $reference
     * @return static
     */
    public static function orderAlreadyPaid(string $reference): self
    {
        $exception = new self(
            "Order {$reference} has already been paid",
            Response::HTTP_BAD_REQUEST
        );
        $exception->code = Response::HTTP_BAD_REQUEST;
        return $exception;
    }

    /**
     * Invalid amount exception
     *
     * @return static
     */
    public static function invalidAmount(): self
    {
        $exception = new self(
            'Invalid order amount',
            Response::HTTP_BAD_REQUEST
        );
        $exception->code = Response::HTTP_BAD_REQUEST;
        return $exception;
    }

    /**
     * Payment gateway connection failed
     *
     * @return static
     */
    public static function connectionFailed(): self
    {
        $exception = new self(
            'Unable to connect to payment gateway. Please try again.',
            Response::HTTP_SERVICE_UNAVAILABLE
        );
        $exception->code = Response::HTTP_SERVICE_UNAVAILABLE;
        return $exception;
    }

    /**
     * Payment verification failed
     *
     * @param string|null $message
     * @return static
     */
    public static function verificationFailed(string $message = null): self
    {
        $exception = new self(
            $message ?? 'Payment verification failed',
            Response::HTTP_BAD_REQUEST
        );
        $exception->code = Response::HTTP_BAD_REQUEST;
        return $exception;
    }

    /**
     * Payment amount mismatch exception
     *
     * @param float $expected
     * @param float $received
     * @return static
     */
    public static function amountMismatch(float $expected, float $received): self
    {
        $exception = new self(
            "Payment amount mismatch. Expected: ₦{$expected}, Received: ₦{$received}",
            Response::HTTP_BAD_REQUEST
        );
        $exception->code = Response::HTTP_BAD_REQUEST;
        return $exception;
    }
}
