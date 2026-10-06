<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_variant_id' => null,
            'product_name' => fake()->words(2, true),
            'color_name' => fake()->colorName(),
            'fulfillment_type' => 'ready',
            'quantity' => 1,
            'unit_price' => 50000,
            'subtotal' => 50000,
            'lead_days' => null,
        ];
    }
}
