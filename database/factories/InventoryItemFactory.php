<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Automotive'];
        $brands = ['Apple', 'Nike', 'Samsung', 'Adidas', 'Sony', 'Generic'];
        $units = ['piece', 'box', 'kg', 'liter', 'meter', 'pair'];

        return [
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('??-####-??')),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 1, 1000),
            'category' => fake()->randomElement($categories),
            'brand' => fake()->randomElement($brands),
            'unit' => fake()->randomElement($units),
            'min_stock_level' => fake()->numberBetween(5, 50),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the item is low in stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_stock_level' => fake()->numberBetween(20, 50),
        ]);
    }

    /**
     * Indicate that the item is expensive.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 500, 5000),
        ]);
    }
} 