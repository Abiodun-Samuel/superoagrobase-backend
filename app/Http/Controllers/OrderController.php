<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        private CartService $cartService
    ) {}

    protected function getCartItems($sessionId, $userId)
    {
        $cart = $this->cartService->getCart($sessionId, $userId);

        if (!$cart) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(
                'No active cart found. Please add items to your cart.'
            );
        }
        $data = $cart->items; //['items' => ->toArray()];
        return $data;
    }

    public function completeOrder(OrderRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            $cartItems = $this->getCartItems($user?->id, $validated['session_id']);

            // if (empty($cartItems)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Your cart is empty',
            //     ], 400);
            // }

            $order = $this->orderService->createOrder($user, $validated, $validated['items']);
            return  $this->successResponse($order, 'Order has created successfully', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return  $this->successResponse('Failed:' . $e->getMessage(), Response::HTTP_CREATED);
        }
    }
}
