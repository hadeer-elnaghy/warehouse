<?php

namespace Database\Factories;

use App\Models\Warehouse;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(10, 200);
        $reserved = fake()->numberBetween(0, min(20, $quantity));
        $available = $quantity - $reserved;

        return [
            'warehouse_id' => Warehouse::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => $quantity,
            'reserved_quantity' => $reserved,
            'available_quantity' => $available,
            'unit_cost' => fake()->randomFloat(2, 1, 100),
            'last_restocked' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Indicate that the stock is low.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(1, 10),
            'available_quantity' => fake()->numberBetween(1, 10),
            'reserved_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the stock is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'available_quantity' => 0,
            'reserved_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the stock has reserved items.
     */
    public function withReserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(50, 200),
            'reserved_quantity' => fake()->numberBetween(10, 30),
            'available_quantity' => function (array $attributes) {
                return $attributes['quantity'] - $attributes['reserved_quantity'];
            },
        ]);
    }
} 