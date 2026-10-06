<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
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
            'code' => '3DP-'.fake()->unique()->bothify('??????????'),
            'customer_name' => fake()->name(),
            'customer_phone' => '6281234567890',
            'shipping_address' => fake()->address(),
            'subtotal' => fake()->numberBetween(50000, 500000),
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
        ];
    }
}
