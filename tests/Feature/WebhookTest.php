<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

test('it blocks webhook request with invalid token', function () {
    $response = $this->postJson('/api/webhooks/payment', [], [
        'X-Webhook-Token' => 'invalid-token',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Não autorizado.');
});

test('it processes approved payment and changes order status to paid', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

    $response = $this->postJson('/api/webhooks/payment', [
        'order_id' => $order->id,
        'status' => 'approved',
    ], [
        'X-Webhook-Token' => 'emporia-secret-token',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Webhook processado com sucesso.');

    expect($order->fresh()->status)->toBe('paid');
});

test('it processes declined payment, cancels order, and restores stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $response = $this->postJson('/api/webhooks/payment', [
        'order_id' => $order->id,
        'status' => 'declined',
    ], [
        'X-Webhook-Token' => 'emporia-secret-token',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Webhook processado com sucesso.');

    expect($order->fresh()->status)->toBe('cancelled');
    // Stock should have been restored by 3 units (from 10 to 13)
    expect($product->fresh()->stock)->toBe(13);
});

test('it processes refunded payment, cancels order, and restores stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5]);
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response = $this->postJson('/api/webhooks/payment', [
        'order_id' => $order->id,
        'status' => 'refunded',
    ], [
        'X-Webhook-Token' => 'emporia-secret-token',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Webhook processado com sucesso.');

    expect($order->fresh()->status)->toBe('cancelled');
    // Stock should have been restored by 2 units (from 5 to 7)
    expect($product->fresh()->stock)->toBe(7);
});

test('it fails webhook validation with bad inputs', function () {
    $response = $this->postJson('/api/webhooks/payment', [
        'order_id' => 99999, // Non-existent order
        'status' => 'invalid-status',
    ], [
        'X-Webhook-Token' => 'emporia-secret-token',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['order_id', 'status']);
});
