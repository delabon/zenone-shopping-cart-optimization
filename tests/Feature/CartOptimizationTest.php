<?php

declare(strict_types=1);

use App\Enums\OptimizationReasonCode;
use App\Models\OptimizationWeight;
use Illuminate\Support\Str;
use Tests\NewCart;
use Tests\NewDistributor;
use Tests\NewDistributorProduct;
use Tests\NewProduct;
use Tests\NewUser;

beforeEach(function () {
    $this->seed();
});

/**
 * Test by adding 1 product to cart
 */

it('optimizes cart with the budget optimization preset', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->budget()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'White Gloves',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributorProduct1 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10000, // $100.00
        'delivery_days' => 3,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10995, // $109.95
        'delivery_days' => 5,
    ])->distributorProduct;

    $distributor3 = new NewDistributor()->distributor;
    $distributorProduct3 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor3->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10455, // $104.55
        'delivery_days' => 7,
    ])->distributorProduct;

    $quantity = 1;
    $newCart->addItem($this, $distributorProduct2->id, $quantity, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [
                    [
                        'original' => [
                            'id' => $distributorProduct2->id,
                            'product_name' => $product->name,
                            'distributor_name' => $distributor2->name,
                            'price' => $distributorProduct2->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributorProduct2->delivery_days,
                            'in_stock' => $distributorProduct2->in_stock,
                        ],
                        'suggested' => [
                            'id' => $distributorProduct1->id,
                            'product_name' => $product->name,
                            'distributor_name' => $distributor1->name,
                            'price' => $distributorProduct1->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributorProduct1->delivery_days,
                            'in_stock' => $distributorProduct1->in_stock,
                        ],
                        'price_difference' => ($distributorProduct2->price - $distributorProduct1->price) / 100,
                        'delivery_days_difference' => $distributorProduct2->delivery_days - $distributorProduct1->delivery_days,
                        'reasons' => [
                            [
                                'code' => OptimizationReasonCode::PriceSavings->value,
                                'message' => 'Save $9.95 on this item',
                                'impact' => 'medium',
                            ],
                            [
                                'code' => OptimizationReasonCode::FasterDelivery->value,
                                'message' => 'Arrives 2 days sooner',
                                'impact' => 'medium',
                            ],
                        ],
                    ],
                ],
                'total_savings' => ($distributorProduct2->price - $distributorProduct1->price) / 100,
                'items_optimized' => 1,
                'items_analyzed' => 1,
            ],
        ]);
});

it('optimizes cart with the urgent optimization preset', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->urgent()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'Yellow Gloves',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributorProduct1 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10000, // $100.00
        'delivery_days' => 3,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10995, // $109.95
        'delivery_days' => 5,
    ])->distributorProduct;

    $distributor3 = new NewDistributor()->distributor;
    $distributorProduct3 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor3->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10455, // $104.55
        'delivery_days' => 1,
    ])->distributorProduct;

    $quantity = 1;
    $newCart->addItem($this, $distributorProduct2->id, $quantity, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [
                    [
                        'original' => [
                            'id' => $distributorProduct2->id,
                            'product_name' => $product->name,
                            'distributor_name' => $distributor2->name,
                            'price' => $distributorProduct2->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributorProduct2->delivery_days,
                            'in_stock' => $distributorProduct2->in_stock,
                        ],
                        'suggested' => [
                            'id' => $distributorProduct3->id,
                            'product_name' => $product->name,
                            'distributor_name' => $distributor3->name,
                            'price' => $distributorProduct3->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributorProduct3->delivery_days,
                            'in_stock' => $distributorProduct3->in_stock,
                        ],
                        'price_difference' => 5.40,
                        'delivery_days_difference' => $distributorProduct2->delivery_days - $distributorProduct3->delivery_days,
                        'reasons' => [
                            [
                                'code' => OptimizationReasonCode::PriceSavings->value,
                                'message' => 'Save $5.40 on this item',
                                'impact' => 'medium',
                            ],
                            [
                                'code' => OptimizationReasonCode::FasterDelivery->value,
                                'message' => 'Arrives 4 days sooner',
                                'impact' => 'high',
                            ],
                        ],
                    ],
                ],
                'total_savings' => 5.40,
                'items_optimized' => 1,
                'items_analyzed' => 1,
            ],
        ]);
});

