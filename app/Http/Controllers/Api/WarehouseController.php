<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::active()->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully',
            'data' => $warehouse,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $warehouse = Warehouse::with(['stocks.inventoryItem'])->find($id);
        
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $warehouse,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreWarehouseRequest $request, string $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);
        
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
            ], 404);
        }
        
        $warehouse->update($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Warehouse updated successfully',
            'data' => $warehouse,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);
        
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
            ], 404);
        }
        
        $warehouse->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully',
        ]);
    }

    /**
     * Get inventory for a specific warehouse with caching.
     */
    public function inventory(string $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
            ], 404);
        }
        
        $cacheKey = "warehouse_inventory_{$id}";
        
        $inventory = Cache::remember($cacheKey, 300, function () use ($warehouse) {
            $warehouse->load(['stocks.inventoryItem']);
            
            return $warehouse->stocks->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'item' => [
                        'id' => $stock->inventoryItem->id,
                        'name' => $stock->inventoryItem->name,
                        'sku' => $stock->inventoryItem->sku,
                        'category' => $stock->inventoryItem->category,
                        'brand' => $stock->inventoryItem->brand,
                        'unit' => $stock->inventoryItem->unit,
                    ],
                    'quantity' => $stock->quantity,
                    'available_quantity' => $stock->available_quantity,
                    'reserved_quantity' => $stock->reserved_quantity,
                    'unit_cost' => $stock->unit_cost,
                    'last_restocked' => $stock->last_restocked,
                    'stock_percentage' => $stock->stock_percentage,
                    'is_low_stock' => $stock->isLowStock(),
                ];
            });
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'warehouse' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                ],
                'inventory' => $inventory,
                'total_items' => $inventory->count(),
                'low_stock_items' => $inventory->where('is_low_stock', true)->count(),
            ],
        ]);
    }
}
