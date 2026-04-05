<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name'          => fake()->words(3, true),
            'sku'           => 'PRD-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'type'          => fake()->randomElement(Product::typeOptions()),
            'unit'          => fake()->randomElement(Product::unitOptions()),
            'cost_price'    => fake()->randomFloat(2, 1000, 500000),
            'sell_price'    => fake()->randomFloat(2, 5000, 1000000),
            'overhead_cost' => fake()->randomFloat(2, 0, 50000),
            'labor_cost'    => fake()->randomFloat(2, 0, 50000),
            'standard_cost' => fake()->randomFloat(2, 1000, 500000),
            'avg_cost'      => fake()->randomFloat(4, 1000, 500000),
            'min_stock'     => fake()->numberBetween(0, 100),
            'is_active'     => true,
        ];
    }
}
