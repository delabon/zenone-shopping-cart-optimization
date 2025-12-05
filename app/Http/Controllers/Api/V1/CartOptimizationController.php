<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cart\OptimizeCartAction;
use App\Http\Requests\OptimizeCartRequest;
use App\Http\Resources\OptimizationSessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

final class CartOptimizationController
{
    public function optimize(
        OptimizeCartRequest $request,
        OptimizeCartAction $action
    ): Response|JsonResponse {
        try {
            $session = $action->execute($request->user()->currentCart, $request->toDto());

            // Eager load relationships needed by the resource
            $session->load([
                'changes.originalDistributorProduct.product',
                'changes.originalDistributorProduct.distributor',
                'changes.suggestedDistributorProduct.product',
                'changes.suggestedDistributorProduct.distributor',
                'cart.items',
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => new OptimizationSessionResource($session),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return new Response([
                'success' => false,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
