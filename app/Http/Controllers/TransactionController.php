<?php

namespace App\Http\Controllers;

use App\Exceptions\TransactionException;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService
    ) {}

    /**
     * Verify transaction status
     * Called when user returns from Paystack payment page
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reference' => 'required|string|max:255'
            ]);

            $result = $this->transactionService->verifyTransaction($validated['reference']);

            // If verification successful and payment succeeded, process it
            if ($result['status'] && ($result['data']['status'] ?? '') === 'success') {
                $this->transactionService->processSuccessfulTransaction($result['data']);
            }

            return $this->successResponse($result['data'], 'Transaction verified successfully');
        } catch (TransactionException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                'Invalid request data',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An error occurred while verifying transaction',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle Paystack webhook events
     * This is called directly by Paystack when payment status changes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature
            if (!$this->verifyWebhookSignature($request)) {
                return response()->json(
                    ['error' => 'Invalid signature'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $event = $request->all();

            // Process event based on type
            $this->handleWebhookEvent($event);

            return $this->successResponse(['status' => 'success'], 'Transaction verified successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Verify Paystack webhook signature
     *
     * @param Request $request
     * @return bool
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');

        if (!$signature) {
            return false;
        }

        $computedSignature = hash_hmac(
            'sha512',
            $request->getContent(),
            config('transaction.paystack.secretKey')
        );

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Route webhook events to appropriate handlers
     *
     * @param array $event
     * @return void
     */
    private function handleWebhookEvent(array $event): void
    {
        $eventType = $event['event'] ?? null;
        $data = $event['data'] ?? [];

        match ($eventType) {
            'charge.success' => $this->handleChargeSuccess($data),
            'charge.failed' => $this->handleChargeFailed($data),
            default => null
        };
    }

    /**
     * Handle successful charge webhook
     *
     * @param array $data
     * @return void
     */
    private function handleChargeSuccess(array $data): void
    {
        try {
            $reference = $data['reference'] ?? null;

            if (!$reference) {
                return;
            }

            // Verify with Paystack before processing
            $verification = $this->transactionService->verifyTransaction($reference);

            if (!$verification['status']) {
                return;
            }

            // Only process if payment is truly successful
            if (($verification['data']['status'] ?? '') === 'success') {
                $this->transactionService->processSuccessfulTransaction($verification['data']);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Handle failed charge webhook
     *
     * @param array $data
     * @return void
     */
    private function handleChargeFailed(array $data): void
    {
        try {
            $reference = $data['reference'] ?? null;

            if (!$reference) {
                return;
            }

            $this->transactionService->processFailedTransaction($reference);
        } catch (\Exception $e) {
        }
    }
}
