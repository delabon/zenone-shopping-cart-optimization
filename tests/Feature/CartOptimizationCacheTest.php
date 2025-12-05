<?php

declare(strict_types=1);

use App\Models\OptimizationWeight;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\NewCart;
use Tests\NewDistributor;
use Tests\NewDistributorProduct;
use Tests\NewProduct;
use Tests\NewUser;

beforeEach(function () {
    $this->seed();
    Cache::flush(); // Clear cache before each test
});

/**
 * Build cache key and tags for distributor products (same logic as in OptimizeCartAction)
 */
function buildCacheKeyAndTags(array $distributorProductIds): array
{
    $cacheKey = 'cart_alternatives:' . md5(implode(',', $distributorProductIds));
    $tags = array_merge(
        ['cart_alternatives'],
        array_map(fn($id) => "distributor_product_{$id}", $distributorProductIds)
    );

    return [$cacheKey, $tags];
}

it('caches cart alternatives and reuses them on subsequent requests', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->budget()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'Test Product',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributorProduct1 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10000,
        'delivery_days' => 3,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 12000,
        'delivery_days' => 5,
    ])->distributorProduct;

    $newCart->addItem($this, $distributorProduct2->id, 1, $newUser->token);

    // First request - creates cache
    $response1 = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: ['weight_preset' => $optimizationWeight->name],
        headers: ['Authorization' => 'Bearer '.$newUser->token]
    );

    $response1->assertOk();

    [$cacheKey, $tags] = buildCacheKeyAndTags([$distributorProduct2->id]);

    expect(Cache::tags($tags)->has($cacheKey))->toBeTrue();

    // Second request - should use cached data
    $response2 = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: ['weight_preset' => $optimizationWeight->name],
        headers: ['Authorization' => 'Bearer '.$newUser->token]
    );

    $response2->assertOk();

    expect(Cache::tags($tags)->has($cacheKey))->toBeTrue();

    // Both responses should have identical optimization suggestions (proves cache works)
    expect($response1->json('data.changes'))->toEqual($response2->json('data.changes'));
    expect($response1->json('data.total_savings'))->toEqual($response2->json('data.total_savings'));
});

it('invalidates cache when distributor product is updated', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->budget()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'Test Product',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributorProduct1 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10000,
        'delivery_days' => 3,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 12000,
        'delivery_days' => 5,
    ])->distributorProduct;

    $newCart->addItem($this, $distributorProduct2->id, 1, $newUser->token);

    // First request - cache the alternatives
    $response1 = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: ['weight_preset' => $optimizationWeight->name],
        headers: ['Authorization' => 'Bearer '.$newUser->token]
    );

    $response1->assertOk();
    $originalSavings = $response1->json('data.total_savings');

    [$cacheKey, $tags] = buildCacheKeyAndTags([$distributorProduct2->id]);

    expect(Cache::tags($tags)->has($cacheKey))->toBeTrue();

    // Update distributor product price - triggers cache invalidation
    $distributorProduct1->update(['price' => 9000]); // Lower price = more savings

    expect(Cache::tags($tags)->has($cacheKey))->toBeFalse();

    // Second request - should get fresh data after cache invalidation
    $response2 = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: ['weight_preset' => $optimizationWeight->name],
        headers: ['Authorization' => 'Bearer '.$newUser->token]
    );

    $response2->assertOk();
    $newSavings = $response2->json('data.total_savings');

    expect(Cache::tags($tags)->has($cacheKey))->toBeTrue();

    // Savings should be different after price update (proves cache was invalidated)
    expect($newSavings)->toBeGreaterThan($originalSavings);
});

it('shares cache across different users with same products', function () {
    $optimizationWeight = OptimizationWeight::query()
        ->budget()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'Shared Product',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributorProduct1 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10000,
        'delivery_days' => 3,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 12000,
        'delivery_days' => 5,
    ])->distributorProduct;

    // User 1
    $user1 = new NewUser();
    $cart1 = new NewCart($user1->user);
    $cart1->addItem($this, $distributorProduct2->id, 1, $user1->token);

    // User 2
    $user2 = new NewUser();
    $cart2 = new NewCart($user2->user);
    $cart2->addItem($this, $distributorProduct2->id, 1, $user2->token);

    [$cacheKey, $tags] = buildCacheKeyAndTags([$distributorProduct2->id]);

    expect(Cache::tags($tags)->has($cacheKey))->toBeFalse();

    // User 1 request - creates cache
    $response1 = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: ['weight_preset' => $optimizationWeight->name],
        headers: ['Authorization' => 'Bearer '.$user1->token]
    );

    $response1->assertOk();

    expect(Cache::tags($tags)->has($cacheKey))->toBeTrue();

    // User 2 request - should use same cache (same products)
    $response2 = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: ['weight_preset' => $optimizationWeight->name],
        headers: ['Authorization' => 'Bearer '.$user2->token]
    );

    $response2->assertOk();

    expect(Cache::tags($tags)->has($cacheKey))->toBeTrue();

    // Both should have same optimization suggestions (proves cache is shared)
    expect($response1->json('data.changes'))->toEqual($response2->json('data.changes'));
});
