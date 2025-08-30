<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\StockTransfer;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->warehouse1 = Warehouse::factory()->create();
        $this->warehouse2 = Warehouse::factory()->create();
        $this->inventoryItem = InventoryItem::factory()->create();
        
        // Create stock in warehouse 1
        $this->stock = Stock::factory()->create([
            'warehouse_id' => $this->warehouse1->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);
    }

    /** @test */
    public function it_can_execute_stock_transfer_successfully()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);

        $result = $transfer->execute();

        $this->assertTrue($result);
        $this->assertEquals(StockTransfer::STATUS_COMPLETED, $transfer->fresh()->status);
        
        // Check source warehouse stock
        $this->stock->refresh();
        $this->assertEquals(50, $this->stock->quantity);
        $this->assertEquals(50, $this->stock->available_quantity);
        
        // Check destination warehouse stock
        $destinationStock = Stock::where('warehouse_id', $this->warehouse2->id)
                                ->where('inventory_item_id', $this->inventoryItem->id)
                                ->first();
        $this->assertNotNull($destinationStock);
        $this->assertEquals(50, $destinationStock->quantity);
        $this->assertEquals(50, $destinationStock->available_quantity);
    }

    /** @test */
    public function it_prevents_over_transfer_of_stock()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 150, // More than available
            'status' => StockTransfer::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);

        $result = $transfer->execute();

        $this->assertFalse($result);
        $this->assertEquals(StockTransfer::STATUS_PENDING, $transfer->fresh()->status);
        
        // Source warehouse stock should remain unchanged
        $this->stock->refresh();
        $this->assertEquals(100, $this->stock->quantity);
        $this->assertEquals(100, $this->stock->available_quantity);
        
        // No destination stock should be created
        $destinationStock = Stock::where('warehouse_id', $this->warehouse2->id)
                                ->where('inventory_item_id', $this->inventoryItem->id)
                                ->first();
        $this->assertNull($destinationStock);
    }

    /** @test */
    public function it_can_cancel_pending_transfer()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);

        $result = $transfer->cancel();

        $this->assertTrue($result);
        $this->assertEquals(StockTransfer::STATUS_CANCELLED, $transfer->fresh()->status);
    }

    /** @test */
    public function it_cannot_execute_completed_transfer()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_COMPLETED,
            'created_by' => $this->user->id,
        ]);

        $result = $transfer->execute();

        $this->assertFalse($result);
    }

    /** @test */
    public function it_validates_transfer_can_be_executed()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);

        $this->assertTrue($transfer->canExecute());

        // Try to transfer more than available
        $transfer->quantity = 150;
        $this->assertFalse($transfer->canExecute());
    }
}
