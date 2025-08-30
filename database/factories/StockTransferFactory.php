<?php

namespace Database\Factories;

use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockTransfer>
 */
class StockTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fromWarehouse = Warehouse::factory()->create();
        $toWarehouse = Warehouse::factory()->create();
        
        // Ensure different warehouses
        while ($toWarehouse->id === $fromWarehouse->id) {
            $toWarehouse = Warehouse::factory()->create();
        }

        return [
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->numberBetween(1, 50),
            'status' => StockTransfer::STATUS_PENDING,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'transferred_at' => null,
        ];
    }

    /**
     * Indicate that the transfer is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransfer::STATUS_COMPLETED,
            'transferred_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the transfer is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransfer::STATUS_CANCELLED,
        ]);
    }

    /**
     * Indicate that the transfer is in transit.
     */
    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransfer::STATUS_IN_TRANSIT,
        ]);
    }

    /**
     * Indicate that the transfer has notes.
     */
    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => fake()->paragraph(),
        ]);
    }
} 