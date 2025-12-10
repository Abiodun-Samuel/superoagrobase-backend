<?php

namespace App\Services;

use App\Exceptions\TransactionException;
use App\Models\Order;
use App\Models\Transaction;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransactionService
{
    private const REQUEST_TIMEOUT = 30;

    private string $secretKey;
    private string $baseUrl;
    private string $frontendUrl;

    public function __construct()
    {
        $this->secretKey = config('transaction.paystack.secretKey');
        $this->baseUrl = config('transaction.paystack.paymentUrl');
        $this->frontendUrl = config('app.frontendUrl');
    }

    public function initializeTransaction(Order $order): string
    {
        $this->validateOrder($order);

        try {
            $payload = $this->buildPaymentPayload($order);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($this->baseUrl . '/transaction/initialize', $payload);

            if (!$response->successful()) {
                $errorData = $response->json();

                throw TransactionException::initializationFailed(
                    $errorData['message'] ?? 'Failed to initialize payment'
                );
            }

            $responseData = $response->json();

            if (!($responseData['status'] ?? false)) {
                throw TransactionException::initializationFailed(
                    $responseData['message'] ?? 'Invalid response from payment gateway'
                );
            }

            $this->createTransactionRecord($order, $responseData['data']);

            return $responseData['data']['authorization_url'];
        } catch (ConnectionException $e) {
            throw TransactionException::connectionFailed();
        } catch (RequestException $e) {
            throw TransactionException::initializationFailed(
                'Payment service is temporarily unavailable',
                503
            );
        } catch (TransactionException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw TransactionException::initializationFailed(
                'An unexpected error occurred. Please try again.'
            );
        }
    }

    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])
                ->timeout(self::REQUEST_TIMEOUT)
                ->get($this->baseUrl . '/transaction/verify/' . $reference);

            $data = $response->json();

            if (!$response->successful()) {
                throw TransactionException::verificationFailed(
                    $data['message'] ?? 'Verification failed'
                );
            }

            if (!($data['status'] ?? false)) {
                throw TransactionException::verificationFailed(
                    $data['message'] ?? 'Verification failed'
                );
            }

            return [
                'status' => true,
                'message' => 'Verification successful',
                'data' => $data['data'],
            ];
        } catch (TransactionException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw TransactionException::verificationFailed(
                'An error occurred during verification'
            );
        }
    }

    public function processSuccessfulTransaction(array $data): bool
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            throw TransactionException::verificationFailed('Invalid payment data');
        }

        return DB::transaction(function () use ($data, $reference) {
            try {
                $order = Order::where('reference', $reference)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw TransactionException::verificationFailed('Order not found');
                }

                if ($order->isPaid()) {
                    return true;
                }

                $expectedAmount = $this->convertToKobo($order->total);
                $receivedAmount = $data['amount'] ?? 0;

                if ($expectedAmount !== $receivedAmount) {
                    throw TransactionException::amountMismatch(
                        $order->total,
                        $receivedAmount / 100
                    );
                }

                $transaction = Transaction::where('reference', $reference)
                    ->lockForUpdate()
                    ->first();

                if ($transaction) {
                    $transaction->update([
                        'transaction_reference' => $data['id'] ?? null,
                        'status' => 'success',
                        'channel' => $data['channel'] ?? null,
                        'transaction_response' => $data,
                    ]);
                }

                $order->update([
                    'status' => 'processing',
                    'payment_status' => 'paid',
                    'payment_gateway' => 'paystack',
                    'transaction_reference' => $data['id'] ?? null,
                    'paid_at' => now(),
                ]);

                return true;
            } catch (TransactionException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw TransactionException::verificationFailed(
                    'Failed to process payment'
                );
            }
        });
    }

    /**
     * Process failed transaction
     *
     * @param string $reference Order reference
     * @return void
     */
    public function processFailedTransaction(string $reference): void
    {
        DB::transaction(function () use ($reference) {
            try {
                $order = Order::where('reference', $reference)
                    ->lockForUpdate()
                    ->first();

                if (!$order || $order->isPaid()) {
                    return;
                }

                $order->update([
                    'payment_status' => 'failed',
                ]);

                $transaction = Transaction::where('reference', $reference)
                    ->lockForUpdate()
                    ->first();

                if ($transaction) {
                    $transaction->update(['status' => 'failed']);
                }
            } catch (\Exception $e) {
            }
        });
    }

    /**
     * Build payment payload for Paystack
     *
     * @param Order $order
     * @return array
     */
    private function buildPaymentPayload(Order $order): array
    {
        return [
            'email' => $order->user->email,
            'amount' => $this->convertToKobo($order->total),
            'reference' => $order->reference,
            'currency' => 'NGN',
            'callback_url' => $this->frontendUrl . '/checkout/verify?ref=' . $order->reference,
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'order_reference' => $order->reference,
                'custom_fields' => [
                    [
                        'display_name' => 'Order Reference',
                        'variable_name' => 'order_reference',
                        'value' => $order->reference,
                    ],
                    [
                        'display_name' => 'Customer Name',
                        'variable_name' => 'customer_name',
                        'value' => $order->user->first_name . ' ' . $order->user->last_name,
                    ],
                ],
            ],
            'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
        ];
    }

    /**
     * Create transaction record in database
     *
     * @param Order $order
     * @param array $paystackData
     * @return void
     */
    private function createTransactionRecord(Order $order, array $paystackData): void
    {
        Transaction::create([
            'order_id' => $order->id,
            'reference' => $order->reference,
            'amount' => $order->total,
            'status' => 'pending',
            'currency' => 'NGN',
            'metadata' => $paystackData,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Validate order before payment initialization
     *
     * @param Order $order
     * @return void
     * @throws TransactionException
     */
    private function validateOrder(Order $order): void
    {
        if ($order->isPaid()) {
            throw TransactionException::orderAlreadyPaid($order->reference);
        }

        if ($order->total <= 0) {
            throw TransactionException::invalidAmount();
        }
    }

    /**
     * Convert amount to Kobo (Nigerian currency smallest unit)
     *
     * @param float $amount
     * @return int
     */
    private function convertToKobo(float $amount): int
    {
        return (int) ($amount * 100);
    }
}