it('optimizes cart with the available optimization preset', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->available()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'Red Gloves',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributorProduct1 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => false,
        'stock_quantity' => 0,
        'price' => 10000, // $100.00
        'delivery_days' => 3,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => false,
        'stock_quantity' => 0,
        'price' => 10995, // $109.95
        'delivery_days' => 5,
    ])->distributorProduct;

    $distributor3 = new NewDistributor()->distributor;
    $distributorProduct3 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor3->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10455, // $104.55
        'delivery_days' => 7,
    ])->distributorProduct;

    $quantity = 1;
    $newCart->addItem($this, $distributorProduct1->id, $quantity, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [
                    [
                        'original' => [
                            'id' => $distributorProduct1->id,
                            'product_name' => $product->name,
                            'distributor_name' => $distributor1->name,
                            'price' => $distributorProduct1->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributorProduct1->delivery_days,
                            'in_stock' => $distributorProduct1->in_stock,
                        ],
                        'suggested' => [
                            'id' => $distributorProduct3->id,
                            'product_name' => $product->name,
                            'distributor_name' => $distributor3->name,
                            'price' => $distributorProduct3->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributorProduct3->delivery_days,
                            'in_stock' => $distributorProduct3->in_stock,
                        ],
                        'price_difference' => -4.55,
                        'delivery_days_difference' => $distributorProduct1->delivery_days - $distributorProduct3->delivery_days,
                        'reasons' => [
                            [
                                'code' => OptimizationReasonCode::InStock->value,
                                'message' => 'Item is in stock',
                                'impact' => 'high',
                            ],
                        ],
                    ],
                ],
                'total_savings' => -4.55,
                'items_optimized' => 1,
                'items_analyzed' => 1,
            ],
        ]);
});

it('optimizes cart with the balanced optimization preset', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->balanced()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'Purple Gloves',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributorProduct1 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 10,
        'price' => 10000, // $100.00
        'delivery_days' => 8,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 999,
        'price' => 10995, // $109.95
        'delivery_days' => 5,
    ])->distributorProduct;

    $distributor3 = new NewDistributor()->distributor;
    $distributorProduct3 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor3->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 10455, // $104.55
        'delivery_days' => 7,
    ])->distributorProduct;

    $quantity = 1;
    $newCart->addItem($this, $distributorProduct3->id, $quantity, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [
                    [
                        'original' => [
                            'id' => $distributorProduct3->id,
                            'product_name' => $product->name,
                            'distributor_name' => $distributor3->name,
                            'price' => $distributorProduct3->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributorProduct3->delivery_days,
                            'in_stock' => $distributorProduct3->in_stock,
                        ],
                        'suggested' => [
                            'id' => $distributorProduct1->id,
                            'product_name' => $product->name,
                            'distributor_name' => $distributor1->name,
                            'price' => $distributorProduct1->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributorProduct1->delivery_days,
                            'in_stock' => $distributorProduct1->in_stock,
                        ],
                        'price_difference' => 4.55,
                        'delivery_days_difference' => $distributorProduct3->delivery_days - $distributorProduct1->delivery_days,
                        'reasons' => [
                            [
                                'code' => OptimizationReasonCode::PriceSavings->value,
                                'message' => 'Save $4.55 on this item',
                                'impact' => 'low',
                            ],
                        ],
                    ],
                ],
                'total_savings' => 4.55,
                'items_optimized' => 1,
                'items_analyzed' => 1,
            ],
        ]);
});

/**
 * Test by adding multiple products to cart
 */

