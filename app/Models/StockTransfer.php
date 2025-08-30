<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_warehouse_id',
        'to_warehouse_id',
        'inventory_item_id',
        'quantity',
        'status',
        'notes',
        'created_by',
        'transferred_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'transferred_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the source warehouse.
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the destination warehouse.
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get the inventory item being transferred.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the user who created the transfer.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Execute the stock transfer.
     */
    public function execute(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return DB::transaction(function () {
            // Check if source warehouse has enough stock
            $sourceStock = Stock::where('warehouse_id', $this->from_warehouse_id)
                               ->where('inventory_item_id', $this->inventory_item_id)
                               ->first();

            if (!$sourceStock || $sourceStock->available_quantity < $this->quantity) {
                return false;
            }

            // Remove stock from source warehouse
            if (!$sourceStock->removeQuantity($this->quantity)) {
                return false;
            }

            // Add stock to destination warehouse
            $destinationStock = Stock::firstOrCreate(
                [
                    'warehouse_id' => $this->to_warehouse_id,
                    'inventory_item_id' => $this->inventory_item_id,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'available_quantity' => 0,
                ]
            );

            if (!$destinationStock->addQuantity($this->quantity)) {
                // Rollback source warehouse stock
                $sourceStock->addQuantity($this->quantity);
                return false;
            }

            // Update transfer status
            $this->status = self::STATUS_COMPLETED;
            $this->transferred_at = now();
            $this->save();

            return true;
        });
    }

    /**
     * Cancel the stock transfer.
     */
    public function cancel(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * Check if transfer can be executed.
     */
    public function canExecute(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $sourceStock = Stock::where('warehouse_id', $this->from_warehouse_id)
                           ->where('inventory_item_id', $this->inventory_item_id)
                           ->first();

        return $sourceStock && $sourceStock->available_quantity >= $this->quantity;
    }

    /**
     * Scope to get transfers by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending transfers.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get completed transfers.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
