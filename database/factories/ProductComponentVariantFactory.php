<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductComponentVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductComponentVariant>
 */
class ProductComponentVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'color_id' => Color::factory(),
            'component' => 'base',
            'availability' => 'ready',
            'stock' => fake()->numberBetween(1, 20),
            'lead_days' => 14,
            'is_active' => true,
        ];
    }
}
