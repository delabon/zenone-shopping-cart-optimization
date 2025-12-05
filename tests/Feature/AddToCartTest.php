<?php

declare(strict_types=1);

use App\Models\CartItem;
use App\Models\DistributorProduct;
use \Illuminate\Http\Response;
use Tests\NewDistributor;
use Tests\NewDistributorProduct;
use Tests\NewProduct;
use Tests\NewUser;

use function Pest\Laravel\assertDatabaseCount;

test('adds to cart successfully', function () {
    $newUser = new NewUser();
    $cart = $newUser->withCart();
    $product = new NewProduct()->product;
    $distributor = new NewDistributor()->distributor;
    $distributorProduct = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor->id,
        'in_stock' => true,
        'stock_quantity' => 100,
    ])->distributorProduct;

    $this->actingAs($newUser->user);

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
        ]
    );

    $cart->refresh();

    $response->assertCreated()
        ->assertJson([
            'guid' => $cart->guid,
            'items_count' => 1,
            'total' => $distributorProduct->price,
            'status' => $cart->status->value,
        ]);

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 1);

    $items = $cart->items;
    expect($items)->toHaveCount(1)
        ->and($items[0])->toBeInstanceOf(CartItem::class)
        ->and($items[0]->distributorProduct)->toBeInstanceOf(DistributorProduct::class)
        ->and($items[0]->distributorProduct->id)->toBe($distributorProduct->id)
        ->and($items[0]->original_distributor_product_id)->toBeNull() // not optimized yet
        ->and($items[0]->is_optimized)->toBeFalse()
        ->and($items[0]->quantity)->toBe(1)
        ->and($items[0]->unit_price)->toBe($distributorProduct->price)
        ->and($items[0]->subtotal)->toBe($distributorProduct->price);
});

it('increases the quantity when adding product multiple times', function () {
    $newUser = new NewUser();
    $cart = $newUser->withCart();
    $product = new NewProduct()->product;
    $distributor = new NewDistributor()->distributor;
    $distributorProduct = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor->id,
        'in_stock' => true,
        'stock_quantity' => 100,
    ])->distributorProduct;

    $this->actingAs($newUser->user);

    // First request

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
        ]
    );

    $cart->refresh();

    $response->assertCreated()
        ->assertJson([
            'guid' => $cart->guid,
            'items_count' => 1,
            'total' => $distributorProduct->price,
            'status' => $cart->status->value,
        ]);

    // Second request

    $response2 = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 4,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
        ]
    );

    $cart->refresh();

    $response2->assertCreated()
        ->assertJson([
            'guid' => $cart->guid,
            'items_count' => 5,
            'total' => $distributorProduct->price * 5,
            'status' => $cart->status->value,
        ]);

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 1); // important not to add a duplicate
});

it('does not increase the quantity when reached max quantity', function () {
    $newUser = new NewUser();
    $cart = $newUser->withCart();
    $product = new NewProduct()->product;
    $distributor = new NewDistributor()->distributor;
    $distributorProduct = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor->id,
        'in_stock' => true,
        'stock_quantity' => 2,
    ])->distributorProduct;

    $this->actingAs($newUser->user);

    // First request

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
        ]
    );

    $cart->refresh();

    $response->assertCreated()
        ->assertJson([
            'guid' => $cart->guid,
            'items_count' => 1,
            'total' => $distributorProduct->price,
            'status' => $cart->status->value,
        ]);

    // Second request

    $response2 = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 6,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
        ]
    );

    $cart->refresh();

    $response2->assertCreated()
        ->assertJson([
            'guid' => $cart->guid,
            'items_count' => 2,
            'total' => $distributorProduct->price * 2,
            'status' => $cart->status->value,
        ]);

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 1); // important not to add a duplicate
});

it('fails when unauthorized', function () {
    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => 1,
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
    $newUser = new NewUser();
    $product = new NewProduct()->product;
    $distributor = new NewDistributor()->distributor;
    $distributorProduct = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor->id,
        'in_stock' => true,
        'stock_quantity' => 2,
    ])->distributorProduct;

    $this->actingAs($newUser->user);

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
        ]
    );

    $response->assertStatus(Response::HTTP_FORBIDDEN);

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
    $newUser = new NewUser();
    $newUser->withCart();

    $this->actingAs($newUser->user);

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $invalidId,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
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
    $newUser = new NewUser();
    $cart = $newUser->withCart();
    $product = new NewProduct()->product;
    $distributor = new NewDistributor()->distributor;
    $distributorProduct = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor->id,
        'in_stock' => false,
        'stock_quantity' => 0,
    ])->distributorProduct;

    $this->actingAs($newUser->user);

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
        ]
    );

    $response->assertUnprocessable();
    expect($response->getContent())->toBe('Product out of stock.');

    assertDatabaseCount('carts', 1);
    assertDatabaseCount('cart_items', 0);
});

it('returns too many requests when a user is trying to add more than 40 items to cart in one minute', function () {
    $newUser = new NewUser();
    $newUser->withCart();
    $product = new NewProduct()->product;
    $distributor = new NewDistributor()->distributor;
    $distributorProduct = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor->id,
        'in_stock' => true,
        'stock_quantity' => 1000,
    ])->distributorProduct;

    $this->actingAs($newUser->user);

    for ($i = 0; $i < 40; $i++) {
        $response = $this->postJson(
            uri: '/api/v1/cart/items',
            data: [
                'distributor_product_id' => $distributorProduct->id,
                'quantity' => 1,
            ],
            headers: [
                'Authorization' => 'Bearer ' . $newUser->token,
            ]
        );
        $response->assertCreated();
    }

    $response = $this->postJson(
        uri: '/api/v1/cart/items',
        data: [
            'distributor_product_id' => $distributorProduct->id,
            'quantity' => 1,
        ],
        headers: [
            'Authorization' => 'Bearer ' . $newUser->token,
        ]
    );
    $response->assertTooManyRequests();
});