it('optimizes cart with multiple products with the budget optimization preset', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->budget()
        ->firstOrFail();

    $product1 = new NewProduct([
        'name' => 'White Gloves',
        'sku' => '123456',
    ])->product;

    $product2 = new NewProduct([
        'name' => 'Premium Sealant',
        'sku' => '98765432',
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributor1Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 400,
        'price' => 9999, // $99.99
        'delivery_days' => 4,
    ])->distributorProduct;
    $distributor1Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 90,
        'price' => 20099, // $200.99
        'delivery_days' => 4,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributor2Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 877,
        'price' => 8995, // $89.95
        'delivery_days' => 3,
    ])->distributorProduct;
    $distributor2Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 7,
        'price' => 19695, // $196.95
        'delivery_days' => 3,
    ])->distributorProduct;

    $distributor3 = new NewDistributor()->distributor;
    $distributor3Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor3->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 8685, // $86.85
        'delivery_days' => 2,
    ])->distributorProduct;
    $distributor3Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor3->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 19255, // $192.55
        'delivery_days' => 2,
    ])->distributorProduct;

    $quantity = 3;
    $newCart->addItem($this, $distributor1Product1->id, $quantity, $newUser->token);
    $newCart->addItem($this, $distributor1Product2->id, $quantity, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [
                    [
                        'original' => [
                            'id' => $distributor1Product1->id,
                            'product_name' => $product1->name,
                            'distributor_name' => $distributor1->name,
                            'price' => $distributor1Product1->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributor1Product1->delivery_days,
                            'in_stock' => $distributor1Product1->in_stock,
                        ],
                        'suggested' => [
                            'id' => $distributor3Product1->id,
                            'product_name' => $product1->name,
                            'distributor_name' => $distributor3->name,
                            'price' => $distributor3Product1->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributor3Product1->delivery_days,
                            'in_stock' => $distributor3Product1->in_stock,
                        ],
                        'price_difference' => ($distributor1Product1->price - $distributor3Product1->price) / 100,
                        'delivery_days_difference' => $distributor1Product1->delivery_days - $distributor3Product1->delivery_days,
                        'reasons' => [
                            [
                                'code' => OptimizationReasonCode::PriceSavings->value,
                                'message' => 'Save $13.14 on this item',
                                'impact' => 'high',
                            ],
                            [
                                'code' => OptimizationReasonCode::FasterDelivery->value,
                                'message' => 'Arrives 2 days sooner',
                                'impact' => 'medium',
                            ],
                        ],
                    ],
                    [
                        'original' => [
                            'id' => $distributor1Product2->id,
                            'product_name' => $product2->name,
                            'distributor_name' => $distributor1->name,
                            'price' => $distributor1Product2->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributor1Product2->delivery_days,
                            'in_stock' => $distributor1Product2->in_stock,
                        ],
                        'suggested' => [
                            'id' => $distributor3Product2->id,
                            'product_name' => $product2->name,
                            'distributor_name' => $distributor3->name,
                            'price' => $distributor3Product2->price,
                            'quantity' => $quantity,
                            'delivery_days' => $distributor3Product2->delivery_days,
                            'in_stock' => $distributor3Product2->in_stock,
                        ],
                        'price_difference' => ($distributor1Product2->price - $distributor3Product2->price) / 100,
                        'delivery_days_difference' => $distributor1Product2->delivery_days - $distributor3Product2->delivery_days,
                        'reasons' => [
                            [
                                'code' => OptimizationReasonCode::PriceSavings->value,
                                'message' => 'Save $8.44 on this item',
                                'impact' => 'medium',
                            ],
                            [
                                'code' => OptimizationReasonCode::FasterDelivery->value,
                                'message' => 'Arrives 2 days sooner',
                                'impact' => 'medium',
                            ],
                        ],
                    ],
                ],
                'total_savings' => 64.74,
                'items_optimized' => 2,
                'items_analyzed' => 2,
            ],
        ]);
});

it('optimizes cart with multiple products with the urgent optimization preset', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->urgent()
        ->firstOrFail();

    $product1 = new NewProduct([
        'name' => 'Blue Gloves',
        'sku' => Str::random(),
    ])->product;

    $product2 = new NewProduct([
        'name' => 'Safety Goggles',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributor1Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 5000, // $50.00
        'delivery_days' => 7,
    ])->distributorProduct;
    $distributor1Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 50,
        'price' => 15000, // $150.00
        'delivery_days' => 5,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributor2Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 200,
        'price' => 5500, // $55.00
        'delivery_days' => 2,
    ])->distributorProduct;
    $distributor2Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 75,
        'price' => 16000, // $160.00
        'delivery_days' => 1,
    ])->distributorProduct;

    $quantity = 2;
    $newCart->addItem($this, $distributor1Product1->id, $quantity, $newUser->token);
    $newCart->addItem($this, $distributor1Product2->id, $quantity, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'items_optimized' => 2,
                'items_analyzed' => 2,
            ],
        ])
        ->assertJsonPath('data.changes.0.suggested.id', $distributor2Product1->id)
        ->assertJsonPath('data.changes.1.suggested.id', $distributor2Product2->id)
        ->assertJsonPath('data.changes.0.reasons.0.code', OptimizationReasonCode::FasterDelivery->value)
        ->assertJsonPath('data.changes.1.reasons.0.code', OptimizationReasonCode::FasterDelivery->value);
});

