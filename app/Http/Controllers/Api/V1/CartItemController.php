<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cart\AddToCartAction;
use App\Http\Requests\AddToCartRequest;
use App\Http\Resources\CartResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class CartItemController
{
    public function store(AddToCartRequest $request, AddToCartAction $action): Response|JsonResponse
    {
        $cart = $request->user()->currentCart;

        abort_if(! $cart, Response::HTTP_NOT_FOUND, 'Cart not found.');

        try {
            $action->execute($cart, $request->toDto());

            return new JsonResponse(new CartResource($cart->refresh()), Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
