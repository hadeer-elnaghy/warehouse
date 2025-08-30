<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Events\LowStockDetected;
use App\Listeners\SendLowStockNotification;
use App\Models\User;
use App\Models\Stock;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LowStockEventTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;
    protected $warehouse2;
    protected $inventoryItem;
    protected $stock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base test data
        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->warehouse2 = Warehouse::factory()->create();
        $this->inventoryItem = InventoryItem::factory()->create([
            'min_stock_level' => 10,
        ]);

        // Stock starts above the min stock level
        $this->stock = Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'available_quantity' => 50,
        ]);

        // Preload relations
        $this->stock->load('inventoryItem', 'warehouse');
    }

    /** @test */
    public function it_dispatches_low_stock_detected_event()
    {
        Event::fake();

        // Lower available stock
        $this->stock->update(['available_quantity' => 5]);

        event(new LowStockDetected($this->stock));

        Event::assertDispatched(LowStockDetected::class, function ($event) {
            return $event->stock->id === $this->stock->id;
        });
    }

    /** @test */
    public function low_stock_detected_event_is_queued_and_handled_by_listener()
    {
        Queue::fake();

        $this->stock->update(['available_quantity' => 5]);

        event(new LowStockDetected($this->stock));

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) {
            return $job->class === SendLowStockNotification::class
                && $job->data[0]->stock->id === $this->stock->id;
        });
    }

}