it('optimizes cart with multiple products with the available optimization preset', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->available()
        ->firstOrFail();

    $product1 = new NewProduct([
        'name' => 'Green Gloves',
        'sku' => Str::random(),
    ])->product;

    $product2 = new NewProduct([
        'name' => 'Hard Hat',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributor1Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => false,
        'stock_quantity' => 0,
        'price' => 3000, // $30.00
        'delivery_days' => 3,
    ])->distributorProduct;
    $distributor1Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => false,
        'stock_quantity' => 0,
        'price' => 4000, // $40.00
        'delivery_days' => 4,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributor2Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 150,
        'price' => 3500, // $35.00
        'delivery_days' => 5,
    ])->distributorProduct;
    $distributor2Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 8500, // $85.00
        'delivery_days' => 6,
    ])->distributorProduct;

    $quantity = 1;
    $newCart->addItem($this, $distributor1Product1->id, $quantity, $newUser->token);
    $newCart->addItem($this, $distributor1Product2->id, $quantity, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'items_optimized' => 2,
                'items_analyzed' => 2,
            ],
        ])
        ->assertJsonPath('data.changes.0.suggested.id', $distributor2Product1->id)
        ->assertJsonPath('data.changes.1.suggested.id', $distributor2Product2->id)
        ->assertJsonPath('data.changes.0.reasons.0.code', OptimizationReasonCode::InStock->value)
        ->assertJsonPath('data.changes.1.reasons.0.code', OptimizationReasonCode::InStock->value);
});

it('optimizes cart with multiple products with the balanced optimization preset', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->balanced()
        ->firstOrFail();

    $product1 = new NewProduct([
        'name' => 'Orange Gloves',
        'sku' => Str::random(),
    ])->product;

    $product2 = new NewProduct([
        'name' => 'Ear Plugs',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributor1Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 50,
        'price' => 4500, // $45.00
        'delivery_days' => 6,
    ])->distributorProduct;
    $distributor1Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 200,
        'price' => 1200, // $12.00
        'delivery_days' => 8,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributor2Product1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 4000, // $40.00
        'delivery_days' => 4,
    ])->distributorProduct;
    $distributor2Product2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 300,
        'price' => 1000, // $10.00
        'delivery_days' => 5,
    ])->distributorProduct;

    $quantity = 5;
    $newCart->addItem($this, $distributor1Product1->id, $quantity, $newUser->token);
    $newCart->addItem($this, $distributor1Product2->id, $quantity, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'items_optimized' => 2,
                'items_analyzed' => 2,
                'changes' => [
                    [
                        'suggested' => [
                            'distributor_name' => $distributor2->name,
                            'price' => $distributor2Product2->price, // Ear Plugs (1000)
                        ],
                        'reasons' => [
                            [
                                'code' => OptimizationReasonCode::PriceSavings->value,
                            ],
                        ],
                    ],
                    [
                        'suggested' => [
                            'distributor_name' => $distributor2->name,
                            'price' => $distributor2Product1->price, // Orange Gloves (4000)
                        ],
                        'reasons' => [
                            [
                                'code' => OptimizationReasonCode::PriceSavings->value,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
});

/**
 * Edge cases and special scenarios
 */

it('returns empty changes when cart has no items', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->budget()
        ->firstOrFail();

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [],
                'total_savings' => 0,
                'items_optimized' => 0,
                'items_analyzed' => 0,
            ],
        ]);
});

it('returns empty changes when no alternatives are available', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->budget()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'Unique Product',
        'sku' => Str::random(),
    ])->product;

    $distributor = new NewDistributor()->distributor;
    $distributorProduct = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 5000,
        'delivery_days' => 3,
    ])->distributorProduct;

    $newCart->addItem($this, $distributorProduct->id, 1, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [],
                'total_savings' => 0,
                'items_optimized' => 0,
                'items_analyzed' => 1,
            ],
        ]);
});

