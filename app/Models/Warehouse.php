<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
        'contact_person',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the stocks for this warehouse.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get the stock transfers from this warehouse.
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    /**
     * Get the stock transfers to this warehouse.
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    /**
     * Get the inventory items available in this warehouse.
     */
    public function inventoryItems()
    {
        return $this->belongsToMany(InventoryItem::class, 'stocks')
                    ->withPivot('quantity', 'available_quantity', 'unit_cost')
                    ->withTimestamps();
    }

    /**
     * Scope to get only active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
