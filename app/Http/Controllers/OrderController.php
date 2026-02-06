<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Exceptions\TransactionException;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    private string $frontendUrl;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly CartService $cartService,
        private readonly TransactionService $transactionService
    ) {
        $this->frontendUrl = config('app.frontendUrl');
    }

    public function index(Request $request): JsonResponse
    {
        $isAdmin = $request->user()->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value]);

        $userId = $isAdmin ? null : $request->user()->id;

        $filters = $request->only([
            'reference',
            'status',
            'payment_status',
            'payment_method',
            'delivery_method',
            'user_id',
            'from_date',
            'to_date',
            'min_total',
            'max_total',
            'search',
            'sort_by',
            'sort_direction',
        ]);

        $orders = $this->orderService->getOrders($filters, $userId);

        return $this->successResponse(
            OrderResource::collection($orders),
            'Orders retrieved successfully'
        );
    }

    public function completeOrder(OrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        try {
            $cartItems = $this->getCartItems($validated['session_id'], $user?->id);

            if (empty($cartItems)) {
                return $this->errorResponse(
                    'Your cart is empty. Please add items before checkout.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $order = $this->orderService->createOrder($user, $validated, $cartItems);

            if (!$order) {
                throw new \Exception('Failed to create order');
            }

            $redirectUrl = $this->getRedirectUrl($order);

            if ($order->payment_method === 'online') {
                try {
                    $authorizationUrl = $this->transactionService->initializeTransaction($order);
                    $redirectUrl = $authorizationUrl;
                } catch (TransactionException $e) {
                    $order->update(['payment_status' => 'failed']);
                    throw $e;
                }
            }

            $this->cartService->clearCart($validated['session_id'], $user?->id);

            return $this->successResponse(
                [
                    'order' => new OrderResource($order),
                    'redirectUrl' => $redirectUrl
                ],
                'Order created successfully',
                Response::HTTP_CREATED
            );
        } catch (TransactionException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to complete order. Please try again.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'transactions']);
        return $this->successResponse(
            new OrderResource($order),
            'Order fetch successfully',
            Response::HTTP_OK
        );
    }

    private function getCartItems(string $sessionId, ?string $userId): array
    {
        $cart = $this->cartService->getCart($sessionId, $userId);

        if (!$cart || $cart->items->isEmpty()) {
            throw new \Exception('No active cart found. Please add items to your cart.');
        }

        return $cart->items->toArray();
    }

    private function getRedirectUrl($order): string
    {
        return $this->frontendUrl . '/account/orders/' . $order->reference;
    }
}
