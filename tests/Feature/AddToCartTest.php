<?php

declare(strict_types=1);

use App\Models\CartItem;
use App\Models\DistributorProduct;
use Database\Factories\UserFactory;
use Tests\NewCart;
use Tests\NewDistributor;

use function Pest\Laravel\assertDatabaseCount;

test('adds to cart successfully', function () {
    $cart = new NewCart();
    $user = $cart->user;
    $token = $user->createToken('Public API Key', ['use-public-api']);
    $this->actingAs($user);
    $distributor = new NewDistributor(distributorProductAttributes: [
        'in_stock' => true,
        'stock_quantity' => 100,
    ]);
    $distributorProduct = $distributor->distributorProduct;

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]
    );

    $cart->cart->refresh();

    $response->assertCreated()
        ->assertJson([
            'guid' => $cart->cart->guid,
            'items_count' => 1,
            'total' => $distributorProduct->price,
            'status' => $cart->cart->status->value,
        ]);

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 1);

    $items = $cart->cart->items;
    expect($items)->toHaveCount(1)
        ->and($items[0])->toBeInstanceOf(CartItem::class)
        ->and($items[0]->distributorProduct)->toBeInstanceOf(DistributorProduct::class)
        ->and($items[0]->distributorProduct->id)->toBe($distributor->distributorProduct->id)
        ->and($items[0]->original_distributor_product_id)->toBeNull() // not optimized yet
        ->and($items[0]->is_optimized)->toBeFalse()
        ->and($items[0]->quantity)->toBe(1)
        ->and($items[0]->unit_price)->toBe($distributor->distributorProduct->price)
        ->and($items[0]->subtotal)->toBe($distributor->distributorProduct->price);
});

it('increases the quantity when adding product multiple times', function () {
    $cart = new NewCart();
    $user = $cart->user;
    $token = $user->createToken('Public API Key', ['use-public-api']);
    $this->actingAs($user);
    $distributor = new NewDistributor(distributorProductAttributes: [
        'in_stock' => true,
        'stock_quantity' => 100,
    ]);
    $distributorProduct = $distributor->distributorProduct;

    // First request

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]
    );

    $cart->cart->refresh();

    $response->assertCreated()
        ->assertJson([
            'guid' => $cart->cart->guid,
            'items_count' => 1,
            'total' => $distributorProduct->price,
            'status' => $cart->cart->status->value,
        ]);

    // Second request

    $response2 = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 4,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]
    );

    $cart->cart->refresh();

    $response2->assertCreated()
        ->assertJson([
            'guid' => $cart->cart->guid,
            'items_count' => 5,
            'total' => $distributorProduct->price * 5,
            'status' => $cart->cart->status->value,
        ]);

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 1); // important not to add a duplicate
});

it('does not increase the quantity when reached max quantity', function () {
    $cart = new NewCart();
    $user = $cart->user;
    $token = $user->createToken('Public API Key', ['use-public-api']);
    $this->actingAs($user);
    $distributor = new NewDistributor(distributorProductAttributes: [
        'in_stock' => true,
        'stock_quantity' => 2,
    ]);
    $distributorProduct = $distributor->distributorProduct;

    // First request

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]
    );

    $cart->cart->refresh();

    $response->assertCreated()
        ->assertJson([
            'guid' => $cart->cart->guid,
            'items_count' => 1,
            'total' => $distributorProduct->price,
            'status' => $cart->cart->status->value,
        ]);

    // Second request

    $response2 = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 6,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]
    );

    $cart->cart->refresh();

    $response2->assertCreated()
        ->assertJson([
            'guid' => $cart->cart->guid,
            'items_count' => 2,
            'total' => $distributorProduct->price * 2,
            'status' => $cart->cart->status->value,
        ]);

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 1); // important not to add a duplicate
});

it('fails when unauthorized', function () {
    UserFactory::new()->create();
    $distributor = new NewDistributor();
    $distributorProduct = $distributor->distributorProduct;

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer 213lkjl21lkdlk12eoi12jd2j1odou',
        ]
    );

    $response->assertUnauthorized();

    assertDatabaseCount('carts', 0);
    assertDatabaseCount('cart_items', 0);
});

it('fails when a user has no cart', function () {
    $user = UserFactory::new()->create();
    $token = $user->createToken('Public API Key', ['use-public-api']);
    $this->actingAs($user);
    $distributor = new NewDistributor();
    $distributorProduct = $distributor->distributorProduct;

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]
    );

    $response->assertNotFound();

    assertDatabaseCount('carts', 0);
    assertDatabaseCount('cart_items', 0);
});

dataset('invalid_distributor_product_id', [
    [
        null, // missing
        'The distributor product id field is required.'
    ],
    [
        true, // invalid
        'The selected distributor product id is invalid.'
    ],
    [
        123123, // not found
        'The selected distributor product id is invalid.'
    ],
]);

it('fails with invalid distributor_product_id data', function (mixed $invalidId, string $expectedMessage) {
    $cart = new NewCart();
    $user = $cart->user;
    $token = $user->createToken('Public API Key', ['use-public-api']);
    $this->actingAs($user);

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $invalidId,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]
    );

    $response->assertUnprocessable();

    $response->assertJsonValidationErrors([
        'distributor_product_id' => $expectedMessage
    ]);

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 0);
})->with('invalid_distributor_product_id');

it('fails when a user is trying to add an out of stock product', function () {
    $cart = new NewCart();
    $user = $cart->user;
    $token = $user->createToken('Public API Key', ['use-public-api']);
    $this->actingAs($user);
    $distributor = new NewDistributor();
    $distributor->distributorProduct->update([
        'in_stock' => false,
        'stock_quantity' => 0,
    ]);

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributor->distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ]
    );

    $response->assertUnprocessable();
    expect($response->getContent())->toBe('Product out of stock.');

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 0);
});
