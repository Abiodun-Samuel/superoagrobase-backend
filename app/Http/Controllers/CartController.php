<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertCartRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(
            [
                'user_id' => 'nullable|string|max:255',
                'session_id' => 'required|string|uuid'
            ]
        );

        $cart = $this->cartService->getCart($request->user_id, $request->session_id);

        if (!$cart) {
            return $this->successResponse(
                ['items' => []],
                'Cart is empty'
            );
        }
        return $this->successResponse(new CartResource($cart), 'Cart retrieved successfully');
    }

    public function store(UpsertCartRequest $request): JsonResponse
    {
        try {
            $result = $this->cartService->addItem($request->validated());
            return $this->successResponse(
                new CartItemResource($result['cart_item']),
                $result['message'],
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_CONFLICT
            );
        }
    }
    public function update(UpsertCartRequest $request, int $cartItemId): JsonResponse
    {
        try {
            $result = $this->cartService->updateItem($cartItemId, $request->validated());

            return $this->successResponse(
                new CartItemResource($result['cart_item']),
                $result['message'],
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_FORBIDDEN
            );
        }
    }
    public function destroy(Request $request, int $cartItemId): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string', 'uuid'],
        ]);

        try {
            $result = $this->cartService->removeItem(
                $cartItemId,
                $request->user_id,
                $request->session_id
            );

            return $this->successResponse(
                null,
                $result['message']
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_FORBIDDEN
            );
        }
    }
    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string', 'uuid'],
        ]);
        $this->cartService->clearCart(
            $request->user_id,
            $request->session_id
        );
        return $this->successResponse(
            null,
            'Cart has been cleared successfully'
        );
    }
}
