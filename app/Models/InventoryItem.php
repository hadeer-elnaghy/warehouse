<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'category',
        'brand',
        'unit',
        'min_stock_level',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the stocks for this inventory item.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get the stock transfers for this inventory item.
     */
    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }

    /**
     * Get the warehouses that have this item in stock.
     */
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'stocks')
                    ->withPivot('quantity', 'available_quantity', 'unit_cost')
                    ->withTimestamps();
    }

    /**
     * Scope to get only active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search items by name or SKU.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter items by price range.
     */
    public function scopePriceRange($query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }
        
        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }
        
        return $query;
    }

    /**
     * Scope to filter items by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter items by brand.
     */
    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Get total quantity across all warehouses.
     */
    public function getTotalQuantityAttribute()
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Get total available quantity across all warehouses.
     */
    public function getTotalAvailableQuantityAttribute()
    {
        return $this->stocks()->sum('available_quantity');
    }

    /**
     * Check if item is low in stock.
     */
    public function isLowStock()
    {
        return $this->total_available_quantity <= $this->min_stock_level;
    }
}
