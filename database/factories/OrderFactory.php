<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
/**
 * @extends Factory<Order>
 */
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'paid', 'shipped', 'delivered', 'cancelled']),
            'total_amount' => fake()->randomFloat(2, 10, 1500),
            'shipping_address' => fake()->address(),
        ];
    }
}
