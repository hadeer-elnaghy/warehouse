<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Models\Stock;
use App\Models\StockTransfer;
use Laravel\Sanctum\Sanctum;

class StockTransferFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $warehouse1;
    protected $warehouse2;
    protected $inventoryItem;
    protected $stock;
    protected $bearerToken;

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

        // Get bearer token for the user
        $this->bearerToken = $this->user->createToken('test-token')->plainTextToken;
    }

    #[Test]
    public function it_can_create_stock_transfer()
    {
        $transferData = [
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'notes' => 'Test transfer',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/json',
        ])->postJson('/api/stock-transfers', $transferData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'from_warehouse_id',
                        'to_warehouse_id',
                        'inventory_item_id',
                        'quantity',
                        'status',
                        'notes',
                    ]
                ]);

        $this->assertDatabaseHas('stock_transfers', [
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_PENDING,
        ]);
    }

    #[Test]
    public function it_validates_stock_availability_when_creating_transfer()
    {
        $transferData = [
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 150, // More than available
            'notes' => 'Test transfer',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/json',
        ])->postJson('/api/stock-transfers', $transferData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['quantity']);
    }

    #[Test]
    public function it_can_execute_stock_transfer()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/json',
        ])->postJson("/api/stock-transfers/{$transfer->id}/execute");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Stock transfer executed successfully',
                ]);

        $this->assertEquals(StockTransfer::STATUS_COMPLETED, $transfer->fresh()->status);
    }

    #[Test]
    public function it_can_cancel_stock_transfer()
    {
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/json',
        ])->postJson("/api/stock-transfers/{$transfer->id}/cancel");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Stock transfer cancelled successfully',
                ]);

        $this->assertEquals(StockTransfer::STATUS_CANCELLED, $transfer->fresh()->status);
    }

    #[Test]
    public function it_can_list_stock_transfers()
    {
        StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/json',
        ])->getJson('/api/stock-transfers');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'from_warehouse_id',
                                'to_warehouse_id',
                                'inventory_item_id',
                                'quantity',
                                'status',
                            ]
                        ]
                    ]
                ]);
    }

    #[Test]
    public function it_can_get_stock_transfer_statistics()
    {
        // Create some transfers
        StockTransfer::create([
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 50,
            'status' => StockTransfer::STATUS_COMPLETED,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->bearerToken,
            'Accept' => 'application/json',
        ])->getJson('/api/stock-transfers-statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_transfers',
                        'pending_transfers',
                        'completed_transfers',
                        'cancelled_transfers',
                        'total_items_transferred',
                    ]
                ]);
    }
}
