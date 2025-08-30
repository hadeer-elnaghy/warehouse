<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Events\LowStockDetected;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'inventory_item_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'unit_cost',
        'last_restocked',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'last_restocked' => 'date',
    ];

    /**
     * Get the warehouse that owns this stock.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the inventory item that owns this stock.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($stock) {
            // Check if stock is low after update
            if ($stock->isLowStock()) {
                event(new LowStockDetected($stock));
            }
        });
    }

    /**
     * Check if stock is low.
     */
    public function isLowStock(): bool
    {
        return $this->available_quantity <= $this->inventoryItem->min_stock_level;
    }

    /**
     * Reserve quantity for pending orders.
     */
    public function reserveQuantity(int $quantity): bool
    {
        if ($this->available_quantity >= $quantity) {
            $this->reserved_quantity += $quantity;
            $this->available_quantity -= $quantity;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Release reserved quantity.
     */
    public function releaseQuantity(int $quantity): bool
    {
        if ($this->reserved_quantity >= $quantity) {
            $this->reserved_quantity -= $quantity;
            $this->available_quantity += $quantity;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Add quantity to stock.
     */
    public function addQuantity(int $quantity): bool
    {
        $this->quantity += $quantity;
        $this->available_quantity += $quantity;
        $this->last_restocked = now();
        return $this->save();
    }

    /**
     * Remove quantity from stock.
     */
    public function removeQuantity(int $quantity): bool
    {
        if ($this->available_quantity >= $quantity) {
            $this->quantity -= $quantity;
            $this->available_quantity -= $quantity;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Get the percentage of stock remaining.
     */
    public function getStockPercentageAttribute(): float
    {
        if ($this->quantity === 0) {
            return 0;
        }
        
        return round(($this->available_quantity / $this->quantity) * 100, 2);
    }
}
