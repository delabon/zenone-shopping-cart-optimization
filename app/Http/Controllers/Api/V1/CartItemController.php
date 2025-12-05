<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cart\AddToCartAction;
use App\Http\Requests\AddToCartRequest;
use App\Http\Resources\CartResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

final class CartItemController
{
    public function store(AddToCartRequest $request, AddToCartAction $action): Response|JsonResponse
    {
        $cart = $request->user()->currentCart;

        try {
            $action->execute($cart, $request->toDto());

            return new JsonResponse(new CartResource($cart->refresh()), Response::HTTP_CREATED);
        } catch (Throwable $e) {
            return new Response($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
