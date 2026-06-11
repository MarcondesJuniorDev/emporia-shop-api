<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

test('it blocks access to order endpoints if unauthenticated', function () {
    $this->getJson('/api/orders')->assertStatus(401);
    $this->getJson('/api/orders/1')->assertStatus(401);
    $this->postJson('/api/orders', [])->assertStatus(401);
});

test('it lists only orders belonging to the authenticated user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $orderA = Order::factory()->create(['user_id' => $userA->id]);
    Order::factory()->create(['user_id' => $userB->id]);

    $response = $this->actingAs($userA, 'sanctum')->getJson('/api/orders');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $orderA->id);
});

test('it displays details of an order belonging to the user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100.00]);
    $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 200.00]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 100.00,
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/orders/'.$order->id);

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonPath('data.total_amount', 200)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.product_name', $product->name);
});

test('it blocks displaying details of an order belonging to another user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $userB->id]);

    $response = $this->actingAs($userA, 'sanctum')->getJson('/api/orders/'.$order->id);

    $response->assertStatus(403);
});

test('it places an order successfully and updates stock', function () {
    $user = User::factory()->create();
    $product1 = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true]);
    $product2 = Product::factory()->create(['price' => 150.00, 'stock' => 5, 'is_active' => true]);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', [
        'shipping_address' => 'Rua das Flores, 123',
        'items' => [
            ['product_id' => $product1->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.total_amount', 250)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.shipping_address', 'Rua das Flores, 123')
        ->assertJsonCount(2, 'data.items');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'total_amount' => 250,
        'shipping_address' => 'Rua das Flores, 123',
    ]);

    // Check stock reduction
    expect($product1->fresh()->stock)->toBe(8);
    expect($product2->fresh()->stock)->toBe(4);
});

test('it fails checkout if validation rules fail', function () {
    $user = User::factory()->create();

    // Empty payload
    $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['shipping_address', 'items']);

    // Empty items
    $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', [
        'shipping_address' => 'Rua Flores',
        'items' => [],
    ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('it fails checkout and rolls back transaction if stock is insufficient', function () {
    $user = User::factory()->create();
    $product1 = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => true]);
    $product2 = Product::factory()->create(['price' => 150.00, 'stock' => 5, 'is_active' => true]);

    // product 2 requires quantity 10 but has only 5
    $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', [
        'shipping_address' => 'Rua das Flores, 123',
        'items' => [
            ['product_id' => $product1->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 10], // Insufficient stock
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);

    // Check database transactions rolled back: product 1 stock should NOT have decreased
    expect($product1->fresh()->stock)->toBe(10);
    expect($product2->fresh()->stock)->toBe(5);

    // No order should be created
    expect(Order::count())->toBe(0);
    expect(OrderItem::count())->toBe(0);
});

test('it fails checkout if product is inactive', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 50.00, 'stock' => 10, 'is_active' => false]);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', [
        'shipping_address' => 'Rua das Flores, 123',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
    expect(Order::count())->toBe(0);
});
