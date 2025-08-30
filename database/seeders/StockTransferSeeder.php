<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Models\User;

class StockTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user if none exists
        $user = User::firstOrCreate(
            ['email' => 'admin@warehouse.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );

        // Get warehouses and inventory items
        $warehouses = Warehouse::all();
        $inventoryItems = InventoryItem::all();

        // Create 15 sample stock transfers
        for ($i = 0; $i < 15; $i++) {
            $fromWarehouse = $warehouses->random();
            $toWarehouse = $warehouses->where('id', '!=', $fromWarehouse->id)->random();
            $inventoryItem = $inventoryItems->random();

            StockTransfer::factory()->create([
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'inventory_item_id' => $inventoryItem->id,
                'created_by' => $user->id,
            ]);
        }

        $this->command->info('Stock transfers seeded successfully!');
    }
} 