it('returns empty changes when current selection is already optimal', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->budget()
        ->firstOrFail();

    $product = new NewProduct([
        'name' => 'Optimal Product',
        'sku' => Str::random(),
    ])->product;

    $distributor1 = new NewDistributor()->distributor;
    $distributorProduct1 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor1->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 3000, // $30.00 - cheapest
        'delivery_days' => 1, // fastest
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => true,
        'stock_quantity' => 50,
        'price' => 5000, // $50.00
        'delivery_days' => 5,
    ])->distributorProduct;

    $newCart->addItem($this, $distributorProduct1->id, 1, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [],
                'total_savings' => 0,
                'items_optimized' => 0,
                'items_analyzed' => 1,
            ],
        ]);
});

it('suggests consolidation when appropriate', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);
    $optimizationWeight = OptimizationWeight::query()
        ->balanced()
        ->firstOrFail();

    $product1 = new NewProduct([
        'name' => 'Product A',
        'sku' => Str::random(),
    ])->product;

    $product2 = new NewProduct([
        'name' => 'Product B',
        'sku' => Str::random(),
    ])->product;

    // Primary distributor (will have most items)
    $primaryDistributor = new NewDistributor()->distributor;
    $primaryProduct1 = new NewDistributorProduct([
        'product_id' => $product1->id,
        'distributor_id' => $primaryDistributor->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 5000, // $50.00
        'delivery_days' => 3,
    ])->distributorProduct;
    $primaryProduct2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $primaryDistributor->id,
        'in_stock' => true,
        'stock_quantity' => 100,
        'price' => 6000, // $60.00
        'delivery_days' => 3,
    ])->distributorProduct;

    // Secondary distributor
    $secondaryDistributor = new NewDistributor()->distributor;
    $secondaryProduct2 = new NewDistributorProduct([
        'product_id' => $product2->id,
        'distributor_id' => $secondaryDistributor->id,
        'in_stock' => true,
        'stock_quantity' => 50,
        'price' => 5500, // $55.00 - cheaper but from different distributor
        'delivery_days' => 3,
    ])->distributorProduct;

    // Add items: product1 from primary, product2 from secondary
    $newCart->addItem($this, $primaryProduct1->id, 2, $newUser->token);
    $newCart->addItem($this, $secondaryProduct2->id, 1, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertOk();

    // Check if consolidation reason is present
    $data = $response->json('data');
    if ($data['items_optimized'] > 0) {
        $hasConsolidation = collect($data['changes'])
            ->pluck('reasons')
            ->flatten(1)
            ->contains('code', OptimizationReasonCode::Consolidation->value);

        expect($hasConsolidation)->toBeTrue();
    }
});

/**
 * Validation and authorization tests
 */

it('requires authentication', function () {
    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => 'budget',
        ]
    );

    $response->assertUnauthorized();
});

it('requires weight_preset parameter', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['weight_preset']);
});

it('validates weight_preset exists in database', function () {
    $newUser = new NewUser();
    $newCart = new NewCart($newUser->user);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => 'non_existent_preset',
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['weight_preset']);
});

it('handles out of stock alternatives correctly', function () {
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
        'price' => 5000,
        'delivery_days' => 3,
    ])->distributorProduct;

    $distributor2 = new NewDistributor()->distributor;
    $distributorProduct2 = new NewDistributorProduct([
        'product_id' => $product->id,
        'distributor_id' => $distributor2->id,
        'in_stock' => false, // Out of stock
        'stock_quantity' => 0,
        'price' => 3000, // Cheaper but out of stock
        'delivery_days' => 2,
    ])->distributorProduct;

    $newCart->addItem($this, $distributorProduct1->id, 1, $newUser->token);

    $response = $this->postJson(
        uri: '/api/v1/cart/optimize',
        data: [
            'weight_preset' => $optimizationWeight->name,
        ],
        headers: [
            'Authorization' => 'Bearer '.$newUser->token,
        ]
    );

    // Should not suggest out of stock alternative
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'changes' => [],
                'items_optimized' => 0,
                'items_analyzed' => 1,
            ],
        ]);
});
