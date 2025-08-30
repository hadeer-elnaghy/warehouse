<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\InventoryItem;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all warehouses and inventory items
        $warehouses = Warehouse::all();
        $inventoryItems = InventoryItem::all();

        // Create stock entries for each warehouse-item combination
        foreach ($warehouses as $warehouse) {
            foreach ($inventoryItems->random(rand(5, 12)) as $item) {
                Stock::factory()->create([
                    'warehouse_id' => $warehouse->id,
                    'inventory_item_id' => $item->id,
                ]);
            }
        }

        $this->command->info('Stock data seeded successfully!');
    }
} 